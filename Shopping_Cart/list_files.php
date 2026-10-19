
<?php
$directory = __DIR__;

echo "<h2>Files in Directory</h2>";

if (is_dir($directory)) {
    $items = scandir($directory);
    $fileFound = false;

    echo "<ul>";

    foreach ($items as $item) {
        if ($item === "." || $item === "..") {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;

        if (is_file($path)) {
            echo "<li>" .
                htmlspecialchars($item, ENT_QUOTES, "UTF-8") .
                "</li>";

            $fileFound = true;
        }
    }

    echo "</ul>";

    if (!$fileFound) {
        echo "<p>No files found in this directory.</p>";
    }
} else {
    echo "<p>Directory does not exist.</p>";
}
?>
