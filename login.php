<?php
session_start();

// Wenn Bibliothekar schon eingeloggt → direkt zu admin.php
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

$error = "";

// Prüfen ob das Formular abgeschickt wurde
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    // Fester Login für die Bibliothekarin
    if ($username === "lenya" && $password === "passwort") {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = "lenya";
        header("Location: admin.php");
        exit;
    } else {
        $error = "❌ Benutzername oder Passwort falsch!";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login - Bibliothek</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>

    <h1>Bibliothekar Login</h1>

    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Benutzername:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Passwort:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Einloggen</button>
    </form>

</body>
</html>
