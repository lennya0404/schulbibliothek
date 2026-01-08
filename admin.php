<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// Datenbankverbindung
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bibliothek";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

$message = "";

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        if ($action == 'add') {
            $title = $conn->real_escape_string($_POST['title']);
            $isbn = $conn->real_escape_string($_POST['isbn']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = $conn->real_escape_string($_POST['price']);
            $sql = "INSERT INTO books (title, isbn, description, price) VALUES ('$title', '$isbn', '$description', '$price')";
            if ($conn->query($sql) === TRUE) {
                $message = "Buch hinzugefügt.";
            } else {
                $message = "Fehler: " . $conn->error;
            }
        } elseif ($action == 'update') {
            $id = $conn->real_escape_string($_POST['id']);
            $title = $conn->real_escape_string($_POST['title']);
            $isbn = $conn->real_escape_string($_POST['isbn']);
            $description = $conn->real_escape_string($_POST['description']);
            $price = $conn->real_escape_string($_POST['price']);
            $sql = "UPDATE books SET title='$title', isbn='$isbn', description='$description', price='$price' WHERE book_id='$id'";
            if ($conn->query($sql) === TRUE) {
                $message = "Buch aktualisiert.";
            } else {
                $message = "Fehler: " . $conn->error;
            }
        } elseif ($action == 'delete') {
            $id = $conn->real_escape_string($_POST['id']);
            $sql = "DELETE FROM books WHERE book_id='$id'";
            if ($conn->query($sql) === TRUE) {
                $message = "Buch gelöscht.";
            } else {
                $message = "Fehler: " . $conn->error;
            }
        }
    }
}

// Get book for editing
$edit_book = null;
if (isset($_GET['edit'])) {
    $id = $conn->real_escape_string($_GET['edit']);
    $sql = "SELECT * FROM books WHERE book_id='$id'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $edit_book = $result->fetch_assoc();
    }
}

// SQL Abfrage für alle Bücher
$sql = "SELECT book_id, title, description, isbn, price FROM books";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bibliothekar</title>
</head>
<body>
    <h1>Willkommen, <?= $_SESSION['username'] ?>!</h1>
    <a href="logout.php">Logout</a>
    <a href="index.php">Home</a>
     <link rel="stylesheet" href="style.css">

    <h2>Bücher verwalten</h2>
    <p>Hier kannst du Bücher hinzufügen, bearbeiten und löschen.</p>

    <?php if ($message): ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>

    <?php
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' cellpadding='8' cellspacing='0'>
                <tr>
                    <th>Titel</th>
                    <th>ISBN</th>
                    <th>Beschreibung</th>
                    <th>Preis</th>
                    <th>Aktionen</th>
                </tr>";

        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['title']) . "</td>
                    <td>" . htmlspecialchars($row['isbn']) . "</td>
                    <td>" . htmlspecialchars($row['description']) . "</td>
                    <td>" . htmlspecialchars($row['price']) . "</td>
                    <td>
                        <a href='?edit=" . $row['book_id'] . "'>Bearbeiten</a>
                        <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='action' value='delete'>
                            <input type='hidden' name='id' value='" . $row['book_id'] . "'>
                            <button type='submit' onclick='return confirm(\"Wirklich löschen?\")'>Löschen</button>
                        </form>
                    </td>
                  </tr>";
        }

        echo "</table>";
    } else {
        echo "Keine Bücher gefunden.";
    }
    ?>

    <h3><?php echo $edit_book ? 'Buch bearbeiten' : 'Neues Buch hinzufügen'; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="<?php echo $edit_book ? 'update' : 'add'; ?>">
        <?php if ($edit_book): ?>
            <input type="hidden" name="id" value="<?php echo $edit_book['book_id']; ?>">
        <?php endif; ?>

        <label for="title">Titel:</label><br>
        <input type="text" id="title" name="title" value="<?php echo $edit_book ? htmlspecialchars($edit_book['title']) : ''; ?>" required><br><br>

        <label for="isbn">ISBN:</label><br>
        <input type="number" id="isbn" name="isbn" value="<?php echo $edit_book ? htmlspecialchars($edit_book['isbn']) : ''; ?>" required><br><br>

        <label for="description">Beschreibung:</label><br>
        <textarea id="description" name="description" required><?php echo $edit_book ? htmlspecialchars($edit_book['description']) : ''; ?></textarea><br><br>

        <label for="price">Preis:</label><br>
        <input type="text" id="price" name="price" value="<?php echo $edit_book ? htmlspecialchars($edit_book['price']) : ''; ?>" required><br><br>

        <button type="submit"><?php echo $edit_book ? 'Aktualisieren' : 'Hinzufügen'; ?></button>
        <?php if ($edit_book): ?>
            <a href="admin.php">Abbrechen</a>
        <?php endif; ?>
    </form>

</body>
</html>
