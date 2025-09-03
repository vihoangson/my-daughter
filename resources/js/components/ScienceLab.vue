<template>
  <div class="science-lab-container">
    <div class="top-bar d-flex justify-content-between align-items-center px-3 py-2">
      <div class="title-wrap">
        <h2 class="m-0 fw-bold text-glow">Science Lab - Hệ Mặt Trời</h2>
        <p class="small text-muted mb-0">Nhấp vào một hành tinh để xem thông tin thú vị!</p>
      </div>
      <div class="btn-group">
        <router-link to="/user-kid/game" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Quay lại</router-link>
        <button class="btn btn-warning btn-sm" @click="resetSelection"><i class="fas fa-redo me-1"></i> Reset</button>
      </div>
    </div>

    <div class="solar-system-wrapper">
      <!-- Sun -->
      <div class="sun" @click="resetSelection" :class="{ pulse: !selectedPlanet }">
        <div class="sun-core"></div>
        <div class="sun-glow"></div>
        <span class="sun-label">Mặt Trời</span>
      </div>

      <!-- Planet Orbits -->
      <div v-for="planet in planets" :key="planet.id" class="orbit" :class="['orbit-' + planet.id, { paused: selectedPlanet && selectedPlanet.id === planet.id }]" :style="orbitStyle(planet)">
        <div class="planet" :class="['planet-' + planet.id, { active: selectedPlanet && selectedPlanet.id === planet.id }]" :style="planetStyle(planet)" @click.stop="selectPlanet(planet)">
          <span class="planet-label">{{ planet.short }}</span>
        </div>
      </div>

      <!-- Info Panel -->
      <transition name="fade-slide">
        <div v-if="selectedPlanet" class="info-panel card shadow-lg">
          <button class="btn-close btn-close-white position-absolute top-0 end-0 m-2" @click="resetSelection"></button>
          <div class="card-body">
            <div class="d-flex align-items-center mb-2">
              <div class="planet-preview me-3" :style="planetPreviewStyle(selectedPlanet)"></div>
              <div>
                <h4 class="card-title mb-1">{{ selectedPlanet.name }}</h4>
                <small class="text-uppercase opacity-75">{{ selectedPlanet.english }}</small>
              </div>
            </div>
            <p class="small mb-2">{{ selectedPlanet.description }}</p>
            <ul class="list-unstyled stat-list small mb-3">
              <li><i class="fas fa-ruler-horizontal me-2 text-info"></i>Bán kính: <strong>{{ formatNumber(selectedPlanet.radiusKm) }}</strong> km</li>
              <li><i class="fas fa-arrows-alt-h me-2 text-warning"></i>Khoảng cách TB tới Mặt Trời: <strong>{{ selectedPlanet.distanceAU }} AU</strong></li>
              <li><i class="fas fa-sync-alt me-2 text-success"></i>Chu kỳ quỹ đạo: <strong>{{ selectedPlanet.orbitalPeriodDays }} ngày</strong></li>
              <li><i class="fas fa-clock me-2 text-primary"></i>Độ dài 1 ngày: <strong>{{ selectedPlanet.dayLengthHours }} giờ</strong></li>
              <li v-if="selectedPlanet.moons"><i class="fas fa-moon me-2 text-light"></i>Số vệ tinh: <strong>{{ selectedPlanet.moons }}</strong></li>
            </ul>
            <div class="d-flex gap-2 flex-wrap">
              <button class="btn btn-sm btn-outline-light" @click="focusPlanet(selectedPlanet)"><i class="fas fa-search-plus me-1"></i>Phóng to</button>
              <button class="btn btn-sm btn-outline-info" @click="toggleSpin(selectedPlanet)">
                <i :class="['fas', selectedPlanet.paused ? 'fa-play' : 'fa-pause', 'me-1']"></i>
                {{ selectedPlanet.paused ? 'Tiếp tục quay' : 'Tạm dừng quay' }}
              </button>
            </div>
          </div>
        </div>
      </transition>

      <!-- Planet List (mobile / accessibility) -->
      <div class="planet-strip bg-dark bg-opacity-50 rounded-4 p-2">
        <div v-for="p in planets" :key="p.id" class="strip-item" :class="{ selected: selectedPlanet && selectedPlanet.id === p.id }" @click="selectPlanet(p)" :title="p.name">
          <div class="strip-dot" :style="{ background: p.color }"></div>
          <span class="d-none d-sm-inline small">{{ p.short }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: 'ScienceLab',
  data() {
    return {
      selectedPlanet: null,
      orbitBase: 150, // increased base radius
      orbitStep: 85,  // increased spacing between orbits
      planets: [
        {
          id: 1, name: 'Sao Thủy', english: 'Mercury', short: 'Thủy',
          color: 'linear-gradient(135deg,#b8b2a6,#7d7568)',
          description: 'Hành tinh nhỏ nhất & gần Mặt Trời nhất, bề mặt đầy hố va chạm.',
            radiusKm: 2440, orbitalPeriodDays: 88, distanceAU: 0.39, dayLengthHours: 1407.6, moons: 0, orbitSeconds: 14,
        },
        {
          id: 2, name: 'Sao Kim', english: 'Venus', short: 'Kim',
          color: 'linear-gradient(135deg,#e0c07d,#b8860b)',
          description: 'Nóng nhất hệ do hiệu ứng nhà kính dày đặc, quay rất chậm và ngược.',
            radiusKm: 6052, orbitalPeriodDays: 225, distanceAU: 0.72, dayLengthHours: 5832, moons: 0, orbitSeconds: 22,
        },
        {
          id: 3, name: 'Trái Đất', english: 'Earth', short: 'Đất',
          color: 'linear-gradient(135deg,#2e7dd1,#1bb37b)',
          description: 'Ngôi nhà của chúng ta, có nước lỏng và sự sống phong phú.',
            radiusKm: 6371, orbitalPeriodDays: 365, distanceAU: 1, dayLengthHours: 24, moons: 1, orbitSeconds: 30,
        },
        {
          id: 4, name: 'Sao Hỏa', english: 'Mars', short: 'Hỏa',
          color: 'linear-gradient(135deg,#ce5937,#a8321b)',
          description: 'Hành tinh đỏ với núi lửa cao nhất và hẻm vực sâu nhất hệ.',
            radiusKm: 3389, orbitalPeriodDays: 687, distanceAU: 1.52, dayLengthHours: 24.6, moons: 2, orbitSeconds: 40,
        },
        {
          id: 5, name: 'Sao Mộc', english: 'Jupiter', short: 'Mộc',
          color: 'linear-gradient(135deg,#d2b48c,#8b5a2b)',
          description: 'Hành tinh khí khổng lồ lớn nhất, có Vết Đỏ Lớn là bão khổng lồ.',
            radiusKm: 69911, orbitalPeriodDays: 4333, distanceAU: 5.2, dayLengthHours: 9.9, moons: 79, orbitSeconds: 55,
        },
        {
          id: 6, name: 'Sao Thổ', english: 'Saturn', short: 'Thổ',
          color: 'linear-gradient(135deg,#e6d6a8,#b79d63)',
          description: 'Nổi tiếng với vành đai băng đá và bụi tuyệt đẹp.',
            radiusKm: 58232, orbitalPeriodDays: 10759, distanceAU: 9.58, dayLengthHours: 10.7, moons: 83, orbitSeconds: 65,
        },
        {
          id: 7, name: 'Sao Thiên Vương', english: 'Uranus', short: 'Thiên',
          color: 'linear-gradient(135deg,#8ad5e6,#3f9abf)',
          description: 'Quay nghiêng gần như nằm ngang, có màu xanh ngọc.',
            radiusKm: 25362, orbitalPeriodDays: 30687, distanceAU: 19.2, dayLengthHours: 17.2, moons: 27, orbitSeconds: 75,
        },
        {
          id: 8, name: 'Sao Hải Vương', english: 'Neptune', short: 'Hải',
          color: 'linear-gradient(135deg,#4462e6,#172a8e)',
          description: 'Xa nhất trong 8 hành tinh, có gió mạnh nhất hệ.',
            radiusKm: 24622, orbitalPeriodDays: 60190, distanceAU: 30.05, dayLengthHours: 16.1, moons: 14, orbitSeconds: 85,
        }
      ]
    }
  },
  mounted() {
    this.computeOrbitParams();
    window.addEventListener('resize', this.computeOrbitParams);
  },
  beforeUnmount() { window.removeEventListener('resize', this.computeOrbitParams); },
  methods: {
    selectPlanet(planet) {
      this.selectedPlanet = planet;
    },
    resetSelection() { this.selectedPlanet = null; },
    formatNumber(n) { return n.toLocaleString('vi-VN'); },
    computeOrbitParams() {
      const min = Math.min(window.innerWidth, window.innerHeight);
      if (min < 520) { this.orbitBase = 90; this.orbitStep = 55; }
      else if (min < 700) { this.orbitBase = 110; this.orbitStep = 65; }
      else if (min < 900) { this.orbitBase = 130; this.orbitStep = 75; }
      else { this.orbitBase = 150; this.orbitStep = 85; }
    },
    orbitStyle(p) {
      // use wider spacing so planets do not overlap visually
      const radius = this.orbitBase + p.id * this.orbitStep;
      return { width: radius + 'px', height: radius + 'px', animationDuration: p.orbitSeconds + 's' };
    },
    planetStyle(p) {
      const scale = this.relativeSize(p.radiusKm);
      return { background: p.color, transform: `scale(${scale})` };
    },
    planetPreviewStyle(p) { return { background: p.color }; },
    relativeSize(radiusKm) {
      const earth = 6371;
      const factor = radiusKm / earth;
      return Math.min(1.6, Math.max(0.35, factor ** 0.4)); // slightly reduced min & max to fit larger spacing
    },
    focusPlanet(p) {
      // scroll the orbit into view (future improvement); placeholder animation trigger
      const el = document.querySelector('.planet-' + p.id);
      if (el) {
        el.classList.add('focus-pulse');
        setTimeout(()=> el.classList.remove('focus-pulse'), 1500);
      }
    },
    toggleSpin(p) {
      p.paused = !p.paused;
      const orbitEl = document.querySelector('.orbit-' + p.id);
      if (orbitEl) {
        if (p.paused) orbitEl.classList.add('force-paused'); else orbitEl.classList.remove('force-paused');
      }
    }
  }
}
</script>

<style scoped>
.science-lab-container { position:relative; min-height:100vh; background:radial-gradient(circle at 50% 50%, #0d1b33, #040a14 70%); color:#fff; font-family:'Poppins', system-ui, sans-serif; overflow:hidden; }
.top-bar { position:absolute; z-index:30; width:100%; background:rgba(0,0,20,0.4); backdrop-filter:blur(6px); border-bottom:1px solid rgba(255,255,255,0.05); }
.text-glow { text-shadow:0 0 6px rgba(255,255,255,0.7), 0 0 18px rgba(0,140,255,0.5); }
.solar-system-wrapper { position:relative; width:100%; height:100vh; display:flex; align-items:center; justify-content:center; }

/* star background dynamic */
.science-lab-container:before, .science-lab-container:after { content:""; position:absolute; inset:0; background-image:radial-gradient(#ffffffcc 1px, transparent 1px), radial-gradient(#ffffff66 1px, transparent 1px); background-size: 120px 120px, 80px 80px; background-position:0 0, 40px 60px; animation: starMove 120s linear infinite; opacity:0.35; }
.science-lab-container:after { animation-direction:reverse; opacity:0.2; }
@keyframes starMove { to { transform:translate3d(40px,60px,0); } }

.sun { position:absolute; width:140px; height:140px; border-radius:50%; background:radial-gradient(circle at 30% 30%, #ffd27a, #ff9800 55%, #ff6d00 70%); box-shadow:0 0 35px 12px rgba(255,153,0,0.55), 0 0 80px 30px rgba(255,120,0,0.35); display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:5; }
.sun-core { width:90%; height:90%; border-radius:inherit; background:radial-gradient(circle at 40% 35%, #fff8e3, #ffbb33 60%, rgba(255,120,0,0.6) 80%); animation: sunPulse 4s ease-in-out infinite; }
.sun-glow { position:absolute; inset:-10px; border-radius:50%; background:radial-gradient(circle,#ffb34722,#ff980000); filter:blur(8px); animation: glow 5s linear infinite; }
.sun-label { position:absolute; top:100%; margin-top:4px; font-size:.7rem; letter-spacing:1px; opacity:0.75; }
@keyframes sunPulse { 0%,100% { transform:scale(1); } 50% { transform:scale(1.05); } }
@keyframes glow { 0%,100% { opacity:.6; } 50% { opacity:.2; } }
.pulse { animation:pulseSelect 1.6s infinite; }
@keyframes pulseSelect { 0%,100% { box-shadow:0 0 35px 12px rgba(255,153,0,0.55), 0 0 80px 30px rgba(255,120,0,0.35); } 50% { box-shadow:0 0 35px 12px rgba(255,200,0,0.85), 0 0 90px 40px rgba(255,150,0,0.55);} }

.orbit { position:absolute; border:1px dashed rgba(255,255,255,0.15); border-radius:50%; display:flex; align-items:flex-start; justify-content:center; animation: orbitSpin linear infinite; }
.orbit.force-paused, .orbit.paused { animation-play-state:paused !important; }
@keyframes orbitSpin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }

.planet { position:relative; top:-12px; width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 0 8px 2px rgba(255,255,255,0.15), inset 0 0 4px rgba(255,255,255,0.3); transition:.4s; }
.planet:hover { transform:scale(1.2) !important; }
.planet.active { box-shadow:0 0 14px 4px rgba(255,255,255,0.65), 0 0 30px 10px rgba(0,150,255,0.6); transform:scale(1.4) !important; }
.focus-pulse { animation: focusPulse 1.4s ease-in-out 1; }
@keyframes focusPulse { 0% { box-shadow:0 0 0 0 rgba(255,255,255,0.5);} 70% { box-shadow:0 0 20px 15px rgba(255,255,255,0);} 100% { box-shadow:0 0 0 0 rgba(255,255,255,0);} }

.planet-label { position:absolute; top:100%; white-space:nowrap; font-size:.55rem; transform:translateY(4px); opacity:.75; letter-spacing:.5px; }

/* Info Panel */
.info-panel { position:absolute; right:20px; top:80px; width:300px; max-width:84vw; background:linear-gradient(155deg, rgba(25,40,70,0.92), rgba(15,25,45,0.85)); border:1px solid rgba(255,255,255,0.08); backdrop-filter:blur(8px); color:#fff; border-radius:18px; z-index:40; }
.info-panel .card-body { padding:1rem 1.1rem 1.1rem; }
.planet-preview { width:60px; height:60px; border-radius:50%; box-shadow:0 0 10px 2px rgba(255,255,255,0.3); background-size:cover; background-position:center; animation: spinPlanet 18s linear infinite; }
@keyframes spinPlanet { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
.stat-list li { margin-bottom:4px; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all .35s cubic-bezier(.4,.0,.2,1); }
.fade-slide-enter-from, .fade-slide-leave-to { opacity:0; transform:translateY(-12px) scale(.95); }

/* Planet strip */
.planet-strip { position:absolute; bottom:15px; left:50%; transform:translateX(-50%); display:flex; gap:10px; z-index:25; }
.strip-item { display:flex; align-items:center; gap:4px; background:rgba(255,255,255,0.08); padding:4px 10px; border-radius:30px; cursor:pointer; transition:.25s; font-size:.7rem; user-select:none; }
.strip-item.selected, .strip-item:hover { background:rgba(0,150,255,0.5); box-shadow:0 0 8px rgba(0,150,255,0.6); }
.strip-dot { width:14px; height:14px; border-radius:50%; box-shadow:0 0 4px rgba(255,255,255,0.5); }

/* Responsive tweaks */
@media (max-width: 800px) {
  .info-panel { position:fixed; right:10px; left:10px; top:auto; bottom:90px; width:auto; }
  .sun { width:110px; height:110px; }
  .planet { width:34px; height:34px; }
  .orbit { border-style:solid; }
}
@media (max-width: 520px) { .top-bar h2 { font-size:1.05rem; } .top-bar p { font-size:.65rem; } }
</style>
