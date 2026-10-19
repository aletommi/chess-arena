# Chess Arena ♟️

A web platform to play chess against **Stockfish** right in the browser, with an Elo rating that adapts the engine's strength to the player.

Built as the final project for the *SAW – Web Application Development* course (Computer Science, University of Genoa). The interface is in Italian.

## Features

- **Play against Stockfish**: the engine runs client-side as WebAssembly in a Web Worker, so no server-side engine is needed.
- **Full chess rules**: castling, en passant, promotion, check, checkmate and stalemate, implemented from scratch in JavaScript.
- **Adaptive difficulty**: Stockfish plays at the player's own Elo (via `UCI_Elo`, between 1350 and 2850).
- **Elo rating system**: after each game the rating is updated with the standard Elo formula (K = 32).
- **Time controls**: 1+1, 3+2 and 10 minute games, with clocks and increment.
- **Leaderboard, profiles and badges**: ranking of all players, editable profiles with avatar upload, achievements.
- **Admin area**: enable/disable users, promote admins and send a newsletter to subscribed users (PHPMailer + SMTP).

## Security

- Passwords hashed with `password_hash()`
- Prepared statements (PDO) against SQL injection
- Output escaping against XSS
- CSRF tokens on every form
- Role-based access control (user / admin)
- Credentials read from environment variables, never committed

## Tech stack

**Backend:** PHP 8.2 · MySQL / MariaDB · PDO · PHPMailer (Composer)
**Frontend:** HTML · CSS · vanilla JavaScript · Stockfish (WebAssembly)

## Project structure

```
api/         JSON endpoint that records a finished game and updates the rating
assets/      CSS, game logic (chess-game.js), Stockfish adapter and engine
db/          database schema
includes/    bootstrap, configuration, database, auth, CSRF, helpers
lib/         services: users, games, badges, newsletter
*.php        pages (home, game, leaderboard, profile, admin, ...)
```

## Run locally

Requirements: PHP 8.2+, MySQL or MariaDB, Composer.

```bash
composer install -d includes
mysql -u root -e "CREATE DATABASE SAW"
mysql -u root SAW < db/schema.sql
php -S localhost:8000
```

Then open http://localhost:8000.

Configuration is read from environment variables (see `includes/config.php`): `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `BASE_PATH` (if the app is served from a subfolder) and `SMTP_*` for the newsletter.

## Credits

Chess engine: [Stockfish](https://stockfishchess.org/) (GPL-3.0), compiled to WebAssembly.
