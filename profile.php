<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';
require_once 'includes/SupabaseStorage.php';

requireLogin();

$user = getCurrentUser();
$message = '';
$error = '';

// Fetch fresh user data from DB to get bio/image/fullname
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch();

$fullName = $userData['full_name'] ?? '';
$bio = $userData['bio'] ?? '';
$profileImage = $userData['profile_image'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Handle File Upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar']['tmp_name'];
        $sourceProperties = getimagesize($file);

        if ($sourceProperties !== false) {
            $uploadImageType = $sourceProperties[2];
            $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];

            if (in_array($uploadImageType, $allowedTypes)) {
                $uploadFile = $file;
                
                // Get extension
                $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                if (!$extension) {
                     $extension = image_type_to_extension($uploadImageType, false);
                }
                
                $fileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
                
                $storage = new SupabaseStorage();
                
                // Delete old image if exists
                if (!empty($userData['profile_image'])) {
                    $oldFileName = basename($userData['profile_image']);
                    $storage->delete($oldFileName);
                }

                if ($storage->upload($uploadFile, $fileName)) {
                    $profileImage = $storage->getUrl($fileName);
                } else {
                    $error = "Failed to upload image. Please try again.";
                }
            } else {
                 $error = "Invalid image type. Only JPG, PNG and GIF are allowed.";
            }
        } else {
             $error = "Invalid file. Please upload a valid image.";
        }
    }

    if (empty($error)) {
        if (updateUser($pdo, $user['id'], $fullName, $bio, $profileImage)) {
            $message = "Profile updated successfully!";

            // ✅ Refresh session so the header avatar updates immediately
            $_SESSION['profile_image'] = $profileImage;
            
            // Refresh local data
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
            $profileImage = $userData['profile_image'];
        } else {
            $error = "Failed to update profile database.";
        }
    }
}
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>My Profile</h1>
        <p>Manage your account settings</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= sanitize($message) ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?= sanitize($error) ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <form method="POST" enctype="multipart/form-data" class="profile-form">
            <div class="profile-avatar-section">
                <div class="avatar-preview">
                    <?php if ($profileImage): ?>
                        <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" id="avatar-img" width="300" height="300">
                    <?php else: ?>
                        <div class="avatar-placeholder" id="avatar-placeholder"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                        <img src="" alt="Profile" id="avatar-img" width="300" height="300" style="display:none;">
                    <?php endif; ?>
                </div>
                <div class="file-input-wrapper">
                    <label for="avatar" class="btn btn-secondary">Change Photo</label>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                </div>
                <script>
                document.getElementById('avatar').addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (!file || !file.type.startsWith('image/')) return;

                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const img = document.getElementById('avatar-img');
                        img.src = ev.target.result;
                        img.style.display = '';

                        const placeholder = document.getElementById('avatar-placeholder');
                        if (placeholder) placeholder.style.display = 'none';
                    };
                    reader.readAsDataURL(file);
                });
                </script>
            </div>

            <div class="profile-details-section">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?= sanitize($userData['username']) ?>" disabled class="input-disabled">
                    <small>Username cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" value="<?= sanitize($userData['email']) ?>" disabled class="input-disabled">
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= sanitize($fullName) ?>" placeholder="Your Name">
                </div>

                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Tell us a bit about yourself..."><?= sanitize($bio) ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="index.php" class="btn btn-text">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>
