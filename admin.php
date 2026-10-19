<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/lib/UserRepository.php';

require_admin();

$repo = new UserRepository(Database::connection());
$adminUser = current_user();
$messages = [];
$errors = [];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Sessione non valida.';
    } else {
        $action = $_POST['action'] ?? '';
        $targetId = (int) ($_POST['user_id'] ?? 0);
        if ($targetId > 0) {
            if ($adminUser && $targetId === (int) $adminUser['id']) {
                $errors[] = 'Non puoi eseguire azioni sul tuo account.';
            } else {
                if ($action === 'toggle_active') {
                    $active = (int) ($_POST['active'] ?? 0) === 1;
                    $repo->setActive($targetId, $active);
                    $messages[] = 'Stato utente aggiornato.';
                }
                if ($action === 'set_role') {
                    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
                    $repo->setRole($targetId, $role);
                    $messages[] = 'Ruolo aggiornato.';
                }
            }
        }
    }
}

$users = $repo->listAll();
if ($adminUser) {
    $filtered = [];
    foreach ($users as $u) {
        if ((int) $u['id'] === (int) $adminUser['id']) {
            continue;
        }
        $filtered[] = $u;
    }
    $users = $filtered;
}

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <h1>Area amministrativa</h1>
    <?php if ($messages): ?>
        <div class="alert">
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

    <table class="table">
        <thead>
            <tr>
                <th>Utente</th>
                <th>Email</th>
                <th>Score</th>
                <th>Ruolo</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['username']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e((string) $u['score']) ?></td>
                    <td><?= e($u['role']) ?></td>
                    <td><?= $u['active'] ? 'Attivo' : 'Disattivo' ?></td>
                    <td>
                        <form method="post" style="display:inline-block;">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="toggle_active">
                            <input type="hidden" name="user_id" value="<?= e((string) $u['id']) ?>">
                            <input type="hidden" name="active" value="<?= $u['active'] ? '0' : '1' ?>">
                            <button class="button secondary"
                                type="submit"><?= $u['active'] ? 'Disattiva' : 'Riattiva' ?></button>
                        </form>
                        <form method="post" style="display:inline-block;">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="set_role">
                            <input type="hidden" name="user_id" value="<?= e((string) $u['id']) ?>">
                            <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                            <button class="button secondary"
                                type="submit"><?= $u['role'] === 'admin' ? 'Rendi user' : 'Rendi admin' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>