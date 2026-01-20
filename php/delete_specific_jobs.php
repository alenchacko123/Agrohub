<?php
/**
 * Delete Specific Jobs (IDs 1-5)
 * 
 * This script deletes the 5 specific jobs shown in the preview.
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Specific Jobs - AgroHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h1, h2 { color: #1a1a2e; }
        .success {
            background: #d4edda;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 2px solid #dc3545;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #721c24;
        }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        th {
            background: #10b981;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .btn {
            padding: 10px 20px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
            margin: 10px 5px;
        }
    </style>
</head>
<body>
    <h1>🗑️ Delete Specific Jobs</h1>
    
    <?php
    try {
        $action = isset($_GET['action']) ? $_GET['action'] : 'preview';
        
        if ($action === 'preview') {
            // Show jobs that will be deleted
            $query = "SELECT id, job_title, farmer_name, location, status, created_at 
                      FROM job_postings 
                      WHERE id IN (1, 2, 3, 4, 5)
                      ORDER BY id";
            
            $result = $conn->query($query);
            
            if ($result && $result->num_rows > 0) {
                echo "<h2>Jobs to be Deleted (" . $result->num_rows . "):</h2>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Job Title</th><th>Farmer</th><th>Location</th><th>Status</th><th>Created</th></tr>";
                
                while ($row = $result->fetch_assoc()) {
                    echo "<tr style='background: #fee2e2;'>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['job_title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['farmer_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<p><strong>Are you sure you want to delete these " . $result->num_rows . " jobs?</strong></p>";
                echo "<a href='?action=delete' class='btn' style='background: #ef4444;' onclick='return confirm(\"This will permanently delete these jobs. Continue?\")'>🗑️ Delete These Jobs</a>";
                echo "<a href='../job-portal-dashboard.html' class='btn'>← Cancel</a>";
            } else {
                echo "<div class='success'>No jobs found with IDs 1-5. They may already be deleted.</div>";
                echo "<a href='../job-portal-dashboard.html' class='btn'>← Go to Dashboard</a>";
            }
            
        } elseif ($action === 'delete') {
            // Delete the jobs
            $deleteQuery = "DELETE FROM job_postings WHERE id IN (1, 2, 3, 4, 5)";
            
            if ($conn->query($deleteQuery) === TRUE) {
                $deletedCount = $conn->affected_rows;
                
                echo "<div class='success'>";
                echo "<h2>✅ Successfully deleted $deletedCount job(s)!</h2>";
                echo "<p>The specified jobs have been removed from the database.</p>";
                echo "</div>";
                
                // Show remaining jobs
                $remainingQuery = "SELECT id, job_title, farmer_name, location, status, created_at 
                                  FROM job_postings 
                                  ORDER BY created_at DESC
                                  LIMIT 10";
                
                $remainingResult = $conn->query($remainingQuery);
                
                if ($remainingResult && $remainingResult->num_rows > 0) {
                    echo "<h2>Remaining Jobs in Database (" . $remainingResult->num_rows . " shown):</h2>";
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Job Title</th><th>Farmer</th><th>Location</th><th>Status</th><th>Created</th></tr>";
                    while ($row = $remainingResult->fetch_assoc()) {
                        echo "<tr style='background: #d1fae5;'>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . htmlspecialchars($row['job_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['farmer_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                        echo "<td>" . $row['status'] . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No jobs remaining in database.</p>";
                }
                
                echo "<a href='../job-portal-dashboard.html' class='btn'>📋 Go to Job Portal Dashboard</a>";
            } else {
                throw new Exception("Error deleting jobs: " . $conn->error);
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h2>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
        echo "</div>";
    }
    
    $conn->close();
    ?>
    
</body>
</html>
