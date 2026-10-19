<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/lib/UserRepository.php';

$errors = [];
$values = ['email' => '', 'first_name' => '', 'last_name' => ''];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Sessione non valida. Riprova.';
    } else {
        $values['email'] = trim($_POST['email'] ?? '');
        $values['username'] = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email non valida.';
        }
        if ($values['username'] === '') {
            $errors[] = 'Username obbligatorio.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $values['username'])) {
            $errors[] = 'L\'username deve contenere solo lettere, numeri e underscore (3-20 caratteri).';
        }

        if (strlen($password) < 6) {
            $errors[] = 'La password deve avere almeno 6 caratteri.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Le password non coincidono.';
        }

        $repo = new UserRepository(Database::connection());
        if (!$errors && $repo->findByEmail($values['email'])) {
            $errors[] = 'Email gia registrata.';
        }
        if (!$errors && $repo->findByUsername($values['username'])) {
            $errors[] = 'Username non disponibile.';
        }

        if (!$errors) {
            $userId = $repo->create($values['email'], $values['username'], $password);
            login_user($userId);
            redirect(base_url('profile_edit.php'));
        }
    }
}
?>
<section class="container">
    <div class="form-card">
        <h1>Registrazione</h1>
        <?php if ($errors): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form class="register" method="post">
            <?= csrf_input() ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($values['username'] ?? '') ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Conferma password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <div class="form-actions">
                <button class="button primary" type="submit">Crea account</button>
                <a class="button secondary" href="<?= e(base_url('login.php')) ?>">Hai gia un account?</a>
            </div>
        </form>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>