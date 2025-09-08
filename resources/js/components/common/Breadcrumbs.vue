<template>
  <nav class="breadcrumbs" aria-label="Breadcrumb">
    <ol>
      <li v-for="(c,i) in crumbs" :key="c.to || c.label" :class="{active: i===crumbs.length-1}">
        <router-link v-if="c.to && i!==crumbs.length-1" :to="c.to">{{ c.label }}</router-link>
        <span v-else>{{ c.label }}</span>
      </li>
    </ol>
  </nav>
</template>
<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

// Build crumbs based on path segments and route meta.breadcrumb
const crumbs = computed(()=>{
  const path = route.path; // e.g. /parent/add-points
  const segments = path.split('/').filter(Boolean); // ['parent','add-points']
  const built = [];
  let accum = '';
  segments.forEach((seg, idx) => {
    accum += '/' + seg;
    const match = router.getRoutes().find(r => r.path === accum);
    if (match) {
      built.push({
        label: match.meta?.breadcrumb || match.name || seg,
        to: idx < segments.length -1 ? match.path : null
      });
    }
  });
  // If no crumbs fallback
  if (!built.length) return [{ label: 'Trang chủ', to: '/' }];
  return built;
});
</script>
<style scoped>
.breadcrumbs { font-size:.72rem; margin:0 0 .75rem; }
.breadcrumbs ol { list-style:none; padding:0; margin:0; display:flex; flex-wrap:wrap; gap:.4rem; }
.breadcrumbs li { display:flex; align-items:center; gap:.4rem; color:#555; }
.breadcrumbs li:not(:last-child)::after { content:'›'; font-size:.65rem; color:#999; }
.breadcrumbs a { text-decoration:none; color:#2563eb; font-weight:600; }
.breadcrumbs a:hover { text-decoration:underline; }
.breadcrumbs li.active { color:#111; font-weight:600; }
@media (prefers-color-scheme: dark) {
  .breadcrumbs li { color:#9ca3af; }
  .breadcrumbs li.active { color:#e5e7eb; }
  .breadcrumbs a { color:#60a5fa; }
}
</style>

