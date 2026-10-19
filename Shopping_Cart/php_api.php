
<?php
header("Content-Type: application/json; charset=UTF-8");

$students = [
    [
        "id" => 1,
        "name" => "Amit Patel",
        "course" => "BCA"
    ],
    [
        "id" => 2,
        "name" => "Neha Shah",
        "course" => "MCA"
    ],
    [
        "id" => 3,
        "name" => "Rahul Desai",
        "course" => "BCA"
    ]
];

echo json_encode($students);
?>
