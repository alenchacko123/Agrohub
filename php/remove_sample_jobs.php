<?php
/**
 * Remove Sample Job Postings - SELECTIVE DELETION
 * 
 * This script removes ONLY test/sample job postings while preserving real farmer jobs.
 * Run this file once to clean up sample data.
 */

header('Content-Type: text/html; charset=utf-8');
require_once 'config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remove Sample Jobs - AgroHub</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f7fa;
        }
        h1, h2 {
            color: #1a1a2e;
        }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
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
        tr:nth-child(even) {
            background: #f9fafb;
        }
        .sample-job {
            background: #fee2e2 !important;
        }
        .real-job {
            background: #d1fae5 !important;
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
        .btn-danger {
            background: #ef4444;
        }
    </style>
</head>
<body>
    <h1>🗑️ Remove Sample Job Postings</h1>
    
    <?php
    try {
        // Get action parameter
        $action = isset($_GET['action']) ? $_GET['action'] : 'preview';
        
        if ($action === 'preview') {
            // STEP 1: Show ALL jobs and identify which are samples
            echo "<div class='warning'>";
            echo "<strong>⚠️ PREVIEW MODE</strong><br>";
            echo "Below you'll see all jobs in the database. Sample jobs are highlighted in RED.";
            echo "</div>";
            
            $allJobsQuery = "SELECT id, job_title, farmer_name, location, status, created_at 
                            FROM job_postings 
                            ORDER BY created_at DESC";
            
            $result = $conn->query($allJobsQuery);
            
            if ($result->num_rows > 0) {
                echo "<h2>All Jobs in Database (" . $result->num_rows . " total):</h2>";
                echo "<table>";
                echo "<tr>
                        <th>ID</th>
                        <th>Job Title</th>
                        <th>Farmer</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Type</th>
                      </tr>";
                
                $sampleCount = 0;
                $realCount = 0;
                
                while ($row = $result->fetch_assoc()) {
                    // Determine if this is a sample job using more selective criteria
                    $isSample = false;
                    $reason = '';
                    
                    // Only mark as sample if:
                    // 1. Farmer name contains "Test"
                    // 2. Job title contains "Test Job"
                    // 3. Specific known sample titles
                    
                    if (stripos($row['farmer_name'], 'Test Farmer') !== false) {
                        $isSample = true;
                        $reason = 'Test Farmer Account';
                        $sampleCount++;
                    } elseif (stripos($row['job_title'], 'Test Job') !== false) {
                        $isSample = true;
                        $reason = 'Test Job Title';
                        $sampleCount++;
                    } elseif (in_array($row['job_title'], ['Sample Job', 'Demo Job', 'Example Job'])) {
                        $isSample = true;
                        $reason = 'Sample Job';
                        $sampleCount++;
                    } else {
                        $realCount++;
                    }
                    
                    $rowClass = $isSample ? 'sample-job' : 'real-job';
                    $typeLabel = $isSample ? "🗑️ SAMPLE - $reason" : "✅ REAL JOB";
                    
                    echo "<tr class='$rowClass'>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($row['job_title']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['farmer_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['created_at'] . "</td>";
                    echo "<td><strong>$typeLabel</strong></td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                echo "<div class='warning'>";
                echo "<h3>Summary:</h3>";
                echo "<p>✅ <strong>Real Jobs (will be KEPT):</strong> $realCount</p>";
                echo "<p>🗑️ <strong>Sample Jobs (will be DELETED):</strong> $sampleCount</p>";
                echo "</div>";
                
                if ($sampleCount > 0) {
                    echo "<h3>Ready to delete sample jobs?</h3>";
                    echo "<a href='?action=delete' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete $sampleCount sample job(s)? This cannot be undone!\")'>
                            🗑️ Delete $sampleCount Sample Jobs
                          </a>";
                    echo "<a href='../job-portal-dashboard.html' class='btn'>← Cancel & Go to Dashboard</a>";
                } else {
                    echo "<div class='success'>";
                    echo "<strong>✅ No sample jobs found!</strong><br>";
                    echo "All jobs in the database appear to be real farmer jobs.";
                    echo "</div>";
                    echo "<a href='../job-portal-dashboard.html' class='btn'>← Go to Dashboard</a>";
                }
                
            } else {
                echo "<p>No jobs found in database.</p>";
            }
            
        } elseif ($action === 'delete') {
            // STEP 2: Actually delete the sample jobs
            
            // More selective deletion - only delete jobs with "Test Farmer" or "Test Job" in title
            $deleteQuery = "DELETE FROM job_postings 
                           WHERE farmer_name LIKE '%Test Farmer%'
                              OR job_title LIKE '%Test Job%'
                              OR job_title IN ('Sample Job', 'Demo Job', 'Example Job')";
            
            if ($conn->query($deleteQuery) === TRUE) {
                $deletedCount = $conn->affected_rows;
                
                echo "<div class='success'>";
                echo "<h2>✅ Successfully deleted $deletedCount sample job(s)!</h2>";
                echo "<p>Sample jobs have been removed from the database.</p>";
                echo "</div>";
                
                // Show remaining jobs
                $remainingQuery = "SELECT id, job_title, farmer_name, location, status, created_at 
                                  FROM job_postings 
                                  ORDER BY created_at DESC";
                
                $remainingResult = $conn->query($remainingQuery);
                
                echo "<h2>Remaining Jobs in Database (" . $remainingResult->num_rows . " jobs):</h2>";
                
                if ($remainingResult->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Job Title</th><th>Farmer</th><th>Location</th><th>Status</th><th>Created</th></tr>";
                    while ($row = $remainingResult->fetch_assoc()) {
                        echo "<tr class='real-job'>";
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
                
                echo "<br>";
                echo "<a href='../job-portal-dashboard.html' class='btn'>📋 Go to Job Portal Dashboard</a>";
                echo "<a href='?action=preview' class='btn'>🔄 Run Cleanup Again</a>";
                
            } else {
                throw new Exception("Error deleting sample jobs: " . $conn->error);
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
