
<?php
session_start();
require_once("dbconn.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!empty($username) && !empty($password)) {
        $sql = "SELECT * FROM logintable WHERE username = ? AND password = ?";
        $result = $connection->prepare($sql);
        if ($result) {
            $result->bind_param("ss", $username, $password);
            $result->execute();
            $result->store_result();
            
            if ($result->num_rows > 0) {
                session_regenerate_id(true);
                $_SESSION["username"] = $username;

                header("Location: welcome.php");
                exit;
            } else {
                header("Location: index.php?error=invalid");
                exit;
            }

            $result->close();

        } else {
            die("Error query " . $connection->error);
        }

    } else {
        header("Location: index.php?error=invalid");
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}
?>
