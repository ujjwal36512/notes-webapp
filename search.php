<?php
$pageTitle = 'Search Results';
require_once 'includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$notes = [];

if (!empty($query)) {
    $notes = searchNotes($pdo, $query);
}
?>

<div class="page-header">
    <h1>Search Results</h1>
    <a href="index.php" class="btn btn-secondary">Back to Notes</a>
</div>

<?php if (empty($query)): ?>
    <div class="alert alert-warning">Please enter a search term.</div>
    <div class="empty-state">
        <div class="empty-state-icon">🔍</div>
        <h2>Enter a Search Term</h2>
        <p>Use the search box above to find your notes.</p>
    </div>
<?php elseif (empty($notes)): ?>
    <div class="search-info">
        <p>No results found for "<strong><?= sanitize($query) ?></strong>"</p>
    </div>
    <div class="empty-state">
        <div class="empty-state-icon">😕</div>
        <h2>No Notes Found</h2>
        <p>Try a different search term or create a new note.</p>
        <a href="create.php" class="btn btn-primary">Create Note</a>
    </div>
<?php else: ?>
    <div class="search-info">
        <p>Found <strong><?= count($notes) ?></strong> note(s) for "<strong><?= sanitize($query) ?></strong>"</p>
    </div>

    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <div class="note-card <?= $note['is_pinned'] ? 'pinned' : '' ?>"
                 style="background-color: <?= sanitize($note['color']) ?>">

                <?php if ($note['is_pinned']): ?>
                    <span class="pin-badge" title="Pinned">📌</span>
                <?php endif; ?>

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
                        <a href="edit.php?id=<?= $note['id'] ?>" title="Edit">✏️</a>
                        <a href="delete.php?id=<?= $note['id'] ?>"
                           class="delete"
                           title="Delete"
                           onclick="return confirm('Are you sure you want to delete this note?')">🗑️</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
