<template>
  <div class="game-container">
    <div v-if="!gameStarted" class="start-screen">
      <h2>Simple Ball Game</h2>
      <p>Use arrow keys to move the ball and collect stars!</p>
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

          // Create player (blue circle)
          this.player = this.add.circle(400, 300, 20, 0x0099ff);
          this.physics.add.existing(this.player);
          this.player.body.setCollideWorldBounds(true);

          // Create stars group
          this.stars = this.physics.add.group();

          // Create initial stars
          for (let i = 0; i < 5; i++) {
            this.createStar();
          }

          // Set up cursor keys
          this.cursors = this.input.keyboard.createCursorKeys();

          // Collision between player and stars
          this.physics.add.overlap(this.player, this.stars, this.collectStar, null, this);

          // Timer to create new stars
          this.starTimer = this.time.addEvent({
            delay: 2000,
            callback: this.createStar,
            callbackScope: this,
            loop: true
          });
        }

        update() {
          // Player movement
          if (this.cursors.left.isDown) {
            this.player.body.setVelocityX(-200);
          } else if (this.cursors.right.isDown) {
            this.player.body.setVelocityX(200);
          } else {
            this.player.body.setVelocityX(0);
          }

          if (this.cursors.up.isDown) {
            this.player.body.setVelocityY(-200);
          } else if (this.cursors.down.isDown) {
            this.player.body.setVelocityY(200);
          } else {
            this.player.body.setVelocityY(0);
          }
        }

        createStar() {
          const x = Phaser.Math.Between(50, 750);
          const y = Phaser.Math.Between(50, 550);
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
