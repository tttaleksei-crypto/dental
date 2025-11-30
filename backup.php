php
session_start();
require_once 'configauth.php';
checkAccess('accountant');

$database = new Database();
$backup_file = 'backupdatabase_backup_' . date('Y-m-d_H-i-s') . '.sql';

 Создаем папку backup если нет
if (!is_dir('backup')) {
    mkdir('backup', 0755, true);
}

 Получаем все таблицы
$tables = $database-getConnection()-query(SELECT name FROM sqlite_master WHERE type='table')-fetchAll(PDOFETCH_COLUMN);

$backup_content = ;
foreach ($tables as $table) {
     Пропускаем системные таблицы
    if ($table === 'sqlite_sequence') continue;
    
    $backup_content .= -- Table $tablen;
    
     Структура таблицы
    $create_table = $database-getConnection()-query(SELECT sql FROM sqlite_master WHERE name='$table')-fetch(PDOFETCH_ASSOC);
    $backup_content .= $create_table['sql'] . ;nn;
    
     Данные таблицы
    $data = $database-getConnection()-query(SELECT  FROM $table)-fetchAll(PDOFETCH_ASSOC);
    if ($data) {
        $columns = array_keys($data[0]);
        $backup_content .= INSERT INTO $table ( . implode(', ', $columns) . ) VALUESn;
        
        $rows = [];
        foreach ($data as $row) {
            $values = array_map(function($value) use ($database) {
                if ($value === null) return 'NULL';
                return ' . str_replace(', '', $value) . ';
            }, $row);
            $rows[] = ( . implode(', ', $values) . );
        }
        $backup_content .= implode(,n, $rows) . ;nn;
    }
}

 Сохраняем backup
file_put_contents($backup_file, $backup_content);

echo h1Резервная копия созданаh1;
echo pФайл $backup_filep;
echo pРазмер  . number_format(filesize($backup_file)) .  байтp;
echo a href='$backup_file' downloadСкачать backupabrbr;
echo a href='index.php'На главнуюa;

 Показываем список backup'ов
$backups = glob('backupdatabase_backup_.sql');
if ($backups) {
    echo h3Доступные резервные копииh3;
    rsort($backups);
    foreach (array_slice($backups, 0, 5) as $backup) {
        echo div . basename($backup) .  ( . date('d.m.Y Hi', filemtime($backup)) . )div;
    }
}
