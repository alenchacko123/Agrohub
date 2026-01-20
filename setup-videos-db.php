<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Videos Database - AgroHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
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
            margin-bottom: 1rem;
            font-size: 2rem;
        }
        
        .status {
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            font-size: 1rem;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .button {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.3s ease;
            margin-top: 1rem;
        }
        
        .button:hover {
            transform: translateY(-2px);
        }
        
        .steps {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
        }
        
        .steps h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .steps ol {
            margin-left: 1.5rem;
            line-height: 1.8;
        }
        
        .steps li {
            margin-bottom: 0.5rem;
        }
        
        code {
            background: #e9ecef;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Setup Videos Database</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['run'])) {
            ?>
            <div class="info status">
                <strong>ℹ️ Ready to Setup</strong><br>
                Click the button below to create the videos table and insert sample data.
            </div>
            
            <div class="steps">
                <h3>What will happen:</h3>
                <ol>
                    <li>Create the <code>videos</code> table in your database</li>
                    <li>Add 4 sample tutorial videos</li>
                    <li>Enable video management features</li>
                </ol>
            </div>
            
            <a href="?run=true" class="button">Create Videos Table</a>
            <?php
        } else {
            require_once 'php/config.php';
            
            try {
                $conn = getDBConnection();
                
                // SQL to create videos table
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
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_category (category),
                    INDEX idx_level (level),
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                if ($conn->query($createTableSQL)) {
                    echo '<div class="success status"><strong>✅ Success!</strong><br>Videos table created successfully!</div>';
                    
                    // Insert sample data
                    $sampleDataSQL = "INSERT INTO videos (title, description, video_url, category, level, duration, instructor, topics, thumbnail_url, rating, views) VALUES
                    ('Tractor Operation for Beginners', 'Learn the basics of tractor operation, controls, and safe driving practices.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '25 min', 'Ramesh Kumar', '[\"Controls\", \"Starting\", \"Driving\", \"Safety\"]', '', 4.8, 1250),
                    ('Harvester Operation & Safety', 'Master combined harvester operation with safety protocols and efficiency tips.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'intermediate', '40 min', 'Prakash Reddy', '[\"Operation\", \"Adjustment\", \"Safety\", \"Maintenance\"]', '', 4.9, 890),
                    ('Rotavator Usage & Maintenance', 'Complete guide to rotavator operation, blade maintenance, and soil preparation.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'equipment', 'beginner', '18 min', 'Suresh Patil', '[\"Setup\", \"Operation\", \"Blade Care\", \"Depth Control\"]', '', 4.7, 1450),
                    ('Equipment Safety Fundamentals', 'Essential safety guidelines for all agricultural equipment operators.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'safety', 'beginner', '22 min', 'Raghu N', '[\"PPE\", \"Emergency\", \"Fire Safety\", \"First Aid\"]', '', 4.9, 2100)";
                    
                    if ($conn->query($sampleDataSQL)) {
                        echo '<div class="success status"><strong>✅ Sample Data Added!</strong><br>4 sample tutorial videos have been inserted.</div>';
                    } else {
                        // Check if error is due to duplicate entries (table already has data)
                        if (strpos($conn->error, 'Duplicate') !== false) {
                            echo '<div class="info status"><strong>ℹ️ Note:</strong><br>Sample data already exists in the table.</div>';
                        } else {
                            echo '<div class="error status"><strong>⚠️ Warning:</strong><br>Table created but sample data insertion failed: ' . $conn->error . '</div>';
                        }
                    }
                    
                    echo '<div class="steps">
                        <h3>✨ Setup Complete! Next Steps:</h3>
                        <ol>
                            <li>Go to <a href="admin-videos.html" style="color: #667eea;">Admin Video Management</a></li>
                            <li>Upload your tutorial videos</li>
                            <li>Check <a href="tutorials.html" style="color: #667eea;">Farmer Tutorials Page</a> to see videos</li>
                        </ol>
                    </div>';
                    
                } else {
                    echo '<div class="error status"><strong>❌ Error!</strong><br>Failed to create videos table: ' . $conn->error . '</div>';
                }
                
                $conn->close();
                
            } catch (Exception $e) {
                echo '<div class="error status"><strong>❌ Error!</strong><br>' . $e->getMessage() . '</div>';
            }
        }
        ?>
    </div>
</body>
</html>
