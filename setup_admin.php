<?php
// Admin Setup Script
// This script will create the admin table and default admin user

require_once 'db.php';

echo "<h2>Admin Setup Script</h2>";

try {
    // Check if admin table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'admins'");
    
    if ($checkTable->num_rows === 0) {
        echo "<p>Creating admin table...</p>";
        
        // Create admin table
        $createTable = "CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'super_admin') DEFAULT 'admin',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($db->query($createTable)) {
            echo "<p style='color: green;'>✓ Admin table created successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create admin table: " . $db->error . "</p>";
            exit;
        }
        
        // Insert default admin user (password: admin123)
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insertAdmin = "INSERT INTO admins (name, email, password, role) VALUES 
                       ('Admin User', 'admin@bitcoingiftcards.com', '$defaultPassword', 'super_admin')";
        
        if ($db->query($insertAdmin)) {
            echo "<p style='color: green;'>✓ Default admin user created successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create default admin user: " . $db->error . "</p>";
            exit;
        }
        
        // Add indexes
        $db->query("CREATE INDEX idx_admins_email ON admins(email)");
        $db->query("CREATE INDEX idx_admins_active ON admins(is_active)");
        echo "<p style='color: green;'>✓ Database indexes created!</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ Admin table already exists.</p>";
    }
    
    echo "<hr>";
    echo "<h3>Admin Login Credentials:</h3>";
    echo "<p><strong>Email:</strong> admin@bitcoingiftcards.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    echo "<p><strong>Login URL:</strong> <a href='admin_login.php'>admin_login.php</a></p>";
    
    echo "<hr>";
    echo "<h3>Security Recommendations:</h3>";
    echo "<ul>";
    echo "<li>Change the default password immediately after first login</li>";
    echo "<li>Delete this setup file after successful setup</li>";
    echo "<li>Consider using a more secure email address</li>";
    echo "<li>Enable HTTPS in production</li>";
    echo "</ul>";
    
    echo "<p style='color: green; font-weight: bold;'>Setup completed successfully! You can now login to the admin panel.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 