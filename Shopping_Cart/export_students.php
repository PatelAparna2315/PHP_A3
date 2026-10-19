
<?php
require_once "config/db.php";

try {
    // Fetch all students from the database
    $stmt = $pdo->query(
        "SELECT name, email, age, course FROM students"
    );

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create the XML document
    $xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?><students></students>'
    );

    // Add student records to XML
    foreach ($students as $student) {
        $studentNode = $xml->addChild("student");

        $studentNode->addChild(
            "name",
            htmlspecialchars($student["name"], ENT_XML1, "UTF-8")
        );

        $studentNode->addChild(
            "email",
            htmlspecialchars($student["email"], ENT_XML1, "UTF-8")
        );

        $studentNode->addChild("age", (string) $student["age"]);

        $studentNode->addChild(
            "course",
            htmlspecialchars($student["course"], ENT_XML1, "UTF-8")
        );
    }

    // Save the XML file without overwriting the original input
    $outputFile = __DIR__ . "/students_export.xml";

    $xml->asXML($outputFile);

    echo "<h2>Database Data Exported Successfully!</h2>";
    echo "<p>Total students exported: " . count($students) . "</p>";
    echo "<p>Output file: students_export.xml</p>";

} catch (PDOException $e) {
    http_response_code(500);
    echo "Database error: " .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
}
?>
