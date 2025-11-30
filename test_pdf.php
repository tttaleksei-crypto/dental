<?php
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml('<h1>Тест PDF генерации</h1><p>Библиотека dompdf работает!</p>');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Сохраняем в файл для теста
file_put_contents('test_output.pdf', $dompdf->output());

echo "PDF создан: test_output.pdf";
?>