<template>
  <div class="game-container">
    <div v-if="!gameStarted" class="start-screen">
      <h2>Simple Ball Game</h2>
      <p>Di chuyển: rê chuột để bay theo hướng con trỏ (desktop) hoặc chạm giữ rồi kéo như joystick ảo ở bất kỳ vị trí nào (mobile / touch).</p>
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
    }
  },
  mounted() {
    this.initializeGame();
  },
  beforeUnmount() {
    if (this.game) {
      this.game.destroy(true);
    }
  },
  methods: {
    initializeGame() {
      const self = this;

      // Define a custom scene class
      class GameScene extends Phaser.Scene {
        constructor() {
          super({ key: 'GameScene' });
        }

        preload() {
          // We don't need to load images for simple shapes
        }

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

          // Virtual joystick (touch / mouse hold if desired)
          this.joystick = {
            active: false,
            pointerId: null,
            origin: { x: 0, y: 0 },
            dir: { x: 0, y: 0 },
            dist: 0,
            base: this.add.circle(0, 0, 50, 0xffffff, 0.12).setVisible(false).setDepth(9999),
            knob: this.add.circle(0, 0, 25, 0xffffff, 0.35).setVisible(false).setDepth(10000),
            maxRadius: 50,
            deadZone: 8
          };

          // Mouse hover movement (no click)
          this.mouseMoveActive = false;
          this.mouseDir = { x: 0, y: 0 };
          this.mouseSpeed = 220;

          const updateMouseDirection = (pointer) => {
            const dx = pointer.worldX - this.player.x;
            const dy = pointer.worldY - this.player.y;
            const len = Math.sqrt(dx*dx + dy*dy);
            if (len > 4) {
              this.mouseDir.x = dx / len;
              this.mouseDir.y = dy / len;
              this.mouseMoveActive = true;
            } else {
              this.mouseMoveActive = false;
            }
          };

          const engageJoystick = (pointer) => {
            // Skip joystick for pure mouse hover usage unless button is pressed and we want joystick style; only use for touch
            if (pointer.pointerType === 'mouse' && !pointer.isDown) return;
            if (this.joystick.active && this.joystick.pointerId !== pointer.id) return;
            this.joystick.active = true;
            this.joystick.pointerId = pointer.id;
            this.joystick.origin.x = pointer.worldX;
            this.joystick.origin.y = pointer.worldY;
            this.joystick.base.setPosition(pointer.worldX, pointer.worldY).setVisible(true);
            this.joystick.knob.setPosition(pointer.worldX, pointer.worldY).setVisible(true);
            this.joystick.dir.x = 0; this.joystick.dir.y = 0; this.joystick.dist = 0;
            // Disable mouse movement while joystick active
            this.mouseMoveActive = false;
          };

          const moveJoystick = (pointer) => {
            if (!this.joystick.active || this.joystick.pointerId !== pointer.id) return;
            const dx = pointer.worldX - this.joystick.origin.x;
            const dy = pointer.worldY - this.joystick.origin.y;
            let dist = Math.sqrt(dx * dx + dy * dy);
            const maxR = this.joystick.maxRadius;
            let clampedX = dx;
            let clampedY = dy;
            if (dist > maxR) {
              clampedX = dx / dist * maxR;
              clampedY = dy / dist * maxR;
              dist = maxR;
            }
            this.joystick.knob.setPosition(this.joystick.origin.x + clampedX, this.joystick.origin.y + clampedY);
            this.joystick.dist = dist;
            if (dist > this.joystick.deadZone) {
              this.joystick.dir.x = clampedX / maxR;
              this.joystick.dir.y = clampedY / maxR;
            } else {
              this.joystick.dir.x = 0;
              this.joystick.dir.y = 0;
            }
          };

          const releaseJoystick = (pointer) => {
            if (this.joystick.pointerId !== pointer.id) return;
            this.joystick.active = false;
            this.joystick.pointerId = null;
            this.joystick.dir.x = 0; this.joystick.dir.y = 0; this.joystick.dist = 0;
            this.joystick.base.setVisible(false);
            this.joystick.knob.setVisible(false);
            this.player.body.setVelocity(0, 0);
          };

          // Input events
          this.input.on('pointerdown', (p) => {
            if (p.pointerType === 'mouse' && !p.isDown) return;
            engageJoystick(p);
          });

          this.input.on('pointermove', (p) => {
            if (this.joystick.active && p.id === this.joystick.pointerId) {
              moveJoystick(p);
            } else if (p.pointerType === 'mouse' && !p.isDown) {
              // Only if joystick not active
              if (!this.joystick.active) updateMouseDirection(p);
            }
          });

          this.input.on('pointerup', (p) => {
            releaseJoystick(p);
            if (p.pointerType === 'mouse') this.mouseMoveActive = false;
          });
          this.input.on('pointerupoutside', (p) => {
            releaseJoystick(p);
            if (p.pointerType === 'mouse') this.mouseMoveActive = false;
          });
          this.input.on('pointerout', (p) => {
            if (p.pointerType === 'mouse') this.mouseMoveActive = false;
          });

          this.physics.add.overlap(this.player, this.stars, this.collectStar, null, this);
          // Periodic star creation
          this.time.addEvent({ delay: 2000, callback: this.createStar, callbackScope: this, loop: true });

          // Handle resize
          this.scale.on('resize', (gameSize) => {
            this.physics.world.setBounds(0, 0, gameSize.width, gameSize.height);
            this.player.x = Phaser.Math.Clamp(this.player.x, 0 + this.player.radius, gameSize.width - this.player.radius);
            this.player.y = Phaser.Math.Clamp(this.player.y, 0 + this.player.radius, gameSize.height - this.player.radius);
            if (this.joystick.active) {
              this.joystick.base.setVisible(false);
              this.joystick.knob.setVisible(false);
              this.joystick.active = false;
              this.player.body.setVelocity(0, 0);
            }
            this.mouseMoveActive = false;
          });
        }
        update() {
          if (this.joystick && this.joystick.active) {
            const speed = 250;
            const ratio = Phaser.Math.Clamp(this.joystick.dist / this.joystick.maxRadius, 0, 1);
            this.player.body.setVelocity(this.joystick.dir.x * speed * ratio, this.joystick.dir.y * speed * ratio);
          } else if (this.mouseMoveActive) {
            this.player.body.setVelocity(this.mouseDir.x * this.mouseSpeed, this.mouseDir.y * this.mouseSpeed);
          } else {
            this.player.body.setVelocity(0, 0);
          }
        }

        createStar() {
          const margin = 50;
          const w = this.scale.width;
          const h = this.scale.height;
          const x = Phaser.Math.Between(margin, Math.max(margin, w - margin));
          const y = Phaser.Math.Between(margin, Math.max(margin, h - margin));
          const star = this.add.star(x, y, 5, 10, 20, 0xffff00);
          this.physics.add.existing(star);
          this.stars.add(star);

          // Make star disappear after 5 seconds if not collected
          this.time.delayedCall(5000, () => {
            if (star.active) {
              star.destroy();
            }
          });
        }

        collectStar(player, star) {
          star.destroy();
          this.vueComponent.score += 10;

          // Simple visual feedback - make player flash
          this.tweens.add({
            targets: this.player,
            alpha: 0.5,
            duration: 100,
            yoyo: true,
            repeat: 1
          });
        }
      }

      this.gameConfig = {
        type: Phaser.AUTO,
        width: 800,
        height: 600,
        parent: 'phaser-game',
        physics: {
          default: 'arcade',
          arcade: {
            gravity: { y: 0 },
            debug: false
          }
        },
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
    },
    endGame() {
      this.gameOver = true;
      this.gameStarted = false;
    }
  }
}
</script>

<style scoped>
.game-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  position: relative;
}

#phaser-game {
  border: 3px solid #fff;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.score-display {
  position: absolute;
  top: 20px;
  right: 20px;
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
</style>
