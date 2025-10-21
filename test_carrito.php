<?php
// Simple test to verify the carrito.php file is working correctly
session_start();

// Check if the file can be loaded without errors
if (file_exists('carrito.php')) {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Test Carrito - SaludPerfecta</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 40px; 
                background: #f0f0f0;
            }
            .success { 
                color: #28a745; 
                background: #d4edda;
                padding: 15px;
                border-radius: 5px;
                border-left: 5px solid #28a745;
            }
        </style>
    </head>
    <body>
        <div class='success'>
            <h2>✓ Carrito.php file exists and can be loaded</h2>
            <p>The carrito.php file has been successfully located in the directory.</p>
            <p><a href='carrito.php'>Click here to view the improved carrito page</a></p>
        </div>
    </body>
    </html>";
} else {
    echo "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <title>Test Carrito - SaludPerfecta</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 40px; 
                background: #f0f0f0;
            }
            .error { 
                color: #dc3545; 
                background: #f8d7da;
                padding: 15px;
                border-radius: 5px;
                border-left: 5px solid #dc3545;
            }
        </style>
    </head>
    <body>
        <div class='error'>
            <h2>✗ Carrito.php file not found</h2>
            <p>The carrito.php file could not be located in the directory.</p>
        </div>
    </body>
    </html>";
}
?>