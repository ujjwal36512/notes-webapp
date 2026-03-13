<?php
$pageTitle = 'Archived Notes';
require_once 'includes/header.php';

// Handle archive action
if (isset($_GET['archive'])) {
    $id = (int)$_GET['archive'];
    if (archiveNote($pdo, $id)) {
        setFlashMessage('success', 'Note archived!');
    }
    redirect('index.php');
}

// Handle restore action
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    if (restoreNote($pdo, $id)) {
        setFlashMessage('success', 'Note restored!');
    }
    redirect('archive.php');
}

// Handle permanent delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $note = getNoteById($pdo, $id);

    if ($note && $note['is_archived']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Note permanently deleted!');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Failed to delete note.');
        }
    }
    redirect('archive.php');
}

// Get archived notes
$notes = getAllNotes($pdo, true);
?>



<?php if (empty($notes)): ?>
    <div class="empty-state">
    
        <h2>No Archived Notes</h2>
        <p>Notes you archive will appear here.</p>
        <a href="index.php" class="btn btn-primary">View All Notes</a>
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        Archived notes can be restored or permanently deleted.
    </div>

    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <div class="note-card" style="background-color: <?= sanitize($note['color']) ?>; opacity: 0.85;">
                <div class="note-header">
                    <h3><?= sanitize($note['title']) ?></h3>
                    <?php if ($note['category_name']): ?>
                        <span class="note-category"
                              style="background-color: <?= sanitize($note['category_color']) ?>">
                            <?= sanitize($note['category_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="note-content">
                    <?= nl2br(sanitize(normalizeNoteContentForDisplay($note['content']))) ?>
                </div>

                <div class="note-footer">
                    <span><?= formatDate($note['updated_at']) ?></span>
                    <div class="note-actions">
                        <a href="archive.php?restore=<?= $note['id'] ?>"
                           title="Restore"
                           style="color: var(--success-color);">↩️ Restore</a>
                        <a href="archive.php?delete=<?= $note['id'] ?>"
                           class="delete"
                           title="Delete Permanently"
                           onclick="return confirm('Permanently delete this note? This cannot be undone!')">🗑️</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
