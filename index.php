<?php
// Startet eine Session (z.B. für Login-Status)
session_start();

// ----------------------------
// Datenbankverbindung
// ----------------------------
$conn = new mysqli("localhost", "root", "", "bibliothek");
$conn->set_charset("utf8");

// Prüft, ob die Verbindung fehlgeschlagen ist
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// ----------------------------
// Suchbegriff initialisieren
// ----------------------------
$search = "";

// Prüft, ob ein Suchbegriff über GET übergeben wurde
if (isset($_GET['q'])) {
    // Schutz vor SQL-Injection
    $search = $conn->real_escape_string($_GET['q']);
}

// ----------------------------
// SQL-Abfrage: Bücher + Reservierungsstatus
// ----------------------------
$sql = "
SELECT 
    b.book_id,
    b.title,
    b.description,
    b.isbn,
    CASE 
        WHEN r.id IS NULL THEN 'verfügbar'
        ELSE 'reserviert'
    END AS status
FROM books b
LEFT JOIN reservations r ON b.book_id = r.book_id
";

// Falls ein Suchbegriff existiert, wird die WHERE-Bedingung ergänzt
if (!empty($search)) {
    $sql .= " WHERE 
        b.title LIKE '%$search%' 
        OR b.description LIKE '%$search%'
        OR b.isbn LIKE '%$search%'";
}

// SQL-Abfrage ausführen
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Büchersuche</title>
    <!-- Externe CSS-Datei -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navigationsleiste -->
<nav>
    <a href="index.php">Home</a>

    <!-- Wenn nicht eingeloggt, Login anzeigen -->
    <?php if (!isset($_SESSION['logged_in'])): ?>
        <a href="login.php">Login</a>
    <?php else: ?>
        <!-- Wenn eingeloggt, Admin- und Logout-Link anzeigen -->
        <a href="admin.php">Admin</a>
        <a href="logout.php">Logout</a>
    <?php endif; ?>
</nav>

<h1>Büchersuche</h1>

<!-- Suchformular -->
<form method="GET">
    <!-- htmlspecialchars schützt vor XSS -->
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>">
    <button>Suchen</button>
</form>

<hr>

<!-- Ergebnistabelle -->
<table border="1" cellpadding="8">
<tr>
    <th>Titel</th>
    <th>ISBN</th>
    <th>Status</th>
    <th>Beschreibung</th>
</tr>

<?php if ($result && $result->num_rows > 0): ?>
    <!-- Alle gefundenen Bücher anzeigen -->
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['isbn']) ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <!-- Falls keine Bücher gefunden wurden -->
    <tr>
        <td colspan="4">Keine Bücher gefunden.</td>
    </tr>
<?php endif; ?>
</table>

</body>
</html>
