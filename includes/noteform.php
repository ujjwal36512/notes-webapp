<?php
/**
 * Partial: Note Form Fields
 * -------------------------
 * Reusable form UI shared by create.php and edit.php.
 *
 * Variables expected from the caller:
 *   string  $title        - current title value
 *   string  $content      - current content value
 *   string  $color        - selected colour hex
 *   mixed   $category_id  - selected category id (or null)
 *   array   $errors       - validation errors ['field' => 'message']
 *   string  $submitLabel  - submit button text, e.g. "Create Note"
 */

// Fetch data needed by the form (keeps callers clean)
$categories = getAllCategories($pdo);
$colors     = getNoteColors();
?>

<?php if (isset($errors['database'])): ?>
    <div class="alert alert-error"><?= $errors['database'] ?></div>
<?php endif; ?>

<form method="POST" action="" id="noteForm">

    <!-- Title -->
    <div class="form-group">
        <label for="title">
            Title <span style="color: var(--danger-color);">*</span>
        </label>
        <input type="text"
               id="title"
               name="title"
               value="<?= sanitize($title) ?>"
               placeholder="Enter note title..."
               maxlength="255"
               required>
        <?php if (isset($errors['title'])): ?>
            <span class="error"><?= $errors['title'] ?></span>
        <?php endif; ?>
        <div class="char-count">
            <span id="titleCount"><?= strlen($title) ?></span>/255
        </div>
    </div>

    <!-- Content -->
    <div class="form-group">
        <label for="content">Content</label>
        <textarea id="content"
                  name="content"
                  placeholder="Write your note here..."><?= sanitize($content) ?></textarea>
        <div class="char-count">
            <span id="contentCount"><?= strlen($content) ?></span> characters
        </div>
    </div>

    <!-- Category -->
    <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
            <option value="">No Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                        <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= sanitize($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Colour Picker -->
    <div class="form-group">
        <label>Note Color</label>
        <div class="color-options">
            <?php foreach ($colors as $hex => $name):
                $colorId = 'color-' . str_replace('#', '', $hex); ?>
                <input type="radio"
                       name="color"
                       value="<?= $hex ?>"
                       id="<?= $colorId ?>"
                       <?= $color === $hex ? 'checked' : '' ?>
                       style="display: none;">
                <label for="<?= $colorId ?>"
                       class="color-option"
                       style="background-color: <?= $hex ?>;"
                       title="<?= $name ?>">
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="form-actions">
        <a href="index.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <?= htmlspecialchars($submitLabel) ?>
        </button>
    </div>

</form>
