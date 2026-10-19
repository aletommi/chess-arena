<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/lib/NewsletterService.php';

require_admin();

$user = current_user();
$service = new NewsletterService(Database::connection());
$messages = [];
$errors = [];

if (is_post()) {
    if (!csrf_verify()) {
        $errors[] = 'Sessione non valida.';
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $body = trim($_POST['body'] ?? '');
        if ($subject === '' || $body === '') {
            $errors[] = 'Oggetto e testo sono obbligatori.';
        } else {
            $sent = $service->send((int)$user['id'], $subject, $body);
            $messages[] = 'Newsletter inviata. Email consegnate: ' . $sent;
        }
    }
}

$history = $service->listMessages();

require_once __DIR__ . '/includes/header.php';
?>
<section class="container">
    <h1>Newsletter</h1>
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
    <div class="form-card">
        <h2>Nuovo invio</h2>
        <form method="post">
            <?= csrf_input() ?>
            <label for="subject">Oggetto</label>
            <input type="text" id="subject" name="subject" required>

            <label for="body">Messaggio</label>
            <textarea id="body" name="body" required></textarea>

            <div class="form-actions">
                <button class="button primary" type="submit">Invia newsletter</button>
            </div>
        </form>
    </div>
</section>

<section class="section container">
    <h2>Storico invii</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Oggetto</th>
                <th>Admin</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$history): ?>
                <tr>
                    <td colspan="3">Nessun invio effettuato.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($history as $row): ?>
                    <tr>
                        <td><?= e($row['sent_at']) ?></td>
                        <td><?= e($row['subject']) ?></td>
                        <td><?= e($row['admin_email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>
