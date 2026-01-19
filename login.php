<?php
// Startet die Session (wichtig für Login-Status)
session_start();

// -------------------------------------------------
// Falls der Bibliothekar bereits eingeloggt ist,
// wird er direkt zur Admin-Seite weitergeleitet
// -------------------------------------------------
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

// Variable für Fehlermeldungen
$error = "";

// -------------------------------------------------
// Prüfen, ob das Login-Formular abgeschickt wurde
// -------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Benutzername und Passwort aus dem Formular holen
    $username = $_POST["username"] ?? "";
    $password = $_POST["password"] ?? "";

    // -------------------------------------------------
    // Fester Login für die Bibliothekarin
    // (ohne Datenbank, nur zu Übungszwecken)
    // -------------------------------------------------
    if ($username === "Bibliothekar" && $password === "passwort") {

        // Login-Status in der Session speichern
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = "Bibliothekar";

        // Weiterleitung zur Admin-Seite
        header("Location: admin.php");
        exit;

    } else {
        // Fehlermeldung bei falschen Login-Daten
        $error = "❌ Benutzername oder Passwort falsch!";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login - Bibliothek</title>
    <!-- Externe CSS-Datei -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navigation -->
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>

    <h1>Bibliothekar Login</h1>

    <!-- Fehlermeldung anzeigen, falls vorhanden -->
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?= $error ?></p>
    <?php endif; ?>

    <!-- Login-Formular -->
    <form method="POST">
        <label>Benutzername:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Passwort:</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Einloggen</button>
    </form>

</body>
</html>
