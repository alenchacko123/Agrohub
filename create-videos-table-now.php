<?php
// Auto-create videos table - just open this file in browser
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creating Videos Table...</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            margin: 0;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        .status {
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1rem 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            margin-top: 1rem;
            font-weight: 600;
        }
        .btn:hover {
            opacity: 0.9;
        }
        pre {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 0.9rem;
        }
        ul {
            line-height: 2;
            margin-left: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Creating Videos Table</h1>
        
        <?php
        require_once 'php/config.php';
        
        try {
            $conn = getDBConnection();
            
            echo '<div class="spinner"></div>';
            echo '<p style="text-align: center; color: #666;">Setting up database...</p>';
            
            // Create videos table
            $createTableSQL = "CREATE TABLE IF NOT EXISTS videos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                video_url VARCHAR(500) NOT NULL,
                category VARCHAR(50) DEFAULT 'equipment',
                level VARCHAR(20) DEFAULT 'beginner',
                duration VARCHAR(20),
                instructor VARCHAR(100),
                topics JSON,
                thumbnail_url VARCHAR(500),
                created_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                status VARCHAR(20) DEFAULT 'active',
                views INT DEFAULT 0,
                rating DECIMAL(2,1) DEFAULT 0.0,
                INDEX idx_category (category),
                INDEX idx_level (level),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (!$conn->query($createTableSQL)) {
                throw new Exception("Failed to create table: " . $conn->error);
            }
            
            echo '<div class="success status">';
            echo '<h2 style="margin: 0 0 1rem 0;">✅ Success!</h2>';
            echo '<p><strong>Videos table created successfully!</strong></p>';
            echo '</div>';
            
            // Insert sample data
            $sampleDataSQL = "INSERT INTO videos (title, description, video_url, category, level, duration, instructor, topics, rating, views) VALUES
            ('Tractor Operation for Beginners', 'Learn the basics of tractor operation, controls, and safe driving practices.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '25 min', 'Ramesh Kumar', '[\"Controls\", \"Starting\", \"Driving\", \"Safety\"]', 4.8, 1250),
            ('Harvester Operation & Safety', 'Master combined harvester operation with safety protocols and efficiency tips.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'intermediate', '40 min', 'Prakash Reddy', '[\"Operation\", \"Adjustment\", \"Safety\", \"Maintenance\"]', 4.9, 890),
            ('Rotavator Usage & Maintenance', 'Complete guide to rotavator operation, blade maintenance, and soil preparation.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '18 min', 'Suresh Patil', '[\"Setup\", \"Operation\", \"Blade Care\", \"Depth Control\"]', 4.7, 1450),
            ('Equipment Safety Fundamentals', 'Essential safety guidelines for all agricultural equipment operators.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'safety', 'beginner', '22 min', 'Raghu N', '[\"PPE\", \"Emergency\", \"Fire Safety\", \"First Aid\"]', 4.9, 2100)";
            
            if ($conn->query($sampleDataSQL)) {
                echo '<div class="success status">';
                echo '<h3 style="margin: 0 0 0.5rem 0;">🎬 Sample Videos Added!</h3>';
                echo '<p>4 tutorial videos have been inserted as examples.</p>';
                echo '</div>';
            } else {
                // Don't show error if data already exists
                if (strpos($conn->error, 'Duplicate') === false && $conn->error) {
                    echo '<div style="background: #fff3cd; padding: 1rem; border-radius: 8px; margin: 1rem 0; color: #856404;">';
                    echo '<p><strong>Note:</strong> Sample data may already exist or could not be inserted.</p>';
                    echo '</div>';
                }
            }
            
            echo '<div style="background: #e7f3ff; padding: 1.5rem; border-radius: 12px; margin: 2rem 0;">';
            echo '<h3 style="margin: 0 0 1rem 0; color: #0066cc;">✨ All Done! Next Steps:</h3>';
            echo '<ul style="color: #333;">';
            echo '<li>Go to <a href="admin-videos.html" style="color: #667eea; font-weight: 600;">Admin Video Management</a></li>';
            echo '<li>Upload your tutorial videos</li>';
            echo '<li>View them in <a href="tutorials.html" style="color: #667eea; font-weight: 600;">Farmer Tutorials</a></li>';
            echo '</ul>';
            echo '</div>';
            
            echo '<a href="admin-videos.html" class="btn">Go to Video Management →</a>';
            
            $conn->close();
            
        } catch (Exception $e) {
            echo '<div class="error status">';
            echo '<h2 style="margin: 0 0 1rem 0;">❌ Error</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
            echo '</div>';
            
            echo '<div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">';
            echo '<h4>Troubleshooting:</h4>';
            echo '<ul>';
            echo '<li>Make sure XAMPP/MySQL is running</li>';
            echo '<li>Check database connection in <code>php/config.php</code></li>';
            echo '<li>Verify database name is correct</li>';
            echo '</ul>';
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
