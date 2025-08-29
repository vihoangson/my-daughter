<template>
  <div class="game-container">
    <div v-if="!gameStarted" class="start-screen">
      <h2>Simple Ball Game</h2>
      <p>Di chuyển bóng bằng cách rê chuột (hover) hoặc chạm giữ rồi kéo (touch drag) để định hướng!</p>
      <button @click="startGame" class="start-button">Start Game</button>
    </div>
    <div v-if="gameOver" class="game-over">
      <h2>Game Over!</h2>
      <p>Your score: {{ score }}</p>
      <button @click="startGame" class="restart-button">Play Again</button>
    </div>
    <div id="phaser-game"></div>
    <div class="score-display" v-if="gameStarted && !gameOver">
      Score: {{ score }}
    </div>
  </div>
</template>

<script>
import Phaser from 'phaser';

export default {
  name: 'PuzzleChallenge',
  data() {
    return {
      game: null,
      score: 0,
      gameStarted: false,
      gameOver: false,
      gameConfig: null,
      resizeHandler: null,
    }
  },
  mounted() {
    this.initializeGame();
  },
  beforeUnmount() {
    if (this.game) {
      this.game.destroy(true);
    }
    if (this.resizeHandler) window.removeEventListener('resize', this.resizeHandler);
  },
  methods: {
    initializeGame() {
      const self = this;

      class GameScene extends Phaser.Scene {
        constructor() { super({ key: 'GameScene' }); }
        preload() {}
        create() {
          this.vueComponent = self;

          // Center based on current size
          const centerX = this.scale.width / 2;
          const centerY = this.scale.height / 2;
          this.player = this.add.circle(centerX, centerY, 20, 0x0099ff);
          this.physics.add.existing(this.player);
          // Set world bounds to current viewport size
          this.physics.world.setBounds(0, 0, this.scale.width, this.scale.height);
          this.player.body.setCollideWorldBounds(true);

          this.stars = this.physics.add.group();
          for (let i = 0; i < 5; i++) this.createStar();

          // Pointer state (existing logic kept)
          this.pointerActive = false;
          this.moveDir = { x: 0, y: 0 };
          const computeDirection = (pointer) => {
            const dx = pointer.worldX - this.player.x;
            const dy = pointer.worldY - this.player.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist > 6) { this.moveDir.x = dx / dist; this.moveDir.y = dy / dist; }
          };
          this.input.on('pointermove', (p) => { this.pointerActive = true; computeDirection(p); });
          this.input.on('pointerdown', (p) => { this.pointerActive = true; computeDirection(p); });
          this.input.on('pointerup', () => { this.pointerActive = false; this.player.body.setVelocity(0,0); this.moveDir.x = 0; this.moveDir.y = 0; });

          this.physics.add.overlap(this.player, this.stars, this.collectStar, null, this);
          // Periodic star creation
          this.time.addEvent({ delay: 2000, callback: this.createStar, callbackScope: this, loop: true });

          // Handle resize
          this.scale.on('resize', (gameSize) => {
            // Update world bounds to new size
            this.physics.world.setBounds(0, 0, gameSize.width, gameSize.height);
            // Clamp player inside new bounds
            this.player.x = Phaser.Math.Clamp(this.player.x, 0 + this.player.radius, gameSize.width - this.player.radius);
            this.player.y = Phaser.Math.Clamp(this.player.y, 0 + this.player.radius, gameSize.height - this.player.radius);
          });
        }
        update() {
          if (this.pointerActive) {
            const speed = 200;
            this.player.body.setVelocity(this.moveDir.x * speed, this.moveDir.y * speed);
          } else {
            this.player.body.setVelocity(0,0);
          }
        }
        createStar() {
          const w = this.scale.width;
          const h = this.scale.height;
          const margin = 50;
          const x = Phaser.Math.Between(margin, Math.max(margin, w - margin));
          const y = Phaser.Math.Between(margin, Math.max(margin, h - margin));
          const star = this.add.star(x, y, 5, 10, 20, 0xffff00);
          this.physics.add.existing(star);
          this.stars.add(star);
          this.time.delayedCall(5000, () => { if (star.active) star.destroy(); });
        }
        collectStar(player, star) {
          star.destroy();
          this.vueComponent.score += 10;
          this.tweens.add({ targets: this.player, alpha: 0.5, duration: 100, yoyo: true, repeat: 1 });
        }
      }

      this.gameConfig = {
        type: Phaser.AUTO,
        width: 800,
        height: 600,
        parent: 'phaser-game',
        backgroundColor: '#000000',
        // Manual control of resize; we will call game.scale.resize()
        scale: { mode: Phaser.Scale.NONE, autoCenter: Phaser.Scale.NO_CENTER },
        physics: { default: 'arcade', arcade: { gravity: { y: 0 }, debug: false } },
        scene: GameScene
      };
    },
    startGame() {
      this.score = 0;
      this.gameStarted = true;
      this.gameOver = false;

      if (this.game) {
        this.game.destroy(true);
      }

      this.game = new Phaser.Game(this.gameConfig);
      // Delay adjust to ensure canvas appended
      setTimeout(() => {
        this.adjustGameSize();
        // Attach resize listener once
        if (!this.resizeHandler) {
          this.resizeHandler = () => this.adjustGameSize();
          window.addEventListener('resize', this.resizeHandler);
        }
      }, 0);
    },
    adjustGameSize() {
      if (!this.game) return;
      const appEl = document.getElementById('app');
      if (!appEl) return;
      const aspectW = 800, aspectH = 600; // base ratio 4:3
      let maxWidth = appEl.clientWidth;
      if (maxWidth <= 0) return;
      let width = maxWidth;
      let height = Math.round(width * aspectH / aspectW);
      const viewportH = window.innerHeight;
      // If height exceeds viewport, reduce width to fit height
      if (height > viewportH) {
        height = viewportH;
        width = Math.round(height * aspectW / aspectH);
      }
      // Apply resize
      this.game.scale.resize(width, height);
      // Update physics world & clamp player
      const scene = this.game.scene.keys['GameScene'];
      if (scene) {
        if (scene.physics && scene.physics.world) {
          scene.physics.world.setBounds(0, 0, width, height);
        }
        if (scene.player) {
          scene.player.x = Phaser.Math.Clamp(scene.player.x, scene.player.radius, width - scene.player.radius);
          scene.player.y = Phaser.Math.Clamp(scene.player.y, scene.player.radius, height - scene.player.radius);
        }
      }
    }
  }
}
</script>

<style scoped>
.game-container { width:100%; height:auto; position:relative; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
#phaser-game { width:100%; } /* height controlled via canvas resize */
#phaser-game canvas { display:block; width:100% !important; height:auto !important; }

.score-display {
  position: absolute;
  top: 10px;
  right: 15px;
  background: rgba(255, 255, 255, 0.9);
  padding: 10px 20px;
  border-radius: 20px;
  font-size: 20px;
  font-weight: bold;
  color: #333;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.start-screen, .game-over {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.8);
  z-index: 10;
  color: white;
  text-align: center;
}

.start-screen h2, .game-over h2 {
  font-size: 3em;
  margin-bottom: 20px;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

.start-screen p {
  font-size: 1.2em;
  margin-bottom: 30px;
  opacity: 0.9;
}

.start-button, .restart-button {
  padding: 15px 30px;
  font-size: 18px;
  background: linear-gradient(45deg, #ff6b6b, #ee5a24);
  color: white;
  border: none;
  border-radius: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
}

.start-button:hover, .restart-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 7px 20px rgba(0, 0, 0, 0.4);
  background: linear-gradient(45deg, #ee5a24, #ff6b6b);
}

@media (max-width: 600px) {
  .score-display { left:50%; right:auto; transform:translateX(-50%); font-size:16px; padding:6px 12px; }
  .start-screen h2 { font-size: 2.2em; }
  .start-screen p { font-size: 1em; }
}
</style>
