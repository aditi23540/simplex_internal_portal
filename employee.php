<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Employee Registration</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 0; }
    .container { max-width: 700px; margin: 50px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    h2 { text-align: center; color: #0077b6; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input, select, textarea { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
    button { margin-top: 20px; padding: 12px 20px; width: 100%; background: #0077b6; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
    button:hover { background: #005f87; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 12px; text-align: left; }
    th { background: #0077b6; color: white; }
  </style>
</head>
<body>
  <div class="container">
    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
      <h2>Employee Details Summary</h2>
      <table>
        <tr><th>Field</th><th>Details</th></tr>
        <tr><td>Employee ID</td><td><?= htmlspecialchars($_POST["empId"]) ?></td></tr>
        <tr><td>Full Name</td><td><?= htmlspecialchars($_POST["name"]) ?></td></tr>
        <tr><td>Email</td><td><?= htmlspecialchars($_POST["email"]) ?></td></tr>
        <tr><td>Phone</td><td><?= htmlspecialchars($_POST["phone"]) ?></td></tr>
        <tr><td>Gender</td><td><?= htmlspecialchars($_POST["gender"]) ?></td></tr>
        <tr><td>Date of Birth</td><td><?= htmlspecialchars($_POST["dob"]) ?></td></tr>
        <tr><td>Date of Joining</td><td><?= htmlspecialchars($_POST["doj"]) ?></td></tr>
        <tr><td>Department</td><td><?= htmlspecialchars($_POST["department"]) ?></td></tr>
        <tr><td>Designation</td><td><?= htmlspecialchars($_POST["designation"]) ?></td></tr>
        <tr><td>Salary</td><td><?= htmlspecialchars($_POST["salary"]) ?></td></tr>
        <tr><td>Address</td><td><?= nl2br(htmlspecialchars($_POST["address"])) ?></td></tr>
      </table>
    <?php else: ?>
      <h2>Employee Registration Form</h2>
      <form method="POST" action="">
        <label>Employee ID:</label>
        <input type="text" name="empId" required>

        <label>Full Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Phone:</label>
        <input type="tel" name="phone" required>

        <label>Gender:</label>
        <select name="gender" required>
          <option value="">Select gender</option>
          <option>Male</option>
          <option>Female</option>
          <option>Other</option>
        </select>

        <label>Date of Birth:</label>
        <input type="date" name="dob" required>

        <label>Date of Joining:</label>
        <input type="date" name="doj" required>

        <label>Department:</label>
        <input type="text" name="department" required>

        <label>Designation:</label>
        <input type="text" name="designation" required>

        <label>Salary:</label>
        <input type="number" name="salary" step="0.01" required>

        <label>Address:</label>
        <textarea name="address" rows="4" required></textarea>

        <button type="submit">Register Employee</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
