<?php
// Session starten (für Login-Status)
session_start();

// Prüfen, ob der Benutzer eingeloggt ist
if (!isset($_SESSION['logged_in'])) {
    // Falls nicht eingeloggt → Weiterleitung zur Login-Seite
    header("Location: login.php");
    exit;
}

/* =========================
   DATENBANKVERBINDUNG
========================= */

// Verbindung zur MySQL-Datenbank herstellen
$conn = new mysqli("localhost", "root", "", "bibliothek");

// Zeichensatz auf UTF-8 setzen (Umlaute etc.)
$conn->set_charset("utf8");

// Prüfen, ob Verbindung fehlgeschlagen ist
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// Variable für Statusmeldungen
$message = "";

/* =========================
   FORMULAR-AKTIONEN
========================= */

// Wird ausgeführt, wenn ein Formular abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ========= BUCH HINZUFÜGEN ========= */
    if ($_POST['action'] === 'add') {

        // Eingaben absichern
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // Automatische ISBN: höchste vorhandene ISBN +1
        $res = $conn->query("SELECT MAX(isbn) AS max_isbn FROM books");
        $row = $res->fetch_assoc();
        $isbn = $row['max_isbn'] !== null ? $row['max_isbn'] + 1 : 1000000000000;

        // SQL-Befehl zum Einfügen eines neuen Buches
        $sql = "INSERT INTO books (title, isbn, description, price)
                VALUES ('$title', '$isbn', '$description', '$price')";

        // Erfolg oder Fehler ausgeben
        if ($conn->query($sql)) {
            $message = "Buch hinzugefügt (ISBN: $isbn)";
        } else {
            $message = "Fehler: " . $conn->error;
        }
    }

    /* ========= BUCH AKTUALISIEREN ========= */
    if ($_POST['action'] === 'update') {

        // Buch-ID als Integer
        $id = (int)$_POST['id'];

        // Eingaben absichern
        $title = $conn->real_escape_string($_POST['title']);
        $description = $conn->real_escape_string($_POST['description']);
        $price = $conn->real_escape_string($_POST['price']);

        // ISBN bleibt unverändert
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

        // Erst Reservierungen löschen (Fremdschlüssel!)
        $conn->query("DELETE FROM reservations WHERE book_id=$id");

        // Danach Buch löschen
        $conn->query("DELETE FROM books WHERE book_id=$id");

        $message = "Buch gelöscht.";
    }

    /* ========= RESERVIERUNG UMSCHALTEN ========= */
    if ($_POST['action'] === 'toggle_reservation') {

        $book_id = (int)$_POST['book_id'];
        $user_id = 1; // Demo-Benutzer

        // Prüfen, ob Buch bereits reserviert ist
        $check = $conn->query("SELECT id FROM reservations WHERE book_id=$book_id");

        if ($check->num_rows > 0) {
            // Falls ja → Reservierung entfernen
            $conn->query("DELETE FROM reservations WHERE book_id=$book_id");
        } else {
            // Falls nein → Reservierung hinzufügen
            $conn->query(
                "INSERT INTO reservations (book_id, user_id, reserved_at)
                 VALUES ($book_id, $user_id, NOW())"
            );
        }
    }
}

/* =========================
   BUCH ZUM BEARBEITEN LADEN
========================= */

$edit_book = null;

// Wenn Bearbeiten-Button gedrückt wurde
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM books WHERE book_id=$id");
    $edit_book = $res->fetch_assoc();
}

/* =========================
   BÜCHERLISTE LADEN
========================= */

// Bücher mit Reservierungsstatus laden
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
