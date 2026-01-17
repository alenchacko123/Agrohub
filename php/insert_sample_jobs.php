<?php
/**
 * Sample Job Data Insertion Script
 * Run this once to populate the job_postings table with test data
 */

require_once 'config.php';
$conn = getDBConnection();

// Sample job data
$sampleJobs = [
    [
        'farmer_id' => 1,
        'farmer_name' => 'Ramesh Kumar',
        'farmer_email' => 'ramesh@agrohub.com',
        'farmer_phone' => '+91 98765 43210',
        'farmer_location' => 'Mysore, Karnataka',
        'job_title' => 'Rice Harvesting Worker',
        'job_type' => 'seasonal',
        'job_category' => 'harvesting',
        'job_description' => 'We are looking for experienced workers to help with rice harvesting on our 10-acre farm. The work involves traditional harvesting methods using sickles, bundling the harvested crop, and transporting it to the threshing area. This is a great opportunity for workers who have experience in paddy cultivation and harvesting.',
        'workers_needed' => 5,
        'wage_per_day' => 800,
        'duration_days' => 3,
        'start_date' => date('Y-m-d', strtotime('+2 days')),
        'end_date' => date('Y-m-d', strtotime('+5 days')),
        'location' => 'Mysore, Karnataka',
        'work_hours_per_day' => 8,
        'requirements' => json_encode([
            'Experience with traditional rice harvesting methods',
            'Ability to work 8-10 hours daily in field conditions',
            'Physical fitness to handle manual labor',
            'Knowledge of proper harvesting techniques to minimize crop damage',
            'Own basic tools (sickle preferred but not mandatory)'
        ]),
        'responsibilities' => json_encode([
            'Harvest rice crops using traditional methods',
            'Bundle and organize harvested crops properly',
            'Transport bundles to designated collection points',
            'Maintain quality standards during harvesting',
            'Follow safety guidelines and farm protocols'
        ]),
        'accommodation_provided' => 1,
        'food_provided' => 1,
        'transportation_provided' => 0,
        'tools_provided' => 1,
        'other_benefits' => 'Free lunch and evening snacks provided. Bonus payment upon completion of work.',
        'status' => 'active'
    ],
    [
        'farmer_id' => 1,
        'farmer_name' => 'Suresh Patil',
        'farmer_email' => 'suresh@agrohub.com',
        'farmer_phone' => '+91 98123 45678',
        'farmer_location' => 'Mandya, Karnataka',
        'job_title' => 'Tractor Operator',
        'job_type' => 'contract',
        'job_category' => 'machine_operation',
        'job_description' => 'Looking for a skilled tractor operator for plowing 20 acres of land. Must have valid driving license and at least 2+ years of experience operating farm tractors. Knowledge of modern farming equipment preferred.',
        'workers_needed' => 1,
        'wage_per_day' => 1500,
        'duration_days' => 5,
        'start_date' => date('Y-m-d', strtotime('+1 week')),
        'end_date' => date('Y-m-d', strtotime('+12 days')),
        'location' => 'Mandya, Karnataka',
        'work_hours_per_day' => 8,
        'requirements' => json_encode([
            'Valid driving license for agricultural vehicles',
            'Minimum 2 years of tractor operation experience',
            'Knowledge of modern tillage equipment',
            'Ability to perform basic tractor maintenance'
        ]),
        'responsibilities' => json_encode([
            'Operate tractor for plowing 20 acres of land',
            'Perform daily equipment checks',
            'Ensure proper fuel management',
            'Report any mechanical issues immediately',
            'Maintain work logs and field records'
        ]),
        'accommodation_provided' => 0,
        'food_provided' => 1,
        'transportation_provided' => 1,
        'tools_provided' => 1,
        'other_benefits' => 'All equipment and fuel provided. Accommodation allowance of ₹200/day if needed.',
        'status' => 'active'
    ],
    [
        'farmer_id' => 1,
        'farmer_name' => 'Mahesh Gowda',
        'farmer_email' => 'mahesh@agrohub.com',
        'farmer_phone' => '+91 97654 32109',
        'farmer_location' => 'Bangalore Rural',
        'job_title' => 'Crop Planting Assistant',
        'job_type' => 'temporary',
        'job_category' => 'planting',
        'job_description' => 'Help us with vegetable crop planting. Basic farming knowledge required. Training will be provided for specific crops.',
        'workers_needed' => 3,
        'wage_per_day' => 750,
        'duration_days' => 2,
        'start_date' => date('Y-m-d', strtotime('+3 days')),
        'end_date' => date('Y-m-d', strtotime('+5 days')),
        'location' => 'Bangalore Rural',
        'work_hours_per_day' => 7,
        'requirements' => json_encode([
            'Basic farming knowledge',
            'Physical fitness for outdoor work',
            'Willingness to learn'
        ]),
        'responsibilities' => json_encode([
            'Help with vegetable crop planting',
            'Prepare soil beds',
            'Follow planting guidelines',
            'Water newly planted crops'
        ]),
        'accommodation_provided' => 0,
        'food_provided' => 1,
        'transportation_provided' => 1,
        'tools_provided' => 1,
        'other_benefits' => null,
        'status' => 'active'
    ],
    [
        'farmer_id' => 1,
        'farmer_name' => 'Krishna Reddy',
        'farmer_email' => 'krishna@agrohub.com',
        'farmer_phone' => '+91 99876 54321',
        'farmer_location' => 'Hassan, Karnataka',
        'job_title' => 'Irrigation System Operator',
        'job_type' => 'contract',
        'job_category' => 'irrigation',
        'job_description' => 'Operate and maintain drip irrigation system for sugarcane fields. Knowledge of modern irrigation techniques preferred.',
        'workers_needed' => 2,
        'wage_per_day' => 1200,
        'duration_days' => 7,
        'start_date' => date('Y-m-d', strtotime('+4 days')),
        'end_date' => date('Y-m-d', strtotime('+11 days')),
        'location' => 'Hassan, Karnataka',
        'work_hours_per_day' => 8,
        'requirements' => json_encode([
            'Knowledge of drip irrigation systems',
            'Basic plumbing skills',
            'Ability to identify and fix minor leaks'
        ]),
        'responsibilities' => json_encode([
            'Operate drip irrigation system daily',
            'Monitor water flow and pressure',
            'Perform routine maintenance',
            'Keep irrigation schedule logs'
        ]),
        'accommodation_provided' => 1,
        'food_provided' => 1,
        'transportation_provided' => 0,
        'tools_provided' => 1,
        'other_benefits' => 'Free accommodation in farm quarters with electricity and water.',
        'status' => 'active'
    ],
    [
        'farmer_id' => 1,
        'farmer_name' => 'Venkatesh Naik',
        'farmer_email' => 'venkatesh@agrohub.com',
        'farmer_phone' => '+91 96543 21098',
        'farmer_location' => 'Tumkur, Karnataka',
        'job_title' => 'Pesticide Spray Operator',
        'job_type' => 'daily_wage',
        'job_category' => 'pest_control',
        'job_description' => 'Looking for 1 day spray work for pesticide application on cotton fields. Must have experience with spray equipment.',
        'workers_needed' => 1,
        'wage_per_day' => 900,
        'duration_days' => 1,
        'start_date' => date('Y-m-d', strtotime('tomorrow')),
        'end_date' => date('Y-m-d', strtotime('tomorrow')),
        'location' => 'Tumkur, Karnataka',
        'work_hours_per_day' => 6,
        'requirements' => json_encode([
            'Experience with pesticide spray equipment',
            'Knowledge of safety protocols',
            'Physical fitness'
        ]),
        'responsibilities' => json_encode([
            'Apply pesticides to cotton crops',
            'Ensure proper coverage',
            'Follow safety guidelines',
            'Clean equipment after use'
        ]),
        'accommodation_provided' => 0,
        'food_provided' => 1,
        'transportation_provided' => 1,
        'tools_provided' => 1,
        'other_benefits' => 'Safety equipment provided including mask, gloves, and protective clothing.',
        'status' => 'active'
    ]
];

echo "Starting to insert sample job postings...\n\n";

$inserted = 0;
$failed = 0;

foreach ($sampleJobs as $index => $job) {
    try {
        $sql = "INSERT INTO job_postings (
            farmer_id, farmer_name, farmer_email, farmer_phone, farmer_location,
            job_title, job_type, job_category, job_description,
            workers_needed, wage_per_day, duration_days, start_date, end_date,
            location, work_hours_per_day,
            requirements, responsibilities,
            accommodation_provided, food_provided, transportation_provided, 
            tools_provided, other_benefits, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        $stmt->bind_param(
            "issssssssidisssissiiiss",
            $job['farmer_id'],
            $job['farmer_name'],
            $job['farmer_email'],
            $job['farmer_phone'],
            $job['farmer_location'],
            $job['job_title'],
            $job['job_type'],
            $job['job_category'],
            $job['job_description'],
            $job['workers_needed'],
            $job['wage_per_day'],
            $job['duration_days'],
            $job['start_date'],
            $job['end_date'],
            $job['location'],
            $job['work_hours_per_day'],
            $job['requirements'],
            $job['responsibilities'],
            $job['accommodation_provided'],
            $job['food_provided'],
            $job['transportation_provided'],
            $job['tools_provided'],
            $job['other_benefits'],
            $job['status']
        );
        
        if ($stmt->execute()) {
            $inserted++;
            echo "✓ Inserted: {$job['job_title']} - {$job['farmer_name']}\n";
        } else {
            $failed++;
            echo "✗ Failed: {$job['job_title']} - Error: {$stmt->error}\n";
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $failed++;
        echo "✗ Failed: {$job['job_title']} - Exception: {$e->getMessage()}\n";
    }
}

echo "\n========================================\n";
echo "Summary:\n";
echo "  ✓ Successfully inserted: {$inserted} jobs\n";
echo "  ✗ Failed: {$failed} jobs\n";
echo "========================================\n";

if ($inserted > 0) {
    echo "\n✅ Sample data inserted successfully!\n";
    echo "You can now:\n";
    echo "  1. Visit job-portal-dashboard.html to see the jobs\n";
    echo "  2. Click 'View Details' on any job to see full information\n";
    echo "  3. Test the filters (category, location, wage, duration)\n";
    echo "  4. Try posting a new job from post-job.html\n";
}

$conn->close();
?>
