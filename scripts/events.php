<?php

// 1. SETTINGS - Use environment variables for sensitive values
$apiKey = getenv('UNI_EVENT_API_KEY');
$bucketName = getenv('UNI_EVENT_BUCKET') ?: 'unievent-media-assignment1-2026';
$timestamp = date('Y-m-d_H-i-s');

if (!$apiKey) {
    http_response_code(500);
    echo '<html><body style="font-family: sans-serif; padding: 40px; background-color: #f4f7f6;">';
    echo '<h2 style="color: #c0392b;">Configuration Error</h2>';
    echo '<p>Missing required environment variable: <b>UNI_EVENT_API_KEY</b></p>';
    echo '</body></html>';
    exit;
}

$query = http_build_query([
    'classificationName' => 'university',
    'apikey' => $apiKey,
]);
$apiUrl = "https://app.ticketmaster.com/discovery/v2/events.json?$query";

// 2. FETCH DATA - From Open API (Ticketmaster) using cURL with timeouts
$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$jsonResponse = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($jsonResponse === false || $httpCode >= 400) {
    http_response_code(502);
    echo '<html><body style="font-family: sans-serif; padding: 40px; background-color: #f4f7f6;">';
    echo '<h2 style="color: #c0392b;">API Fetch Failed</h2>';
    echo '<p>Unable to fetch events from Ticketmaster at this time.</p>';
    if ($curlError) {
        echo '<p><small>Detail: ' . htmlspecialchars($curlError, ENT_QUOTES, 'UTF-8') . '</small></p>';
    }
    echo '</body></html>';
    exit;
}

$data = json_decode($jsonResponse, true);

// 3. AUTOMATION - Store retrieved data persistently in S3
$tempDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
$localFile = $tempDir . DIRECTORY_SEPARATOR . "university_events_$timestamp.json";
file_put_contents($localFile, $jsonResponse);

$sourceArg = escapeshellarg($localFile);
$targetArg = escapeshellarg("s3://$bucketName/fetched_data/university_events_$timestamp.json");
exec("aws s3 cp $sourceArg $targetArg 2>&1", $output, $returnCode);



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

        $name = htmlspecialchars($event['name'] ?? 'Untitled Event', ENT_QUOTES, 'UTF-8');

        $date = htmlspecialchars($event['dates']['start']['localDate'] ?? 'TBA', ENT_QUOTES, 'UTF-8');

        $venue = htmlspecialchars($event['_embedded']['venues'][0]['name'] ?? 'Campus Main Hall', ENT_QUOTES, 'UTF-8');

        $img = htmlspecialchars($event['images'][0]['url'] ?? '', ENT_QUOTES, 'UTF-8'); // Event poster link



        echo "<div style='background: white; margin-bottom: 20px; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: flex;'>";

        if ($img !== '') {
            echo "<img src='$img' alt='Event Poster' style='width: 150px; border-radius: 4px; margin-right: 20px;'>";
        }

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
