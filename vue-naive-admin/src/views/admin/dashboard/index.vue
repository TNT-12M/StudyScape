<template>
  <AppPage>
    <n-space vertical size="large">
      <n-alert v-if="loadError" type="warning" closable>{{ loadError }}</n-alert>

      <!-- 顶部统计卡片 -->
      <n-grid :cols="4" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
        <n-gi v-for="item in statCards" :key="item.label" span="4 s:2 m:1">
          <div class="stat-card" :style="{ '--accent': item.color }">
            <div class="stat-icon" :style="{ background: item.color + '20', color: item.color }">
              <span v-html="item.icon" />
            </div>
            <div class="stat-info">
              <div class="stat-label">{{ item.label }}</div>
              <div class="stat-value">{{ item.valueText }}</div>
              <div v-if="item.sub" class="stat-sub">{{ item.sub }}</div>
            </div>
          </div>
        </n-gi>
      </n-grid>

      <!-- 图表区：访问趋势 -->
      <n-card title="近 7 日访问趋势" segmented>
        <template #header-extra>
          <div class="chart-legend">
            <span class="legend-item"><i class="legend-dot" style="background:#3C2ECA" />访问量</span>
            <span class="legend-item"><i class="legend-dot" style="background:#27D2BF" />独立用户</span>
          </div>
        </template>
        <div class="chart-wrap">
          <v-chart v-if="trendRows.length" class="trend-chart" :option="trendOption" autoresize />
          <n-empty v-else-if="!loading" description="暂无趋势数据" />
          <n-skeleton v-else text :round="false" />
        </div>
      </n-card>

      <!-- 下方双栏：题库分布 + 快捷入口 -->
      <n-grid :cols="3" :x-gap="16" responsive="screen" item-responsive>
        <n-gi span="3 m:2">
          <n-card title="题库构成" segmented>
            <div class="chart-wrap chart-wrap--sm">
              <v-chart v-if="subjectData.length" class="subject-chart" :option="subjectOption" autoresize />
              <n-skeleton v-else-if="loading" text :round="false" />
            </div>
          </n-card>
        </n-gi>
        <n-gi span="3 m:1">
          <n-card title="后台功能" segmented>
            <div class="shortcut-grid">
              <div
                v-for="item in shortcuts"
                :key="item.path"
                class="shortcut-item"
                @click="router.push(item.path)"
              >
                <div class="shortcut-icon" :style="{ color: item.color, background: item.color + '18' }">
                  <span v-html="item.icon" />
                </div>
                <span class="shortcut-label">{{ item.label }}</span>
              </div>
            </div>
          </n-card>
        </n-gi>
      </n-grid>
    </n-space>
  </AppPage>
</template>

<script setup>
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, PieChart, BarChart } from 'echarts/charts'
import {
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent,
} from 'echarts/components'
import VChart from 'vue-echarts'
import { phpAdminApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'

use([CanvasRenderer, LineChart, PieChart, BarChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

const router = useRouter()
const session = useSessionStore()
const loading = ref(false)
const loadError = ref('')
const statistics = ref({})

const shortcuts = computed(() => {
  const items = []
  if (session.isRoot) items.push({ label: '用户管理', path: '/admin/users', icon: '👥', color: '#3C2ECA' })
  items.push({ label: '题库管理', path: '/admin/questions', icon: '📚', color: '#FF7A45' })
  if (session.isRoot) items.push({ label: '考试管理', path: '/admin/papers', icon: '📝', color: '#6C5CE7' })
  items.push({ label: '资料管理', path: '/admin/materials', icon: '📁', color: '#27D2BF' })
  items.push({ label: 'OCR 审核', path: '/admin/ocr', icon: '🔍', color: '#F6A623' })
  if (session.isRoot) items.push({ label: '反馈处理', path: '/admin/feedback', icon: '💬', color: '#E8463A' })
  return items
})

const statCards = computed(() => {
  const data = statistics.value
  const today = data.today || {}
  const summary = data.summary || {}
  const uv7d = numberValue(summary.uv_7d ?? data.uv_7d)
  const todayUv = numberValue(today.uv ?? data.today_uv)
  return [
    { label: '用户总数', value: numberValue(data.user_count ?? data.users ?? data.total_users), icon: '👥', color: '#3C2ECA', sub: '累计注册用户' },
    { label: '题目总数', value: numberValue(data.question_count ?? data.questions ?? data.total_questions), icon: '📚', color: '#FF7A45', sub: `${numberValue(data.subject_count ?? data.subjects)} 个科目` },
    { label: '今日访问量', value: todayUv, icon: '📈', color: '#27D2BF', sub: uv7d > 0 ? `近7日 ${uv7d} 次` : '暂无近7日数据' },
    { label: '待处理反馈', value: numberValue(data.feedback_open_count), icon: '💬', color: '#E8463A', sub: '需要及时回复' },
  ].map(item => ({
    ...item,
    valueText: item.value >= 10000 ? (item.value / 10000).toFixed(1) + 'w' : item.value.toLocaleString(),
  }))
})

const trendRows = computed(() => normalizeTrend(statistics.value))

// 访问趋势折线图配置
const trendOption = computed(() => {
  const dates = trendRows.value.map(item => item.date.slice(5)) // MM-DD
  const visits = trendRows.value.map(item => item.visits)
  const users = trendRows.value.map(item => item.users)
  return {
    grid: { left: 40, right: 20, top: 30, bottom: 30 },
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(23, 23, 23, 0.92)',
      borderWidth: 0,
      textStyle: { color: '#fff', fontSize: 12 },
      padding: [10, 14],
    },
    legend: { show: false },
    xAxis: {
      type: 'category',
      data: dates,
      boundaryGap: false,
      axisLine: { lineStyle: { color: 'rgba(23, 23, 23, 0.12)' } },
      axisLabel: { color: 'var(--n-text-color-3)', fontSize: 12 },
      axisTick: { show: false },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: 'rgba(23, 23, 23, 0.06)', type: 'dashed' } },
      axisLabel: { color: 'var(--n-text-color-3)', fontSize: 12 },
      axisLine: { show: false },
      axisTick: { show: false },
    },
    series: [
      {
        name: '访问量',
        type: 'line',
        data: visits,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        showSymbol: false,
        lineStyle: { width: 2.5, color: '#3C2ECA' },
        itemStyle: { color: '#3C2ECA', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: {
            type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(60, 46, 202, 0.22)' },
              { offset: 1, color: 'rgba(60, 46, 202, 0.02)' },
            ],
          },
        },
        emphasis: { focus: 'series' },
      },
      {
        name: '独立用户',
        type: 'line',
        data: users,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        showSymbol: false,
        lineStyle: { width: 2.5, color: '#27D2BF' },
        itemStyle: { color: '#27D2BF', borderWidth: 2, borderColor: '#fff' },
        areaStyle: {
          color: {
            type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(39, 210, 191, 0.18)' },
              { offset: 1, color: 'rgba(39, 210, 191, 0.02)' },
            ],
          },
        },
        emphasis: { focus: 'series' },
      },
    ],
  }
})

// 科目分布数据
const subjectData = computed(() => {
  const bySubject = statistics.value.by_subject ?? statistics.value.subject_stats ?? []
  if (Array.isArray(bySubject) && bySubject.length) {
    return bySubject.map(item => ({
      name: item.subject ?? item.name ?? '未分类',
      value: numberValue(item.count ?? item.total ?? item.value),
    }))
  }
  // 没有数据时给个示例结构，让图表不空
  const total = numberValue(statistics.value.question_count ?? statistics.value.questions)
  if (total > 0) return [{ name: '题目总数', value: total }]
  return []
})

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
  color: ['#3C2ECA', '#FF7A45', '#27D2BF', '#F6A623', '#6C5CE7', '#E8463A', '#1DC981', '#9B59B6'],
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

<style scoped>
.stat-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 16px;
  background: var(--n-color);
  border-radius: 12px;
  border: 1px solid var(--n-border-color);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 3px;
  height: 100%;
  background: var(--accent);
  border-radius: 0 4px 4px 0;
}
.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}
.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-size: 22px;
  flex-shrink: 0;
}
.stat-info { flex: 1; min-width: 0; }
.stat-label {
  font-size: 13px;
  color: var(--n-text-color-3);
  margin-bottom: 4px;
}
.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: var(--n-text-color);
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}
.stat-sub {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin-top: 4px;
}

.chart-legend {
  display: flex;
  gap: 16px;
  align-items: center;
}
.legend-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: var(--n-text-color-2);
}
.legend-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: inline-block;
}

.chart-wrap {
  width: 100%;
}
.trend-chart {
  width: 100%;
  height: 320px;
}
.chart-wrap--sm .subject-chart {
  width: 100%;
  height: 280px;
}

.shortcut-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}
.shortcut-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: background-color 0.15s ease, transform 0.15s ease;
  border: 1px solid var(--n-border-color);
}
.shortcut-item:hover {
  background: var(--n-color-hover);
  transform: translateY(-1px);
}
.shortcut-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  font-size: 18px;
  flex-shrink: 0;
}
.shortcut-label {
  font-size: 13px;
  color: var(--n-text-color-2);
  font-weight: 500;
}

@media (max-width: 640px) {
  .trend-chart { height: 240px; }
  .chart-wrap--sm .subject-chart { height: 240px; }
  .stat-value { font-size: 20px; }
}
</style>
