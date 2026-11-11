<?php
$loginError = "";
if (($_GET["error"] ?? "") === "invalid") {
    $loginError = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main id="login-form">
        <form action="loginform.php" method="POST" id="form" autocomplete="off">
            <h1>Log In Form</h1>
            <?php if ($loginError !== ""): ?>
                <p class="error"><?php echo htmlspecialchars($loginError, ENT_QUOTES, "UTF-8"); ?></p>
            <?php endif; ?>

            <label for="username">Username:</label>
            <input type="text" id="username" name="username" autocomplete="off" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" autocomplete="new-password" required>

            <button type="submit" id="submit-btn">Login</button>
        </form>
    </main>
</body>
</html>
