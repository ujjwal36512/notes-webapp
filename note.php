<?php
/**
 * Note Controller
 * ---------------
 * Handles Create, Edit, and Delete in one place.
 *
 * Routes:
 *   ?action=create          → Create form / POST
 *   ?action=edit&id=X       → Edit form / POST
 *   ?action=delete&id=X     → Delete & redirect (no form)
 */

$action = $_GET['action'] ?? 'create';
$id     = (int)($_GET['id'] ?? 0);

// ── DELETE ──────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    session_start();
    require_once 'config/database.php';
    require_once 'includes/functions.php';

    if ($id > 0) {
        $userId   = $_SESSION['user_id'] ?? null;
        $ownerSql = $userId ? 'AND user_id = ?' : 'AND user_id IS NULL';
        $params   = $userId ? [$id, $userId] : [$id];

        try {
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? $ownerSql");
            $stmt->execute($params);

            $msg = $stmt->rowCount() > 0
                ? ['success', 'Note deleted successfully!']
                : ['error',   'Note not found or permission denied.'];
        } catch (PDOException $e) {
            $msg = ['error', 'Failed to delete note. Please try again.'];
        }
    } else {
        $msg = ['error', 'Invalid note ID!'];
    }

    setFlashMessage(...$msg);
    redirect('index.php');
}

// ── CREATE & EDIT (have a shared form) ──────────────────────────────────────

// Page title based on action
$pageTitle = $action === 'edit' ? 'Edit Note' : 'Create Note';
require_once 'includes/header.php';

// Default form values
$title = $content = '';
$color = '#ffffff';
$category_id = null;
$errors = [];

// ── EDIT: load existing note ─────────────────────────────────────────────────
if ($action === 'edit') {
    $note = getNoteById($pdo, $id);

    if (!$note) {
        setFlashMessage('error', 'Note not found!');
        redirect('index.php');
    }

    // Seed form from the database record (content normalized for clean display in textarea)
    $title       = $note['title'];
    $content     = normalizeNoteContentForDisplay($note['content']);
    $color       = $note['color'];
    $category_id = $note['category_id'];
}

// ── Handle POST (both create and edit) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = validateNoteInput($_POST);        // ← shared validation helper
    $errors = $result['errors'];
    $data   = $result['data'];

    // Unpack so the form re-populates on validation error
    ['title' => $title, 'content' => $content,
     'color' => $color, 'category_id' => $category_id] = $data;

    if (empty($errors)) {
        try {
            if ($action === 'edit') {
                // UPDATE existing note
                $stmt = $pdo->prepare(
                    "UPDATE notes
                     SET title = ?, content = ?, color = ?, category_id = ?
                     WHERE id = ?"
                );
                $stmt->execute([$title, $content, $color, $category_id, $id]);
                setFlashMessage('success', 'Note updated successfully!');

            } else {
                // INSERT new note
                $stmt = $pdo->prepare(
                    "INSERT INTO notes (title, content, color, category_id, user_id)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$title, $content, $color, $category_id, $_SESSION['user_id'] ?? null]);
                setFlashMessage('success', 'Note created successfully!');
            }

            redirect('index.php');

        } catch (PDOException $e) {
            $errors['database'] = 'Failed to save note. Please try again.';
        }
    }
}

// ── Render the form ───────────────────────────────────────────────────────────
$submitLabel = $action === 'edit' ? 'Update Note' : 'Create Note';
$pageHeader  = $action === 'edit' ? 'Edit Note'   : 'Create New Note';
?>

<div class="page-header">
    <h1><?= $pageHeader ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to Notes</a>
</div>

<div class="form-container">
    <?php require_once 'includes/noteform.php'; ?>  <!-- shared form partial -->
</div>

<?php require_once 'includes/footer.php'; ?>
