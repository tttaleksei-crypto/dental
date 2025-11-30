<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Доступ запрещен</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md text-center">
        <div class="text-6xl mb-4">🚫</div>
        <h1 class="text-2xl font-bold text-red-600 mb-4">Доступ запрещен</h1>
        <p class="text-gray-600 mb-6">У вас недостаточно прав для просмотра этой страницы.</p>
        <a href="index.php" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded">
            На главную
        </a>
    </div>
</body>
</html>