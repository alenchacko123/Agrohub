<?php
require_once 'php/config.php';

$tables_query = $conn->query("SHOW TABLES");
while ($table = $tables_query->fetch_array()) {
    $tableName = $table[0];
    echo "Table: $tableName\n";
    $columns_query = $conn->query("DESCRIBE $tableName");
    while ($column = $columns_query->fetch_assoc()) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    echo "\n";
}
?>
