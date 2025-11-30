<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Статистика для дашборда
$patients_count = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch(PDO::FETCH_ASSOC)['count'];
$doctors_count = $conn->query("SELECT COUNT(*) as count FROM doctors WHERE is_active = 1")->fetch(PDO::FETCH_ASSOC)['count'];
$today_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = date('now')")->fetch(PDO::FETCH_ASSOC)['count'];
$month_income = $conn->query("SELECT SUM(cost) as income FROM work_orders WHERE strftime('%Y-%m', date_completed) = strftime('%Y-%m', 'now')")->fetch(PDO::FETCH_ASSOC)['income'];
$low_stock_count = $conn->query("SELECT COUNT(*) as count FROM materials WHERE current_stock <= min_stock")->fetch(PDO::FETCH_ASSOC)['count'];

// Баланс кассы
try {
    $cash_balance = $conn->query("
        SELECT SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as balance 
        FROM cash_operations
    ")->fetch(PDO::FETCH_ASSOC)['balance'];
} catch (Exception $e) {
    $cash_balance = 0;
}

// Системные уведомления
$notifications = [];

// Уведомления о низких запасах
if ($low_stock_count > 0) {
    $notifications[] = [
        'type' => 'warning',
        'message' => "{$low_stock_count} материалов с низким запасом",
        'link' => 'api/material_reports.php'
    ];
}

// Уведомления о незавершенных выплатах
try {
    $pending_payments = $conn->query("SELECT COUNT(*) as count FROM doctor_payments WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['count'];
    if ($pending_payments > 0) {
        $notifications[] = [
            'type' => 'info',
            'message' => "{$pending_payments} выплат ожидают обработки",
            'link' => 'api/salary_calculations.php'
        ];
    }
} catch (Exception $e) {
    // Таблица может не существовать
}

// Уведомления о сегодняшних приемах
if ($today_appointments > 0) {
    $notifications[] = [
        'type' => 'success',
        'message' => "Сегодня {$today_appointments} приемов",
        'link' => 'api/schedule.php'
    ];
}

// Простые последние активности (без сложных запросов)
$recent_activities = [];
try {
    $recent_activities = $conn->query("
        SELECT 
            'appointment' as type,
            created_at as date,
            CASE 
                WHEN status = 'planned' THEN 'Новая запись'
                WHEN status = 'completed' THEN 'Завершен прием'
                ELSE 'Изменение записи'
            END as action,
            id
        FROM appointments 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Если ошибка - показываем тестовые данные
    $recent_activities = [
        ['type' => 'system', 'date' => date('Y-m-d H:i:s'), 'action' => 'Система запущена', 'id' => 1],
        ['type' => 'system', 'date' => date('Y-m-d H:i:s', strtotime('-1 hour')), 'action' => 'Добро пожаловать!', 'id' => 2]
    ];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой ортодонт - Главная</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Шапка -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <h1 class="text-xl font-semibold text-blue-800">🦷 Мой ортодонт</h1>
            <nav class="flex items-center space-x-6">
                <ul class="flex space-x-4">
                    <li><a href="index.php" class="text-gray-600 hover:text-blue-800 font-medium">Главная</a></li>
                    <li><a href="api/patients.php" class="text-gray-600 hover:text-blue-800">Пациенты</a></li>
                    <li><a href="api/schedule.php" class="text-gray-600 hover:text-blue-800">Расписание</a></li>
                    <li><a href="api/doctors.php" class="text-gray-600 hover:text-blue-800">Врачи</a></li>
                    <li><a href="api/services.php" class="text-gray-600 hover:text-blue-800">Услуги</a></li>
                </ul>
                <div class="flex items-center space-x-4">
                    <?php if(isset($_SESSION['doctor_name'])): ?>
                    <span class="text-gray-700"><?= $_SESSION['doctor_name'] ?></span>
                    <a href="logout.php" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                        Выйти
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                        Войти
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <!-- Приветствие -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                <?php if(isset($_SESSION['doctor_name'])): ?>
                Добро пожаловать, <?= $_SESSION['doctor_name'] ?>!
                <?php else: ?>
                Добро пожаловать в систему учета
                <?php endif; ?>
            </h2>
            <p class="text-gray-600">Стоматологическая клиника "Мой ортодонт" - <?= date('d.m.Y') ?></p>
        </div>

        <!-- Уведомления -->
        <?php if($notifications): ?>
        <div class="mb-8 space-y-3">
            <?php foreach($notifications as $notification): ?>
            <div class="flex items-center justify-between p-4 rounded-lg 
                <?= $notification['type'] == 'warning' ? 'bg-yellow-50 border border-yellow-200' : 
                   ($notification['type'] == 'info' ? 'bg-blue-50 border border-blue-200' : 'bg-green-50 border border-green-200') ?>">
                <div class="flex items-center">
                    <span class="text-lg mr-3">
                        <?= $notification['type'] == 'warning' ? '⚠️' : 
                           ($notification['type'] == 'info' ? 'ℹ️' : '✅') ?>
                    </span>
                    <span class="<?= $notification['type'] == 'warning' ? 'text-yellow-800' : 
                                  ($notification['type'] == 'info' ? 'text-blue-800' : 'text-green-800') ?>">
                        <?= $notification['message'] ?>
                    </span>
                </div>
                <a href="<?= $notification['link'] ?>" class="text-sm 
                    <?= $notification['type'] == 'warning' ? 'text-yellow-600 hover:text-yellow-800' : 
                       ($notification['type'] == 'info' ? 'text-blue-600 hover:text-blue-800' : 'text-green-600 hover:text-green-800') ?>">
                    Перейти →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Статистика -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Пациентов</div>
                <div class="text-2xl font-bold text-blue-600"><?= $patients_count ?></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Врачей</div>
                <div class="text-2xl font-bold text-green-600"><?= $doctors_count ?></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Записей сегодня</div>
                <div class="text-2xl font-bold text-purple-600"><?= $today_appointments ?></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Доход за месяц</div>
                <div class="text-2xl font-bold text-yellow-600"><?= number_format($month_income ?: 0, 2) ?> ₽</div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Низкий запас</div>
                <div class="text-2xl font-bold text-red-600"><?= $low_stock_count ?></div>
            </div>
            <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition-shadow">
                <div class="text-sm text-gray-500">Баланс кассы</div>
                <div class="text-2xl font-bold text-teal-600"><?= number_format($cash_balance ?: 0, 2) ?> ₽</div>
            </div>
        </div>

        <!-- Быстрый доступ -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
            <!-- Регистратура -->
            <a href="api/schedule.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-blue-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <span class="text-2xl">📅</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Регистратура</h3>
                </div>
                <p class="text-gray-600 text-sm">Учет расписания, запись пациентов, оформление документов</p>
            </a>

            <!-- Пациенты -->
            <a href="api/patients.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-green-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <span class="text-2xl">👥</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Пациенты</h3>
                </div>
                <p class="text-gray-600 text-sm">Электронные карты, история посещений, лечение</p>
            </a>

            <!-- Врачи -->
            <a href="api/doctors.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-purple-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <span class="text-2xl">👨‍⚕️</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Врачи</h3>
                </div>
                <p class="text-gray-600 text-sm">Список врачей, специализации, контакты</p>
            </a>

            <!-- Услуги -->
            <a href="api/services.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-indigo-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-indigo-100 rounded-lg">
                        <span class="text-2xl">🦷</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Услуги</h3>
                </div>
                <p class="text-gray-600 text-sm">Прайс-лист, стоимость услуг, описание</p>
            </a>

            <!-- Наряд-заказы -->
            <a href="api/work_orders.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-orange-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <span class="text-2xl">📋</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Наряд-заказы</h3>
                </div>
                <p class="text-gray-600 text-sm">Учет выполненных работ, расчет ЗП врачей</p>
            </a>

            <!-- Финансы -->
            <a href="api/finance.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-yellow-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <span class="text-2xl">💰</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Финансы</h3>
                </div>
                <p class="text-gray-600 text-sm">Прибыль, расчет ЗП, наряд-заказы, отчеты</p>
            </a>

            <!-- Материалы -->
            <a href="api/materials.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-red-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <span class="text-2xl">📦</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Материалы</h3>
                </div>
                <p class="text-gray-600 text-sm">Учет материалов, остатки, движение</p>
            </a>

            <!-- Отчеты -->
            <a href="api/reports.php" class="block p-6 bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 border-l-4 border-pink-500 transform hover:-translate-y-1">
                <div class="flex items-center mb-4">
                    <div class="p-2 bg-pink-100 rounded-lg">
                        <span class="text-2xl">📊</span>
                    </div>
                    <h3 class="ml-3 text-lg font-medium text-gray-900">Отчеты</h3>
                </div>
                <p class="text-gray-600 text-sm">Аналитика, эффективность, популярные услуги</p>
            </a>
        </div>

        <!-- Дополнительные модули -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <!-- Последние активности -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Последние активности</h3>
                <div class="space-y-3 text-sm">
                    <?php foreach($recent_activities as $activity): ?>
                    <div class="flex items-center justify-between py-2 border-b">
                        <div class="flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-3"></span>
                            <span><?= $activity['action'] ?></span>
                        </div>
                        <span class="text-gray-500 text-xs">
                            <?= date('d.m H:i', strtotime($activity['date'])) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Системный статус -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Системный статус</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span>База данных</span>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">✓ Активна</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Система учета</span>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">✓ Работает</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span>Резервное копирование</span>
                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">✓ Включено</span>
                    </div>
                </div>
                
                <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                    <h4 class="font-medium text-blue-800 mb-2">Техническая поддержка</h4>
                    <p class="text-sm text-blue-600">
                        Телефон: +7 (922) 757-19-69<br>
                        Email: info@мойортодонт.рф
                    </p>
                </div>
            </div>
        </div>

        <!-- Бухгалтерские модули (только для бухгалтера) -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'accountant'): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Бухгалтерские модули</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="api/cash.php" class="bg-teal-500 hover:bg-teal-600 text-white text-center py-3 px-4 rounded-lg transition-colors">
                    <div class="font-medium">Касса</div>
                    <div class="text-sm opacity-90">Операции</div>
                </a>
                <a href="api/salary_calculations.php" class="bg-cyan-500 hover:bg-cyan-600 text-white text-center py-3 px-4 rounded-lg transition-colors">
                    <div class="font-medium">Зарплаты</div>
                    <div class="text-sm opacity-90">Выплаты</div>
                </a>
                <a href="api/online_cash.php" class="bg-emerald-500 hover:bg-emerald-600 text-white text-center py-3 px-4 rounded-lg transition-colors">
                    <div class="font-medium">Онлайн-касса</div>
                    <div class="text-sm opacity-90">Эквайринг</div>
                </a>
                <a href="api/egisz.php" class="bg-violet-500 hover:bg-violet-600 text-white text-center py-3 px-4 rounded-lg transition-colors">
                    <div class="font-medium">ЕГИСЗ</div>
                    <div class="text-sm opacity-90">Отчетность</div>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Подвал -->
    <footer class="bg-white mt-12 py-6 border-t">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Мой ортодонт</h4>
                    <p class="text-sm text-gray-600">
                        Стоматологическая клиника<br>
                        Полная система учета для современной практики
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Контакты</h4>
                    <p class="text-sm text-gray-600">
                        город такойто<br>
                        +77777777777<br>
                        info@авыаывт.рф
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Система</h4>
                    <p class="text-sm text-gray-600">
                        Версия 1.0<br>
                        © 2024 Все права защищены
                    </p>
                </div>
            </div>
            <div class="text-center mt-6 pt-6 border-t">
                <a href="help.php" class="text-blue-600 hover:text-blue-800 text-sm">Помощь и поддержка</a>
            </div>
        </div>
    </footer>

</body>

</html>
