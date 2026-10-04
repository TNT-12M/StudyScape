<template>
  <AppPage>
    <n-space vertical size="large">
      <n-alert v-if="loadError" type="warning" closable>{{ loadError }}</n-alert>
      <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
        <n-gi v-for="item in statCards" :key="item.label" span="4 s:2 m:1">
          <n-card size="small"><n-statistic :label="item.label" :value="item.value" /></n-card>
        </n-gi>
      </n-grid>
      <n-card title="近 7 日访问趋势" segmented>
        <n-data-table :columns="trendColumns" :data="trendRows" :loading="loading" :pagination="false" />
        <n-empty v-if="!loading && !trendRows.length" description="暂无趋势数据" />
      </n-card>
      <n-card title="后台功能" segmented>
        <n-space wrap>
          <n-button v-for="item in shortcuts" :key="item.path" @click="router.push(item.path)">{{ item.label }}</n-button>
        </n-space>
      </n-card>
    </n-space>
  </AppPage>
</template>

<script setup>
import { phpAdminApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'

const router = useRouter()
const session = useSessionStore()
const loading = ref(false)
const loadError = ref('')
const statistics = ref({})
const shortcuts = computed(() => [
  ...(session.isRoot ? [{ label: '用户管理', path: '/admin/users' }] : []),
  { label: '题库管理', path: '/admin/questions' },
  ...(session.isRoot ? [{ label: '考试管理', path: '/admin/papers' }] : []),
  { label: '资料管理', path: '/admin/materials' },
  ...(session.isRoot ? [{ label: '反馈处理', path: '/admin/feedback' }] : []),
])

const statCards = computed(() => {
  const data = statistics.value
  const today = data.today || {}
  const summary = data.summary || {}
  return [
    { label: '用户总数', value: numberValue(data.user_count ?? data.users ?? data.total_users) },
    { label: '题目总数', value: numberValue(data.question_count ?? data.questions ?? data.total_questions) },
    { label: '科目数量', value: numberValue(data.subject_count ?? data.subjects ?? data.total_subjects) },
    { label: '已发布试卷', value: numberValue(data.published_paper_count ?? data.paper_count ?? data.papers ?? data.total_papers) },
    { label: '资料数量', value: numberValue(data.material_count ?? data.materials ?? data.total_materials) },
    { label: '资料下载量', value: numberValue(data.download_count ?? data.downloads ?? data.total_downloads) },
    { label: '今日访问量', value: numberValue(today.uv ?? data.today_uv) },
    { label: '近 7 日访问量', value: numberValue(summary.uv_7d ?? data.uv_7d) },
    { label: '待处理反馈', value: numberValue(data.feedback_open_count) },
  ]
})

const trendRows = computed(() => normalizeTrend(statistics.value))
const trendColumns = [
  { title: '日期', key: 'date' },
  { title: '访问量', key: 'visits' },
  { title: '独立用户', key: 'users' },
]

function numberValue(value) {
  if (Array.isArray(value) || typeof value === 'object') return 0
  return Number(value) || 0
}

function normalizeTrend(data) {
  const source = data.trend ?? data.access?.trend ?? data.recent_7_days ?? data.last_7_days ?? data.daily ?? []
  if (Array.isArray(source)) {
    return source.map((item, index) => ({
      date: item.date ?? item.day ?? item.label ?? `第 ${index + 1} 天`,
      visits: numberValue(item.uv ?? item.visits ?? item.visit_count ?? item.count ?? item.value),
      users: numberValue(item.users ?? item.user_count ?? item.unique_users ?? item.uv),
    }))
  }
  if (source && typeof source === 'object') {
    return Object.entries(source).map(([date, value]) => ({
      date,
      visits: numberValue(value?.visits ?? value?.count ?? value),
      users: numberValue(value?.users ?? value?.user_count),
    }))
  }
  return []
}

async function loadStatistics() {
  loading.value = true
  loadError.value = ''
  try {
    const result = await phpAdminApi.accessStats()
    statistics.value = result.data?.statistics || result.data?.stats || result.data || {}
  }
  catch (error) {
    loadError.value = error.message || '统计数据加载失败'
  }
  finally {
    loading.value = false
  }
}

onMounted(loadStatistics)
</script>
