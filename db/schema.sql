CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  score INT NOT NULL DEFAULT 1200,
  active TINYINT(1) NOT NULL DEFAULT 1,
  avatar_path VARCHAR(255) NULL,
  newsletter_opt_in TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  result ENUM('win','loss','draw') NOT NULL,
  moves TEXT NULL,
  score_delta INT NOT NULL DEFAULT 0,
  played_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_games_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description TEXT NOT NULL,
  icon VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_badges (
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, badge_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS newsletter_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_by INT NOT NULL,
  CONSTRAINT fk_newsletter_admin FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO badges (code, name, description, icon)
VALUES
  ('Loser', 'Loser', 'Lose one game against stockfish', 'assets/img/badge-bronze.svg'),
  ('Winner', 'Winner', 'Win one game against stockfish', 'assets/img/badge-gold.svg')
ON DUPLICATE KEY UPDATE
  name=VALUES(name), description=VALUES(description), icon=VALUES(icon);
