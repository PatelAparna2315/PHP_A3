
<?php
require_once "../config/db.php";

$username = "admin";
$plainPassword = "Admin@123";
$hashedPassword = password_hash(
    $plainPassword,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "SELECT admin_id FROM admins WHERE username = ?"
);
$stmt->execute([$username]);

if ($stmt->fetch()) {
    echo "Admin account already exists.";
} else {
    $stmt = $pdo->prepare(
        "INSERT INTO admins (username, password)
         VALUES (?, ?)"
    );

    $stmt->execute([$username, $hashedPassword]);

    echo "Admin account created successfully!";
    echo "<br>Username: admin";
    echo "<br>Password: Admin@123";
}
?>
