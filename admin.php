<?php
// für Login/Logout benötigt
session_start();

// Prüft, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['logged_in'])) {
    // Falls nicht eingeloggt → Weiterleitung zur Login-Seite
    header("Location: login.php");
    exit;
}

/* =========================
   DB-VERBINDUNG
========================= */

// Verbindung zur MySQL-Datenbank herstellen
$conn = new mysqli("localhost", "root", "", "bibliothek");

// Zeichensatz auf UTF-8 setzen (Umlaute etc.)
$conn->set_charset("utf8");

// Prüfen, ob die Verbindung fehlgeschlagen ist
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Variable für Statusmeldungen (z. B. Erfolg / Fehler)
$message = "";

/* =========================
   FORMULAR-AKTIONEN
========================= */

// Prüft, ob das Formular per POST abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========= BUCH HINZUFÜGEN ========= */
    if ($_POST['action'] === 'add') {

        // Benutzereingaben absichern (SQL-Injection-Schutz)
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // ISBN automatisch um 1 erhöhen
        $res = $conn->query("SELECT MAX(isbn) AS max_isbn FROM books");
        $row = $res->fetch_assoc();

        // Falls noch keine ISBN existiert → Startwert setzen
        $isbn = $row['max_isbn'] !== null ? $row['max_isbn'] + 1 : 1000000000000;

        // SQL-Befehl zum Einfügen eines neuen Buches
        $sql = "INSERT INTO books (title, isbn, description, price)
                VALUES ('$title', '$isbn', '$description', '$price')";

        // SQL ausführen und Erfolg/Fehler speichern
        if ($conn->query($sql)) {
            $message = "Buch hinzugefügt (ISBN: $isbn)";
        } else {
            $message = "Fehler: " . $conn->error;
        }
    }

    /* ========= BUCH AKTUALISIEREN ========= */
    if ($_POST['action'] === 'update') {

        // Buch-ID aus dem Formular (als Integer)
        $id = (int)$_POST['id'];

        // Eingaben absichern
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // ISBN bleibt UNVERÄNDERT
        $sql = "UPDATE books 
                SET title='$title',
                    description='$description',
                    price='$price'
                WHERE book_id=$id";

        // Update ausführen und Meldung setzen
        $message = $conn->query($sql) ? "Buch aktualisiert." : $conn->error;
    }

    /* ========= BUCH LÖSCHEN ========= */
    if ($_POST['action'] === 'delete') {

        // Buch-ID aus dem Formular
        $id = (int)$_POST['id'];

        // Zuerst alle Reservierungen zu diesem Buch löschen
        $conn->query("DELETE FROM reservations WHERE book_id=$id");

        // Danach das Buch selbst löschen
        $conn->query("DELETE FROM books WHERE book_id=$id");

        // Bestätigungsmeldung
        $message = "Buch gelöscht.";
    }

    /* ========= RESERVIERUNG  ========= */
    if ($_POST['action'] === 'toggle_reservation') {

        // Buch-ID aus dem Formular
        $book_id = (int)$_POST['book_id'];

        // Prüfen, ob das Buch bereits reserviert ist
        $check = $conn->query("SELECT id FROM reservations WHERE book_id=$book_id");

        if ($check->num_rows > 0) {
            // Falls reserviert → Reservierung löschen
            $conn->query("DELETE FROM reservations WHERE book_id=$book_id");
        } else {
            // NEU: Namen für Reservierung abfragen
            // Prüfen, ob Name eingegeben wurde
            if (empty($_POST['reserver_name'])) {
                $message = "Bitte geben Sie einen Namen für die Reservierung ein.";
            } else {
                // Namen absichern
                $reserver_name = $conn->real_escape_string($_POST['reserver_name']);
                
                // NEU: Mit Namen in Reservierungstabelle eintragen
                $conn->query(
                    "INSERT INTO reservations (book_id, reserver_name, reserved_at)
                     VALUES ($book_id, '$reserver_name', NOW())"
                );
            }
        }
    }
}

/* =========================
   BUCH BEARBEITEN
========================= */

// Variable für das aktuell zu bearbeitende Buch
$edit_book = null;

// Prüft, ob ein Buch bearbeitet werden soll
if (isset($_GET['edit'])) {

    // Buch-ID aus der URL
    $id = (int)$_GET['edit'];

    // Buchdaten aus der Datenbank laden
    $res = $conn->query("SELECT * FROM books WHERE book_id=$id");
    $edit_book = $res->fetch_assoc();
}

/* =========================
   BÜCHER LADEN
========================= */

// SQL-Abfrage zum Laden aller Bücher inkl. Reservierungsstatus
// NEU: Jetzt auch mit Reservierungsnamen
$sql = "
SELECT 
    b.book_id, b.title, b.isbn, b.description, b.price,
    CASE 
        WHEN r.id IS NULL THEN 'verfügbar'
        ELSE 'reserviert'
    END AS status,
    r.reserver_name  -- NEU: Reservierungsnamen mit laden
FROM books b
LEFT JOIN reservations r ON b.book_id = r.book_id
";

// Abfrage ausführen
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Bibliothekar</title>
    <!-- Responsive Viewport -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Externe CSS-Datei -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Willkommen, Bibliothekar!</h1>

<!-- Navigation -->
<a href="logout.php">Logout</a> | <a href="index.php">Home</a>

<h2>Bücher verwalten</h2>

<!-- Statusmeldung ausgeben -->
<p><?= htmlspecialchars($message) ?></p>

<!-- Container für horizontales Scrollen auf kleinen Geräten -->
<div class="table-container">
<!-- Tabelle mit allen Büchern -->
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
    <!-- Buchdaten anzeigen -->
    <td><?= htmlspecialchars($row['title']) ?></td>
    <td><?= htmlspecialchars($row['isbn']) ?></td>
    <td><?= htmlspecialchars($row['description']) ?></td>
    <td><?= htmlspecialchars($row['price']) ?></td>

    <!-- Reservierungsstatus -->
    <td>
        <?php if ($row['status'] === 'reserviert'): ?>
            <!-- NEU: Reservierungsnamen anzeigen, falls vorhanden -->
            Reserviert<?= $row['reserver_name'] ? " von: " . htmlspecialchars($row['reserver_name']) : '' ?>
        <?php else: ?>
            Verfügbar
        <?php endif; ?>
    </td>

    <!-- Aktionen -->
    <td>
        <div class="actions">
            <!-- Bearbeiten-Link -->
            <a href="?edit=<?= $row['book_id'] ?>">Bearbeiten</a>

            <!-- Löschen-Formular -->
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $row['book_id'] ?>">
                <button onclick="return confirm('Wirklich löschen?')">Löschen</button>
            </form>
            
            <!-- Reservierungs-Formular -->
            <form method="POST" class="reservation-form">
                <input type="hidden" name="action" value="toggle_reservation">
                <input type="hidden" name="book_id" value="<?= $row['book_id'] ?>">
                
                <?php if ($row['status'] === 'reserviert'): ?>
                    <!-- Falls bereits reserviert: Button zum Löschen -->
                    <button type="submit">Reservierung löschen</button>
                <?php else: ?>
                    <!-- NEU: Nur bei verfügbaren Büchern: Namensfeld anzeigen -->
                    <input type="text" name="reserver_name" placeholder="Name" required>
                    <button type="submit">Reservieren</button>
                <?php endif; ?>
            </form>
        </div>
    </td>
</tr>
<?php endwhile; ?>
</table>
</div> <!-- ENDE table-container -->

<hr>

<!-- Formular zum Hinzufügen oder Bearbeiten -->
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