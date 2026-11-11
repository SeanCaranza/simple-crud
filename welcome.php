<?php
session_start();
require_once("dbconn.php");

if (!isset($_SESSION["username"])) {
    header("Location: index.php");
    exit;
}

function skiphtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$tableCreated = $connection->query("
    CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        joining_date DATE NOT NULL
    )
");
if (!$tableCreated) {
    die("Could not create the employees table: " . skiphtml($connection->error));
}

$error = "";

// Handling all the function for Add, Edit, and Delete form.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "delete") {
        $id = (int) ($_POST["id"] ?? 0);

        $statement = $connection->prepare("DELETE FROM employees WHERE id = ?");
        if (!$statement) {
            die("Could not prepare the delete query: " . skiphtml($connection->error));
        }
        $statement->bind_param("i", $id);
        if (!$statement->execute()) {
            die("Could not delete the employee: " . skiphtml($statement->error));
        }

        header("Location: welcome.php");
        exit;
    }

    if ($action === "add" || $action === "edit") {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $joiningDate = $_POST["joining_date"] ?? "";

        if ($name === "" || $email === "" || $phone === "" || $joiningDate === "") {
            $error = "Please fill in all fields.";
        } elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {
            $error = "Name can contain letters and spaces only.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif (!preg_match("/^\\d{4}-\\d{2}-\\d{2}$/", $joiningDate)
            || !checkdate(
                (int) substr($joiningDate, 5, 2),
                (int) substr($joiningDate, 8, 2),
                (int) substr($joiningDate, 0, 4)
            )) {
            $error = "Please enter a valid joining date.";
        } else {
            if ($action === "add") {
                $statement = $connection->prepare(
                    "INSERT INTO employees (name, email, phone, joining_date) VALUES (?, ?, ?, ?)"
                );
                if (!$statement) {
                    die("Could not prepare the add query: " . skiphtml($connection->error));
                }
                $statement->bind_param("ssss", $name, $email, $phone, $joiningDate);
            } else {
                $id = (int) ($_POST["id"] ?? 0);
                $statement = $connection->prepare(
                    "UPDATE employees SET name = ?, email = ?, phone = ?, joining_date = ? WHERE id = ?"
                );
                if (!$statement) {
                    die("Could not prepare the edit query: " . skiphtml($connection->error));
                }
                $statement->bind_param("ssssi", $name, $email, $phone, $joiningDate, $id);
            }

            if (!$statement->execute()) {
                die("Could not save the employee: " . skiphtml($statement->error));
            }
            header("Location: welcome.php");
            exit;
        }
    }
}

// Load that employee into the form for editing.
$editEmployee = null;
if (isset($_GET["edit"])) {
    $id = (int) $_GET["edit"];
    $statement = $connection->prepare(
        "SELECT id, name, email, phone, joining_date FROM employees WHERE id = ?"
    );
    if (!$statement) {
        die("Could not prepare the employee lookup: " . skiphtml($connection->error));
    }
    $statement->bind_param("i", $id);
    if (!$statement->execute()) {
        die("Could not load the employee: " . skiphtml($statement->error));
    }
    $result = $statement->get_result();
    $editEmployee = $result->fetch_assoc();
}

// Read all employees so we can show them in the table.
$employees = $connection->query(
    "SELECT id, name, email, phone, joining_date FROM employees ORDER BY id"
);
if (!$employees) {
    die("Could not load employees: " . skiphtml($connection->error));
}

// Keep the values in the form
$name = $_POST["name"] ?? ($editEmployee["name"] ?? "");
$email = $_POST["email"] ?? ($editEmployee["email"] ?? "");
$phone = $_POST["phone"] ?? ($editEmployee["phone"] ?? "");
$joiningDate = $_POST["joining_date"] ?? ($editEmployee["joining_date"] ?? "");
$isEditing = $editEmployee !== null || (($_POST["action"] ?? "") === "edit");
$editId = $editEmployee["id"] ?? (int) ($_POST["id"] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="crud-page">
        <h1>Employee Records</h1>

        <?php if ($error !== ""): ?>
            <p class="error"><?php echo skiphtml($error); ?></p>
        <?php endif; ?>

        <section class="employee-form">
            <h2><?php echo $isEditing ? "Edit employee" : "Add a new employee"; ?></h2>
            <form method="POST" action="welcome.php">
                <input type="hidden" name="action"
                    value="<?php echo $isEditing ? "edit" : "add"; ?>">

                <?php if ($isEditing): ?>
                    <input type="hidden" name="id" value="<?php echo skiphtml($editId); ?>">
                <?php endif; ?>

                <label for="name">Name</label>
                <input id="name" name="name" type="text" maxlength="100"
                    pattern="[A-Za-z ]+" title="Use letters and spaces only" required
                    value="<?php echo skiphtml($name); ?>">

                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="150" required
                    value="<?php echo skiphtml($email); ?>">

                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="tel" maxlength="30" required
                    value="<?php echo skiphtml($phone); ?>">

                <label for="joining_date">Joining date</label>
                <input id="joining_date" name="joining_date" type="date" required
                    value="<?php echo skiphtml($joiningDate); ?>">

                <button class="button button-add" type="submit">
                    <?php echo $isEditing ? "Save changes" : "Add employee"; ?>
                </button>

                <?php if ($isEditing): ?>
                    <a class="cancel-link" href="welcome.php">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="employee-list">
            <h2>Employees</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Joining date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($employees->num_rows === 0): ?>
                            <tr><td colspan="6">No employees added yet.</td></tr>
                        <?php else: ?>
                            <?php while ($employee = $employees->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo skiphtml($employee["id"]); ?></td>
                                    <td><?php echo skiphtml($employee["name"]); ?></td>
                                    <td><?php echo skiphtml($employee["email"]); ?></td>
                                    <td><?php echo skiphtml($employee["phone"]); ?></td>
                                    <td><?php echo skiphtml($employee["joining_date"]); ?></td>
                                    <td class="actions">
                                        <a class="button button-edit"
                                            href="welcome.php?edit=<?php echo skiphtml($employee["id"]); ?>">Edit</a>
                                        <form method="POST" action="welcome.php"
                                            onsubmit="return confirm('Delete this employee?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id"
                                                value="<?php echo skiphtml($employee["id"]); ?>">
                                            <button class="button button-delete" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
