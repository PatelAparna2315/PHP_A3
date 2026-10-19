
<?php
require_once "config/db.php";

$xmlFile = __DIR__ . "/students.xml";

if (!file_exists($xmlFile)) {
    die("Error: students.xml file not found.");
}

libxml_use_internal_errors(true);

$xml = simplexml_load_file($xmlFile);

if ($xml === false) {
    die("Error: Unable to read XML file.");
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO students (name, email, age, course)
         VALUES (?, ?, ?, ?)"
    );

    $count = 0;

    foreach ($xml->student as $student) {
        $name = trim((string) $student->name);
        $email = trim((string) $student->email);
        $age = filter_var(
            (string) $student->age,
            FILTER_VALIDATE_INT
        );
        $course = trim((string) $student->course);

        if (
            $name === "" ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            $age === false ||
            $age < 1 ||
            $course === ""
        ) {
            throw new Exception(
                "Invalid student data found in XML file."
            );
        }

        $stmt->execute([
            $name,
            $email,
            $age,
            $course
        ]);

        $count++;
    }

    $pdo->commit();

    echo "<h2>XML Data Imported Successfully!</h2>";
    echo "<p>Total students inserted: " . $count . "</p>";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo "Import failed: " .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8");
}
?>
