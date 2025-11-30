<?php
session_start();
require_once 'config/auth.php';
checkAccess('accountant');

echo "<h1>Обновление системы</h1>";

// Проверяем наличие новых таблиц
$database = new Database();
$conn = $database->getConnection();

$required_tables = [
    'patients', 'doctors', 'services', 'appointments', 'work_orders',
    'material_categories', 'materials', 'material_transactions', 
    'cash_operations', 'doctor_payments', 'online_cash_operations',
    'sync_1c', 'egisz_reports', 'medical_reports', 'receipts'
];

echo "<h3>Проверка таблиц:</h3>";
foreach ($required_tables as $table) {
    try {
        $result = $conn->query("SELECT 1 FROM $table LIMIT 1");
        echo "✅ Таблица $table<br>";
    } catch (Exception $e) {
        echo "❌ Таблица $table отсутствует<br>";
    }
}

// Проверяем версию системы
echo "<h3>Версия системы:</h3>";
echo "Текущая версия: 1.0<br>";
echo "Дата сборки: " . date('Y-m-d') . "<br>";

// Проверяем целостность данных
echo "<h3>Проверка целостности:</h3>";
$checks = [
    'Пациенты' => "SELECT COUNT(*) FROM patients",
    'Врачи' => "SELECT COUNT(*) FROM doctors WHERE is_active = 1", 
    'Услуги' => "SELECT COUNT(*) FROM services",
    'Записи' => "SELECT COUNT(*) FROM appointments"
];

foreach ($checks as $name => $query) {
    try {
        $count = $conn->query($query)->fetchColumn();
        echo "✅ $name: $count записей<br>";
    } catch (Exception $e) {
        echo "❌ $name: ошибка проверки<br>";
    }
}

echo "<h3>Рекомендации:</h3>";
echo "1. Создайте резервную копию: <a href='backup.php'>Backup</a><br>";
echo "2. Проверьте системные требования: <a href='system_check.php'>Проверка</a><br>";
echo "3. Обратитесь к документации: <a href='EXPORT_README.md'>README</a><br>";
?>