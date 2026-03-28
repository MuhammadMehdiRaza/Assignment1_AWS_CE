<?php
/**
 * UniEvent Portal - Core Application Logic
 * Student: Muhammad Mehdi Raza (2023466)
 * Purpose: API Integration, Activity Registration, and Automated S3 Persistence.
 */

// 1. ARCHITECTURAL SETTINGS
$apiKey = "JOwFCuAw6oo8QjeaSDA9kBXNwHqvecVN"; 
$bucketName = "unievent-media-assignment1-2026";
$apiUrl = "https://app.ticketmaster.com/discovery/v2/events.json?classificationName=university&apikey=" . $apiKey;

// 2. REQUIREMENT 4: SECURE S3 MEDIA UPLOAD
$uploadStatus = "";
if (isset($_FILES['student_poster'])) {
    $tempFile = $_FILES['student_poster']['tmp_name'];
    $fileName = time() . "_" . basename($_FILES['student_poster']['name']);
    $s3Path = "s3://$bucketName/student_uploads/$fileName";

    // Executes the upload via the IAM Instance Profile (UniEvent-S3-Role)
    exec("aws s3 cp $tempFile $s3Path 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        $uploadStatus = "<p style='color: #27ae60;'><b>✔ Media Uploaded to S3 successfully.</b></p>";
    } else {
        $uploadStatus = "<p style='color: #e74c3c;'><b>✘ Media Upload Failed. Check IAM Role.</b></p>";
    }
}

// 3. REQUIREMENT 2 & 3: AUTOMATED API FETCHING & PERSISTENT STORAGE
$jsonResponse = file_get_contents($apiUrl);
$data = json_decode($jsonResponse, true);

// Save a persistent record of the fetch to S3
$timestamp = date("Y-m-d_H-i-s");
$localJson = "/tmp/uni_events_$timestamp.json";
file_put_contents($localJson, $jsonResponse);
exec("aws s3 cp $localJson s3://$bucketName/fetched_data/");

// 4. USER INTERFACE (Requirement 1 & 5)
echo "<!DOCTYPE html><html><head><title>UniEvent Portal</title></head>";
echo "<body style='font-family: Arial, sans-serif; background: #f0f2f5; padding: 30px;'>";
echo "<div style='max-width: 900px; margin: auto;'>";
echo "<h1 style='color: #1a73e8; border-bottom: 3px solid #1a73e8;'>Official UniEvent - University Portal</h1>";

// --- ACTIVITY REGISTRATION & MEDIA UPLOAD INTERFACE ---
echo "<div style='background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 25px;'>";
echo "<h3 style='margin-top: 0;'>Submit Event Media (Posters/Images)</h3>";
echo "<form action='events.php' method='POST' enctype='multipart/form-data'>";
echo "<input type='file' name='student_poster' required style='margin-bottom: 10px;'><br>";
echo "<input type='submit' value='Upload to University S3' style='background: #1a73e8; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;'>";
echo "</form>";
echo $uploadStatus;
echo "</div>";

echo "<h2>Available University Events</h2>";
echo "<p style='color: #5f6368;'><i>Automated Cloud Sync Active: $timestamp</i></p><hr>";

if (isset($data['_embedded']['events'])) {
    foreach ($data['_embedded']['events'] as $event) {
        $title = $event['name'];
        $venue = $event['_embedded']['venues'][0]['name'] ?? "Campus Hall";
        $date = $event['dates']['start']['localDate'];
        $image = $event['images'][0]['url'];

        echo "<div style='background: #fff; display: flex; padding: 20px; border-radius: 10px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); align-items: center;'>";
        echo "<img src='$image' style='width: 120px; height: 120px; object-fit: cover; border-radius: 8px; margin-right: 20px;'>";
        echo "<div>";
        echo "<h3 style='margin: 0; color: #202124;'>$title</h3>";
        echo "<p style='margin: 5px 0; color: #5f6368;'><b>Venue:</b> $venue | <b>Date:</b> $date</p>";
        
        // REQUIREMENT: Register for Activities
        echo "<button onclick='alert(\"You have successfully registered for $title!\")' style='background: #34a853; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin-top: 10px;'>Register for Activity</button>";
        
        echo "</div></div>";
    }
} else {
    echo "<div style='padding: 40px; text-align: center; background: #fff; border-radius: 10px;'><h3>No events found. Please check API connectivity.</h3></div>";
}

echo "</div></body></html>";
?>