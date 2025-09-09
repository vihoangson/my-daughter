<template>
  <div class="family-blog-view" v-if="loaded">
    <Breadcrumbs />
    <div class="top-bar">
      <h2 class="title">{{ post.title }}</h2>
      <div class="actions" v-if="canManage">
        <router-link :to="{name:'ParentFamilyBlogList'}" class="btn ghost">← Danh sách</router-link>
        <router-link :to="{name:'ParentFamilyBlogEdit', params:{id:post.id}}" class="btn warn">Sửa</router-link>
        <button class="btn" @click="togglePublish" :disabled="busy">{{ post.status==='published' ? 'Đưa về nháp' : 'Xuất bản' }}</button>
        <button class="btn danger" @click="remove" :disabled="busy">Xoá</button>
      </div>
      <div class="actions" v-else>
        <router-link :to="{name:'Homepage'}" class="btn ghost">Trang chủ</router-link>
      </div>
    </div>

    <div class="meta-line">
      <span class="badge st" :class="post.status">{{ statusLabel(post.status) }}</span>
      <span class="badge vis" :class="post.visibility">{{ visibilityLabel(post.visibility) }}</span>
      <span v-if="post.pinned" class="badge pin">📌 Ghim</span>
      <span v-if="post.published_at" class="pub-date">Xuất bản: {{ formatDate(post.published_at) }}</span>
      <span class="author" v-if="post.author">Tác giả: {{ post.author.name }}</span>
    </div>

    <div v-if="post.tags && post.tags.length" class="tags">
      <span class="tag" v-for="t in post.tags" :key="t">#{{ t }}</span>
    </div>

    <article class="content markdown-body" v-html="renderedContent"></article>

    <div class="foot-actions" v-if="canManage">
      <router-link :to="{name:'ParentFamilyBlogEdit', params:{id:post.id}}" class="btn warn">✏️ Sửa</router-link>
      <button class="btn" @click="togglePin" :disabled="busy">{{ post.pinned? 'Bỏ ghim' : 'Ghim bài' }}</button>
    </div>

    <div v-if="toast.message" class="toast" :class="toast.type">{{ toast.message }}</div>
  </div>
  <div v-else class="family-blog-view loading-wrap">
    <Breadcrumbs />
    <p class="loading">Đang tải...</p>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import Breadcrumbs from '../common/Breadcrumbs.vue';

const route = useRoute();
const router = useRouter();
const id = route.params.id;
const post = reactive({});
const loaded = ref(false);
const busy = ref(false);
const toast = reactive({ message:'', type:'ok' });
const canManage = ref(false);
let toastTimer=null;

function showToast(m,t='ok',ttl=2400){toast.message=m;toast.type=t;clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.message='',ttl);}
function formatDate(dt){ return new Date(dt).toLocaleString('vi-VN',{hour12:false}); }
function statusLabel(s){ return {draft:'Nháp', published:'Đã xuất bản', archived:'Lưu trữ'}[s]||s; }
function visibilityLabel(v){ return {public:'Công khai', members:'Thành viên', private:'Riêng tư'}[v]||v; }
function mdEscapeHtml(str){
  return (str||'').replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;' }[c]));
}
function normalizeImageUrl(u){
  if(!u) return u;
  try {
    const origin = window.location.origin;
    if(u.startsWith(origin)) return u.slice(origin.length) || '/';
    // Also strip common localhost dev base
    const dev = 'http://127.0.0.1:8000';
    if(u.startsWith(dev)) return u.slice(dev.length) || '/';
    return u;
  } catch { return u; }
}
function markdownToHtml(src){
  if(!src) return '';
  let text = src.replace(/\r\n?/g,'\n');
  // Extract fenced code blocks
  const fences = [];
  text = text.replace(/```(\w+)?\n([\s\S]*?)```/g,(m,lang,code)=>{
    const idx = fences.length;
    fences.push({lang: (lang||'').trim(), code});
    return `@@CODE_BLOCK_${idx}@@`;
  });
  // Escape
  text = mdEscapeHtml(text);
  // Restore fenced code blocks later
  // Headings ###### to #
  for(let i=6;i>=1;i--){
    const re = new RegExp(`^${'#'.repeat(i)}\\s+(.+)$`,'gm');
    text = text.replace(re,(m,c)=>`<h${i}>${c.trim()}</h${i}>`);
  }
  // Blockquotes
  text = text.replace(/^>\s?(.*)$/gm,'<blockquote>$1</blockquote>');
  // Lists (unordered)
  // Convert consecutive lines starting with * or - into <ul>
  text = text.replace(/^(?:[*-] \s?.+\n?)+/gm, block=>{
    const items = block.trim().split(/\n/).map(l=> l.replace(/^[*-]\s?/,'').trim()).map(it=>`<li>${it}</li>`).join('');
    return `<ul>${items}</ul>`;
  });
  // Ordered lists
  text = text.replace(/^(?:\d+\.\s.+\n?)+/gm, block=>{
    const items = block.trim().split(/\n/).map(l=> l.replace(/^\d+\.\s?/,'').trim()).map(it=>`<li>${it}</li>`).join('');
    return `<ol>${items}</ol>`;
  });
  // Bold **text**
  text = text.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>');
  // Italic *text*
  text = text.replace(/(^|[^*])\*(?!\*)([^*]+)\*(?!\*)/g,(m,prefix,content)=> `${prefix}<em>${content}</em>`);
  // Inline code `code`
  text = text.replace(/`([^`]+)`/g,'<code class="inline-code">$1</code>');
  // Images ![alt](url)
  text = text.replace(/!\[([^\]]*)]\(([^)]+)\)/g,(m,alt,url)=>{
    const cleanUrl = normalizeImageUrl(url.trim());
    return `<img src="${cleanUrl}" alt="${mdEscapeHtml(alt)}" class="blog-img" />`;
  });
  // Links [text](url)
  text = text.replace(/\[([^\]]+)\]\((https?:[^)\s]+)\)/g,'<a href="$2" target="_blank" rel="noopener">$1</a>');
  // Paragraphs: wrap standalone lines not already block-level
  const blockTags = ['h1','h2','h3','h4','h5','h6','blockquote','ul','ol','pre','img'];
  text = text.split(/\n{2,}/).map(chunk=>{
    const trimmed = chunk.trim();
    if(!trimmed) return '';
    if(/^<\/?(h\d|blockquote|ul|ol|pre|img)/.test(trimmed)) return trimmed;
    return `<p>${trimmed.replace(/\n/g,'<br/>')}</p>`;
  }).join('\n');
  // Restore code blocks
  text = text.replace(/@@CODE_BLOCK_(\d+)@@/g,(m,i)=>{
    const f = fences[+i];
    const langClass = f.lang? ` class="language-${f.lang}"` : '';
    return `<pre><code${langClass}>${mdEscapeHtml(f.code)}</code></pre>`;
  });
  return text;
}
const renderedContent = computed(()=> markdownToHtml(post.content));

async function load(){
  const token = localStorage.getItem('token');
  let authTried = false;
  if(token){
    try {
      authTried = true;
      const { data } = await axios.get(`/api/parent/family/blog-posts/${id}`);
      Object.assign(post, data.post);
      canManage.value = true;
      loaded.value = true;
      return;
    } catch(e){
      // fall through to public attempt
    }
  }
  // Public attempt
  try {
    const { data } = await axios.get(`/api/public/blog-posts/${id}`);
    Object.assign(post, data.post);
    canManage.value = false;
    loaded.value = true;
  } catch(e){
    showToast('Không tìm thấy hoặc không công khai','err');
    setTimeout(()=> router.push({name:'Homepage'}), 1600);
  }
}

async function togglePublish(){
  if(!canManage.value) return;
  busy.value=true;
  try {
    const target = post.status==='published' ? 'draft' : 'published';
    await axios.put(`/api/parent/family/blog-posts/${post.id}`, { status: target });
    post.status = target;
    if(target==='published' && !post.published_at) post.published_at = new Date().toISOString();
    showToast(target==='published'?'Đ�� xuất bản':'Đưa về nháp');
  } catch(e){ showToast('Lỗi','err'); } finally { busy.value=false; }
}
async function togglePin(){
  if(!canManage.value) return;
  busy.value=true;
  try { await axios.put(`/api/parent/family/blog-posts/${post.id}`, { pinned: !post.pinned }); post.pinned=!post.pinned; showToast(post.pinned?'Đã ghim':'Đã bỏ ghim'); }
  catch(e){ showToast('Lỗi','err'); } finally { busy.value=false; }
}
async function remove(){
  if(!canManage.value) return;
  if(!confirm('Xoá bài viết này?')) return;
  busy.value=true;
  try { await axios.delete(`/api/parent/family/blog-posts/${post.id}`); showToast('Đã xoá'); setTimeout(()=> router.push({name:'ParentFamilyBlogList'}),800); }
  catch(e){ showToast('Lỗi xoá','err'); } finally { busy.value=false; }
}

onMounted(load);
</script>

<style scoped>
.family-blog-view { padding:1.2rem 1.4rem 2.3rem; max-width:960px; }
.top-bar { display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.title { margin:0; font-size:1.75rem; line-height:1.15; }
.actions { display:flex; flex-wrap:wrap; gap:.55rem; }
.meta-line { display:flex; flex-wrap:wrap; gap:.5rem; margin:1rem 0 .4rem; font-size:.65rem; align-items:center; }
.badge { padding:.25rem .55rem; border-radius:999px; background:#e2e8f0; font-weight:600; letter-spacing:.5px; text-transform:uppercase; font-size:.58rem; }
.badge.st.published { background:#d1fae5; color:#065f46; }
.badge.st.draft { background:#fef3c7; color:#92400e; }
.badge.st.archived { background:#e5e7eb; color:#374151; }
.badge.vis.public { background:#bae6fd; color:#05507a; }
.badge.vis.members { background:#e0f2fe; color:#075985; }
.badge.vis.private { background:#f3e8ff; color:#6b21a8; }
.badge.pin { background:#fde68a; color:#92400e; }
.pub-date, .author { font-size:.65rem; color:#475569; }
.tags { display:flex; flex-wrap:wrap; gap:.35rem; margin-bottom:.9rem; }
.tag { background:#f1f5f9; padding:.3rem .55rem; border-radius:999px; font-size:.6rem; font-weight:600; }
.content { background:#fff; border:1px solid #d9e0e6; border-radius:16px; padding:1.1rem 1.2rem; line-height:1.55; font-size:.92rem; box-shadow:0 1px 2px rgba(0,0,0,.05); }
.content img.blog-img { max-width:100%; height:auto; display:block; margin:.75rem auto; border:1px solid #e2e8f0; border-radius:8px; background:#fff; }
.content.markdown-body h1{ font-size:1.55rem; border-bottom:1px solid #e2e8f0; padding-bottom:.25rem; }
.content.markdown-body h2{ font-size:1.35rem; border-bottom:1px solid #e2e8f0; padding-bottom:.2rem; margin-top:1.4rem; }
.content.markdown-body h3{ font-size:1.15rem; margin-top:1.2rem; }
.content.markdown-body blockquote{ margin:.8rem 0; padding:.6rem .9rem; background:#f1f5f9; border-left:4px solid #2563eb; border-radius:4px; color:#334155; }
.content.markdown-body ul, .content.markdown-body ol{ margin:.75rem 0 .9rem .5rem; padding-left:1.1rem; }
.content.markdown-body li{ margin:.25rem 0; }
.content.markdown-body pre{ background:#1e293b; color:#f1f5f9; padding:.85rem 1rem; border-radius:10px; overflow:auto; font-size:.8rem; line-height:1.4; margin:1rem 0; }
.content.markdown-body code.inline-code{ background:#e2e8f0; padding:.15rem .4rem; border-radius:5px; font-size:.75rem; }
.content.markdown-body pre code{ background:transparent; padding:0; }
.content.markdown-body a{ color:#2563eb; text-decoration:none; }
.content.markdown-body a:hover{ text-decoration:underline; }
.foot-actions { display:flex; gap:.6rem; margin-top:1rem; }
.btn { border:none; background:#2563eb; color:#fff; padding:.55rem .9rem; border-radius:8px; font-size:.75rem; cursor:pointer; font-weight:600; text-decoration:none; }
.btn.warn { background:#f59e0b; }
.btn.ghost { background:#e2e8f0; color:#1e293b; }
.btn.ghost:hover { background:#cbd5e1; }
.btn.danger { background:#dc2626; }
.btn:disabled { opacity:.55; cursor:default; }
.loading-wrap { padding:1.2rem 1.4rem; }
.loading { color:#475569; }
.toast { position:fixed; bottom:1rem; right:1rem; background:#1e293b; color:#fff; padding:.55rem .75rem; font-size:.7rem; border-radius:7px; box-shadow:0 2px 8px rgba(0,0,0,.25); }
.toast.err { background:#b91c1c; }
.toast.ok { background:#166534; }
</style>
