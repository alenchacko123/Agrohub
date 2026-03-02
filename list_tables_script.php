<?php
require_once 'php/config.php';

$tables_query = $conn->query("SHOW TABLES");
$output = "";
while ($table = $tables_query->fetch_array()) {
    $tableName = $table[0];
    $output .= "Table: $tableName\n";
}
file_put_contents('tables_list.txt', $output);
?>
