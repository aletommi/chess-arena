<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/lib/UserRepository.php';


require_login();
$repo = new UserRepository(Database::connection());

$user = current_user();
$profile = $user;
$messages = [];
$errors = [];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Sessione non valida. Riprova.';
    } else {
        $action = $_POST['action'] ?? 'update';
        if ($action === 'update') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            // Profile Data Preparation
            $data = [
                'avatar_path' => (string) ($profile['avatar_path'] ?? ''),
                'newsletter_opt_in' => isset($_POST['newsletter_opt_in']) ? 1 : 0,
            ];

            // Validate Username
            if ($username === '') {
                $errors[] = 'L\'username non può essere vuoto.';
            } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
                $errors[] = 'L\'username deve contenere solo lettere, numeri e underscore (3-20 caratteri).';
            } else {
                if ($username !== ($profile['username'] ?? '')) {
                    if ($repo->findByUsername($username)) {
                        $errors[] = 'Username non disponibile.';
                    } else {
                        $data['username'] = $username;
                    }
                }
            }

            // Validate Password (if provided)
            $updatePassword = false;
            if (!empty($password) || !empty($confirm)) {
                if (strlen($password) < 6) {
                    $errors[] = 'La password deve avere almeno 6 caratteri.';
                } elseif ($password !== $confirm) {
                    $errors[] = 'Le password non coincidono.';
                } else {
                    $updatePassword = true;
                }
            }

            // Image Upload Handling
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['avatar']['tmp_name'];
                $imageInfo = @getimagesize($fileTmp);
                $mime = is_array($imageInfo) && isset($imageInfo['mime']) ? (string) $imageInfo['mime'] : '';
                $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

                if (!isset($allowedTypes[$mime])) {
                    $errors[] = 'Formato immagine non supportato.';
                } else {
                    $ext = $allowedTypes[$mime];
                    $newFileName = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
                    $uploadDir = __DIR__ . '/assets/user_images/';
                    if (!is_dir($uploadDir))
                        mkdir($uploadDir, 0755, true);

                    if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                        $oldAvatar = $data['avatar_path']; // Keep ref to delete
                        $data['avatar_path'] = 'assets/user_images/' . $newFileName;

                        // Cleanup old
                        if ($oldAvatar && strpos($oldAvatar, 'assets/user_images/') === 0 && is_file(__DIR__ . '/' . $oldAvatar)) {
                            @unlink(__DIR__ . '/' . $oldAvatar);
                        }
                    } else {
                        $errors[] = 'Errore upload immagine.';
                    }
                }
            }

            // Execute Updates if no errors
            if (!$errors) {
                $repo->updateProfile((int) $user['id'], $data);
                if ($updatePassword) {
                    $repo->updatePassword((int) $user['id'], $password);
                }

                $profile = $repo->findById((int) $user['id']);
                $messages[] = 'Profilo aggiornato con successo.';
            }
        }
    }
    redirect("profile.php");
}
require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <div class="form-card" style="max-width: 600px; margin: 0 auto;">
        <h1>Modifica profilo</h1>
        <?php if ($messages): ?>
            <div class="alert success">
                <?php foreach ($messages as $message): ?>
                    <p><?= e($message) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($errors): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="profile-edit" method="post" enctype="multipart/form-data">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="update">

            <!-- Clickable Avatar Upload -->
            <div class="avatar-upload-container">
                <label for="avatar" class="avatar-upload-label">
                    <?php
                    $avatarUrl = !empty($profile['avatar_path']) ? base_url($profile['avatar_path']) : base_url('assets/img/avatar-placeholder.svg');
                    ?>
                    <img id="avatar-preview" src="<?= e($avatarUrl) ?>" alt="Avatar">
                    <div class="avatar-overlay">
                        <span>Cambia foto</span>
                    </div>
                </label>
                <input type="file" id="avatar" name="avatar" accept="image/*" style="display: none;">
            </div>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($profile['username'] ?? '') ?>" required>

            <!-- Newsletter Toggle -->
            <div class="form-group-toggle">
                <label class="toggle-switch">
                    <input type="checkbox" name="newsletter_opt_in" <?= ($profile['newsletter_opt_in'] ?? 0) ? 'checked' : '' ?>>
                    <span class="slider round"></span>
                </label>
                <span class="toggle-label">Iscriviti alla newsletter</span>
            </div>

            <hr style="border: 1px solid var(--border); margin: 2rem 0; opacity: 0.5;">

            <h3 style="text-align:center; margin-bottom:1rem; color: var(--text-muted);">Cambia Password (Opzionale)
            </h3>

            <label for="password">Nuova password</label>
            <input type="password" id="password" name="password"
                placeholder="Lascia vuoto per mantenere la tua password">

            <label for="confirm_password">Conferma password</label>
            <input type="password" id="confirm_password" name="confirm_password">

            <div class="form-actions">
                <button class="button primary" type="submit">Salva tutto</button>
                <a class="button secondary" href="<?= e(base_url('profile.php')) ?>">Annulla</a>
            </div>
        </form>
    </div>
</section>
<script>
    (function () {
        var input = document.getElementById('avatar');
        var preview = document.getElementById('avatar-preview');
        if (!input || !preview) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var url = URL.createObjectURL(file);
            preview.src = url;
            preview.onload = function () {
                URL.revokeObjectURL(url);
            };
        });
    })();
</script>
<script>
    (function () {
        var input = document.getElementById('avatar');
        var preview = document.getElementById('avatar-preview');
        if (!input || !preview) return;
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var url = URL.createObjectURL(file);
            preview.src = url;
            preview.onload = function () {
                URL.revokeObjectURL(url);
            };
        });
    })();
</script>
<?php
require_once __DIR__ . '/includes/footer.php';
?>