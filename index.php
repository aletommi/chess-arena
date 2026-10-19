<?php
require_once __DIR__ . '/includes/bootstrap.php';


$user = current_user();


require_once __DIR__ . '/includes/header.php';
?>
<section class="hero container">
    <div class="hero-card">
        <h1>GameSAW Chess</h1>
        <p>Un'arena di scacchi singleplayer contro Stockfish, pensata per chi vuole allenarsi, salire in classifica e
            collezionare badge. Registrati, personalizza il tuo profilo e sfida l'AI.</p>
        <div class="hero-actions">
            <?php if ($user): ?>
                <a class="button primary" href="<?= e(base_url('game.php')) ?>">Gioca ora</a>
                <a class="button secondary" href="<?= e(base_url('profile.php')) ?>">Il mio profilo</a>
            <?php else: ?>
                <a class="button primary" href="<?= e(base_url('register.php')) ?>">Crea un account</a>
                <a class="button secondary" href="<?= e(base_url('login.php')) ?>">Accedi</a>
            <?php endif; ?>
        </div>
        <div class="hero-modes">
            <h3>Modalità di gioco</h3>
            <?php if ($user): ?>
                <div class="hero-modes-actions">
                    <a class="button secondary" href="<?= e(base_url('game.php?tc=60&inc=1')) ?>">1 min +1</a>
                    <a class="button secondary" href="<?= e(base_url('game.php?tc=180&inc=2')) ?>">3 min +2</a>
                    <a class="button secondary" href="<?= e(base_url('game.php?tc=600&inc=0')) ?>">10 min</a>
                </div>
            <?php else: ?>
                <p style="margin: 0; color: var(--text-muted);">Accedi per scegliere la modalità.</p>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="card">
            <h3>Stockfish integrato</h3>
            <p>Il motore degli scacchi gira in locale tramite Web Worker. Se il worker non e presente, la modalita di
                emergenza usa mosse casuali.</p>
        </div>
    </div>
</section>


<section class="section container">
    <h2 style="margin-top:0.83em;">Funzionalita principali</h2>
    <div class="card-grid">
        <div class="card">
            <h3>Profilo completo</h3>
            <p>Gestisci username, avatar, newsletter e password.</p>
        </div>
        <div class="card">
            <h3>Classifica</h3>
            <p>Scopri i migliori giocatori e l'andamento del punteggio con una vista dedicata.</p>
        </div>
        <div class="card">
            <h3>Badge e premi</h3>
            <p>Raggiungi le soglie di punteggio per ottenere badge esclusivi.</p>
        </div>
        <div class="card">
            <h3>Area admin</h3>
            <p>Gestisci utenti, ruoli e newsletter con strumenti essenziali.</p>
        </div>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
?>