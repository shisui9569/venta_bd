<?php
// Simple database test
include 'conexion.php';

// Check if we can connect and get basic info
echo "<h1>Database Connection Test</h1>";

// Get MySQL version
$version = $conexion->query("SELECT VERSION() as version");
if ($version && $row = $version->fetch_assoc()) {
    echo "<p>MySQL Version: " . $row['version'] . "</p>";
}

// List tables
echo "<h2>Tables in Database</h2>";
$tables = $conexion->query("SHOW TABLES");
echo "<ul>";
while ($row = $tables->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
    
    // Show structure of each table
    echo "<h3>Structure of " . $row[0] . "</h3>";
    $structure = $conexion->query("DESCRIBE " . $row[0]);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($field = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . ($field['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $field['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "</ul>";

$conexion->close();
?>