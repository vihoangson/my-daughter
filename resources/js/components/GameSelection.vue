<template>
  <div class="game-selection-container">
    <div class="container py-5">
      <div class="row mb-4">
        <div class="col-12">
          <h1 class="text-center mb-2">Chọn Game Yêu Thích</h1>
          <p class="text-center text-muted">Chọn một game bạn muốn chơi</p>
        </div>
      </div>

      <div class="row g-4">
        <!-- Game Grid - 3x3 Matrix -->
        <div class="col-md-4 col-6" v-for="(game, index) in games" :key="index">
          <div class="card game-card h-100 shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="game-image-container">
              <img :src="game.image" class="card-img-top game-image" :alt="game.name">
              <div class="game-overlay d-flex align-items-center justify-content-center">
                <button class="btn btn-primary" @click="selectGame(game)">
                  <i class="fas fa-gamepad me-2"></i>Chơi Ngay
                </button>
              </div>
            </div>
            <div class="card-body text-center">
              <h5 class="card-title fw-bold">{{ game.name }}</h5>
              <p class="card-text small text-muted">{{ game.description }}</p>
              <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-success">
                  <i class="fas fa-star me-1"></i>{{ game.rating }}
                </span>
                <span class="text-muted small">
                  <i class="fas fa-users me-1"></i>{{ game.players }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row mt-4">
        <div class="col-12 text-center">
          <router-link to="/user-kid" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Quay lại Dashboard
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'GameSelection',
  data() {
    return {
      games: [
        {

          id: 1,
          name: 'Puzzle Challenge',
          description: 'Trò chơi ghép hình trí tuệ giúp phát triển tư duy logic',
          image: 'https://placehold.co/400x300/9C27B0/FFFFFF?text=Puzzle+Challenge',
          rating: 4.8,
          players: '2k+'
        },
        {
          id: 2,
          name: 'Math Adventure',
          description: 'Blocks nhiều màu rơi xuống chứa phép tính + - × ÷ đúng hoặc sai. Chọn kết quả đúng để ghi điểm, sai bị trừ điểm. Điều chỉnh độ khó & thời gian.',
          image: 'https://placehold.co/400x300/3F51B5/FFFFFF?text=Math+Adventure',
          rating: 4.5,
          players: '1.5k+'
        },

        {
          id: 3,
          name: 'Memory Match',
          description: 'Rèn luyện trí nhớ bằng cách ghép các thẻ bài giống nhau',
          image: 'https://placehold.co/400x300/2196F3/FFFFFF?text=Memory+Match',
          rating: 4.7,
          players: '3k+'
        },
        {
          id: 4,
          name: 'Word Quest',
          description: 'Tìm và nối các chữ cái để tạo thành từ có nghĩa',
          image: 'https://placehold.co/400x300/009688/FFFFFF?text=Word+Quest',
          rating: 4.6,
          players: '1.8k+'
        },
        {
          id: 5,
          name: 'Drawing Fun',
          description: 'Phát triển óc sáng tạo qua hoạt động vẽ tranh',
          image: 'https://placehold.co/400x300/4CAF50/FFFFFF?text=Drawing+Fun',
          rating: 4.9,
          players: '2.5k+'
        },
        {
          id: 6,
          name: 'Science Lab',
          description: 'Khám phá thế giới khoa học qua các thí nghiệm ảo',
          image: 'https://placehold.co/400x300/CDDC39/000000?text=Science+Lab',
          rating: 4.4,
          players: '1.2k+'
        },
        {
          id: 7,
          name: 'Music Maker',
          description: 'Sáng tạo âm nhạc và học về các nhạc cụ khác nhau',
          image: 'https://placehold.co/400x300/FFC107/000000?text=Music+Maker',
          rating: 4.3,
          players: '1.6k+'
        },
        {
          id: 8,
          name: 'Coding Kids',
          description: 'Học lập trình cơ bản thông qua các trò chơi đơn giản',
          image: 'https://placehold.co/400x300/FF5722/FFFFFF?text=Coding+Kids',
          rating: 4.7,
          players: '900+'
        },
        {
          id: 9,
          name: 'Animal Quiz',
          description: 'Học về các loài động vật qua câu đố thú vị',
          image: 'https://placehold.co/400x300/795548/FFFFFF?text=Animal+Quiz',
          rating: 4.8,
          players: '2.2k+'
        }
      ]
    }
  },
  methods: {
    selectGame(game) {
      if (game.id === 1) {
        this.$router.push('/user-kid/game/puzzle-challenge');
      } else if (game.id === 2) {
        this.$router.push('/user-kid/game/math-adventure');
      } else if (game.id === 3) {
        this.$router.push('/user-kid/game/memory-match');
      } else if (game.id === 6) {
        this.$router.push('/user-kid/game/science-lab');
      } else if (game.id === 7) {
        this.$router.push('/user-kid/game/music-maker');
      } else {
        alert(`Bạn đã chọn trò chơi: ${game.name}. Chức năng này sẽ được phát triển trong phiên bản tới!`);
      }
    }
  }
}
</script>

<style scoped>
.game-selection-container {
  min-height: 100vh;
  background-color: #fff5f7; /* Light pink background for kid interface */
  padding-bottom: 50px;
}

.game-card {
  transition: all 0.3s ease;
  cursor: pointer;
}

.game-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.game-image-container {
  position: relative;
  overflow: hidden;
  height: 180px;
}

.game-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: all 0.5s ease;
}

.game-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  opacity: 0;
  transition: all 0.3s ease;
}

.game-card:hover .game-overlay {
  opacity: 1;
}

.game-card:hover .game-image {
  transform: scale(1.1);
}

/* Responsive adjustments for smaller screens */
@media (max-width: 768px) {
  .game-image-container {
    height: 150px;
  }
}
</style>
