<?php
session_start();

// Datenbankverbindung
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bibliothek";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

// Fehler prüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Suchbegriff aus Formular
$search = "";
if (isset($_GET['q'])) {
    $search = $conn->real_escape_string($_GET['q']);
}

// SQL Suche leer → zeige alle Bücher
$sql = "SELECT book_id, title, description, isbn, price FROM books";

if (!empty($search)) {
    $sql .= " WHERE 
                title LIKE '%$search%' 
                OR description LIKE '%$search%'
                OR isbn LIKE '%$search%'";
}

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bücher suchen - Bibliothek</title>
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav>
        <a href="index.php">Home</a>

        <?php if (!isset($_SESSION['logged_in'])): ?>
            <!-- Schüler sehen nur Login -->
            <a href="login.php">Login</a>
        <?php else: ?>
            <!-- Bibliothekar sieht zusätzlich Admin und Logout -->
            <a href="admin.php">Admin</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </nav>

    <h1>Büchersuche</h1>

    <!-- Suchfeld -->
    <form method="GET" action="">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" >
        <button type="submit">Suchen</button>
    </form>

    <hr>

    <h2>Suchergebnisse:</h2>

    <?php
    if ($result && $result->num_rows > 0) {

        echo "<table border='1' cellpadding='8' cellspacing='0'>
                <tr>
                    <th>Titel</th>
                    <th>ISBN</th>
                    <th>Status</th>
                    <th>Beschreibung</th>
                </tr>";

        while($row = $result->fetch_assoc()) {

            // Status immer "Verfügbar" für Schüler
            echo "<tr>
                    <td>" . htmlspecialchars($row['title']) . "</td>
                    <td>" . htmlspecialchars($row['isbn']) . "</td>
                    <td>Verfügbar</td>
                    <td>" . htmlspecialchars($row['description']) . "</td>
                  </tr>";
        }

        echo "</table>";

    } else {
        echo "Keine Bücher gefunden.";
    }
    ?>

</body>
</html>
