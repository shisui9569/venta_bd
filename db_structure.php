<?php
include 'conexion.php';

echo "<h1>Database Tables</h1>\n";
echo "<ul>\n";

// Get table structure
$result = $conexion->query("SHOW TABLES");
$tables = [];
while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
    echo "<li>$row[0]</li>\n";
}

echo "</ul>\n";

// Check sample data from productos table
echo "<h2>Sample Products Data (5 records)</h2>\n";
$result = $conexion->query("SELECT * FROM productos LIMIT 5");
if ($result) {
    echo "<table border='1' style='border-collapse: collapse;'>\n";
    echo "<thead>\n<tr>\n";
    
    // Print headers
    $fields = $result->fetch_fields();
    foreach ($fields as $field) {
        echo "<th style='padding: 8px; background: #f0f0f0;'>{$field->name}</th>\n";
    }
    echo "</tr>\n</thead>\n<tbody>\n";
    
    // Print data rows
    while ($row = $result->fetch_assoc()) {
        echo "<tr>\n";
        foreach ($row as $value) {
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($value) . "</td>\n";
        }
        echo "</tr>\n";
    }
    
    echo "</tbody>\n</table>\n";
}

$conexion->close();
?>