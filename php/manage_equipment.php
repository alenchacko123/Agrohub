<?php
session_start();
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

// Handle deletion
if (isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = "Equipment deleted successfully!";
        $messageType = "success";
    } else {
        $message = "Error deleting equipment: " . $conn->error;
        $messageType = "error";
    }
    $stmt->close();
}

// Fetch all equipment
$sql = "SELECT * FROM equipment ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - AgroHub</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            font-weight: 600;
        }

        .message.success {
            background: #22c55e;
            color: white;
        }

        .message.error {
            background: #ef4444;
            color: white;
        }

        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .equipment-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .equipment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        .equipment-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .equipment-content {
            padding: 1.5rem;
        }

        .equipment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .equipment-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .equipment-id {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.available {
            background: #d1fae5;
            color: #065f46;
        }

        .status-badge.rented {
            background: #fef3c7;
            color: #92400e;
        }

        .equipment-details {
            margin: 1rem 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .detail-label {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .detail-value {
            color: #1f2937;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0077b6;
            margin: 1rem 0;
        }

        .description {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 1rem 0;
            line-height: 1.5;
        }

        .delete-btn {
            width: 100%;
            padding: 0.75rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 1rem;
        }

        .delete-btn:hover {
            background: #dc2626;
        }

        .no-equipment {
            text-align: center;
            color: white;
            font-size: 1.5rem;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .back-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: #f3f4f6;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../owner-dashboard.html" class="back-btn">← Back to Dashboard</a>
        
        <h1>🚜 Manage Equipment Listings</h1>

        <?php if (isset($message)): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="equipment-grid">
                <?php while ($equipment = $result->fetch_assoc()): ?>
                    <div class="equipment-card">
                        <img src="<?php echo htmlspecialchars($equipment['image_url'] ?? '../assets/placeholder.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($equipment['equipment_name']); ?>" 
                             class="equipment-image"
                             onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                        
                        <div class="equipment-content">
                            <div class="equipment-header">
                                <div>
                                    <div class="equipment-name">
                                        <?php echo htmlspecialchars($equipment['equipment_name']); ?>
                                    </div>
                                    <div class="equipment-id">ID: #<?php echo $equipment['id']; ?></div>
                                </div>
                                <span class="status-badge <?php echo strtolower($equipment['availability_status']); ?>">
                                    <?php echo htmlspecialchars($equipment['availability_status']); ?>
                                </span>
                            </div>

                            <div class="price">₹<?php echo number_format($equipment['price_per_day'], 0); ?> /day</div>

                            <div class="equipment-details">
                                <div class="detail-row">
                                    <span class="detail-label">Owner ID:</span>
                                    <span class="detail-value"><?php echo $equipment['owner_id']; ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Owner:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($equipment['owner_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Category:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($equipment['category']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Condition:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($equipment['equipment_condition']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Added:</span>
                                    <span class="detail-value">
                                        <?php echo date('M d, Y', strtotime($equipment['created_at'])); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($equipment['description'])): ?>
                                <div class="description">
                                    <?php echo htmlspecialchars($equipment['description']); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this equipment?');">
                                <input type="hidden" name="delete_id" value="<?php echo $equipment['id']; ?>">
                                <button type="submit" class="delete-btn">🗑️ Delete Equipment</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-equipment">
                <p>No equipment listings found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
