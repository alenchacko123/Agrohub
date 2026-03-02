<?php
require_once 'php/config.php';

function describe($table) {
    global $conn;
    $res = $conn->query("DESCRIBE $table");
    $out = "Table: $table\n";
    while ($row = $res->fetch_assoc()) {
        $out .= "  {$row['Field']} - {$row['Type']}\n";
    }
    return $out . "\n";
}

$output = describe('rental_requests');
$output .= describe('agreements');
file_put_contents('key_tables_schema.txt', $output);
?>
