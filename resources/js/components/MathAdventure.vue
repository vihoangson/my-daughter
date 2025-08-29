<template>
  <div class="game-container">
    <div v-if="!gameStarted" class="overlay-screen">
      <h2>Math Adventure</h2>
      <p>Trò chơi giúp trẻ em học toán (cộng / trừ lớp 3) trong phạm vi 0–100. Chọn các khối có phép tính đúng để ghi điểm! Đúng +2 điểm, sai -1 điểm. Không có kết quả âm.</p>

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
      </div>

      <button @click="startGame" class="start-button">Bắt đầu chơi</button>
    </div>

    <div v-if="gameOver" class="overlay-screen">
      <h2>Hết giờ!</h2>
      <p>Điểm của bạn: {{ score }}</p>
      <p>Độ chính xác: {{ accuracy }}%</p>
      <button @click="startGame" class="restart-button">Chơi lại</button>
      <router-link to="/user-kid/game" class="btn btn-outline-light mt-3">Quay lại danh sách game</router-link>
    </div>

    <div id="math-adventure-game"></div>

    <div v-if="gameStarted && !gameOver" class="hud">
      <div>Điểm: <strong>{{ score }}</strong></div>
      <div>Thời gian: <strong>{{ timeLeft }}</strong>s</div>
      <div>Độ khó: <strong class="text-capitalize">{{ level }}</strong></div>
      <div>Đúng/Sai: <strong>{{ correctClicks }}/{{ wrongClicks }}</strong></div>
      <div>Chính xác: <strong>{{ accuracy }}%</strong></div>
    </div>
  </div>
</template>

<script>
import Phaser from 'phaser';

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
      difficulties: [
        { value: 'easy', label: 'Dễ' },
        { value: 'medium', label: 'Trung bình' },
        { value: 'hard', label: 'Khó' }
      ],
      gameConfig: null
    };
  },
  computed: {
    accuracy() {
      const total = this.correctClicks + this.wrongClicks;
      if (!total) return 0;
      return ((this.correctClicks / total) * 100).toFixed(0);
    }
  },
  mounted() {
    this.initConfig();
  },
  beforeUnmount() {
    this.destroyGame();
  },
  methods: {
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
          if (this.vue.gameOver) return;
          // Spawn new block
          if (time - this.lastSpawn > this.spawnInterval) {
            this.lastSpawn = time;
            this.spawnBlock();
            // adaptive interval
            this.spawnInterval = self.getSpawnInterval();
            this.speed = self.getBlockSpeed();
          }
          // Move blocks
          this.blocks.getChildren().forEach(block => {
            block.y += this.speed * (delta / 1000);
            if (block.y - block.height/2 > 600) {
              block.destroy();
            }
          });
        }

        spawnBlock() {
          const { expression, isCorrect } = self.generateExpression();
          const x = Phaser.Math.Between(60, 740);
          const color = self.getColor();

          // Base rectangle + text
          const rect = this.add.rectangle(0, 0, 120, 50, color, 0.95).setStrokeStyle(2, 0xffffff, 0.8);
          const text = this.add.text(0, 0, expression, { fontSize: '18px', fontFamily: 'Arial', color: '#ffffff' }).setOrigin(0.5);
          const container = this.add.container(x, -30, [rect, text]);
          container.isCorrect = isCorrect;
          container.clicked = false;
          container.setSize(120, 50);
          // Simple interactive area based on setSize
          container.setInteractive();

          container.on('pointerdown', () => {
            if (this.vue.gameOver || container.clicked) return;
            container.clicked = true;
            if (container.isCorrect) {
              this.vue.correctClicks++;
              this.vue.score += 2;
              self.flashContainer(container, 0x00c853);
            } else {
              this.vue.wrongClicks++;
              this.vue.score = Math.max(0, this.vue.score - 1);
              self.flashContainer(container, 0xd50000);
            }
            this.tweens.add({ targets: container, alpha: 0, duration: 300, onComplete: () => container.destroy() });
          });

          this.blocks.add(container);
        }
      }

      this.gameConfig = {
        type: Phaser.AUTO,
        width: 800,
        height: 600,
        parent: 'math-adventure-game',
        backgroundColor: '#0d47a1',
        scene: MathScene
      };
    },
    startGame() {
      this.destroyGame();
      this.score = 0;
      this.correctClicks = 0;
      this.wrongClicks = 0;
      this.timeLeft = this.duration;
      this.gameOver = false;
      this.gameStarted = true;

      this.game = new Phaser.Game(this.gameConfig);

      this.startTimer();
    },
    startTimer() {
      const interval = setInterval(() => {
        if (!this.gameStarted || this.gameOver) { clearInterval(interval); return; }
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
      this.destroyGame();
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
        case 'medium': return Phaser.Math.Between(800, 1200);
        case 'hard': return Phaser.Math.Between(500, 900);
      }
      return 1300;
    },
    getBlockSpeed() {
      switch (this.level) {
        case 'easy': return 90;
        case 'medium': return 140;
        case 'hard': return 200;
      }
      return 100;
    },
    getColor() {
      const palette = [0x1565c0, 0x2e7d32, 0xc62828, 0xf9a825];
      return palette[Phaser.Math.Between(0, palette.length - 1)];
    },
    generateExpression() {
      // Grade 3 level: only addition & subtraction, numbers 0-100, no negative results, final result 0-100
      const operations = ['+','-'];
      const op = operations[Math.floor(Math.random()*operations.length)];
      let a, b;
      if (op === '+') {
        a = Phaser.Math.Between(0, 100);
        b = Phaser.Math.Between(0, 100 - a); // ensures sum <= 100
      } else { // '-'
        a = Phaser.Math.Between(0, 100);
        b = Phaser.Math.Between(0, a); // ensures a - b >= 0
      }
      const correctResult = this.evalOp(a, b, op);
      const showCorrect = Math.random() < 0.5;

      let shownResult = correctResult;
      if (!showCorrect) {
        // Try to find a different result within [0,100]
        let candidate = correctResult;
        for (let i = 0; i < 20; i++) {
          const delta = Phaser.Math.Between(1, 15) * (Math.random() < 0.5 ? -1 : 1);
          candidate = correctResult + delta;
          if (candidate >= 0 && candidate <= 100 && candidate !== correctResult) {
            shownResult = candidate;
            break;
          }
        }
        if (shownResult === correctResult) {
          // Fallback adjust by +1 or -1 staying inside range
            if (correctResult < 100) shownResult = correctResult + 1; else shownResult = correctResult - 1;
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
    flashContainer(container, color) {
      const scene = container.scene;
      // overlay rectangle
      const flash = scene.add.rectangle(container.x, container.y, 120, 50, color, 0.4).setDepth(10);
      scene.time.delayedCall(150, () => flash.destroy());
    }
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
}
#math-adventure-game {
  border: 3px solid #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.35);
}
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
</style>
