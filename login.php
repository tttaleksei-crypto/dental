<?php
session_start();

// Если уже авторизован - редирект на главную
if (isset($_SESSION['doctor_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/database.php';

$error = '';

if ($_POST && isset($_POST['email']) && isset($_POST['password'])) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM doctors WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($doctor && password_verify($_POST['password'], $doctor['password_hash'])) {
        $_SESSION['doctor_id'] = $doctor['id'];
        $_SESSION['doctor_name'] = $doctor['full_name'];
        $_SESSION['role'] = $doctor['role'] ?? 'doctor';
        $_SESSION['specialization'] = $doctor['specialization'] ?? 'Врач';
        
        // Редирект в зависимости от роли
        if ($_SESSION['role'] === 'accountant') {
            header('Location: api/accountant_dashboard.php');
        } else {
            header('Location: index.php');
        }
        exit;
    } else {
        $error = "Неверный email или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход в систему</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold text-center mb-6">Вход в систему</h1>
        
        <?php if($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?= $error ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Пароль</label>
                    <input type="password" name="password" required class="mt-1 block w-full border border-gray-300 rounded-md p-2">
                </div>
                
                <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                    Войти
                </button>
            </div>
        </form>

        <!-- Тестовые данные -->
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Тестовые данные:</h3>
            <div class="text-xs text-gray-600 space-y-1">
                <p><strong>Врач:</strong> smirnov@clinic.ru / 123456</p>
                <p><strong>Бухгалтер:</strong> buh@clinic.ru / 123456</p>
            </div>
        </div>
    </div>
</body>
</html>