'use strict';

class StockfishAdapter {
  // Inizializza l'adapter con il percorso del worker di Stockfish
  constructor(workerPath) {
    this.workerPath = workerPath;
    this.engine = null;
    this.ready = false;
    this.pending = null;
    this.status = 'idle';
    this.onStatus = null;
  }

  // Avvia il motore Stockfish e imposta i listener per i messaggi
  init() {

    try {
      this.engine = typeof STOCKFISH === 'function' ? STOCKFISH() : new Worker(this.workerPath);
    } catch (err) {
      this.engine = null;
      this.setStatus('fallback');
      return;
    }

    if (!this.engine) {
      this.setStatus('fallback');
      return;
    }

    if (this.engine instanceof Worker) {
      this.engine.onerror = () => {
        if (this.engine) {
          this.engine.terminate();
        }
        this.engine = null;
        this.ready = false;
        this.setStatus('fallback');
      };
    }

    this.engine.onmessage = (event) => {
      let line;
      if (event && typeof event === 'object' && typeof event.data !== 'undefined') {
        line = event.data;
      } else {
        line = event;
      }
      line = String(line || '');
      if (line.indexOf('stockfish-failed') !== -1) {
        if (this.engine && this.engine instanceof Worker) {
          this.engine.terminate();
        }
        this.engine = null;
        this.ready = false;
        this.setStatus('fallback');
        return;
      }
      if (line === 'uciok') {
        this.setStatus('loading');
        this.send('isready');
        return;
      }
      if (line === 'readyok') {
        this.ready = true;
        this.setStatus('ready');
        return;
      }
      if (line.indexOf('bestmove') === 0 && this.pending) {
        const parts = line.split(' ');
        const move = parts[1];
        this.pending.resolve(move);
        this.pending = null;
      }
    };

    this.ready = false;
    this.setStatus('loading');
    this.send('uci');
  }

  // Prepara il motore per una nuova partita
  newGame() {
    if (!this.engine) {
      this.setStatus('fallback');
      return;
    }
    this.ready = false;
    this.setStatus('loading');
    this.send('ucinewgame');
    this.send('isready');
  }

  // Imposta la forza di gioco di Stockfish in base all'ELO dell'utente
  setElo(elo) {
    if (!this.engine) return;
    // Map user ELO to engine ELO. Stockfish UCI_Elo ranges often from 1350 to 2850.
    // If user is < 1350, we set to min.
    const engineElo = Math.max(1350, Math.min(2850, elo));
    this.send('setoption name UCI_LimitStrength value true');
    this.send('setoption name UCI_Elo value ' + engineElo);
  }

  // Richiede al motore la mossa migliore per la posizione corrente
  getBestMove(uciMoves, legalMoves) {
    if (!this.engine || !this.ready) {
      this.setStatus('fallback');
      return Promise.resolve(this.pickRandom(legalMoves));
    }

    if (this.pending) {
      return Promise.resolve(this.pickRandom(legalMoves));
    }

    return new Promise((resolve, reject) => {
      this.pending = {
        resolve: resolve,
        reject: reject
      };
      const movesString = Array.isArray(uciMoves) && uciMoves.length ? ' ' + uciMoves.join(' ') : '';
      this.send('position startpos moves' + movesString);
      this.send('go depth 12');
    });
  }

  // Invia un comando UCI al motore Stockfish
  send(command) {
    if (!this.engine || typeof this.engine.postMessage !== 'function') {
      return;
    }
    this.engine.postMessage(command);
  }

  // Aggiorna lo stato interno dell'adapter e notifica i listener
  setStatus(status) {
    if (this.status === status) {
      return;
    }
    this.status = status;
    if (typeof this.onStatus === 'function') {
      this.onStatus(status);
    }
  }

  // Seleziona una mossa casuale tra quelle legali (fallback)
  pickRandom(legalMoves) {
    if (!legalMoves || legalMoves.length === 0) {
      return null;
    }
    const index = Math.floor(Math.random() * legalMoves.length);
    return legalMoves[index].uci;
  }
}
