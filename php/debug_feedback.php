<?php
require 'config.php';
$conn = getDBConnection();
$res = $conn->query("SELECT q1_condition, q2_performance, q3_value, q4_communication, q5_recommend FROM rental_feedback");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
?>
