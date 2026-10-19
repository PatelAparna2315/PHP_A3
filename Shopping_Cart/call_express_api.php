<?php
$apiUrl = "http://localhost:3000/api/students";

// Initialize cURL
$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, $apiUrl);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($curl, CURLOPT_TIMEOUT, 10);

// Call the Express REST API
$response = curl_exec($curl);

if ($response === false) {
    echo "API Request Failed: " .
        htmlspecialchars(curl_error($curl), ENT_QUOTES, "UTF-8");
    curl_close($curl);
    exit;
}

$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($statusCode !== 200) {
    echo "API returned HTTP status: " . $statusCode;
    exit;
}

// Convert JSON response into a PHP array
$students = json_decode($response, true);

if (!is_array($students)) {
    echo "Invalid API response.";
    exit;
}

echo "<h2>Students Data From Express REST API</h2>";

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Course</th></tr>";

foreach ($students as $student) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars((string) $student["id"], ENT_QUOTES, "UTF-8") . "</td>";
    echo "<td>" . htmlspecialchars($student["name"], ENT_QUOTES, "UTF-8") . "</td>";
    echo "<td>" . htmlspecialchars($student["course"], ENT_QUOTES, "UTF-8") . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
