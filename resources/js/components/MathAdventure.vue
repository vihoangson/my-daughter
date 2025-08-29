<template>
  <div class="game-container" :class="feedbackClass">
    <div v-if="!gameStarted" class="overlay-screen">
      <h2>Math Adventure</h2>
      <!-- Updated instruction text to reflect new ranges per difficulty -->
      <p>Phép tính cộng / trừ theo độ khó: Dễ (0–20), Trung bình (0–30), Khó (0–40). Chọn khối có phép tính đúng: đúng +2, sai -1.</p>

      <div class="settings mb-3">
        <label class="form-label fw-bold d-block mb-2">Chọn độ khó:</label>
        <div class="btn-group">
          <button
            v-for="lvl in difficulties"
            :key="lvl.value"
            class="btn"
            :class="level === lvl.value ? 'btn-primary' : 'btn-outline-primary'"
            @click="level = lvl.value"
          >{{ lvl.label }}</button>
        </div>
        <div class="mt-3">
          <label class="form-label mb-1">Thời lượng (giây)</label>
          <input type="number" min="30" max="300" step="15" class="form-control w-auto d-inline-block ms-2" v-model.number="duration" />
        </div>
        <div class="mt-3 text-start" style="max-width:420px;">
          <label class="form-label mb-1 d-flex justify-content-between">
            <span>Tốc độ rơi ({{ fallSpeed }} px/s)</span>
            <small class="text-muted">20 - 300</small>
          </label>
          <input type="range" min="20" max="300" step="10" v-model.number="fallSpeed" class="form-range" />
        </div>
      </div>

      <button @click="startGame" class="start-button">Bắt đầu chơi</button>
    </div>

    <div v-if="gameOver" class="overlay-screen">
      <h2>Hết giờ!</h2>
      <p>Điểm của bạn: {{ score }}</p>
      <p v-if="bestScore !== null">Điểm cao nhất của bạn: {{ bestScore }}</p>
      <p v-if="savingScore">Đang lưu điểm...</p>
      <p v-if="saveError" class="text-warning">{{ saveError }}</p>
      <p>Độ chính xác: {{ accuracy }}%</p>
      <button @click="startGame" class="restart-button">Chơi lại</button>
      <router-link to="/user-kid/game" class="btn btn-outline-light mt-3">Quay lại danh sách game</router-link>
    </div>

    <div id="math-adventure-game" ref="gameParent" class="game-stage"></div>

    <div v-if="gameStarted && !gameOver" class="hud">
      <div>Điểm: <strong>{{ score }}</strong></div>
      <div>Thời gian: <strong>{{ timeLeft }}</strong>s</div>
      <div>Độ khó: <strong class="text-capitalize">{{ level }}</strong></div>
      <div>Đúng/Sai: <strong>{{ correctClicks }}/{{ wrongClicks }}</strong></div>
      <div>Chính xác: <strong>{{ accuracy }}%</strong></div>
    </div>
    <div v-if="gameStarted && !gameOver" class="control-bar">
      <button class="btn btn-sm btn-warning me-2" @click="togglePause">
        <i :class="isPaused ? 'fas fa-play' : 'fas fa-pause'"></i>
        {{ isPaused ? 'Tiếp tục' : 'Tạm dừng' }}
      </button>
      <button class="btn btn-sm btn-info me-2" @click="replayGame">
        <i class="fas fa-undo"></i> Chơi lại
      </button>
      <button class="btn btn-sm btn-secondary" @click="endGame">Kết thúc
      </button>
    </div>
  </div>
</template>

<script>
import Phaser from 'phaser';
import axios from 'axios';

export default {
  name: 'MathAdventure',
  data() {
    return {
      game: null,
      score: 0,
      timeLeft: 60,
      duration: 60,
      level: 'easy',
      gameStarted: false,
      gameOver: false,
      correctClicks: 0,
      wrongClicks: 0,
      spawnTimer: null,
      fallSpeed: 120,
      isPaused: false,
      gameWidth: 800,
      gameHeight: 600,
      resizeObserver: null,
      difficulties: [
        { value: 'easy', valueInternal: 'easy', label: 'Dễ' },
        { value: 'middle', valueInternal: 'middle', label: 'Trung bình' },
        { value: 'hard', valueInternal: 'hard', label: 'Khó' }
      ],
      gameConfig: null,
      savingScore: false,
      bestScore: null,
      saveError: null,
      feedbackState: '', // '', 'correct', 'wrong'
      feedbackTimer: null,
    };
  },
  computed: {
    accuracy() {
      const total = this.correctClicks + this.wrongClicks;
      if (!total) return 0;
      return ((this.correctClicks / total) * 100).toFixed(0);
    },
    feedbackClass() {
      return this.feedbackState ? `feedback-${this.feedbackState}` : '';
    }
  },
  mounted() {
    this.calcGameSize();
    window.addEventListener('resize', this.handleResize, { passive: true });
    this.initConfig();
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize);
    this.destroyGame();
  },
  methods: {
    handleResize() {
      this.calcGameSize();
      if (this.game) {
        this.game.scale.resize(this.gameWidth, this.gameHeight);
      }
    },
    calcGameSize() {
      const vw = window.innerWidth;
      const vh = window.innerHeight;
      // Padding to keep HUD/buttons visible on very small screens
      const minW = 320; const minH = 420;
      // Prefer full width; cap to 1024 for performance
      this.gameWidth = Math.max(minW, Math.min(vw, 1024));
      this.gameHeight = Math.max(minH, vh); // use full viewport height
    },
    triggerFeedback(type) {
      if (this.feedbackTimer) clearTimeout(this.feedbackTimer);
      this.feedbackState = type; // 'correct' or 'wrong'
      this.feedbackTimer = setTimeout(() => { this.feedbackState = ''; }, 500);
    },
    initConfig() {
      const self = this;

      class MathScene extends Phaser.Scene {
        constructor() { super({ key: 'MathScene' }); }

        create() {
          this.vue = self;
          this.blocks = this.add.group();
          this.lastSpawn = 0;
          this.spawnInterval = self.getSpawnInterval();
          this.speed = self.getBlockSpeed();
        }

        update(time, delta) {
          if (this.vue.gameOver || this.vue.isPaused) return;
          // Spawn new block
          if (time - this.lastSpawn > this.spawnInterval) {
            this.lastSpawn = time;
            this.spawnBlock();
            // adaptive interval
            this.spawnInterval = self.getSpawnInterval();
            this.speed = self.getBlockSpeed();
          }
          const h = this.scale.height;

          // Move blocks
          this.blocks.getChildren().forEach(block => {
            block.y += this.speed * (delta / 1000);
            if (block.y - block.height/2 > h) {
              block.destroy();
            }
          });
        }

        spawnBlock() {
          const { expression, isCorrect } = self.generateExpression();
          const padding = 60;
          const w = this.scale.width;
          const x = Phaser.Math.Between(padding, Math.max(padding, w - padding));
          const color = self.getColor();
          const rect = this.add.rectangle(0, 0, 120, 50, color, 0.95).setStrokeStyle(2, 0xffffff, 0.8);
          const text = this.add.text(0, 0, expression, { fontSize: '18px', fontFamily: 'Arial', color: '#ffffff' }).setOrigin(0.5);
          const container = this.add.container(x, -30, [rect, text]);
          container.rect = rect;
          container.isCorrect = isCorrect;
          container.clicked = false;
          container.setSize(120, 50);
          // Simple interactive area based on setSize
          container.setInteractive({ useHandCursor: true });

          container.on('pointerdown', () => {
            if (this.vue.gameOver || container.clicked) return;
            container.clicked = true;
            if (container.isCorrect) {
              this.vue.correctClicks++;
              this.vue.score += 2;
              self.triggerFeedback('correct');
              // Turn block green and fade out after 500ms
              container.rect.setFillStyle(0x00c853, 0.95);
              this.tweens.add({ targets: container, scale: 1.05, yoyo: true, duration: 200 });
              this.time.delayedCall(500, () => {
                this.tweens.add({ targets: container, alpha: 0, duration: 250, onComplete: () => container.destroy() });
              });
            } else {
              this.vue.wrongClicks++;
              this.vue.score = Math.max(0, this.vue.score - 1);
              self.triggerFeedback('wrong');
              // Blink red for 0.5s then destroy
              const originalColor = container.rect.fillColor;
              let blink = true;
              const blinkEvent = this.time.addEvent({
                delay: 100,
                repeat: 4, // 5 toggles over ~500ms
                callback: () => {
                  container.rect.setFillStyle(blink ? 0xd50000 : 0x000000, blink ? 0.95 : 0.2);
                  blink = !blink;
                }
              });
              this.time.delayedCall(500, () => {
                blinkEvent.remove(false);
                container.rect.setFillStyle(originalColor, 0.95);
                this.tweens.add({ targets: container, alpha: 0, duration: 220, onComplete: () => container.destroy() });
              });
            }
          });

          this.blocks.add(container);
        }
      }

      this.gameConfig = {
        type: Phaser.AUTO,
        width: this.gameWidth,
        height: this.gameHeight,
        parent: 'math-adventure-game',
        backgroundColor: '#0d47a1',
        scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH },
        scene: MathScene
      };
    },
    startGame() {
      this.destroyGame();
      // ensure size up-to-date right before start
      this.calcGameSize();
      this.gameConfig.width = this.gameWidth;
      this.gameConfig.height = this.gameHeight;
      this.score = 0;
      this.correctClicks = 0;
      this.wrongClicks = 0;
      this.timeLeft = this.duration;
      this.gameOver = false;
      this.gameStarted = true;
      this.isPaused = false;

      this.game = new Phaser.Game(this.gameConfig);

      this.startTimer();
    },
    replayGame() {
      this.startGame();
    },
    togglePause() {
      if (!this.game || this.gameOver) return;
      this.isPaused = !this.isPaused;
      const scene = this.game.scene.getScene('MathScene');
      if (scene) {
        if (this.isPaused) scene.scene.pause(); else scene.scene.resume();
      }
    },
    startTimer() {
      const interval = setInterval(() => {
        if (!this.gameStarted || this.gameOver) { clearInterval(interval); return; }
        if (this.isPaused) return; // don't decrement while paused
        this.timeLeft--;
        if (this.timeLeft <= 0) {
          this.endGame();
          clearInterval(interval);
        }
      }, 1000);
    },
    endGame() {
      this.gameOver = true;
      this.gameStarted = false;
      this.isPaused = false;
      this.persistScore();
      this.destroyGame();
    },
    async persistScore() {
      this.savingScore = true;
      this.saveError = null;
      try {
        const { data } = await axios.post('/api/kid/game-scores', { game_id: 'math_adventure', score: this.score });
        this.bestScore = data.best_score;
      } catch (e) {
        console.error('Save score failed', e);
        this.saveError = 'Không lưu được điểm';
      } finally {
        this.savingScore = false;
      }
    },
    destroyGame() {
      if (this.game) {
        this.game.destroy(true);
        this.game = null;
      }
    },
    getSpawnInterval() {
      switch (this.level) {
        case 'easy': return Phaser.Math.Between(1200, 1600);
        case 'middle': return Phaser.Math.Between(800, 1200);
        case 'hard': return Phaser.Math.Between(500, 900);
      }
      return 1300;
    },
    getBlockSpeed() {
      return this.fallSpeed; // unchanged
    },
    getColor() {
      const palette = [0x1565c0, 0x2e7d32, 0xc62828, 0xf9a825];
      return palette[Phaser.Math.Between(0, palette.length - 1)];
    },
    generateExpression() {
      // Difficulty-based max range
      let max;
      if (this.level === 'easy') max = 20; else if (this.level === 'middle') max = 30; else max = 40;
      const operations = ['+','-'];
      const op = operations[Math.floor(Math.random()*operations.length)];
      let a, b;
      if (op === '+') {
        a = Phaser.Math.Between(0, max);
        b = Phaser.Math.Between(0, max - a); // ensures sum <= max
      } else { // '-'
        a = Phaser.Math.Between(0, max);
        b = Phaser.Math.Between(0, a); // ensures a - b >= 0
      }
      const correctResult = this.evalOp(a, b, op);
      const showCorrect = Math.random() < 0.5;
      let shownResult = correctResult;
      if (!showCorrect) {
        let candidate = correctResult;
        for (let i = 0; i < 20; i++) {
          const delta = Phaser.Math.Between(1, Math.min(10, max)) * (Math.random() < 0.5 ? -1 : 1);
          candidate = correctResult + delta;
          if (candidate >= 0 && candidate <= max && candidate !== correctResult) { shownResult = candidate; break; }
        }
        if (shownResult === correctResult) {
          if (correctResult < max) shownResult = correctResult + 1; else shownResult = correctResult - 1; // stay in range
        }
      }
      const expression = `${a} ${op} ${b} = ${shownResult}`;
      return { expression, isCorrect: showCorrect };
    },
    evalOp(a,b,op){
      switch(op){
        case '+': return a + b;
        case '-': return a - b;
        case '*': return a * b;
        case '/': return a / b; // integer guaranteed by construction
      }
      return 0;
    },
  }
};
</script>

<style scoped>
.game-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg,#1e3c72,#2a5298);
  position: relative;
  color: #fff;
  width: 100%;
  height: 100vh;
  overflow: hidden;
}
.game-stage {
  width: 100%;
  height: 100%;
  max-width: 1024px;
  flex: 1 1 auto;
}
#math-adventure-game canvas { width: 100% !important; height: 100% !important; }
.hud {
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 18px;
  background: rgba(0,0,0,0.45);
  padding: 8px 20px;
  border-radius: 30px;
  font-size: 14px;
  backdrop-filter: blur(4px);
  pointer-events: none;
}
.control-bar {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  z-index: 10;
  gap: 10px;
  background: rgba(0,0,0,0.45);
  padding: 8px 16px;
  border-radius: 40px;
  backdrop-filter: blur(4px);
}
.overlay-screen {
  position: absolute;
  top:0;left:0;right:0;bottom:0;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  background: rgba(0,0,0,0.78);
  z-index: 5;
  padding: 30px 20px;
  text-align: center;
}
.overlay-screen h2 { font-size: 3rem; margin-bottom: 15px; }
.overlay-screen p { max-width: 600px; }
.start-button, .restart-button {
  padding: 14px 34px;
  font-size: 18px;
  background: linear-gradient(45deg,#ff9800,#ff5722);
  color:#fff;
  border:none;
  border-radius: 30px;
  cursor:pointer;
  box-shadow: 0 5px 18px rgba(0,0,0,0.3);
  transition: .3s;
}
.start-button:hover, .restart-button:hover { transform: translateY(-3px); }
.settings label { color:#fff; }
.feedback-correct { animation: feedbackGreen 0.5s ease; }
.feedback-wrong { animation: feedbackRed 0.5s ease; }
@keyframes feedbackGreen {
  0% { filter: none; }
  10%,90% { background: radial-gradient(circle at center, #1b5e20 0%, #0d3d12 60%) !important; }
  100% { background: linear-gradient(135deg,#1e3c72,#2a5298); }
}
@keyframes feedbackRed {
  0% { background: linear-gradient(135deg,#1e3c72,#2a5298); }
  15% { background: #b71c1c; }
  30% { background: #7f0000; }
  45% { background: #b71c1c; }
  60% { background: #7f0000; }
  75% { background: #b71c1c; }
  100% { background: linear-gradient(135deg,#1e3c72,#2a5298); }
}
@media (max-width: 600px) {
  .hud {
    font-size: 11px;
    gap: 8px;
    padding: 6px 12px;
    top: 8px; /* back to top since control buttons moved bottom */
    left: 50%;
    transform: translateX(-50%);
    flex-wrap: wrap;
    max-width: 95%;
  }
  .control-bar {
    bottom: 6px;
    padding: 6px 14px;
    gap: 8px;
  }
  .control-bar .btn { font-size: 12px; padding: 4px 10px; }
}
</style>
