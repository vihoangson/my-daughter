<template>
  <div class="memory-game-container" :class="feedbackClass">
    <div v-if="!gameStarted" class="overlay-screen">
      <h2>Memory Match</h2>
      <p>Rèn luyện trí nhớ bằng cách ghép các thẻ bài giống nhau. Lật 2 thẻ: đúng +10 điểm, sai -2 điểm. Hoàn thành khi ghép hết các cặp hoặc hết giờ.</p>
      <div class="settings mb-3">
        <label class="form-label fw-bold d-block mb-2">Chọn độ khó:</label>
        <div class="btn-group">
          <button v-for="lvl in difficulties" :key="lvl.value" class="btn" :class="level === lvl.value ? 'btn-primary' : 'btn-outline-primary'" @click="level = lvl.value">{{ lvl.label }}</button>
        </div>
        <div class="mt-3">
          <label class="form-label mb-1">Thời lượng (giây)</label>
          <input type="number" min="30" max="300" step="15" class="form-control w-auto d-inline-block ms-2" v-model.number="duration" />
        </div>
      </div>
      <button @click="startGame" class="start-button">Bắt đầu chơi</button>
      <router-link to="/user-kid/game" class="btn btn-outline-light mt-3">Quay lại</router-link>
    </div>

    <div v-if="gameOver" class="overlay-screen">
      <h2>Kết thúc!</h2>
      <p>Điểm của bạn: {{ score }}</p>
      <p v-if="bestScore !== null">Điểm cao nhất của bạn: {{ bestScore }}</p>
      <p v-if="savingScore">Đang lưu điểm...</p>
      <p v-if="saveError" class="text-warning">{{ saveError }}</p>
      <p>Cặp đúng: {{ matchedPairs }}/{{ totalPairs }}</p>
      <p>Độ chính xác: {{ accuracy }}%</p>
      <button @click="startGame" class="restart-button">Chơi lại</button>
      <router-link to="/user-kid/game" class="btn btn-outline-light mt-3">Quay lại danh sách game</router-link>
    </div>

    <div v-if="gameStarted && !gameOver" class="hud">
      <div>Điểm: <strong>{{ score }}</strong></div>
      <div>Thời gian: <strong>{{ timeLeft }}</strong>s</div>
      <div>Độ khó: <strong class="text-capitalize">{{ level }}</strong></div>
      <div>Đúng / Tổng cặp: <strong>{{ matchedPairs }}/{{ totalPairs }}</strong></div>
      <div>Chính xác: <strong>{{ accuracy }}%</strong></div>
      <div>Lượt thử: <strong>{{ attempts }}</strong></div>
    </div>
    <div v-if="gameStarted && !gameOver" class="control-bar">
      <button class="btn btn-sm btn-warning me-2" @click="togglePause">
        <i :class="isPaused ? 'fas fa-play' : 'fas fa-pause'"></i>
        {{ isPaused ? 'Tiếp tục' : 'Tạm dừng' }}
      </button>
      <button class="btn btn-sm btn-info me-2" @click="replayGame">
        <i class="fas fa-undo"></i> Chơi lại
      </button>
      <button class="btn btn-sm btn-secondary" @click="endGame">Kết thúc</button>
    </div>

    <div v-if="gameStarted" class="board-wrapper" :class="[`pairs-${totalPairs}`]">
      <div class="card-grid" :class="{ paused: isPaused }">
        <div
          v-for="card in cards"
          :key="card.uid"
          class="memory-card"
          :class="{ revealed: card.flipped || card.matched, matched: card.matched }"
          @click="flipCard(card)"
        >
          <div class="card-face" :class="{ placeholder: !(card.flipped || card.matched) }">
            <span v-if="card.flipped || card.matched">{{ card.symbol }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'MemoryMatch',
  data() {
    return {
      level: 'easy',
      difficulties: [
        { value: 'easy', label: 'Dễ' },
        { value: 'middle', label: 'Trung bình' },
        { value: 'hard', label: 'Khó' }
      ],
      duration: 120,
      timeLeft: 120,
      timerHandle: null,
      gameStarted: false,
      gameOver: false,
      isPaused: false,
      lockBoard: false,
      firstCard: null,
      secondCard: null,
      cards: [],
      score: 0,
      attempts: 0,
      matchedPairs: 0,
      totalPairs: 0,
      savingScore: false,
      bestScore: null,
      saveError: null,
      feedbackState: '',
      feedbackTimer: null,
      symbolPool: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🐔','🐧','🐦','🐤','🐣','🐙','🐠','🐳','🦋','🐞','🌸','🍀','🍎','🍉','🍇','🍓','🥕','🌽','🍔','🍟','🍕','⚽','🏀','🚗','✈️','🚀','⭐','🌙','☀️']
    };
  },
  computed: {
    accuracy() {
      if (!this.attempts) return 0;
      return ((this.matchedPairs / this.attempts) * 100).toFixed(0);
    },
    feedbackClass() {
      return this.feedbackState ? `feedback-${this.feedbackState}` : '';
    }
  },
  beforeUnmount() {
    this.clearTimer();
  },
  methods: {
    startGame() {
      this.resetState();
      this.prepareDeck();
      this.gameStarted = true;
      this.gameOver = false;
      this.isPaused = false;
      this.timeLeft = this.duration;
      this.startTimer();
    },
    replayGame() { this.startGame(); },
    resetState() {
      this.score = 0;
      this.attempts = 0;
      this.matchedPairs = 0;
      this.cards = [];
      this.firstCard = null;
      this.secondCard = null;
      this.lockBoard = false;
      this.clearTimer();
    },
    pairsForLevel() {
      switch (this.level) {
        case 'easy': return 6;
        case 'middle': return 8;
        case 'hard': return 12;
      }
      return 6;
    },
    prepareDeck() {
      const pairs = this.pairsForLevel();
      this.totalPairs = pairs;
      const chosen = this.shuffle([...this.symbolPool]).slice(0, pairs);
      const deck = [];
      let uid = 0;
      chosen.forEach(sym => {
        deck.push({ uid: uid++, symbol: sym, matched: false, flipped: false, id: sym });
        deck.push({ uid: uid++, symbol: sym, matched: false, flipped: false, id: sym });
      });
      this.cards = this.shuffle(deck);
    },
    shuffle(arr) {
      for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
      }
      return arr;
    },
    flipCard(card) {
      if (this.lockBoard || this.isPaused || card.matched || card.flipped || this.gameOver) return;
      card.flipped = true;
      if (!this.firstCard) {
        this.firstCard = card;
        return;
      }
      this.secondCard = card;
      this.lockBoard = true;
      this.attempts++;
      if (this.firstCard.id === this.secondCard.id) {
        this.handleMatch();
      } else {
        this.handleMismatch();
      }
    },
    handleMatch() {
      this.firstCard.matched = true;
      this.secondCard.matched = true;
      this.score += 10;
      this.matchedPairs++;
      this.triggerFeedback('correct');
      this.resetTurn();
      if (this.matchedPairs === this.totalPairs) {
        this.endGame();
      }
    },
    handleMismatch() {
      this.score = Math.max(0, this.score - 2);
      setTimeout(() => {
        this.firstCard.flipped = false;
        this.secondCard.flipped = false;
        this.resetTurn();
      }, 800);
    },
    resetTurn() {
      this.firstCard = null;
      this.secondCard = null;
      this.lockBoard = false;
    },
    togglePause() {
      if (this.gameOver || !this.gameStarted) return;
      this.isPaused = !this.isPaused;
    },
    startTimer() {
      this.clearTimer();
      this.timerHandle = setInterval(() => {
        if (this.isPaused) return;
        this.timeLeft--;
        if (this.timeLeft <= 0) {
          this.endGame();
        }
      }, 1000);
    },
    clearTimer() {
      if (this.timerHandle) { clearInterval(this.timerHandle); this.timerHandle = null; }
    },
    endGame() {
      if (this.gameOver) return;
      this.gameOver = true;
      this.gameStarted = false;
      this.isPaused = false;
      this.clearTimer();
      this.persistScore();
    },
    async persistScore() {
      this.savingScore = true;
      this.saveError = null;
      try {
        const { data } = await axios.post('/api/kid/game-scores', { game_id: 'memory_match', score: this.score });
        this.bestScore = data.best_score;
      } catch (e) {
        console.error('Save score failed', e);
        this.saveError = 'Không lưu được điểm';
      } finally {
        this.savingScore = false;
      }
    },
    triggerFeedback(type) {
      if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
      this.feedbackState = type;
      this.feedbackTimer = setTimeout(() => { this.feedbackState = ''; }, 500);
    }
  }
};
</script>

<style scoped>
.memory-game-container {
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  min-height:100vh;
  width:100%;
  background:linear-gradient(135deg,#6a11cb,#2575fc);
  color:#fff;
  position:relative;
  overflow:hidden;
  padding:20px 10px;
}
.board-wrapper { width:100%; max-width:1024px; margin:80px auto 40px; }
.card-grid {
  display:grid;
  gap:12px;
  justify-content:center;
}
/* Responsive grid size based on pairs */
.pairs-6 .card-grid { grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); }
.pairs-8 .card-grid { grid-template-columns:repeat(auto-fill,minmax(135px,1fr)); }
.pairs-12 .card-grid { grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); }
.memory-card { width:100%; aspect-ratio:2/3; cursor:pointer; position:relative; }
.memory-card .card-face {
  display:flex;
  align-items:center;
  justify-content:center;
  width:100%;
  height:100%;
  border-radius:16px;
  font-size:3rem;
  font-weight:600;
  user-select:none;
  transition:background .35s, transform .35s, box-shadow .35s;
  background:linear-gradient(135deg,#283593,#1a237e);
  box-shadow:0 6px 14px rgba(0,0,0,0.25);
  border:2px solid #fff;
}
.memory-card .card-face.placeholder span { display:none; }
.memory-card:not(.revealed) .card-face {
  background:linear-gradient(135deg,#283593,#1a237e);
  position:relative;
}
.memory-card:not(.revealed) .card-face:before {
  content:' ';
  position:absolute;
  inset:6px;
  border:2px dashed rgba(255,255,255,0.4);
  border-radius:10px;
}
.memory-card.revealed .card-face {
  background:#ffffff;
  color:#222;
  transform:scale(1.02);
}
.memory-card.matched .card-face {
  background:#c8e6c9;
  color:#1b5e20;
  box-shadow:0 6px 18px rgba(46,125,50,0.5);
}
.hud {
  position:absolute;top:10px;left:50%;transform:translateX(-50%);
  display:flex;gap:16px;background:rgba(0,0,0,0.45);padding:8px 20px;border-radius:30px;font-size:14px;backdrop-filter:blur(4px);pointer-events:none;flex-wrap:wrap; }
.control-bar { position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:10px;background:rgba(0,0,0,0.45);padding:8px 16px;border-radius:40px;backdrop-filter:blur(4px); }
.overlay-screen { position:absolute;top:0;left:0;right:0;bottom:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,0.78);z-index:5;padding:30px 20px;text-align:center; }
.start-button,.restart-button { padding:14px 34px;font-size:18px;background:linear-gradient(45deg,#ff9800,#ff5722);color:#fff;border:none;border-radius:30px;cursor:pointer;box-shadow:0 5px 18px rgba(0,0,0,0.3);transition:.3s; }
.start-button:hover,.restart-button:hover { transform:translateY(-3px); }
.feedback-correct { animation: flashGreen 0.5s ease; }
@keyframes flashGreen { 0%{filter:none;}10%,90%{background:radial-gradient(circle at center,#1b5e20 0%,#0d3d12 60%) !important;}100%{background:linear-gradient(135deg,#6a11cb,#2575fc);} }
@media (max-width: 900px) {
  .pairs-6 .card-grid { grid-template-columns:repeat(auto-fill,minmax(130px,1fr)); }
  .pairs-8 .card-grid { grid-template-columns:repeat(auto-fill,minmax(115px,1fr)); }
  .pairs-12 .card-grid { grid-template-columns:repeat(auto-fill,minmax(95px,1fr)); }
  .memory-card .card-face { font-size:2.4rem; }
}
@media (max-width: 600px) {
  .pairs-6 .card-grid { grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); }
  .pairs-8 .card-grid { grid-template-columns:repeat(auto-fill,minmax(95px,1fr)); }
  .pairs-12 .card-grid { grid-template-columns:repeat(auto-fill,minmax(80px,1fr)); }
  .memory-card .card-face { font-size:2rem; }
}
.card-grid.paused { pointer-events:none; filter:grayscale(0.8) brightness(0.7); }
</style>
