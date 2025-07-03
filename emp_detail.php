<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Employee Details</title>
  <style>
    body { font-family: sans-serif; background: #f4f7f8; margin: 0; padding: 0; }
    .container { max-width: 600px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    h1 { text-align: center; color: #2a9d8f; }
    .info { margin-bottom: 15px; font-size: 18px; }
    .label { font-weight: bold; color: #555; }
    .value { color: #333; }
  </style>
</head>
<body>
  <div class="container">
    <h1>Employee Details</h1>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      echo '<div class="info"><span class="label">Employee ID:</span> <span class="value">' . htmlspecialchars($_POST["empId"]) . '</span></div>';
      echo '<div class="info"><span class="label">Name:</span> <span class="value">' . htmlspecialchars($_POST["name"]) . '</span></div>';
      echo '<div class="info"><span class="label">Designation:</span> <span class="value">' . htmlspecialchars($_POST["designation"]) . '</span></div>';
      echo '<div class="info"><span class="label">Department:</span> <span class="value">' . htmlspecialchars($_POST["department"]) . '</span></div>';
      echo '<div class="info"><span class="label">Email:</span> <span class="value">' . htmlspecialchars($_POST["email"]) . '</span></div>';
      echo '<div class="info"><span class="label">Phone:</span> <span class="value">' . htmlspecialchars($_POST["phone"]) . '</span></div>';
      echo '<div class="info"><span class="label">Gender:</span> <span class="value">' . htmlspecialchars($_POST["gender"]) . '</span></div>';
      echo '<div class="info"><span class="label">Date of Joining:</span> <span class="value">' . htmlspecialchars($_POST["doj"]) . '</span></div>';
      echo '<div class="info"><span class="label">Address:</span> <span class="value">' . nl2br(htmlspecialchars($_POST["address"])) . '</span></div>';
    } else {
      echo "<p>No data received. Please submit the form first.</p>";
    }
    ?>
  </div>
</body>
</html>
