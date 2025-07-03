<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = htmlspecialchars($_POST['title']);
    $severity = htmlspecialchars($_POST['severity']);
    $description = htmlspecialchars($_POST['description']);
    $steps = htmlspecialchars($_POST['steps']);

    echo "<h2>QA Report Submitted</h2>";
    echo "<strong>Title:</strong> $title <br>";
    echo "<strong>Severity:</strong> $severity <br>";
    echo "<strong>Description:</strong> <pre>$description</pre>";
    echo "<strong>Steps to Reproduce:</strong> <pre>$steps</pre>";
} else {
    echo "Invalid request.";
}
?>