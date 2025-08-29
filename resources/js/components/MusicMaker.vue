<template>
  <div class="music-maker-container">
    <div v-if="!gameStarted" class="overlay-screen">
      <h2>Music Maker - Piano</h2>
      <!-- DESCRIPTION UPDATED: single octave practice no scoring -->
      <p>Chơi đàn piano ảo 1 quãng (C4 đến C5). Luyện tập bấm đúng nốt được yêu cầu trong thời gian quy định. Không có tính điểm.</p>
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
      <p>Hết thời gian luyện tập.</p>
      <button @click="startGame" class="restart-button">Chơi lại</button>
      <router-link to="/user-kid/game" class="btn btn-outline-light mt-3">Quay lại danh sách game</router-link>
    </div>

    <div v-if="gameStarted && !gameOver" class="hud">
      <div>Thời gian: <strong>{{ timeLeft }}</strong>s</div>
      <div>Độ khó: <strong class="text-capitalize">{{ level }}</strong></div>
      <div>Nốt mục tiêu: <strong>{{ currentTarget?.label }}</strong></div>
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

    <div v-if="gameStarted && !gameOver" class="piano-wrapper">
      <div class="target-note-display mb-3">
        <span>Nốt c���n bấm:</span>
        <strong class="ms-2 display-target">{{ currentTarget?.label }}</strong>
      </div>

      <div class="piano" ref="pianoEl">
        <div v-for="key in whiteKeys" :key="key.id" class="key white" :class="{active: key.active, target: isTarget(key)}" @mousedown="playKey(key)" @touchstart.prevent="playKey(key)">
          <span class="note-label">{{ key.label }}</span>
          <div v-for="b in blackKeysFor(key)" :key="b.id" class="key black" :class="{active: b.active, target: isTarget(b)}" @mousedown.stop="playKey(b)" @touchstart.stop.prevent="playKey(b)">
            <span class="note-label">{{ b.label }}</span>
          </div>
        </div>
      </div>
      <div class="legend small mt-3">
        <span><i class="fas fa-keyboard me-1"></i> Phím tắt: trắng A S D F G H J K | đen W E T Y U</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'MusicMaker',
  data() {
    return {
      level: 'easy',
      difficulties: [
        { value: 'easy', label: 'Dễ' },
        { value: 'middle', label: 'Trung bình' },
        { value: 'hard', label: 'Khó' }
      ],
      duration: 60,
      timeLeft: 60,
      timerHandle: null,
      gameStarted: false,
      gameOver: false,
      isPaused: false,
      currentTarget: null,
      whiteKeys: [],
      blackKeys: [],
      keyMap: {},
      audioCtx: null,
      keyDownSet: new Set(),
    };
  },
  mounted() {
    this.buildKeyboard();
    window.addEventListener('keydown', this.onKeyDown);
    window.addEventListener('keyup', this.onKeyUp);
  },
  beforeUnmount() {
    this.clearTimer();
    window.removeEventListener('keydown', this.onKeyDown);
    window.removeEventListener('keyup', this.onKeyUp);
  },
  methods: {
    buildKeyboard() {
      // Single octave C4 -> C5 (inclusive high C5)
      const notesDef = [
        { label:'C4', freq:261.63 },{ label:'C#4', freq:277.18 },{ label:'D4', freq:293.66 },{ label:'D#4', freq:311.13 },{ label:'E4', freq:329.63 },{ label:'F4', freq:349.23 },{ label:'F#4', freq:369.99 },{ label:'G4', freq:392.00 },{ label:'G#4', freq:415.30 },{ label:'A4', freq:440.00 },{ label:'A#4', freq:466.16 },{ label:'B4', freq:493.88 },{ label:'C5', freq:523.25 }
      ];
      const isBlack = n => n.includes('#');
      this.whiteKeys = notesDef.filter(n=>!isBlack(n.label)).map((n,i)=>({ ...n, id:`w-${n.label}`, type:'white', active:false, order:i }));
      this.blackKeys = notesDef.filter(n=>isBlack(n.label)).map((n,i)=>({ ...n, id:`b-${n.label}`, type:'black', active:false, order:i }));
      // Map physical keys (white) -> a s d f g h j k
      const whiteLetters = 'asdfghjk'.split('');
      this.whiteKeys.slice(0, whiteLetters.length).forEach((k,i)=>{ this.keyMap[whiteLetters[i]] = k.label; });
      // Map black keys -> w e t y u
      Object.assign(this.keyMap, { w:'C#4', e:'D#4', t:'F#4', y:'G#4', u:'A#4' });
    },
    keysPool() {
      // Difficulty now defines which subset is used within single octave
      if (this.level === 'easy') {
        // Only white keys
        return [...this.whiteKeys];
      }
      if (this.level === 'middle') {
        // White + half of black (filter by even index for variety)
        return [...this.whiteKeys, ...this.blackKeys.filter((_,i)=> i % 2 === 0)];
      }
      // hard: all keys in the octave
      return [...this.whiteKeys, ...this.blackKeys];
    },
    nextTarget() {
      const pool = this.keysPool();
      this.currentTarget = pool[Math.floor(Math.random()*pool.length)];
    },
    startGame() {
      this.resetState();
      this.gameStarted = true; this.gameOver = false; this.isPaused = false;
      this.timeLeft = this.duration;
      this.nextTarget();
      this.startTimer();
      if (!this.audioCtx) {
        try { this.audioCtx = new (window.AudioContext || window.webkitAudioContext)(); } catch(e){ console.warn('AudioContext error', e); }
      }
    },
    replayGame() { this.startGame(); },
    resetState() {
      this.currentTarget = null;
      this.clearTimer();
    },
    togglePause() { if (this.gameOver || !this.gameStarted) return; this.isPaused = !this.isPaused; },
    startTimer() { this.clearTimer(); this.timerHandle = setInterval(()=>{ if (this.isPaused) return; this.timeLeft--; if (this.timeLeft<=0) this.endGame(); },1000); },
    clearTimer() { if (this.timerHandle) { clearInterval(this.timerHandle); this.timerHandle=null; } },
    endGame() { if (this.gameOver) return; this.gameOver = true; this.gameStarted = false; this.isPaused=false; this.clearTimer(); },
    playKey(key) {
      if (!key) return;
      if (this.isPaused || this.gameOver || !this.gameStarted) { this.fireSound(key); return; }
      key.active = true; setTimeout(()=> key.active=false, 180);
      const correct = this.currentTarget && key.label === this.currentTarget.label;
      this.fireSound(key);
      if (correct) {
        this.nextTarget();
      }
    },
    fireSound(key) {
      if (!this.audioCtx) return;
      const now = this.audioCtx.currentTime;
      const osc = this.audioCtx.createOscillator();
      const gain = this.audioCtx.createGain();
      osc.type='sine';
      osc.frequency.value = key.freq;
      gain.gain.setValueAtTime(0, now);
      gain.gain.linearRampToValueAtTime(0.7, now+0.01);
      gain.gain.exponentialRampToValueAtTime(0.0001, now+1.0);
      osc.connect(gain).connect(this.audioCtx.destination);
      osc.start(now); osc.stop(now+1.05);
    },
    isTarget(key) { return this.currentTarget && key.label === this.currentTarget.label; },
    blackKeysFor(whiteKey) {
      const labelBase = whiteKey.label.replace(/[0-9]/g,'');
      const octave = whiteKey.label.match(/[0-9]+/)[0];
      const sharpsAfter = { 'C': 'C#', 'D':'D#', 'F':'F#', 'G':'G#', 'A':'A#' };
      const sharp = sharpsAfter[labelBase] ? `${sharpsAfter[labelBase]}${octave}` : null;
      if (!sharp) return [];
      return this.blackKeys.filter(b=>b.label===sharp);
    },
    onKeyDown(e) {
      const key = e.key.toLowerCase();
      if (this.keyDownSet.has(key)) return;
      this.keyDownSet.add(key);
      const mapped = this.keyMap[key];
      if (mapped) {
        const target = [...this.whiteKeys, ...this.blackKeys].find(k=>k.label===mapped);
        this.playKey(target);
      }
    },
    onKeyUp(e) { this.keyDownSet.delete(e.key.toLowerCase()); }
  }
};
</script>

<style scoped>
/* Style block restored after truncation; feedback flash classes removed */
.music-maker-container { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; width:100%; background:linear-gradient(135deg,#283593,#1a237e); color:#fff; position:relative; overflow:hidden; padding:20px 10px; }
.hud { position:absolute; top:10px; left:50%; transform:translateX(-50%); display:flex; gap:16px; background:rgba(0,0,0,0.45); padding:8px 20px; border-radius:30px; font-size:14px; backdrop-filter:blur(4px); pointer-events:none; flex-wrap:wrap; }
.control-bar { position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:10px; background:rgba(0,0,0,0.45); padding:8px 16px; border-radius:40px; backdrop-filter:blur(4px); }
.overlay-screen { position:absolute; top:0; left:0; right:0; bottom:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background:rgba(0,0,0,0.78); z-index:5; padding:30px 20px; text-align:center; }
.start-button, .restart-button { padding:14px 34px; font-size:18px; background:linear-gradient(45deg,#ff9800,#ff5722); color:#fff; border:none; border-radius:30px; cursor:pointer; box-shadow:0 5px 18px rgba(0,0,0,0.3); transition:.3s; }
.start-button:hover, .restart-button:hover { transform:translateY(-3px); }
.piano-wrapper { width:100%; max-width:1100px; margin:140px auto 60px; text-align:center; }
.target-note-display { font-size:1.3rem; }
.display-target { font-size:2.1rem; letter-spacing:2px; }
.piano { position:relative; display:flex; justify-content:center; user-select:none; padding:20px 18px 30px; background:rgba(255,255,255,0.08); border-radius:18px; box-shadow:0 8px 26px rgba(0,0,0,0.3); overflow:hidden; }
.key { position:relative; cursor:pointer; transition:.15s; display:flex; align-items:flex-end; justify-content:center; }
.key.white { width:60px; height:240px; background:linear-gradient(to bottom,#fafafa,#e0e0e0); margin:0 3px; border:1px solid #bbb; border-radius:0 0 8px 8px; box-shadow:inset 0 0 4px rgba(0,0,0,0.25); }
.key.white.active, .key.white.target { background:linear-gradient(to bottom,#fff,#d7ffd9); box-shadow:0 0 12px #4caf50, inset 0 0 4px rgba(0,0,0,0.3); }
.key.black { position:absolute; top:0; left:38px; width:40px; height:150px; background:linear-gradient(to bottom,#333,#000); border:1px solid #111; border-radius:0 0 6px 6px; z-index:2; margin-left:-20px; box-shadow:0 4px 10px rgba(0,0,0,0.5); }
.key.black.active, .key.black.target { background:linear-gradient(to bottom,#222,#094d1f); box-shadow:0 0 10px #4caf50; }
.note-label { font-size:0.65rem; margin-bottom:6px; color:#222; pointer-events:none; }
.key.black .note-label { color:#eee; }
.key:active { transform:translateY(2px); }
.legend-box { display:inline-block; width:16px; height:16px; border-radius:4px; margin-right:4px; vertical-align:middle; }
@media (max-width: 900px) { .key.white { width:44px; height:190px; } .key.black { width:30px; height:120px; left:27px; } }
@media (max-width: 600px) { .key.white { width:36px; height:150px; margin:0 2px; } .key.black { width:26px; height:100px; left:22px; } .hud { font-size:11px; gap:8px; padding:6px 12px; top:8px; max-width:95%; } .control-bar { bottom:6px; padding:6px 14px; gap:8px; } .control-bar .btn { font-size:12px; padding:4px 10px; } }
</style>
