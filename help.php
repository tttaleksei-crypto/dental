<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Помощь и поддержка</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">Помощь и поддержка</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <!-- Контакты поддержки -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Техническая поддержка</h2>
                <div class="space-y-3">
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">📞</span>
                        <div>
                            <div class="font-medium">Телефон</div>
                            <div class="text-gray-600">+7 (495) 123-45-67</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">📧</span>
                        <div>
                            <div class="font-medium">Email</div>
                            <div class="text-gray-600">support@dentalclinic.ru</div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-2xl mr-3">🕒</span>
                        <div>
                            <div class="font-medium">Время работы</div>
                            <div class="text-gray-600">Пн-Пт: 9:00-18:00</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Частые вопросы -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Частые вопросы</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="font-medium">Как добавить нового пациента?</div>
                        <div class="text-gray-600">Раздел "Пациенты" → кнопка "Добавить пациента"</div>
                    </div>
                    <div>
                        <div class="font-medium">Как создать запись на прием?</div>
                        <div class="text-gray-600">Раздел "Расписание" → форма в левой части</div>
                    </div>
                    <div>
                        <div class="font-medium">Как сформировать отчет?</div>
                        <div class="text-gray-600">Раздел "Отчеты" или "Мед. отчеты" для официальных форм</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Кнопка назад -->
        <div class="text-center mt-8">
            <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                Вернуться на главную
            </a>
        </div>
    </div>
</body>
</html>