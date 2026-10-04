<template>
  <AppPage show-footer>
    <div class="hero"><div><p class="eyebrow">学习中心</p><h1>欢迎回来，{{ session.user?.username }}</h1><p class="muted">继续完成今天的练习，稳步积累每一次进步。</p></div><n-button type="primary" size="large" @click="router.push('/app/practice')">开始刷题</n-button></div>
    <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
      <n-gi v-for="item in stats" :key="item.label" span="4 m:2 l:1"><n-card size="small"><div class="stat-label">{{ item.label }}</div><div class="stat-value">{{ item.value }}</div></n-card></n-gi>
    </n-grid>
    <n-grid class="mt-16" :cols="2" :x-gap="16" responsive="screen" item-responsive>
      <n-gi span="2 l:1"><n-card title="公开概览" segmented><n-list v-if="overview.subjects?.length"><n-list-item v-for="subject in overview.subjects" :key="subject">{{ subject }}</n-list-item></n-list><n-empty v-else description="暂无题库数据" /></n-card></n-gi>
      <n-gi span="2 l:1"><n-card title="最近发布的试卷" segmented><n-list v-if="overview.published_papers?.length"><n-list-item v-for="paper in overview.published_papers" :key="paper.id"><div><b>{{ paper.name }}</b><div class="muted">{{ paper.question_count }} 题 · {{ paper.duration_minutes }} 分钟</div></div><n-button size="small" @click="router.push('/app/papers')">查看</n-button></n-list-item></n-list><n-empty v-else description="暂无已发布试卷" /></n-card></n-gi>
    </n-grid>
  </AppPage>
</template>

<script setup>
import { phpPublicApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'

const router = useRouter()
const session = useSessionStore()
const overview = reactive({ stats: {}, subjects: [], published_papers: [] })
const stats = computed(() => [
  { label: '题库题目', value: overview.stats.question_count || 0 },
  { label: '科目数量', value: overview.stats.subject_count || 0 },
  { label: '已发布试卷', value: overview.stats.published_paper_count || 0 },
  { label: '学习资料', value: overview.stats.material_count || 0 },
])

onMounted(async () => {
  try { Object.assign(overview, (await phpPublicApi.overview()).data || {}) }
  catch (error) { $message.error(error.message || '概览加载失败') }
})
</script>

<style scoped>
.hero { display: flex; justify-content: space-between; align-items: center; gap: 20px; padding: 28px; margin-bottom: 20px; border-radius: 12px; background: linear-gradient(120deg, #e9f1ff, #f5fbff); }.eyebrow { margin: 0 0 8px; color: #3b82f6; font-size: 13px; }.hero h1 { margin: 0; font-size: 28px; }.muted { color: var(--n-text-color-3); font-size: 13px; }.stat-label { color: var(--n-text-color-3); font-size: 13px; }.stat-value { margin-top: 8px; font-size: 28px; font-weight: 700; color: var(--n-primary-color); }.mt-16 { margin-top: 16px; }
</style>
