<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/lib/UserRepository.php';

$errors = [];
$email = '';

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Sessione non valida. Riprova.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $repo = new UserRepository(Database::connection());
        $user = $repo->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Credenziali non valide.';
        } elseif ((int) $user['active'] !== 1) {
            $errors[] = 'Account disattivato. Contatta l amministratore.';
        } else {
            login_user((int) $user['id']);
            $pdo = Database::connection();
            $stmt = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
            $stmt->execute(['id' => $user['id']]);
            redirect(base_url('index.php'));
        }
    }
}
?>
<section class="container">
    <div class="form-car">
        <h1>Login</h1>
        <?php if ($errors): ?>
            <div class="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form class="login" method="post">
            <?= csrf_input() ?>
            <div class="form-email">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($email) ?>" required>
            </div>
            <div class="form-password">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-actions">
                <button class="button primary" type="submit">Accedi</button>
                <a class="button secondary" href="<?= e(base_url('register.php')) ?>">Crea un account</a>
            </div>
        </form>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>