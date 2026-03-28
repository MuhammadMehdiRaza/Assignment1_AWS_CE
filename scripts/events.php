<?php

// 1. SETTINGS - Assignment Requirements

$apiKey = "JOwFCuAw6oo8QjeaSDA9kBXNwHqvecVN";

$bucketName = "unievent-media-assignment1-2026";

$apiUrl = "https://app.ticketmaster.com/discovery/v2/events.json?classificationName=university&apikey=" . $apiKey;



// 2. FETCH DATA - From Open API (Ticketmaster)

$jsonResponse = file_get_contents($apiUrl);

$data = json_decode($jsonResponse, true);



// 3. AUTOMATION - Store retrieved data persistently in S3

// This creates a unique filename with a timestamp

$timestamp = date("Y-m-d_H-i-s");

$localFile = "/tmp/university_events_$timestamp.json";

file_put_contents($localFile, $jsonResponse);



// Use the IAM Role (UniEvent-S3-Role) to upload the file to S3

exec("aws s3 cp $localFile s3://$bucketName/fetched_data/university_events_$timestamp.json 2>&1", $output, $returnCode);



// 4. DISPLAY - Process and show events as "University Events"

echo "<html><body style='font-family: sans-serif; padding: 40px; background-color: #f4f7f6;'>";

echo "<h1 style='color: #2c3e50; border-bottom: 2px solid #3498db;'>Official UniEvent - University Events</h1>";

echo "<p style='color: #7f8c8d;'><i>Automated Cloud System: Data fetched and stored in S3 at $timestamp</i></p>";



if ($returnCode === 0) {

    echo "<p style='color: green;'><b>✔ S3 Storage Sync: Successful</b></p><hr>";

} else {

    echo "<p style='color: red;'><b>✘ S3 Storage Sync: Failed (Check IAM permissions)</b></p><hr>";

}



if (isset($data['_embedded']['events'])) {

    foreach ($data['_embedded']['events'] as $event) {

        $name = $event['name'];

        $date = $event['dates']['start']['localDate'];

        $venue = $event['_embedded']['venues'][0]['name'] ?? "Campus Main Hall";

        $img = $event['images'][0]['url']; // Event poster link



        echo "<div style='background: white; margin-bottom: 20px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex;'>";

        echo "<img src='$img' style='width: 150px; border-radius: 4px; margin-right: 20px;'>";

        echo "<div>";

        echo "<h3 style='margin-top: 0;'>$name</h3>";

        echo "<b>University Venue:</b> $venue <br>";

        echo "<b>Event Date:</b> $date <br>";

        echo "<p><small>Stored securely in S3: $bucketName</small></p>";

        echo "</div></div>";

    }

} else {

    echo "<h3>No upcoming university events found.</h3>";

}



echo "</body></html>";

?>
