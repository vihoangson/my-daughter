<template>
  <div class="family-blog-editor">
    <Breadcrumbs />
    <div class="top-bar">
      <h2 class="title">{{ isEdit ? 'Sửa bài viết' : 'Bài viết mới' }}</h2>
      <div class="actions">
        <router-link :to="{name:'ParentFamilyBlogList'}" class="btn ghost">← Danh sách</router-link>
        <button class="btn" @click="save" :disabled="saving">{{ saving? 'Đang lưu...' : 'Lưu' }}</button>
      </div>
    </div>

    <form class="form" @submit.prevent="save">
      <label class="row">
        <span>Tiêu đề</span>
        <input v-model="form.title" required maxlength="180" />
      </label>
      <div class="grid-3">
        <label class="row">
          <span>Trạng thái</span>
          <select v-model="form.status">
            <option value="draft">Nháp</option>
            <option value="published">Xuất bản</option>
            <option value="archived">Lưu trữ</option>
          </select>
        </label>
        <label class="row">
          <span>Quyền xem</span>
          <select v-model="form.visibility">
            <option value="members">Thành viên</option>
            <option value="public">Công khai</option>
            <option value="private">Riêng tư</option>
          </select>
        </label>
        <label class="row chk">
          <span>Ghim</span>
          <input type="checkbox" v-model="form.pinned" />
        </label>
      </div>
      <label class="row">
        <span>Tags (phân tách bằng dấu phẩy)</span>
        <input v-model="tagInput" placeholder="ví dụ: sinh hoạt, cuối tuần" />
      </label>

      <!-- Editor / Preview Tabs -->
      <div class="editor-tabs">
        <button type="button" class="tab-btn" :class="{active: activeTab==='edit'}" @click="switchTab('edit')">Editor</button>
        <button type="button" class="tab-btn" :class="{active: activeTab==='preview'}" @click="switchTab('preview')">Preview</button>
      </div>

      <div class="editor-surface" v-if="activeTab==='edit'">
        <label class="row no-top-gap">
          <span class="sr-only">Nội dung</span>
          <textarea
            v-model="form.content"
            rows="14"
            placeholder="Viết nội dung... (Dán hoặc kéo thả ảnh vào đây)"
            ref="contentRef"
            @paste="onPaste"
            @dragover.prevent
            @drop.prevent="onDrop"
          />
          <small class="hint">Hỗ trợ: Dán ảnh hoặc kéo thả. Ảnh resize ≤ 400px. Chèn Markdown ![alt](url)</small>
        </label>
      </div>
      <div class="preview-surface" v-else>
        <div v-if="!form.content.trim()" class="preview-empty">(Chưa có nội dung để hiển thị)</div>
        <div v-else class="markdown-body" v-html="previewHtml"></div>
      </div>

      <div class="bottom-actions">
        <button type="submit" class="btn" :disabled="saving">{{ saving? 'Đang lưu...' : 'Lưu' }}</button>
        <button type="button" class="btn ghost" @click="toList">Huỷ</button>
      </div>
    </form>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>

    <div class="upload-list">
      <div v-for="item in uploadingImages" :key="item.id" class="upload-item" :class="{ err: item.err }">
        <div class="progress-bar">
          <span :style="{ width: item.progress + '%' }"></span>
        </div>
        <div class="file-info">
          <div class="file-name">{{ item.name }}</div>
          <button class="remove-btn" @click="removeUploadTracker(item.id)">×</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const isEdit = computed(()=> !!id);
const saving = ref(false);
const loading = ref(false);
const toast = reactive({ message:'', type:'ok' });
let toastTimer=null;

const form = reactive({
  title:'',
  content:'',
  visibility:'members',
  status:'draft',
  pinned:false,
  tags:[]
});
const tagInput = ref('');
const contentRef = ref(null);
const uploadingImages = ref([]); // {id,name,progress,err}
const activeTab = ref('edit');

function showToast(m,t='ok',ttl=2400){toast.message=m;toast.type=t;clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.message='',ttl);}

async function load(){
  if(!isEdit.value) return;
  loading.value=true;
  try {
    const { data } = await axios.get(`/api/parent/family/blog-posts/${id}`);
    Object.assign(form, {
      title: data.post.title,
      content: data.post.content || '',
      visibility: data.post.visibility,
      status: data.post.status,
      pinned: !!data.post.pinned,
      tags: data.post.tags || []
    });
    tagInput.value = form.tags.join(', ');
  }catch(e){ showToast('Không tải được bài','err'); }
  finally{ loading.value=false; }
}

function prepare(){
  form.tags = tagInput.value.split(',').map(t=>t.trim()).filter(Boolean).slice(0,25);
}

async function save(){
  if(!form.title.trim()) { showToast('Tiêu đề bắt buộc','err'); return; }
  prepare();
  saving.value=true;
  try {
    const payload = { ...form, tags: form.tags };
    let res;
    if(isEdit.value){
      res = await axios.put(`/api/parent/family/blog-posts/${id}`, payload);
      showToast('Đã cập nhật');
    } else {
      res = await axios.post('/api/parent/family/blog-posts', payload);
      showToast('Đã tạo');
      router.replace({ name:'ParentFamilyBlogEdit', params:{ id: res.data.post.id }});
    }
  }catch(e){ showToast(e.response?.data?.message || 'Lỗi lưu','err'); }
  finally{ saving.value=false; }
}
function toList(){ router.push({name:'ParentFamilyBlogList'}); }

function insertAtCursor(text){
  nextTick(()=>{
    const el = contentRef.value;
    if(!el){ form.content += text; return; }
    const start = el.selectionStart || 0;
    const end = el.selectionEnd || 0;
    const before = form.content.slice(0,start);
    const after = form.content.slice(end);
    form.content = before + text + after;
    const pos = start + text.length;
    requestAnimationFrame(()=>{ el.focus(); el.setSelectionRange(pos,pos); });
  });
}

function onPaste(e){
  if(!e.clipboardData) return;
  const items = Array.from(e.clipboardData.items).filter(i=> i.type.startsWith('image/'));
  if(!items.length) return;
  items.forEach(item=>{
    const file = item.getAsFile();
    if(file) processImageFile(file);
  });
}

function onDrop(e){
  if(!e.dataTransfer) return;
  const files = Array.from(e.dataTransfer.files).filter(f=> f.type.startsWith('image/'));
  if(!files.length) return;
  files.forEach(processImageFile);
}

function processImageFile(file){
  const id = Math.random().toString(36).slice(2);
  const up = {id,name:file.name,progress:0,err:false};
  uploadingImages.value.push(up);
  resizeImageToBlob(file,400,400).then(blob=>{
    uploadBlob(blob, file.name, up);
  }).catch(()=>{
    // fallback: upload original if resize fails
    uploadBlob(file, file.name, up);
  });
}

function resizeImageToBlob(file, maxW, maxH){
  return new Promise((resolve,reject)=>{
    const img = new Image();
    const url = URL.createObjectURL(file);
    img.onload = () => {
      let { width, height } = img;
      const ratio = Math.min(1, maxW/width, maxH/height);
      if(ratio < 1){ width = Math.round(width*ratio); height = Math.round(height*ratio); }
      const canvas = document.createElement('canvas');
      canvas.width = width; canvas.height = height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img,0,0,width,height);
      canvas.toBlob(blob=>{ blob ? resolve(blob) : reject(); }, 'image/jpeg', 0.85);
      URL.revokeObjectURL(url);
    };
    img.onerror = ()=>{ URL.revokeObjectURL(url); reject(); };
    img.src = url;
  });
}

async function uploadBlob(blob, originalName, tracker){
  try {
    tracker.progress = 5;
    const ext = originalName.split('.').pop()?.toLowerCase() || 'jpg';
    const file = new File([blob], `resized-${Date.now()}.${ext}`, { type: blob.type || 'image/jpeg' });
    const fd = new FormData();
    fd.append('image', file);
    const { data } = await axios.post('/api/parent/family/blog-images', fd, {
      headers:{ 'Content-Type':'multipart/form-data' },
      onUploadProgress: ev=>{ if(ev.total) tracker.progress = Math.round(ev.loaded/ev.total*90); }
    });
    tracker.progress = 100;
    insertAtCursor(`\n![${originalName}](${data.url})\n`);
    showToast('Đã tải ảnh');
    setTimeout(()=> removeUploadTracker(tracker.id), 1200);
  } catch(e){
    tracker.err = true; tracker.progress=0; showToast('Lỗi upload ảnh','err');
  }
}

function removeUploadTracker(id){
  uploadingImages.value = uploadingImages.value.filter(x=>x.id!==id);
}

function switchTab(tab){ activeTab.value = tab; if(tab==='edit'){ nextTick(()=> contentRef.value && contentRef.value.focus()); } }

// Simple markdown -> HTML (consistent with viewer)
function mdEscapeHtml(str){
  return (str||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));
}
function normalizeImg(u){
  if(!u) return u;
  try { const origin = window.location.origin; if(u.startsWith(origin)) return u.slice(origin.length)||'/'; const dev='http://127.0.0.1:8000'; if(u.startsWith(dev)) return u.slice(dev.length)||'/'; return u; } catch{return u;}
}
function mdToHtml(src){
  if(!src) return '';
  let text = src.replace(/\r\n?/g,'\n');
  const fences=[]; text=text.replace(/```(\w+)?\n([\s\S]*?)```/g,(m,l,c)=>{const i=fences.length; fences.push({l:(l||'').trim(),c}); return `@@F${i}@@`;});
  text = mdEscapeHtml(text);
  for(let i=6;i>=1;i--){ const re=new RegExp(`^${'#'.repeat(i)}\\s+(.+)$`,'gm'); text=text.replace(re,(m,c)=>`<h${i}>${c.trim()}</h${i}>`);}
  text = text.replace(/^>\s?(.*)$/gm,'<blockquote>$1</blockquote>');
  text = text.replace(/^(?:[*-] \s?.+\n?)+/gm, blk=>{const items=blk.trim().split(/\n/).map(l=>l.replace(/^[*-]\s?/,'').trim()).map(x=>`<li>${x}</li>`).join('');return `<ul>${items}</ul>`;});
  text = text.replace(/^(?:\d+\.\s.+\n?)+/gm, blk=>{const items=blk.trim().split(/\n/).map(l=>l.replace(/^\d+\.\s?/,'').trim()).map(x=>`<li>${x}</li>`).join('');return `<ol>${items}</ol>`;});
  text = text.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
  text = text.replace(/(^|[^*])\*(?!\*)([^*]+)\*(?!\*)/g,(m,p,c)=>`${p}<em>${c}</em>`);
  text = text.replace(/`([^`]+)`/g,'<code class="inline-code">$1</code>');
  text = text.replace(/!\[([^\]]*)]\(([^)]+)\)/g,(m,a,u)=>`<img src="${normalizeImg(u.trim())}" alt="${mdEscapeHtml(a)}" class="blog-img" />`);
  text = text.replace(/\[([^\]]+)\]\((https?:[^)\s]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>');
  text = text.split(/\n{2,}/).map(ch=>{const t=ch.trim(); if(!t) return ''; if(/^<\/?(h\d|blockquote|ul|ol|pre|img)/.test(t)) return t; return `<p>${t.replace(/\n/g,'<br/>')}</p>`;}).join('\n');
  text = text.replace(/@@F(\d+)@@/g,(m,i)=>{const f=fences[+i];const cls=f.l?` class=\"language-${f.l}\"`:'';return `<pre><code${cls}>${mdEscapeHtml(f.c)}</code></pre>`;});
  return text;
}
const previewHtml = computed(()=> mdToHtml(form.content));

onMounted(load);
</script>

<style scoped>
.family-blog-editor { padding:1.2rem 1.4rem 2.2rem; }
.top-bar { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1rem; }
.title { margin:0; font-size:1.55rem; }
.actions { display:flex; gap:.6rem; }
.form { display:flex; flex-direction:column; gap:.9rem; max-width:960px; }
.row { display:flex; flex-direction:column; gap:.35rem; font-size:.8rem; font-weight:500; }
.row > span { color:#374151; }
input, select, textarea { padding:.55rem .65rem; border:1px solid #c9d1d9; border-radius:9px; font-size:.85rem; background:#fff; font-family:inherit; resize:vertical; }
textarea { line-height:1.4; }
input:focus, select:focus, textarea:focus { outline:2px solid #2563eb33; border-color:#2563eb; }
.grid-3 { display:grid; gap:.75rem; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); }
.chk { flex-direction:row; align-items:center; }
.chk span { flex:1; }
.bottom-actions { display:flex; gap:.6rem; }
.btn { border:none; background:#2563eb; color:#fff; padding:.55rem .9rem; border-radius:8px; font-size:.8rem; cursor:pointer; font-weight:600; text-decoration:none; }
.btn.ghost { background:#e2e8f0; color:#1e293b; }
.btn.ghost:hover { background:#cbd5e1; }
.btn:disabled { opacity:.55; cursor:default; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1e293b; color:#fff; padding:.55rem .75rem; font-size:.7rem; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
.hint { font-size:.65rem; color:#64748b; margin-top:.2rem; }
.upload-list { margin-top:1rem; display:flex; flex-direction:column; gap:.4rem; max-width:600px; }
.upload-item { display:flex; align-items:center; gap:.6rem; font-size:.7rem; background:#f1f5f9; border:1px solid #d9e0e6; padding:.4rem .6rem; border-radius:6px; }
.upload-item.err { background:#fee2e2; border-color:#fca5a5; }
.progress-bar { flex:1; background:#e2e8f0; height:6px; border-radius:4px; overflow:hidden; }
.progress-bar span { display:block; height:100%; background:#2563eb; transition:width .25s; }
.remove-btn { background:none; border:none; cursor:pointer; font-size:1rem; line-height:1; color:#64748b; }
.remove-btn:hover { color:#b91c1c; }
.editor-tabs { display:flex; gap:.4rem; margin-top:.3rem; }
.tab-btn { background:#f1f5f9; border:1px solid #d6dde3; padding:.45rem .85rem; font-size:.72rem; font-weight:600; border-radius:7px 7px 0 0; cursor:pointer; color:#334155; position:relative; top:1px; }
.tab-btn.active { background:#ffffff; border-bottom-color:#ffffff; box-shadow:0 -1px 3px rgba(0,0,0,.04); }
.tab-btn:not(.active):hover { background:#e2e8f0; }
.editor-surface, .preview-surface { border:1px solid #d6dde3; border-radius:0 10px 10px 10px; padding:.6rem .65rem .9rem; background:#ffffff; }
.editor-surface textarea { width:100%; border:1px solid #c9d1d9; border-radius:8px; background:#fff; font-family:inherit; }
.preview-surface { min-height:260px; font-size:.9rem; }
.preview-empty { font-size:.75rem; color:#64748b; font-style:italic; }
.markdown-body h1{ font-size:1.5rem; margin:1rem 0 .6rem; border-bottom:1px solid #e2e8f0; padding-bottom:.25rem; }
.markdown-body h2{ font-size:1.25rem; margin:1rem 0 .5rem; border-bottom:1px solid #e2e8f0; padding-bottom:.2rem; }
.markdown-body h3{ font-size:1.1rem; margin:.9rem 0 .4rem; }
.markdown-body blockquote{ margin:.8rem 0; padding:.55rem .8rem; background:#f1f5f9; border-left:4px solid #2563eb; border-radius:4px; color:#334155; }
.markdown-body ul, .markdown-body ol { margin:.6rem 0 .8rem .5rem; padding-left:1.05rem; }
.markdown-body li { margin:.25rem 0; }
.markdown-body pre { background:#1e293b; color:#f1f5f9; padding:.8rem .95rem; border-radius:9px; overflow:auto; font-size:.75rem; line-height:1.35; }
.markdown-body code.inline-code { background:#e2e8f0; padding:.15rem .4rem; border-radius:4px; font-size:.7rem; }
.markdown-body a { color:#2563eb; text-decoration:none; }
.markdown-body a:hover { text-decoration:underline; }
.markdown-body img.blog-img { max-width:100%; height:auto; display:block; margin:.6rem auto; border:1px solid #e2e8f0; border-radius:8px; background:#fff; }
.no-top-gap { margin-top:0; }
.sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
</style>
