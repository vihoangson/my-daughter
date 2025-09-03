<template>
  <div class="animal-quiz-container">
    <div class="overlay" v-if="showIntro">
      <div class="intro-card shadow-lg">
        <h2 class="fw-bold mb-2 text-center">Animal Quiz</h2>
        <p class="text-muted small text-center mb-4">Trò chơi trắc nghiệm kiến thức động vật (phù hợp 9 - 15 tuổi)</p>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Số câu hỏi</label>
            <div class="btn-group w-100 flex-wrap">
              <button v-for="n in questionSetChoices" :key="n" class="btn btn-sm" :class="n===questionCount?'btn-primary':'btn-outline-primary'" @click="questionCount=n">{{ n }}</button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Chế độ</label>
            <select v-model="mode" class="form-select form-select-sm">
              <option value="mixed">Ngẫu nhiên tổng hợp</option>
              <option value="easy">Dễ</option>
              <option value="medium">Trung bình</option>
              <option value="hard">Khó</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Tùy chọn</label>
            <div class="form-check small">
              <input class="form-check-input" type="checkbox" id="chkTimer" v-model="enableTimer">
              <label class="form-check-label" for="chkTimer">Giới hạn thời gian mỗi câu (15s)</label>
            </div>
            <div class="form-check small">
              <input class="form-check-input" type="checkbox" id="chkExplain" v-model="showExplainImmediate">
              <label class="form-check-label" for="chkExplain">Hiện giải thích ngay sau khi trả lời</label>
            </div>
          </div>
        </div>
        <button class="btn btn-success w-100 mb-2" @click="startQuiz"><i class="fas fa-play me-2"></i>Bắt đầu</button>
        <router-link to="/user-kid/game" class="btn btn-outline-secondary w-100"><i class="fas fa-arrow-left me-2"></i>Quay lại danh sách game</router-link>
        <div class="mt-3 small text-muted">
          <template v-if="loadingBank">Đang tải ngân hàng câu hỏi...</template>
          <template v-else-if="loadError"><span class="text-danger">Lỗi tải câu hỏi: {{ loadError }}</span></template>
          <template v-else>Ngân hàng hiện có: {{ fullBank.length }} câu. Bạn chọn: {{ questionCount }} câu.</template>
        </div>
      </div>
    </div>

    <div v-if="inProgress && !showIntro" class="quiz-wrapper">
      <div class="hud">
        <div><i class="fas fa-layer-group me-1"></i>Câu: <strong>{{ currentIndex+1 }}/{{ activeQuestions.length }}</strong></div>
        <div><i class="fas fa-check-circle text-success me-1"></i>Đúng: <strong>{{ correctCount }}</strong></div>
        <div><i class="fas fa-times-circle text-danger me-1"></i>Sai: <strong>{{ wrongCount }}</strong></div>
        <div v-if="enableTimer" :class="['timer', remainingTime <=5 ? 'danger':'']">
          <i class="fas fa-hourglass-half me-1"></i>{{ remainingTime }}s
        </div>
        <button class="btn btn-sm btn-outline-light" @click="quitConfirm=true"><i class="fas fa-flag"></i></button>
      </div>

      <transition name="fade-move" mode="out-in">
        <div :key="currentQuestion.id" class="question-card shadow">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 class="mb-1">{{ currentQuestion.text }}</h5>
            <span class="badge rounded-pill" :class="difficultyBadge(currentQuestion.difficulty)">{{ labelDifficulty(currentQuestion.difficulty) }}</span>
          </div>
          <div class="small mb-3 text-muted">
            Chủ đề: <strong>{{ currentQuestion.topic }}</strong>
          </div>
          <div class="options-list">
            <button v-for="(opt,i) in currentQuestion.options" :key="i" class="option-item" :disabled="answered" :class="optionClass(i)" @click="answer(i)">
              <span class="prefix">{{ String.fromCharCode(65+i) }}.</span>
              <span class="text">{{ opt }}</span>
              <i v-if="answered && i===currentQuestion.correct" class="fas fa-check ms-auto text-success"></i>
              <i v-if="answered && i===selectedAnswer && i!==currentQuestion.correct" class="fas fa-times ms-auto text-danger"></i>
            </button>
          </div>
          <div v-if="showExplainImmediate && answered" class="explanation mt-3 p-3 rounded small">
            <strong>Giải thích:</strong> {{ currentQuestion.explanation }}
          </div>
          <div class="mt-3 d-flex justify-content-between align-items-center">
            <button class="btn btn-secondary btn-sm" :disabled="!answered" @click="nextQuestion">{{ isLast ? 'Kết thúc' : 'Câu tiếp' }}</button>
            <div class="small text-muted">Tiến độ: {{ Math.round(((currentIndex+1)/activeQuestions.length)*100) }}%</div>
          </div>
        </div>
      </transition>
    </div>

    <div v-if="showResult" class="overlay">
      <div class="result-card shadow-lg">
        <h3 class="fw-bold mb-3 text-center">Kết quả</h3>
        <div class="row g-3 mb-3 stats">
          <div class="col-6 col-md-3">
            <div class="stat-box bg-primary-subtle">
              <div class="label">Tổng</div>
              <div class="value">{{ activeQuestions.length }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box bg-success-subtle">
              <div class="label">Đúng</div>
              <div class="value text-success">{{ correctCount }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box bg-danger-subtle">
              <div class="label">Sai</div>
              <div class="value text-danger">{{ wrongCount }}</div>
            </div>
          </div>
          <div class="col-6 col-md-3">
            <div class="stat-box bg-warning-subtle">
              <div class="label">Điểm (%)</div>
              <div class="value">{{ scorePercent }}</div>
            </div>
          </div>
        </div>
        <div class="progress mb-3" style="height:10px;">
          <div class="progress-bar bg-success" role="progressbar" :style="{width: scorePercent+'%'}"></div>
        </div>
        <div class="mb-3 small" v-if="wrongReview.length">
          <details>
            <summary class="cursor-pointer">Xem lại {{ wrongReview.length }} câu sai</summary>
            <ul class="mt-2 small wrong-list">
              <li v-for="w in wrongReview" :key="w.id" class="mb-2">
                <strong>Câu:</strong> {{ w.text }}<br>
                <span class="text-success">Đáp án đúng: {{ w.options[w.correct] }}</span><br>
                <em class="text-muted">{{ w.explanation }}</em>
              </li>
            </ul>
          </details>
        </div>
        <button class="btn btn-success w-100 mb-2" @click="restart"><i class="fas fa-redo me-1"></i>Chơi lại</button>
        <router-link to="/user-kid/game" class="btn btn-outline-secondary w-100"><i class="fas fa-arrow-left me-2"></i>Về danh sách game</router-link>
      </div>
    </div>

    <!-- Quit confirmation -->
    <div v-if="quitConfirm" class="modal d-block" style="background:rgba(0,0,0,0.6);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Thoát quiz?</h5><button class="btn-close" @click="quitConfirm=false"></button></div>
          <div class="modal-body small">Tiến trình hiện tại sẽ mất. Bạn có chắc muốn thoát?</div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="quitConfirm=false">Hủy</button>
            <button class="btn btn-danger" @click="abortQuiz">Thoát</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
<script>
import axios from 'axios';
export default {
  name: 'AnimalQuiz',
  data(){
    return {
      showIntro:true,
      inProgress:false,
      showResult:false,
      quitConfirm:false,
      questionCount:20,
      questionSetChoices:[10,15,20],
      mode:'mixed',
      enableTimer:false,
      showExplainImmediate:true,
      timerHandle:null,
      remainingTime:15,
      fullBank:[],
      activeQuestions:[],
      currentIndex:0,
      correctCount:0,
      wrongCount:0,
      selectedAnswer:null,
      answered:false,
      wrongReview:[],
      loadingBank:false,
      loadError:null
    }
  },
  computed:{
    currentQuestion(){ return this.activeQuestions[this.currentIndex] || {}; },
    isLast(){ return this.currentIndex === this.activeQuestions.length -1; },
    scorePercent(){ if(!this.activeQuestions.length) return 0; return Math.round((this.correctCount/this.activeQuestions.length)*100); }
  },
  mounted(){ this.buildBank(); },
  beforeUnmount(){ this.clearTimer(); },
  methods:{
    async buildBank(){
      this.loadingBank = true; this.loadError=null; this.fullBank=[];
      try {
        // load default mixed with max base (20) for lobby preview
        const r = await axios.get('/api/kid/animal-quiz/questions?mode=mixed&limit=50');
        this.fullBank = r.data.questions;
        if(this.questionCount > this.fullBank.length) this.questionCount = this.fullBank.length;
      } catch(e){ this.loadError = e.response?.data?.message || e.message; }
      finally { this.loadingBank=false; }
    },
    async startQuiz(){
      this.resetCore();
      this.inProgress=false; this.showResult=false; this.showIntro=false;
      // fetch fresh subset according to mode & questionCount
      this.loadingBank = true; this.loadError=null; this.fullBank=[];
      try {
        const r = await axios.get(`/api/kid/animal-quiz/questions?mode=${this.mode}&limit=${this.questionCount}`);
        this.fullBank = r.data.questions;
      } catch(e){ this.loadError = e.response?.data?.message || e.message; }
      finally { this.loadingBank=false; }
      let pool = [...this.fullBank];
      pool.sort(()=> Math.random()-0.5);
      const picked = pool.slice(0,this.questionCount);
      const shuffled = picked.map(orig => {
        const q = { ...orig };
        const pairs = q.options.map((opt, idx)=>({ opt, idx }));
        for(let i=pairs.length-1;i>0;i--){
          const j = Math.floor(Math.random()*(i+1));
          [pairs[i], pairs[j]] = [pairs[j], pairs[i]];
        }
        const originalCorrect = q.correct;
        q.options = pairs.map(p=>p.opt);
        q.correct = pairs.findIndex(p=>p.idx === originalCorrect);
        return q;
      });
      this.activeQuestions = shuffled;
      this.inProgress = !this.loadError;
      if(this.inProgress) this.runTimer();
    },
    runTimer(){
      this.clearTimer();
      if(!this.enableTimer) return;
      this.remainingTime=15;
      this.timerHandle = setInterval(()=>{
        this.remainingTime--;
        if(this.remainingTime<=0){
          this.autoTimeOut();
        }
      },1000);
    },
    clearTimer(){ if(this.timerHandle){ clearInterval(this.timerHandle); this.timerHandle=null; } },
    autoTimeOut(){
      this.clearTimer();
      if(this.answered) return;
      this.answered=true; this.wrongCount++; this.selectedAnswer = null;
      if(!this.showExplainImmediate) this.nextDelayed();
    },
    answer(i){
      if(this.answered) return;
      this.selectedAnswer=i; this.answered=true; this.clearTimer();
      if(i===this.currentQuestion.correct){ this.correctCount++; }
      else { this.wrongCount++; this.wrongReview.push(this.currentQuestion); }
      if(!this.showExplainImmediate){ this.nextDelayed(); }
    },
    nextDelayed(){ setTimeout(()=> this.nextQuestion(), 900); },
    nextQuestion(){
      if(!this.answered) return; // ensure answered
      if(this.isLast){ this.finish(); return; }
      this.currentIndex++; this.answered=false; this.selectedAnswer=null; this.runTimer();
    },
    finish(){ this.inProgress=false; this.showResult=true; this.clearTimer(); },
    restart(){ this.showIntro=true; this.showResult=false; this.inProgress=false; this.resetCore(); },
    abortQuiz(){ this.quitConfirm=false; this.restart(); },
    resetCore(){
      this.correctCount=0; this.wrongCount=0; this.currentIndex=0; this.answered=false; this.selectedAnswer=null; this.wrongReview=[]; this.clearTimer();
    },
    optionClass(i){
      if(!this.answered) return '';
      if(i===this.currentQuestion.correct) return 'correct';
      if(i===this.selectedAnswer && i!==this.currentQuestion.correct) return 'wrong';
      return 'disabled';
    },
    difficultyBadge(d){ return d==='easy'?'bg-success': d==='medium'?'bg-warning text-dark':'bg-danger'; },
    labelDifficulty(d){ return d==='easy'?'Dễ': d==='medium'?'TBình':'Khó'; }
  }
}
</script>
<style scoped>
.animal-quiz-container{ position:relative; min-height:100vh; background:linear-gradient(135deg,#243b55,#141e30); color:#fff; font-family:'Poppins',system-ui,sans-serif; padding:20px; overflow:hidden; }
.overlay{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.65); z-index:50; padding:15px; }
.intro-card,.result-card{ background:rgba(255,255,255,0.08); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.15); padding:25px 22px; border-radius:20px; width:100%; max-width:560px; }
.quiz-wrapper{ max-width:880px; margin:0 auto; padding-top:60px; }
.hud{ position:fixed; top:10px; left:50%; transform:translateX(-50%); display:flex; gap:14px; background:rgba(0,0,0,0.45); padding:8px 22px; border-radius:40px; font-size:13px; align-items:center; flex-wrap:wrap; z-index:30; }
.timer{ background:#0d6efd; padding:3px 10px; border-radius:20px; font-weight:600; }
.timer.danger{ background:#dc3545; animation:pulse 1s infinite; }
@keyframes pulse { 0%,100%{ transform:scale(1);} 50%{ transform:scale(1.08);} }
.question-card{ background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12); border-radius:18px; padding:22px 22px 26px; position:relative; }
.options-list{ display:flex; flex-direction:column; gap:10px; }
.option-item{ display:flex; align-items:center; gap:10px; background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.15); color:#fff; padding:12px 14px; border-radius:14px; text-align:left; cursor:pointer; font-size:14px; transition:.25s; }
.option-item:hover:not(:disabled){ background:rgba(255,255,255,0.18); }
.option-item.correct{ background:rgba(40,167,69,0.18); border-color:rgba(40,167,69,0.6); box-shadow:0 0 0 1px rgba(40,167,69,0.4); }
.option-item.wrong{ background:rgba(220,53,69,0.18); border-color:rgba(220,53,69,0.6); }
.option-item.disabled{ opacity:.55; }
.option-item:disabled{ cursor:default; }
.option-item .prefix{ font-weight:600; width:28px; flex-shrink:0; }
.explanation{ background:rgba(0,0,0,0.4); border:1px solid rgba(255,255,255,0.15); }
.stats .stat-box{ background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.12); border-radius:14px; padding:12px 10px; text-align:center; }
.stats .value{ font-size:20px; font-weight:700; }
.fade-move-enter-active,.fade-move-leave-active{ transition: all .4s cubic-bezier(.4,0,.2,1); }
.fade-move-enter-from{ opacity:0; transform:translateY(20px) scale(.96); }
.fade-move-leave-to{ opacity:0; transform:translateY(-15px) scale(.96); }
.wrong-list{ max-height:200px; overflow:auto; }
@media (max-width:680px){ .hud{ font-size:11px; gap:8px; padding:6px 14px; } .question-card{ padding:18px 16px 22px; } }
</style>
