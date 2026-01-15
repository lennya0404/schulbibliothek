<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

/* =========================
   DB-VERBINDUNG
========================= */
$conn = new mysqli("localhost", "root", "", "bibliothek");
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$message = "";

/* =========================
   FORMULAR-AKTIONEN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========= BUCH HINZUFÜGEN ========= */
    if ($_POST['action'] === 'add') {
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // ISBN automatisch +1
        $res = $conn->query("SELECT MAX(isbn) AS max_isbn FROM books");
        $row = $res->fetch_assoc();
        $isbn = $row['max_isbn'] !== null ? $row['max_isbn'] + 1 : 1000000000000;

        $sql = "INSERT INTO books (title, isbn, description, price)
                VALUES ('$title', '$isbn', '$description', '$price')";

        if ($conn->query($sql)) {
            $message = "Buch hinzugefügt (ISBN: $isbn)";
        } else {
            $message = "Fehler: " . $conn->error;
        }
    }

    /* ========= BUCH AKTUALISIEREN ========= */
    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // ISBN bleibt UNVERÄNDERT
        $sql = "UPDATE books 
                SET title='$title',
                    description='$description',
                    price='$price'
                WHERE book_id=$id";

        $message = $conn->query($sql) ? "Buch aktualisiert." : $conn->error;
    }

    /* ========= BUCH LÖSCHEN ========= */
    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM reservations WHERE book_id=$id");
        $conn->query("DELETE FROM books WHERE book_id=$id");
        $message = "Buch gelöscht.";
    }

    /* ========= RESERVIERUNG TOGGLE ========= */
    if ($_POST['action'] === 'toggle_reservation') {
        $book_id = (int)$_POST['book_id'];
        $user_id = 1; // Demo

        $check = $conn->query("SELECT id FROM reservations WHERE book_id=$book_id");
        if ($check->num_rows > 0) {
            $conn->query("DELETE FROM reservations WHERE book_id=$book_id");
        } else {
            $conn->query(
                "INSERT INTO reservations (book_id, user_id, reserved_at)
                 VALUES ($book_id, $user_id, NOW())"
            );
        }
    }
}

/* =========================
   BUCH BEARBEITEN
========================= */
$edit_book = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM books WHERE book_id=$id");
    $edit_book = $res->fetch_assoc();
}

/* =========================
   BÜCHER LADEN
========================= */
$sql = "
SELECT 
    b.book_id, b.title, b.isbn, b.description, b.price,
    CASE 
        WHEN r.id IS NULL THEN 'verfügbar'
        ELSE 'reserviert'
    END AS status
FROM books b
LEFT JOIN reservations r ON b.book_id = r.book_id
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bibliothekar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Willkommen, Bibliothekar!</h1>
<a href="logout.php">Logout</a> | <a href="index.php">Home</a>

<h2>Bücher verwalten</h2>
<p><?= htmlspecialchars($message) ?></p>

<table border="1" cellpadding="8">
<tr>
    <th>Titel</th>
    <th>ISBN</th>
    <th>Beschreibung</th>
    <th>Preis</th>
    <th>Status</th>
    <th>Aktionen</th>
</tr>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['isbn']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= htmlspecialchars($row['price']) ?></td>
    <td>
        <form method="POST">
            <input type="hidden" name="action" value="toggle_reservation">
            <input type="hidden" name="book_id" value="<?= $row['book_id'] ?>">
            <input type="checkbox" onchange="this.form.submit()"
                <?= $row['status'] === 'reserviert' ? 'checked' : '' ?>>
            reserviert
        </form>
    </td>
    <td>
        <a href="?edit=<?= $row['book_id'] ?>">Bearbeiten</a>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $row['book_id'] ?>">
            <button onclick="return confirm('Wirklich löschen?')">Löschen</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>
</table>

<hr>

<h3><?= $edit_book ? "Buch bearbeiten" : "Neues Buch" ?></h3>

<form method="POST">
    <input type="hidden" name="action" value="<?= $edit_book ? 'update' : 'add' ?>">
    <?php if ($edit_book): ?>
        <input type="hidden" name="id" value="<?= $edit_book['book_id'] ?>">
    <?php endif; ?>

    Titel:<br>
    <input name="title" required value="<?= $edit_book['title'] ?? '' ?>"><br><br>

    ISBN:<br>
    <input readonly value="<?= $edit_book['isbn'] ?? 'automatisch' ?>"><br><br>

    Beschreibung:<br>
    <textarea name="description" required><?= $edit_book['description'] ?? '' ?></textarea><br><br>

    Preis:<br>
    <input name="price" required value="<?= $edit_book['price'] ?? '' ?>"><br><br>

    <button><?= $edit_book ? "Aktualisieren" : "Hinzufügen" ?></button>
</form>

</body>
</html>
