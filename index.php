<?php
session_start();

// DB-Verbindung
$conn = new mysqli("localhost", "root", "", "bibliothek");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Suche
$search = "";
if (isset($_GET['q'])) {
    $search = $conn->real_escape_string($_GET['q']);
}

// Bücher + Status laden
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

if (!empty($search)) {
    $sql .= " WHERE 
        b.title LIKE '%$search%' 
        OR b.description LIKE '%$search%'
        OR b.isbn LIKE '%$search%'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Büchersuche</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <a href="index.php">Home</a>
    <?php if (!isset($_SESSION['logged_in'])): ?>
        <a href="login.php">Login</a>
    <?php else: ?>
        <a href="admin.php">Admin</a>
        <a href="logout.php">Logout</a>
    <?php endif; ?>
</nav>

<h1>Büchersuche</h1>

<form method="GET">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>">
    <button>Suchen</button>
</form>

<hr>

<table border="1" cellpadding="8">
<tr>
    <th>Titel</th>
    <th>ISBN</th>
    <th>Status</th>
    <th>Beschreibung</th>
</tr>

<?php if ($result && $result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['isbn']) ?></td>
            <td><?= $row['status'] ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="4">Keine Bücher gefunden.</td>
    </tr>
<?php endif; ?>
</table>

</body>
</html>
