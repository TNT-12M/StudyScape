<template>
  <AppPage show-footer>
    <div class="hero">
      <div>
        <p class="eyebrow">学习中心</p>
        <h1>欢迎回来，{{ session.user?.username }}</h1>
        <p class="muted">继续完成今天的练习，稳步积累每一次进步。</p>
      </div>
      <n-button type="primary" size="large" @click="router.push('/app/practice')">开始刷题</n-button>
    </div>

    <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
      <n-gi v-for="item in stats" :key="item.label" span="4 m:2 l:1">
        <n-card size="small">
          <div class="stat-label">{{ item.label }}</div>
          <div class="stat-value">{{ item.value }}</div>
        </n-card>
      </n-gi>
    </n-grid>

    <n-grid class="mt-16" :cols="2" :x-gap="16" responsive="screen" item-responsive>
      <n-gi span="2 l:1">
        <n-card title="题库分布" segmented>
          <div v-if="subjectData.length" class="chart-wrap">
            <v-chart class="subject-chart" :option="subjectOption" autoresize />
          </div>
          <n-empty v-else description="暂无题库数据" />
        </n-card>
      </n-gi>
      <n-gi span="2 l:1">
        <n-card title="资料分布" segmented>
          <div v-if="materialData.length" class="chart-wrap">
            <v-chart class="subject-chart" :option="materialOption" autoresize />
          </div>
          <n-empty v-else description="暂无资料数据" />
        </n-card>
      </n-gi>
    </n-grid>

    <n-card class="mt-16" title="最近发布的试卷" segmented>
      <n-list v-if="overview.published_papers?.length">
        <n-list-item v-for="paper in overview.published_papers" :key="paper.id">
          <div>
            <b>{{ paper.name }}</b>
            <div class="muted">{{ paper.question_count }} 题 · {{ paper.duration_minutes }} 分钟</div>
          </div>
          <n-button size="small" @click="router.push('/app/papers')">查看</n-button>
        </n-list-item>
      </n-list>
      <n-empty v-else description="暂无已发布试卷" />
    </n-card>
  </AppPage>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { PieChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent } from 'echarts/components'
import VChart from 'vue-echarts'
import { phpPublicApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'

use([CanvasRenderer, PieChart, TitleComponent, TooltipComponent, LegendComponent])

const router = useRouter()
const session = useSessionStore()

const overview = reactive({ stats: {}, subjects: [], by_subject: [], published_papers: [], materials: [] })

const stats = computed(() => [
  { label: '题库题目', value: overview.stats.question_count || 0 },
  { label: '科目数量', value: overview.stats.subject_count || 0 },
  { label: '已发布试卷', value: overview.stats.published_paper_count || 0 },
  { label: '学习资料', value: overview.stats.material_count || 0 },
])

const subjectData = computed(() => {
  const list = overview.by_subject || []
  if (Array.isArray(list) && list.length) {
    return list.map(item => ({
      name: item.subject ?? item.name ?? '未分类',
      value: Number(item.count ?? item.total ?? item.value) || 0,
    })).filter(item => item.value > 0)
  }
  return []
})

const materialData = computed(() => {
  const list = overview.materials || []
  if (Array.isArray(list) && list.length) {
    return list.map(item => ({
      name: item.subject ?? item.name ?? '未分类',
      value: Number(item.count ?? item.total ?? item.value) || 0,
    })).filter(item => item.value > 0)
  }
  return []
})

const materialOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    backgroundColor: 'rgba(23, 23, 23, 0.92)',
    borderWidth: 0,
    textStyle: { color: '#fff', fontSize: 12 },
    padding: [10, 14],
    formatter: '{b}: {c} 份 ({d}%)',
  },
  legend: {
    type: 'scroll',
    orient: 'vertical',
    right: 10,
    top: 'center',
    textStyle: { color: 'var(--n-text-color-2)', fontSize: 12 },
    itemWidth: 10,
    itemHeight: 10,
    itemGap: 10,
  },
  color: ['#27D2BF', '#FF7A45', '#3C2ECA', '#F6A623', '#6C5CE7', '#E8463A', '#1DC981', '#9B59B6', '#3498DB', '#E67E22'],
  series: [{
    type: 'pie',
    radius: ['55%', '78%'],
    center: ['35%', '50%'],
    avoidLabelOverlap: true,
    itemStyle: {
      borderRadius: 4,
      borderColor: 'var(--n-color)',
      borderWidth: 2,
    },
    label: { show: false },
    emphasis: {
      label: { show: true, fontSize: 14, fontWeight: 600 },
      itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.2)' },
    },
    labelLine: { show: false },
    data: materialData.value,
  }],
}))

const subjectOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    backgroundColor: 'rgba(23, 23, 23, 0.92)',
    borderWidth: 0,
    textStyle: { color: '#fff', fontSize: 12 },
    padding: [10, 14],
    formatter: '{b}: {c} 题 ({d}%)',
  },
  legend: {
    type: 'scroll',
    orient: 'vertical',
    right: 10,
    top: 'center',
    textStyle: { color: 'var(--n-text-color-2)', fontSize: 12 },
    itemWidth: 10,
    itemHeight: 10,
    itemGap: 10,
  },
  color: ['#3C2ECA', '#FF7A45', '#27D2BF', '#F6A623', '#6C5CE7', '#E8463A', '#1DC981', '#9B59B6', '#3498DB', '#E67E22'],
  series: [{
    type: 'pie',
    radius: ['55%', '78%'],
    center: ['35%', '50%'],
    avoidLabelOverlap: true,
    itemStyle: {
      borderRadius: 4,
      borderColor: 'var(--n-color)',
      borderWidth: 2,
    },
    label: { show: false },
    emphasis: {
      label: { show: true, fontSize: 14, fontWeight: 600 },
      itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.2)' },
    },
    labelLine: { show: false },
    data: subjectData.value,
  }],
}))

onMounted(async () => {
  try {
    Object.assign(overview, (await phpPublicApi.overview()).data || {})
  }
  catch (error) { window.$message.error(error.message || '概览加载失败') }
})
</script>

<style scoped>
.hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 28px;
  margin-bottom: 20px;
  border-radius: 12px;
  background: linear-gradient(120deg, #e9f1ff, #f5fbff);
}
.eyebrow { margin: 0 0 8px; color: #3b82f6; font-size: 13px; }
.hero h1 { margin: 0; font-size: 28px; }
.muted { color: var(--n-text-color-3); font-size: 13px; }
.stat-label { color: var(--n-text-color-3); font-size: 13px; }
.stat-value { margin-top: 8px; font-size: 28px; font-weight: 700; color: var(--n-primary-color); }
.mt-16 { margin-top: 16px; }
.chart-wrap { width: 100%; }
.subject-chart { width: 100%; height: 280px; }
@media (max-width: 768px) {
  .hero { flex-direction: column; align-items: flex-start; }
  .subject-chart { height: 240px; }
}
</style>
