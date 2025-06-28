<?php
echo "🚀 Starting database export...\n";

// XAMPP MySQL connection
$mysqlHost = 'localhost';
$mysqlUser = 'root';
$mysqlPass = '';
$mysqlDb = 'test';

try {
    echo "📡 Connecting to MySQL...\n";
    $pdo = new PDO("mysql:host=$mysqlHost;dbname=$mysqlDb", $mysqlUser, $mysqlPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL database: $mysqlDb\n";
    
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = [];
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    echo "📋 Found " . count($tables) . " tables: " . implode(', ', $tables) . "\n";
    
    // Create simple export
    $sqlFile = 'database_export.sql';
    $handle = fopen($sqlFile, 'w');
    
    fwrite($handle, "-- Database Export for Render\n");
    fwrite($handle, "-- Generated on " . date('Y-m-d H:i:s') . "\n\n");
    
    foreach ($tables as $table) {
        echo "📤 Exporting table: $table\n";
        
        // Get data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            fwrite($handle, "-- Table: $table\n");
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                $escapedValues = array_map(function($value) {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return "'" . addslashes($value) . "'";
                }, $values);
                
                fwrite($handle, "INSERT INTO \"$table\" (\"" . implode('", "', $columns) . "\") VALUES (" . implode(', ', $escapedValues) . ");\n");
            }
            fwrite($handle, "\n");
        }
        
        echo "   ✅ Exported " . count($rows) . " rows\n";
    }
    
    fclose($handle);
    
    echo "\n✅ Export completed! File: $sqlFile\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?> 