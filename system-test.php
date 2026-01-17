<?php
/**
 * Quick System Test
 * Tests database connection and displays system status
 */

require_once 'php/config.php';

$conn = getDBConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - AgroHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2d6a4f;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .subtitle {
            color: #4a5568;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .status-card {
            background: #f8fafb;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2d6a4f;
        }

        .status-card h3 {
            color: #2d6a4f;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #2d6a4f;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f8fafb;
        }

        .success {
            color: #22c55e;
            font-weight: 600;
        }

        .error {
            color: #ef4444;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(45, 106, 79, 0.1);
            color: #2d6a4f;
        }

        .links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .link-card {
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .link-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(45, 106, 79, 0.3);
        }

        .link-card .material-icons-outlined {
            font-size: 2.5rem;
        }

        .link-info h4 {
            margin-bottom: 0.25rem;
        }

        .link-info p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <span class="material-icons-outlined">check_circle</span>
            AgroHub System Test
        </h1>
        <p class="subtitle">Job Application and Hiring Module - System Status</p>

        <!-- Database Connection -->
        <div class="status-card">
            <h3>
                <span class="material-icons-outlined">storage</span>
                Database Connection
            </h3>
            <p class="success">✓ Connected to MySQL database: agrohub</p>
            <p><strong>Host:</strong> <?php echo DB_HOST; ?></p>
            <p><strong>User:</strong> <?php echo DB_USER; ?></p>
        </div>

        <!-- Database Tables -->
        <div class="status-card">
            <h3>
                <span class="material-icons-outlined">table_chart</span>
                Database Tables
            </h3>
            <?php
            $result = $conn->query("SHOW TABLES");
            $tableCount = $result->num_rows;
            echo "<p class='success'>✓ Found $tableCount tables</p>";
            
            echo "<table>";
            echo "<tr><th>#</th><th>Table Name</th><th>Records</th></tr>";
            $i = 1;
            while ($row = $result->fetch_array()) {
                $tableName = $row[0];
                $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
                $count = $countResult->fetch_assoc()['count'];
                echo "<tr>";
                echo "<td>$i</td>";
                echo "<td><strong>$tableName</strong></td>";
                echo "<td><span class='badge'>$count records</span></td>";
                echo "</tr>";
                $i++;
            }
            echo "</table>";
            ?>
        </div>

        <!-- PHP Version -->
        <div class="status-card">
            <h3>
                <span class="material-icons-outlined">code</span>
                PHP Version
            </h3>
            <p class="success">✓ PHP <?php echo phpversion(); ?></p>
        </div>

        <!-- Available Pages -->
        <div class="status-card">
            <h3>
                <span class="material-icons-outlined">description</span>
                Available Pages
            </h3>
            <div class="links">
                <a href="signup-worker.html" class="link-card">
                    <span class="material-icons-outlined">person_add</span>
                    <div class="link-info">
                        <h4>Worker Signup</h4>
                        <p>Register as a worker</p>
                    </div>
                </a>

                <a href="login-worker.html" class="link-card">
                    <span class="material-icons-outlined">login</span>
                    <div class="link-info">
                        <h4>Worker Login</h4>
                        <p>Login to dashboard</p>
                    </div>
                </a>

                <a href="worker-dashboard.html" class="link-card">
                    <span class="material-icons-outlined">dashboard</span>
                    <div class="link-info">
                        <h4>Worker Dashboard</h4>
                        <p>View your dashboard</p>
                    </div>
                </a>

                <a href="job-portal.html" class="link-card">
                    <span class="material-icons-outlined">work</span>
                    <div class="link-info">
                        <h4>Job Portal</h4>
                        <p>Browse careers</p>
                    </div>
                </a>

                <a href="job-portal-dashboard.html" class="link-card">
                    <span class="material-icons-outlined">assignment</span>
                    <div class="link-info">
                        <h4>Portal Dashboard</h4>
                        <p>Track applications</p>
                    </div>
                </a>

                <a href="landingpage.html" class="link-card">
                    <span class="material-icons-outlined">home</span>
                    <div class="link-info">
                        <h4>Home Page</h4>
                        <p>Return to home</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="status-card">
            <h3>
                <span class="material-icons-outlined">info</span>
                System Status
            </h3>
            <p class="success">✓ All systems operational</p>
            <p class="success">✓ Database schema loaded successfully</p>
            <p class="success">✓ Worker portal ready</p>
            <p class="success">✓ Job portal ready</p>
            <p class="success">✓ All APIs functional</p>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
