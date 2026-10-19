'use strict';

const files = 'abcdefgh';

class Piece {
  // Crea un nuovo pezzo con tipo e colore specificati
  constructor(type, color) {
    this.type = type;
    this.color = color;
  }

  // Crea una copia di questo pezzo
  clone() {
    return new Piece(this.type, this.color);
  }

  // Restituisce il simbolo del pezzo (maiuscolo per bianco, minuscolo per nero)
  symbol() {
    return this.color === 'w' ? this.type.toUpperCase() : this.type;
  }
}

class ChessBoard {
  // Inizializza una scacchiera vuota 8x8
  constructor() {
    this.grid = [];
    for (let r = 0; r < 8; r++) {
      let row = [];
      for (let c = 0; c < 8; c++) {
        row.push(null);
      }
      this.grid.push(row);
    }
  }

  // Posiziona i pezzi nella configurazione iniziale standard
  setup() {
    const back = ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r'];
    for (let c = 0; c < 8; c++) {
      this.grid[0][c] = new Piece(back[c], 'b');
      this.grid[1][c] = new Piece('p', 'b');
      this.grid[6][c] = new Piece('p', 'w');
      this.grid[7][c] = new Piece(back[c], 'w');
    }
  }

  // Crea una copia profonda della scacchiera corrente
  clone() {
    const copy = new ChessBoard();
    for (let r = 0; r < 8; r++) {
      for (let c = 0; c < 8; c++) {
        const piece = this.grid[r][c];
        copy.grid[r][c] = piece ? piece.clone() : null;
      }
    }
    return copy;
  }

  // Restituisce il pezzo alla riga e colonna specificate
  getPiece(row, col) {
    if (row < 0 || row > 7 || col < 0 || col > 7) {
      return null;
    }
    return this.grid[row][col];
  }

  // Imposta un pezzo in una specifica posizione della scacchiera
  setPiece(row, col, piece) {
    this.grid[row][col] = piece;
  }
}

// Converte la notazione della casa (es. "e4") in coordinate riga/colonna
function squareToCoords(square) {
  const file = square[0];
  const rank = parseInt(square[1], 10);
  const col = files.indexOf(file);
  const row = 8 - rank;
  return {
    row: row,
    col: col
  };
}

// Converte le coordinate riga/colonna in notazione della casa
function coordsToSquare(row, col) {
  return files[col] + String(8 - row);
}

class ChessGame {
  // Inizializza una nuova partita di scacchi
  constructor() {
    this.board = new ChessBoard();
    this.reset();
  }

  // Reimposta la partita allo stato iniziale
  reset() {
    this.board = new ChessBoard();
    this.board.setup();
    this.turn = 'w';
    this.fullmove = 1;
    this.history = [];
    this.moveLog = [];
    this.enPassant = null;
    this.castling = {
      w: {
        k: true,
        q: true
      },
      b: {
        k: true,
        q: true
      }
    };
  }

  // Ottiene il pezzo presente in una determinata casa
  getPieceAt(square) {
    const coords = squareToCoords(square);
    return this.board.getPiece(coords.row, coords.col);
  }



  // Verifica se le coordinate sono all'interno della scacchiera
  isInside(row, col) {
    return row >= 0 && row < 8 && col >= 0 && col < 8;
  }

  // Calcola le mosse legali possibili da una casa specifica
  getMovesFrom(square) {
    const piece = this.getPieceAt(square);
    if (!piece || piece.color !== this.turn) {
      return [];
    }
    const pseudo = this.getPseudoMoves(square, piece, false);
    const legal = [];
    for (let i = 0; i < pseudo.length; i++) {
      const move = pseudo[i];
      const testBoard = this.board.clone();

      if (move.isCastling) {
        if (this.isInCheckOnBoard(piece.color, this.board)) {
          continue;
        }
        const row = piece.color === 'w' ? 7 : 0;
        if (move.isCastling === 'k') {
          if (this.isSquareAttacked(coordsToSquare(row, 5), piece.color === 'w' ? 'b' : 'w', this.board)) {
            continue;
          }
        } else if (move.isCastling === 'q') {
          if (this.isSquareAttacked(coordsToSquare(row, 3), piece.color === 'w' ? 'b' : 'w', this.board)) {
            continue;
          }
        }
      }

      this.applyMoveToBoard(testBoard, move);
      if (!this.isInCheckOnBoard(piece.color, testBoard)) {
        legal.push(move);
      }
    }
    return legal;
  }

  // Ottiene tutte le mosse legali disponibili per un dato colore
  getLegalMoves(color) {
    const originalTurn = this.turn;
    this.turn = color;
    let moves = [];
    for (let r = 0; r < 8; r++) {
      for (let c = 0; c < 8; c++) {
        const piece = this.board.getPiece(r, c);
        if (piece && piece.color === color) {
          const square = coordsToSquare(r, c);
          const pieceMoves = this.getMovesFrom(square);
          moves = moves.concat(pieceMoves);
        }
      }
    }
    this.turn = originalTurn;
    return moves;
  }

  // Calcola le mosse pseudo-legali (senza controllare lo scacco)
  getPseudoMoves(square, piece, forAttack) {
    const coords = squareToCoords(square);
    let moves = [];
    let directions;

    if (piece.type === 'p') {
      const dir = piece.color === 'w' ? -1 : 1;
      const startRow = piece.color === 'w' ? 6 : 1;
      const oneRow = coords.row + dir;
      if (!forAttack && this.isInside(oneRow, coords.col) && !this.board.getPiece(oneRow, coords.col)) {
        moves.push(this.createMove(square, coordsToSquare(oneRow, coords.col), piece, null));
        const twoRow = coords.row + (dir * 2);
        if (coords.row === startRow && !this.board.getPiece(twoRow, coords.col)) {
          moves.push(this.createMove(square, coordsToSquare(twoRow, coords.col), piece, null));
        }
      }

      const captureCols = [coords.col - 1, coords.col + 1];
      for (let i = 0; i < captureCols.length; i++) {
        const captureCol = captureCols[i];
        if (this.isInside(oneRow, captureCol)) {
          const target = this.board.getPiece(oneRow, captureCol);
          if (forAttack) {
            moves.push(this.createMove(square, coordsToSquare(oneRow, captureCol), piece, target));
          } else if (target && target.color !== piece.color) {
            moves.push(this.createMove(square, coordsToSquare(oneRow, captureCol), piece, target));
          } else if (!target && this.enPassant && this.enPassant === coordsToSquare(oneRow, captureCol)) {
            const capturedPawn = this.board.getPiece(coords.row, captureCol);
            if (capturedPawn && capturedPawn.type === 'p' && capturedPawn.color !== piece.color) {
              const epMove = this.createMove(square, coordsToSquare(oneRow, captureCol), piece, capturedPawn);
              epMove.isEnPassant = true;
              moves.push(epMove);
            }
          }
        }
      }
    }

    if (piece.type === 'n') {
      directions = [
        [-2, -1],
        [-2, 1],
        [-1, -2],
        [-1, 2],
        [1, -2],
        [1, 2],
        [2, -1],
        [2, 1]
      ];
      moves = moves.concat(this.stepMoves(coords, piece, directions));
    }

    if (piece.type === 'b') {
      directions = [
        [-1, -1],
        [-1, 1],
        [1, -1],
        [1, 1]
      ];
      moves = moves.concat(this.slideMoves(coords, piece, directions));
    }

    if (piece.type === 'r') {
      directions = [
        [-1, 0],
        [1, 0],
        [0, -1],
        [0, 1]
      ];
      moves = moves.concat(this.slideMoves(coords, piece, directions));
    }

    if (piece.type === 'q') {
      directions = [
        [-1, -1],
        [-1, 1],
        [1, -1],
        [1, 1],
        [-1, 0],
        [1, 0],
        [0, -1],
        [0, 1]
      ];
      moves = moves.concat(this.slideMoves(coords, piece, directions));
    }

    if (piece.type === 'k') {
      directions = [
        [-1, -1],
        [-1, 0],
        [-1, 1],
        [0, -1],
        [0, 1],
        [1, -1],
        [1, 0],
        [1, 1]
      ];
      moves = moves.concat(this.stepMoves(coords, piece, directions));

      if (!forAttack && this.castling && this.castling[piece.color]) {
        const row = piece.color === 'w' ? 7 : 0;
        if (coords.row === row && coords.col === 4) {
          if (this.castling[piece.color].k) {
            if (!this.board.getPiece(row, 5) && !this.board.getPiece(row, 6)) {
              const rook = this.board.getPiece(row, 7);
              if (rook && rook.type === 'r' && rook.color === piece.color) {
                const move = this.createMove(square, coordsToSquare(row, 6), piece, null);
                move.isCastling = 'k';
                moves.push(move);
              }
            }
          }
          if (this.castling[piece.color].q) {
            if (!this.board.getPiece(row, 1) && !this.board.getPiece(row, 2) && !this.board.getPiece(row, 3)) {
              const rook = this.board.getPiece(row, 0);
              if (rook && rook.type === 'r' && rook.color === piece.color) {
                const move = this.createMove(square, coordsToSquare(row, 2), piece, null);
                move.isCastling = 'q';
                moves.push(move);
              }
            }
          }
        }
      }
    }

    for (let m = 0; m < moves.length; m++) {
      const move = moves[m];
      if (piece.type === 'p') {
        const toCoords = squareToCoords(move.to);
        if (piece.color === 'w' && toCoords.row === 0) {
          move.promotion = 'q';
        }
        if (piece.color === 'b' && toCoords.row === 7) {
          move.promotion = 'q';
        }
      }
      move.uci = move.from + move.to + (move.promotion ? move.promotion : '');
      move.notation = move.from + '-' + move.to;
    }

    return moves;
  }

  // Calcola le mosse per pezzi che si muovono a passi fissi (Re, Cavallo)
  stepMoves(coords, piece, directions) {
    let moves = [];
    for (let i = 0; i < directions.length; i++) {
      const dr = directions[i][0];
      const dc = directions[i][1];
      const r = coords.row + dr;
      const c = coords.col + dc;
      if (!this.isInside(r, c)) {
        continue;
      }
      const target = this.board.getPiece(r, c);
      if (!target || target.color !== piece.color) {
        moves.push(this.createMove(coordsToSquare(coords.row, coords.col), coordsToSquare(r, c), piece, target));
      }
    }
    return moves;
  }

  // Calcola le mosse per pezzi che scivolano (Alfiere, Torre, Regina)
  slideMoves(coords, piece, directions) {
    let moves = [];
    for (let i = 0; i < directions.length; i++) {
      const dr = directions[i][0];
      const dc = directions[i][1];
      let r = coords.row + dr;
      let c = coords.col + dc;
      while (this.isInside(r, c)) {
        const target = this.board.getPiece(r, c);
        if (!target) {
          moves.push(this.createMove(coordsToSquare(coords.row, coords.col), coordsToSquare(r, c), piece, null));
        } else {
          if (target.color !== piece.color) {
            moves.push(this.createMove(coordsToSquare(coords.row, coords.col), coordsToSquare(r, c), piece, target));
          }
          break;
        }
        r += dr;
        c += dc;
      }
    }
    return moves;
  }

  // Crea un oggetto mossa con i dettagli specificati
  createMove(from, to, piece, capture) {
    return {
      from: from,
      to: to,
      piece: piece,
      capture: capture || null,
      isEnPassant: false,
      promotion: null,
      uci: '',
      notation: ''
    };
  }

  // Esegue una mossa sulla scacchiera aggiornando lo stato
  applyMoveToBoard(board, move) {
    const from = squareToCoords(move.from);
    const to = squareToCoords(move.to);
    const piece = board.getPiece(from.row, from.col);
    if (!piece) {
      return;
    }
    let movedPiece = piece;
    if (move.promotion) {
      movedPiece = new Piece(move.promotion, piece.color);
    }
    board.setPiece(to.row, to.col, movedPiece);
    board.setPiece(from.row, from.col, null);

    if (move.isEnPassant) {
      const capturedRow = movedPiece.color === 'w' ? to.row + 1 : to.row - 1;
      if (this.isInside(capturedRow, to.col)) {
        board.setPiece(capturedRow, to.col, null);
      }
    }

    if (move.isCastling) {
      const row = movedPiece.color === 'w' ? 7 : 0;
      if (move.isCastling === 'k') {
        const rook = board.getPiece(row, 7);
        board.setPiece(row, 5, rook);
        board.setPiece(row, 7, null);
      } else if (move.isCastling === 'q') {
        const rook = board.getPiece(row, 0);
        board.setPiece(row, 3, rook);
        board.setPiece(row, 0, null);
      }
    }
  }

  // Effettua una mossa nella partita e aggiorna la cronologia
  makeMove(move) {
    this.enPassant = null;
    if (move.piece.type === 'p') {
      const fromCoords = squareToCoords(move.from);
      const toCoords = squareToCoords(move.to);
      if (Math.abs(toCoords.row - fromCoords.row) === 2) {
        const epRow = (fromCoords.row + toCoords.row) / 2;
        this.enPassant = coordsToSquare(epRow, fromCoords.col);
      }
    }

    if (this.castling) {
      if (move.piece.type === 'k') {
        this.castling[move.piece.color].k = false;
        this.castling[move.piece.color].q = false;
      }
      if (move.piece.type === 'r') {
        if (move.from.indexOf('a') === 0) this.castling[move.piece.color].q = false;
        if (move.from.indexOf('h') === 0) this.castling[move.piece.color].k = false;
      }
    }

    this.applyMoveToBoard(this.board, move);
    this.history.push(move);

    let notation = move.notation;
    if (move.isCastling === 'k') notation = 'O-O';
    if (move.isCastling === 'q') notation = 'O-O-O';

    this.moveLog.push(notation);
    if (this.turn === 'b') {
      this.fullmove += 1;
    }
    this.turn = this.turn === 'w' ? 'b' : 'w';
  }

  // Restituisce la cronologia delle mosse in formato UCI
  getUciHistory() {
    return this.history.map(move => move.uci);
  }

  // Trova la posizione del Re di un determinato colore
  findKing(color, board) {
    const usedBoard = board || this.board;
    for (let r = 0; r < 8; r++) {
      for (let c = 0; c < 8; c++) {
        const piece = usedBoard.getPiece(r, c);
        if (piece && piece.type === 'k' && piece.color === color) {
          return coordsToSquare(r, c);
        }
      }
    }
    return null;
  }

  // Verifica se una casella è sotto attacco da parte di un colore
  isSquareAttacked(square, byColor, board) {
    const usedBoard = board || this.board;
    const coords = squareToCoords(square);
    for (let r = 0; r < 8; r++) {
      for (let c = 0; c < 8; c++) {
        const piece = usedBoard.getPiece(r, c);
        if (!piece || piece.color !== byColor) {
          continue;
        }
        const from = coordsToSquare(r, c);
        const pseudo = this.getPseudoMovesOnBoard(from, piece, usedBoard);
        for (let i = 0; i < pseudo.length; i++) {
          if (pseudo[i].to === square) {
            return true;
          }
        }
      }
    }
    return false;
  }

  // Calcola mosse pseudo-legali su una specifica configurazione della scacchiera
  getPseudoMovesOnBoard(square, piece, board) {
    const original = this.board;
    this.board = board;
    const moves = this.getPseudoMoves(square, piece, true);
    this.board = original;
    return moves;
  }

  // Verifica se il Re di un dato colore è sotto scacco
  isInCheckOnBoard(color, board) {
    const kingSquare = this.findKing(color, board);
    if (!kingSquare) {
      return false;
    }
    const opponent = color === 'w' ? 'b' : 'w';
    return this.isSquareAttacked(kingSquare, opponent, board);
  }

  // Controlla se la partita è terminata (scacco matto o stallo)
  isGameOver() {
    const legal = this.getLegalMoves(this.turn);
    if (legal.length > 0) {
      return {
        over: false
      };
    }
    if (this.isInCheckOnBoard(this.turn, this.board)) {
      const winner = this.turn === 'w' ? 'b' : 'w';
      return {
        over: true,
        result: winner === 'w' ? 'win' : 'loss'
      };
    }
    return {
      over: true,
      result: 'draw'
    };
  }
}

class GameController {
  // Inizializza il controller del gioco con le opzioni fornite
  constructor(options) {
    this.boardElement = document.getElementById(options.boardId);
    this.statusElement = document.getElementById(options.statusId);
    this.logElement = document.getElementById(options.logId);
    this.capturedWhiteElement = document.getElementById(options.capturedWhiteId);
    this.capturedBlackElement = document.getElementById(options.capturedBlackId);
    this.engineStatusElement = document.getElementById(options.engineStatusId);
    this.clockWhiteElement = options.clockWhiteId ? document.getElementById(options.clockWhiteId) : null;
    this.clockBlackElement = options.clockBlackId ? document.getElementById(options.clockBlackId) : null;
    this.recordUrl = options.recordUrl;
    this.homeUrl = options.homeUrl || 'index.php';
    this.selectedSquare = null;
    this.legalTargets = [];
    this.game = new ChessGame();
    this.userElo = options.userElo || 1200;
    const enginePath = options.enginePath || 'assets/engine/stockfish.js';

    this.engine = new StockfishAdapter(enginePath);
    this.engine.onStatus = (status) => {
      this.updateEngineStatus(status);
      if (status === 'ready') {
        this.engine.setElo(this.userElo);
      }
    };
    this.engine.init();

    this.isLocked = true;
    this.playerColor = 'w';
    this.engineColor = 'b';

    this.timeControlMs = Math.max(30, (options.timeControlSeconds || 600)) * 1000;
    this.incrementMs = Math.max(0, (options.incrementSeconds || 0)) * 1000;
    this.whiteTimeMs = this.timeControlMs;
    this.blackTimeMs = this.timeControlMs;
    this.activeClockColor = null;
    this.clockInterval = null;
    this.lastTickAt = 0;
    this.renderClocks();

    this.bindEvents();
    this.showSideSelection();

    const newGameBtn = document.getElementById('new-game');
    if (newGameBtn) newGameBtn.style.display = 'none';

    this.render();
  }

  // Collega i gestori degli eventi agli elementi del DOM
  bindEvents() {
    this.boardElement.addEventListener('click', this.onBoardClick.bind(this));
    const newGameBtn = document.getElementById('new-game');
    if (newGameBtn) {
      newGameBtn.addEventListener('click', () => {
        this.reset();
      });
    }

    const sideBtns = document.querySelectorAll('.side-btn');
    sideBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const color = e.currentTarget.getAttribute('data-color');
        this.startGame(color);
      });
    });

    const playAgainBtn = document.getElementById('modal-play-again');
    if (playAgainBtn) {
      playAgainBtn.addEventListener('click', () => {
        this.hideModal();
        this.reset();
      });
    }

    const closeBtn = document.getElementById('modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', this.hideModal.bind(this));
    }
  }

  // Mostra il modale per la selezione del lato
  showSideSelection() {
    const modal = document.getElementById('side-selection-modal');
    if (modal) modal.style.display = 'flex';
  }

  // Nasconde il modale per la selezione del lato
  hideSideSelection() {
    const modal = document.getElementById('side-selection-modal');
    if (modal) modal.style.display = 'none';
  }

  // Avvia una nuova partita con la scelta del colore specificata
  startGame(choice) {
    this.hideSideSelection();
    this.game.reset();
    this.engine.newGame();
    this.updateEngineStatus(this.engine.status);

    if (choice === 'random') {
      choice = Math.random() < 0.5 ? 'w' : 'b';
    }

    this.playerColor = choice;
    this.engineColor = (choice === 'w' ? 'b' : 'w');

    if (this.playerColor === 'b') {
      this.boardElement.classList.add('flipped');
    } else {
      this.boardElement.classList.remove('flipped');
    }

    const firstLabel = document.querySelector('.first-label');
    const secondLabel = document.querySelector('.second-label');

    if (firstLabel && secondLabel) {
      if (this.playerColor === 'b') {
        firstLabel.textContent = 'Stockfish';
        secondLabel.textContent = 'Tu';
      } else {
        firstLabel.textContent = 'Tu';
        secondLabel.textContent = 'Stockfish';
      }
    }

    this.whiteTimeMs = this.timeControlMs;
    this.blackTimeMs = this.timeControlMs;
    this.renderClocks();

    this.isLocked = false;
    this.startClock('w');

    if (this.engineColor === 'w') {
      this.makeEngineMove();
    }

    this.render();
  }

  // Formatta i millisecondi in una stringa MM:SS
  formatClock(ms) {
    ms = Math.max(0, ms);
    const totalSeconds = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    const mm = minutes < 10 ? '0' + minutes : String(minutes);
    const ss = seconds < 10 ? '0' + seconds : String(seconds);
    return mm + ':' + ss;
  }

  // Aggiorna la visualizzazione degli orologi nell'interfaccia
  renderClocks() {
    if (this.clockWhiteElement) {
      this.clockWhiteElement.textContent = this.formatClock(this.whiteTimeMs);
    }
    if (this.clockBlackElement) {
      this.clockBlackElement.textContent = this.formatClock(this.blackTimeMs);
    }
  }

  // Ferma l'orologio della partita
  stopClock() {
    if (this.clockInterval) {
      clearInterval(this.clockInterval);
      this.clockInterval = null;
    }
    this.activeClockColor = null;
  }

  // Avvia l'orologio per il colore di chi è il turno
  startClock(color) {
    this.stopClock();
    this.activeClockColor = color;
    this.lastTickAt = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
    this.clockInterval = setInterval(this.tickClock.bind(this), 200);
  }

  // Gestisce il ticchettio dell'orologio e aggiorna il tempo residuo
  tickClock() {
    if (!this.activeClockColor) {
      return;
    }

    const now = (typeof performance !== 'undefined' && performance.now) ? performance.now() : Date.now();
    const delta = Math.max(0, now - this.lastTickAt);
    this.lastTickAt = now;

    if (this.activeClockColor === 'w') {
      this.whiteTimeMs -= delta;
      if (this.whiteTimeMs <= 0) {
        this.whiteTimeMs = 0;
        this.renderClocks();
        this.finishGame('loss');
        return;
      }
    } else {
      this.blackTimeMs -= delta;
      if (this.blackTimeMs <= 0) {
        this.blackTimeMs = 0;
        this.renderClocks();
        this.finishGame('win');
        return;
      }
    }

    this.renderClocks();
  }

  // Reimposta il gioco e mostra la selezione del lato
  reset() {
    this.stopClock();
    this.showSideSelection();
  }

  // Nasconde il modale di fine partita
  hideModal() {
    document.getElementById('game-over-modal').style.display = 'none';
  }

  // Gestisce i click sulla scacchiera per selezionare e muovere pezzi
  onBoardClick(event) {
    if (this.isLocked) {
      return;
    }
    if (this.game.turn !== this.playerColor) {
      return;
    }

    const squareEl = event.target.closest('.square');
    if (!squareEl) {
      return;
    }
    const square = squareEl.getAttribute('data-square');
    const piece = this.game.getPieceAt(square);

    if (this.selectedSquare && this.isLegalTarget(square)) {
      const move = this.legalTargets.find(m => m.to === square);
      this.executeMove(move);
      return;
    }

    if (piece && piece.color === this.game.turn) {
      this.selectedSquare = square;
      this.legalTargets = this.game.getMovesFrom(square);
      this.render();
    } else {
      this.selectedSquare = null;
      this.legalTargets = [];
      this.render();
    }
  }

  // Verifica se una casella è una destinazione valida per il pezzo selezionato
  isLegalTarget(square) {
    for (let i = 0; i < this.legalTargets.length; i++) {
      if (this.legalTargets[i].to === square) {
        return true;
      }
    }
    return false;
  }

  // Esegue la mossa selezionata dall'utente
  executeMove(move) {
    if (!move) return;
    this.stopClock();
    this.game.makeMove(move);

    if (move.piece.color === 'w') {
      this.whiteTimeMs += this.incrementMs;
    } else {
      this.blackTimeMs += this.incrementMs;
    }

    this.renderClocks();
    this.selectedSquare = null;
    this.legalTargets = [];
    this.render();

    const status = this.game.isGameOver();
    if (status.over) {
      this.finishGame(status.result);
      return;
    }
    this.startClock(this.game.turn);

    if (this.game.turn === this.engineColor) {
      this.makeEngineMove();
    }
  }

  // Calcola ed esegue la mossa del motore (Stockfish)
  makeEngineMove() {
    const legalMoves = this.game.getLegalMoves(this.engineColor);
    if (legalMoves.length === 0) {
      const status = this.game.isGameOver();
      if (status.over) this.finishGame(status.result);
      return;
    }

    this.statusElement.textContent = '';
    this.statusElement.style.display = 'none';

    this.isLocked = true;

    this.engine.getBestMove(this.game.getUciHistory(), legalMoves).then(uci => {
      let chosen = null;
      for (let i = 0; i < legalMoves.length; i++) {
        if (legalMoves[i].uci === uci) {
          chosen = legalMoves[i];
          break;
        }
      }
      if (!chosen) {
        chosen = legalMoves[0];
      }

      const delay = Math.floor(Math.random() * 2000) + 1000;

      setTimeout(() => {
        this.stopClock();
        this.game.makeMove(chosen);

        if (this.engineColor === 'w') this.whiteTimeMs += this.incrementMs;
        else this.blackTimeMs += this.incrementMs;

        this.renderClocks();
        this.isLocked = false;
        this.render();

        const status = this.game.isGameOver();
        if (status.over) {
          this.finishGame(status.result);
        } else {
          this.startClock(this.playerColor);
        }
      }, delay);

    });
  }

  // Termina la partita e gestisce il risultato
  finishGame(result) {
    this.stopClock();
    this.isLocked = true;

    const titleEl = document.getElementById('modal-title');
    const winnerEl = document.getElementById('modal-winner-text');
    const pointsEl = document.getElementById('modal-points-text');

    if (titleEl) titleEl.textContent = 'CALCOLO PUNTEGGIO...';
    if (winnerEl) {
      if (result === 'win') winnerEl.textContent = 'Vincitore: Tu';
      else if (result === 'loss') winnerEl.textContent = 'Vincitore: Stockfish';
      else winnerEl.textContent = 'Risultato: Patta';
    }

    const modal = document.getElementById('game-over-modal');
    if (modal) modal.style.display = 'flex';

    this.recordResult(result).then(data => {
      const scoreDelta = data.delta;
      if (titleEl) titleEl.textContent = 'PARTITA TERMINATA';

      if (pointsEl) {
        if (scoreDelta > 0) {
          pointsEl.textContent = 'Punti ELO: +' + scoreDelta;
        } else if (scoreDelta < 0) {
          pointsEl.textContent = 'Punti ELO: -' + Math.abs(scoreDelta);
        } else {
          pointsEl.textContent = 'Punti ELO: 0';
        }
      }

      if (data.badges && data.badges.length > 0) {
        data.badges.forEach(badge => {
          this.showBadgeNotification(badge);
        });
      }

    }).catch(err => {
      console.error(err);
      if (pointsEl) {
        pointsEl.textContent = 'Errore nel salvataggio. Ricarica la pagina.';
      }
    });
  }

  // Aggiorna lo stato visivo del motore nell'interfaccia
  updateEngineStatus(status) {
    if (!this.engineStatusElement) {
      return;
    }
    if (status === 'ready') {
      this.engineStatusElement.textContent = 'Motore: Stockfish attivo';
      return;
    }
    if (status === 'fallback') {
      this.engineStatusElement.textContent = 'Motore: mosse casuali (Stockfish non disponibile)';
      return;
    }
    this.engineStatusElement.textContent = 'Motore: in caricamento...';
  }

  // Registra il risultato della partita sul server
  recordResult(result) {
    if (!this.recordUrl) {
      return Promise.resolve({
        delta: 0
      });
    }
    return fetch(this.recordUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        result: result,
        moves: this.game.moveLog.join(' ')
      })
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        if (data && data.message) {
          this.statusElement.textContent = data.message;
        }
        return data || {
          delta: 0
        };
      })
      .catch(err => {
        this.statusElement.textContent = 'Partita salvata localmente. Controlla la connessione.';
        throw err;
      });
  }

  // Mostra una notifica per un nuovo badge ottenuto
  showBadgeNotification(badge) {
    let container = document.getElementById('badge-notification-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'badge-notification-container';
      document.body.appendChild(container);
    }

    const el = document.createElement('div');
    el.className = 'badge-notification';
    el.innerHTML =
      '<img src="' + badge.icon + '" alt="' + badge.name + '">' +
      '<div class="badge-notification-content">' +
      '<h4>Nuovo Badge: ' + badge.name + '</h4>' +
      '<p>' + badge.description + '</p>' +
      '</div>' +
      '<span class="badge-notification-close">&times;</span>';

    container.appendChild(el);

    let hasClosed = false;
    const close = () => {
      if (hasClosed) return;
      hasClosed = true;
      el.classList.add('closing');
      setTimeout(() => {
        if (el.parentNode) el.parentNode.removeChild(el);
      }, 500);
    };

    el.querySelector('.badge-notification-close').addEventListener('click', close);
    setTimeout(close, 5000);
  }

  // Aggiorna il rendering della scacchiera e dell'interfaccia utente
  render() {
    if (!this.boardElement) {
      return;
    }

    if (this.boardElement.children.length === 0) {
      for (let r = 0; r < 8; r++) {
        for (let c = 0; c < 8; c++) {
          const square = document.createElement('div');
          square.className = 'square ' + (((r + c) % 2 === 0) ? 'light' : 'dark');
          square.setAttribute('data-square', coordsToSquare(r, c));
          this.boardElement.appendChild(square);
        }
      }
    }

    const getPieceUrl = (color, type) => {
      return 'https://images.chesscomfiles.com/chess-themes/pieces/neo/150/' + color + type + '.png';
    };

    const squares = this.boardElement.querySelectorAll('.square');
    squares.forEach(squareEl => {
      const square = squareEl.getAttribute('data-square');
      const coords = squareToCoords(square);
      const piece = this.game.board.getPiece(coords.row, coords.col);

      squareEl.innerHTML = '';

      if (piece) {
        const img = document.createElement('img');
        img.src = getPieceUrl(piece.color, piece.type);
        img.draggable = false;
        img.alt = piece.color + piece.type;
        squareEl.appendChild(img);
      }

      squareEl.classList.toggle('selected', this.selectedSquare === square);
      const isTarget = this.isLegalTarget(square);
      squareEl.classList.toggle('move-target', isTarget);

      squareEl.classList.remove('last-move');
      const history = this.game.history;
      if (history.length > 0) {
        const lastMove = history[history.length - 1];
        if (lastMove.from === square || lastMove.to === square) {
          squareEl.classList.add('last-move');
        }
      }

    });

    this.logElement.innerHTML = '';

    const moves = this.game.moveLog;
    for (let i = 0; i < moves.length; i += 2) {
      const row = document.createElement('div');
      const num = (i / 2) + 1;

      const numSpan = document.createElement('span');
      numSpan.className = 'move-number';
      numSpan.textContent = num + '.';

      const wMove = document.createElement('span');
      wMove.className = 'move-white';
      wMove.textContent = moves[i];

      let bMove = null;
      if (i + 1 < moves.length) {
        bMove = document.createElement('span');
        bMove.className = 'move-black';
        bMove.textContent = moves[i + 1];
      }

      row.appendChild(numSpan);
      row.appendChild(wMove);
      if (bMove) row.appendChild(bMove);

      this.logElement.appendChild(row);
    }

    this.logElement.scrollTop = this.logElement.scrollHeight;

    if (this.capturedWhiteElement && this.capturedBlackElement) {
      this.capturedWhiteElement.innerHTML = '';
      this.capturedBlackElement.innerHTML = '';

      const capturedByWhite = [];
      const capturedByBlack = [];

      const history = this.game.history;
      for (let i = 0; i < history.length; i++) {
        const move = history[i];
        if (move.capture) {
          if (move.capture.color === 'b') {
            capturedByWhite.push(move.capture);
          } else {
            capturedByBlack.push(move.capture);
          }
        }
      }

      const renderList = (container, pieces) => {
        pieces.forEach(p => {
          const img = document.createElement('img');
          img.src = getPieceUrl(p.color, p.type);
          img.alt = p.color + p.type;
          container.appendChild(img);
        });
      };

      renderList(this.capturedWhiteElement, capturedByWhite);
      renderList(this.capturedBlackElement, capturedByBlack);
    }
  }
}
