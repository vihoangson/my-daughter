<template>
  <div class="puzzle-challenge-container">
    <div class="container py-4">
      <!-- Header Section -->
      <div class="row mb-3">
        <div class="col-12">
          <div class="d-flex justify-content-between align-items-center">
            <h1 class="mb-0">Puzzle Challenge</h1>
            <router-link to="/user-kid/game" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left me-2"></i>Quay lại
            </router-link>
          </div>
          <p class="text-muted">Kéo và thả các mảnh ghép để hoàn thành bức tranh</p>
        </div>
      </div>

      <!-- Game Status Section -->
      <div class="row mb-3">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <span class="fs-5 me-2">Thời gian:</span>
                <span class="fs-5 fw-bold text-primary">{{ formatTime(elapsedTime) }}</span>
              </div>
              <div>
                <button class="btn btn-primary me-2" @click="startGame" v-if="!gameStarted">
                  <i class="fas fa-play me-1"></i>Bắt đầu
                </button>
                <button class="btn btn-warning me-2" @click="resetGame" v-if="gameStarted && !gameCompleted">
                  <i class="fas fa-sync-alt me-1"></i>Làm lại
                </button>
                <button class="btn btn-success" @click="changeImage" v-if="!gameStarted || gameCompleted">
                  <i class="fas fa-image me-1"></i>Đổi hình
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
          <div class="card" :class="gameCompleted ? 'bg-success text-white' : 'bg-light'">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <span class="fs-5 me-2">Tiến độ:</span>
                <span class="fs-5 fw-bold" :class="gameCompleted ? 'text-white' : 'text-primary'">
                  {{ correctPieces }} / {{ totalPieces }} mảnh
                </span>
              </div>
              <div v-if="gameCompleted">
                <i class="fas fa-trophy fa-2x text-warning me-2"></i>
                <span class="fs-5 fw-bold">Hoàn thành!</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Game Area -->
      <div class="row">
        <div class="col-12 mb-4">
          <!-- Puzzle Board -->
          <div class="card puzzle-board-container">
            <div class="card-body p-2 p-md-3">
              <div ref="puzzleBoard" class="puzzle-board">
                <!-- Show preview before game starts -->
                <div v-if="!gameStarted && !gameCompleted" class="puzzle-overlay d-flex flex-column align-items-center justify-content-center">
                  <img :src="currentImage" alt="Puzzle Preview" class="puzzle-preview mb-3" />
                  <button class="btn btn-lg btn-primary" @click="startGame">
                    <i class="fas fa-play me-2"></i>Bắt đầu Chơi
                  </button>
                </div>

                <!-- Show completion message when game is completed -->
                <div v-if="gameCompleted" class="puzzle-overlay d-flex flex-column align-items-center justify-content-center">
                  <div class="completed-message text-center mb-3">
                    <i class="fas fa-trophy fa-3x text-warning mb-3"></i>
                    <h2 class="mb-2">Chúc mừng!</h2>
                    <p class="mb-1">Bạn đã hoàn thành trò chơi trong:</p>
                    <p class="fs-3 fw-bold text-primary">{{ formatTime(elapsedTime) }}</p>
                    <div class="badge bg-success fs-5 mb-3">
                      <i class="fas fa-award me-1"></i>Huy hiệu: Puzzle Master
                    </div>
                  </div>
                  <div class="d-flex">
                    <button class="btn btn-primary me-2" @click="resetGame">
                      <i class="fas fa-sync-alt me-1"></i>Chơi lại
                    </button>
                    <button class="btn btn-success" @click="changeImage">
                      <i class="fas fa-image me-1"></i>Đổi hình
                    </button>
                  </div>
                </div>

                <!-- Actual puzzle game -->
                <div v-if="gameStarted && !gameCompleted" class="puzzle-game-container">
                  <!-- Puzzle pieces board -->
                  <div class="puzzle-pieces-container mb-4" ref="puzzlePieces">
                    <div
                      v-for="(piece, index) in shuffledPieces"
                      :key="'piece-'+index"
                      class="puzzle-piece"
                      :class="{ 'placed': piece.placed }"
                      :style="getPieceStyle(piece)"
                      @mousedown="startDrag($event, piece, index)"
                      @touchstart="startDrag($event, piece, index)"
                    >
                      <div class="puzzle-piece-inner" :style="getPieceInnerStyle(piece)"></div>
                    </div>
                  </div>

                  <!-- Puzzle target board -->
                  <div class="puzzle-target-container" ref="puzzleTarget">
                    <div
                      v-for="(target, index) in puzzleTargets"
                      :key="'target-'+index"
                      class="puzzle-target"
                      :style="getTargetStyle(target)"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Game Controls Row -->
          <div class="row mt-4">
            <!-- Game Instructions -->
            <div class="col-md-4 mb-4">
              <div class="card">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng dẫn</h5>
                </div>
                <div class="card-body">
                  <ol class="mb-0">
                    <li class="mb-2">Nhấn <strong>Bắt đầu</strong> để chơi trò chơi.</li>
                    <li class="mb-2">Kéo và thả các mảnh ghép vào đúng vị trí.</li>
                    <li class="mb-2">Hoàn thành càng nhanh, điểm càng cao.</li>
                    <li>Hoàn thành để nhận huy hiệu <strong>Puzzle Master</strong>!</li>
                  </ol>
                </div>
              </div>
            </div>

            <!-- Difficulty Selection -->
            <div class="col-md-4 mb-4">
              <div class="card">
                <div class="card-header bg-warning text-dark">
                  <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Độ khó</h5>
                </div>
                <div class="card-body">
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="difficulty" id="difficultyEasy" value="easy" v-model="difficulty" :disabled="gameStarted && !gameCompleted">
                    <label class="form-check-label" for="difficultyEasy">
                      <i class="fas fa-baby me-1"></i>Dễ (3x3)
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="difficulty" id="difficultyMedium" value="medium" v-model="difficulty" :disabled="gameStarted && !gameCompleted">
                    <label class="form-check-label" for="difficultyMedium">
                      <i class="fas fa-child me-1"></i>Trung bình (4x4)
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="difficulty" id="difficultyHard" value="hard" v-model="difficulty" :disabled="gameStarted && !gameCompleted">
                    <label class="form-check-label" for="difficultyHard">
                      <i class="fas fa-user-graduate me-1"></i>Khó (5x5)
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- High Scores -->
            <div class="col-md-4 mb-4">
              <div class="card">
                <div class="card-header bg-success text-white">
                  <h5 class="mb-0"><i class="fas fa-medal me-2"></i>Điểm cao nhất</h5>
                </div>
                <div class="card-body p-0">
                  <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center" v-for="(score, index) in highScores" :key="index">
                      <div>
                        <span class="badge rounded-pill" :class="getScoreBadgeClass(index)">{{ index + 1 }}</span>
                        <span class="ms-2">{{ score.difficulty === 'easy' ? 'Dễ' : score.difficulty === 'medium' ? 'TB' : 'Khó' }}</span>
                      </div>
                      <span class="fw-bold">{{ formatTime(score.time) }}</span>
                    </li>
                    <li v-if="highScores.length === 0" class="list-group-item text-center text-muted py-3">
                      Chưa có điểm nào được ghi nhận
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PuzzleChallenge',
  data() {
    return {
      gameStarted: false,
      gameCompleted: false,
      elapsedTime: 0,
      timerInterval: null,
      correctPieces: 0,
      totalPieces: 0,
      difficulty: 'easy',
      images: [
        'https://img.freepik.com/free-vector/cute-zoo-animals-collection_23-2148955944.jpg',
        'https://img.freepik.com/free-vector/flat-design-dinosaur-collection_23-2149160648.jpg',
        'https://img.freepik.com/free-vector/space-background-with-planets_23-2148008382.jpg',
        'https://img.freepik.com/free-vector/underwater-background-with-fishes-coral-reef_107791-2494.jpg',
        'https://img.freepik.com/free-vector/fairy-tale-castle-pink-clouds-cliff-mountain_107791-5840.jpg'
      ],
      currentImageIndex: 0,
      highScores: [],
      puzzlePieces: [],
      shuffledPieces: [],
      puzzleTargets: [],
      draggedPiece: null,
      draggedIndex: null,
      startX: 0,
      startY: 0,
      pieceX: 0,
      pieceY: 0
    }
  },
  computed: {
    currentImage() {
      return this.images[this.currentImageIndex];
    },
    difficultySize() {
      const difficultyMap = {
        'easy': 3,
        'medium': 4,
        'hard': 5
      };
      return difficultyMap[this.difficulty];
    }
  },
  methods: {
    startGame() {
      this.gameStarted = true;
      this.gameCompleted = false;
      this.elapsedTime = 0;
      this.correctPieces = 0;

      // Create pieces
      this.createPuzzlePieces();

      // Start the timer
      this.timerInterval = setInterval(() => {
        this.elapsedTime++;
      }, 1000);
    },

    resetGame() {
      this.gameStarted = false;
      this.gameCompleted = false;
      this.elapsedTime = 0;
      this.correctPieces = 0;
      this.puzzlePieces = [];
      this.shuffledPieces = [];
      this.puzzleTargets = [];

      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }
    },

    changeImage() {
      this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
      if (this.gameStarted) {
        this.resetGame();
      }
    },

    createPuzzlePieces() {
      const size = this.difficultySize;
      this.totalPieces = size * size;
      this.puzzlePieces = [];
      this.puzzleTargets = [];

      // Get the size of the puzzle board
      const board = this.$refs.puzzleBoard;
      const boardWidth = board.clientWidth;
      const boardHeight = board.clientWidth * 0.75; // 4:3 aspect ratio

      // Calculate piece size
      const pieceWidth = boardWidth / size;
      const pieceHeight = boardHeight / size;

      // Create pieces and targets
      for (let row = 0; row < size; row++) {
        for (let col = 0; col < size; col++) {
          const index = row * size + col;

          // Create target position
          this.puzzleTargets.push({
            id: index,
            row,
            col,
            width: pieceWidth,
            height: pieceHeight,
            top: row * pieceHeight,
            left: col * pieceWidth
          });

          // Create puzzle piece
          this.puzzlePieces.push({
            id: index,
            row,
            col,
            targetRow: row,
            targetCol: col,
            width: pieceWidth,
            height: pieceHeight,
            top: 0,
            left: 0,
            backgroundPositionX: -col * pieceWidth,
            backgroundPositionY: -row * pieceHeight,
            placed: false
          });
        }
      }

      // Shuffle pieces
      this.shufflePieces();
    },

    shufflePieces() {
      // Create a shuffled copy of the pieces
      this.shuffledPieces = [...this.puzzlePieces]
        .map(piece => ({...piece}))
        .sort(() => Math.random() - 0.5);

      // Arrange shuffled pieces in a grid above the target area
      const board = this.$refs.puzzleBoard;
      const piecesContainer = this.$refs.puzzlePieces;
      const containerWidth = piecesContainer.clientWidth;

      const size = this.difficultySize;
      const pieceWidth = this.shuffledPieces[0].width;
      const pieceHeight = this.shuffledPieces[0].height;

      // Place pieces in a grid layout
      this.shuffledPieces.forEach((piece, index) => {
        const row = Math.floor(index / size);
        const col = index % size;

        piece.top = row * pieceHeight;
        piece.left = col * pieceWidth;
      });
    },

    startDrag(event, piece, index) {
      if (piece.placed) return;

      // Prevent default to avoid browser drag behavior
      event.preventDefault();

      this.draggedPiece = piece;
      this.draggedIndex = index;

      // Get starting position
      if (event.type === 'mousedown') {
        this.startX = event.clientX;
        this.startY = event.clientY;
      } else if (event.type === 'touchstart') {
        this.startX = event.touches[0].clientX;
        this.startY = event.touches[0].clientY;
      }

      this.pieceX = piece.left;
      this.pieceY = piece.top;

      // Add event listeners for move and end
      if (event.type === 'mousedown') {
        document.addEventListener('mousemove', this.onDrag);
        document.addEventListener('mouseup', this.endDrag);
      } else if (event.type === 'touchstart') {
        document.addEventListener('touchmove', this.onDrag, { passive: false });
        document.addEventListener('touchend', this.endDrag);
      }
    },

    onDrag(event) {
      if (!this.draggedPiece) return;

      // Prevent default to avoid scrolling on touch devices
      event.preventDefault();

      let clientX, clientY;

      if (event.type === 'mousemove') {
        clientX = event.clientX;
        clientY = event.clientY;
      } else if (event.type === 'touchmove') {
        clientX = event.touches[0].clientX;
        clientY = event.touches[0].clientY;
      }

      // Calculate new position
      const dx = clientX - this.startX;
      const dy = clientY - this.startY;

      // Update piece position
      this.draggedPiece.left = this.pieceX + dx;
      this.draggedPiece.top = this.pieceY + dy;
    },

    endDrag(event) {
      if (!this.draggedPiece) return;

      // Remove event listeners
      document.removeEventListener('mousemove', this.onDrag);
      document.removeEventListener('mouseup', this.endDrag);
      document.removeEventListener('touchmove', this.onDrag);
      document.removeEventListener('touchend', this.endDrag);

      // Check if piece is over its target position
      const target = this.puzzleTargets.find(target =>
        target.id === this.draggedPiece.id
      );

      if (target) {
        const pieceRect = {
          left: this.draggedPiece.left,
          top: this.draggedPiece.top,
          right: this.draggedPiece.left + this.draggedPiece.width,
          bottom: this.draggedPiece.top + this.draggedPiece.height
        };

        const targetRect = {
          left: target.left,
          top: target.top,
          right: target.left + target.width,
          bottom: target.top + target.height
        };

        // Check if piece is close enough to target (50% overlap)
        const overlap = this.checkOverlap(pieceRect, targetRect);

        if (overlap > 0.5) {
          // Snap to target position
          this.draggedPiece.left = target.left;
          this.draggedPiece.top = target.top;
          this.draggedPiece.placed = true;

          // Increment correct pieces count
          this.correctPieces++;

          // Check if puzzle is completed
          if (this.correctPieces === this.totalPieces) {
            this.onPuzzleCompleted();
          }
        }
      }

      this.draggedPiece = null;
      this.draggedIndex = null;
    },

    checkOverlap(rect1, rect2) {
      // Calculate the overlapping area
      const xOverlap = Math.max(0, Math.min(rect1.right, rect2.right) - Math.max(rect1.left, rect2.left));
      const yOverlap = Math.max(0, Math.min(rect1.bottom, rect2.bottom) - Math.max(rect1.top, rect2.top));
      const overlapArea = xOverlap * yOverlap;

      // Calculate the area of the first rectangle
      const rect1Area = (rect1.right - rect1.left) * (rect1.bottom - rect1.top);

      // Return the ratio of overlap
      return overlapArea / rect1Area;
    },

    getPieceStyle(piece) {
      return {
        width: `${piece.width}px`,
        height: `${piece.height}px`,
        transform: `translate(${piece.left}px, ${piece.top}px)`,
        zIndex: piece.placed ? 1 : 10
      };
    },

    getPieceInnerStyle(piece) {
      return {
        width: `100%`,
        height: `100%`,
        backgroundImage: `url(${this.currentImage})`,
        backgroundSize: `${piece.width * this.difficultySize}px ${piece.height * this.difficultySize}px`,
        backgroundPosition: `${piece.backgroundPositionX}px ${piece.backgroundPositionY}px`
      };
    },

    getTargetStyle(target) {
      return {
        width: `${target.width}px`,
        height: `${target.height}px`,
        top: `${target.top}px`,
        left: `${target.left}px`
      };
    },

    onPuzzleCompleted() {
      this.gameCompleted = true;

      if (this.timerInterval) {
        clearInterval(this.timerInterval);
        this.timerInterval = null;
      }

      // Add to high scores
      this.highScores.push({
        difficulty: this.difficulty,
        time: this.elapsedTime
      });

      // Sort high scores by time (ascending)
      this.highScores.sort((a, b) => a.time - b.time);

      // Keep only top 5 scores
      if (this.highScores.length > 5) {
        this.highScores = this.highScores.slice(0, 5);
      }

      // Save high scores to localStorage
      localStorage.setItem('puzzleHighScores', JSON.stringify(this.highScores));
    },

    formatTime(seconds) {
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = seconds % 60;
      return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    },

    getScoreBadgeClass(index) {
      const classes = [
        'bg-warning text-dark', // Gold
        'bg-secondary text-white', // Silver
        'bg-danger text-white', // Bronze
        'bg-primary text-white',
        'bg-info text-dark'
      ];
      return classes[index] || 'bg-dark text-white';
    }
  },
  mounted() {
    // Load high scores from localStorage
    const savedScores = localStorage.getItem('puzzleHighScores');
    if (savedScores) {
      this.highScores = JSON.parse(savedScores);
    }

    // Add event listener for window resize
    window.addEventListener('resize', () => {
      if (this.gameStarted && !this.gameCompleted) {
        // Recalculate piece positions when window is resized
        this.createPuzzlePieces();
      }
    });
  },
  beforeUnmount() {
    // Clean up timer when component is destroyed
    if (this.timerInterval) {
      clearInterval(this.timerInterval);
    }

    // Remove event listeners
    document.removeEventListener('mousemove', this.onDrag);
    document.removeEventListener('mouseup', this.endDrag);
    document.removeEventListener('touchmove', this.onDrag);
    document.removeEventListener('touchend', this.endDrag);
    window.removeEventListener('resize', this.handleResize);
  }
}
</script>

<style scoped>
.puzzle-challenge-container {
  min-height: 100vh;
  background-color: #fff5f7; /* Light pink background to match kid interface */
  padding-bottom: 2rem;
}

.puzzle-board-container {
  border: none;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  border-radius: 0.5rem;
  overflow: hidden;
}

.puzzle-board {
  position: relative;
  aspect-ratio: 4/3;
  width: 100%;
  background-color: #f8f9fa;
  border-radius: 0.25rem;
  overflow: hidden;
}

.puzzle-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(255,255,255,0.9);
  z-index: 20;
  padding: 2rem;
}

.puzzle-preview {
  max-width: 80%;
  max-height: 60%;
  object-fit: contain;
  border-radius: 0.5rem;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.completed-message {
  background-color: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  max-width: 90%;
}

.puzzle-game-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.puzzle-pieces-container {
  position: relative;
  width: 100%;
  height: 50%;
  background-color: #e9ecef;
  border-bottom: 2px dashed #ced4da;
}

.puzzle-target-container {
  position: relative;
  width: 100%;
  height: 50%;
  background-color: #f8f9fa;
}

.puzzle-piece {
  position: absolute;
  cursor: grab;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  border: 2px solid white;
  transition: box-shadow 0.2s ease;
}

.puzzle-piece:hover {
  box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.puzzle-piece.placed {
  cursor: default;
  box-shadow: none;
  border: 1px solid #ced4da;
}

.puzzle-piece-inner {
  width: 100%;
  height: 100%;
}

.puzzle-target {
  position: absolute;
  border: 1px dashed #ced4da;
  background-color: rgba(0,0,0,0.05);
}

/* Responsive adjustments */
@media (max-width: 767.98px) {
  .puzzle-board {
    aspect-ratio: 1/1;
  }

  .puzzle-overlay {
    padding: 1rem;
  }

  .completed-message {
    padding: 1.5rem;
  }
}
</style>
