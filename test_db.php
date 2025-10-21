<?php
include 'conexion.php';

// Get table structure
$tables = [];
$result = $conexion->query("SHOW TABLES");
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

echo "<h1>Database Structure</h1>";
echo "<pre>";

foreach ($tables as $table) {
    echo "\n\n=== TABLE: $table ===\n";
    $result = $conexion->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['Field'], 20) . " " . 
             str_pad($row['Type'], 20) . " " . 
             str_pad($row['Null'], 8) . " " . 
             str_pad($row['Key'], 5) . " " . 
             str_pad($row['Default'], 15) . " " . 
             $row['Extra'] . "\n";
    }
}

echo "</pre>";

// Check sample data from productos table
echo "<h2>Sample Products Data</h2>";
$result = $conexion->query("SELECT * FROM productos LIMIT 5");
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
    }
    echo "</pre>";
}

$conexion->close();
?>