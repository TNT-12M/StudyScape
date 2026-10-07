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

      <!-- 题库分布 + 资料分布 -->
      <n-grid :cols="2" :x-gap="16" responsive="screen" item-responsive>
        <n-gi span="2 m:1">
          <n-card title="题库构成" segmented>
            <div class="chart-wrap chart-wrap--sm">
              <v-chart v-if="subjectData.length" class="subject-chart" :option="subjectOption" autoresize />
              <n-skeleton v-else-if="loading" text :round="false" />
            </div>
          </n-card>
        </n-gi>
        <n-gi span="2 m:1">
          <n-card title="资料构成" segmented>
            <div class="chart-wrap chart-wrap--sm">
              <v-chart v-if="materialSubjectData.length" class="subject-chart" :option="materialSubjectOption" autoresize />
              <n-skeleton v-else-if="loading" text :round="false" />
            </div>
          </n-card>
        </n-gi>
      </n-grid>

      <!-- 快捷入口 -->
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

      <!-- 安全监控：异常 IP -->
      <n-card v-if="session.isRoot" title="安全监控 · 异常请求 IP" segmented>
        <template #header-extra>
          <div class="sec-header">
            <span v-if="secStatus.last_scan" class="sec-scan-time">
              上次扫描：{{ formatScanTime(secStatus.last_scan.finished_at || secStatus.last_scan.started_at) }}
              <n-tag v-if="secStatus.last_scan.status === 'running'" size="small" type="info" round>扫描中</n-tag>
              <n-tag v-else-if="secStatus.last_scan.status === 'finished'" size="small" type="success" round>已完成</n-tag>
              <n-tag v-else-if="secStatus.last_scan.status === 'failed'" size="small" type="error" round>失败</n-tag>
            </span>
            <n-button size="small" type="primary" :loading="secScanning" @click="quickScan">
              立即扫描
            </n-button>
            <n-tooltip trigger="hover" placement="bottom">
              <template #trigger>
                <n-button size="small" :loading="secScanningFull" @click="forceScan" quaternary>
                  完整扫描
                </n-button>
              </template>
              Python 模式，含 IP 地理位置查询（较慢）
            </n-tooltip>
            <n-button size="small" :loading="secChecking" @click="runCheck">
              自检
            </n-button>
          </div>
          <n-alert v-if="secStatus.last_scan?.status === 'failed' && secStatus.last_scan.result_info" type="error" :show-icon="true" size="small">
            扫描失败：{{ secStatus.last_scan.result_info }}
          </n-alert>
          <n-alert v-if="checkResult && checkVisible" type="info" :show-icon="true" size="small" @on-close="checkVisible = false">
            <div style="font-size:12px; line-height:1.8;">
              <div>日志文件：{{ checkResult.log_path }} ({{ checkResult.log_exists ? '存在' : '不存在' }}, {{ formatSize(checkResult.log_size) }})</div>
              <div>扫描脚本：{{ checkResult.script_path }} ({{ checkResult.script_exists ? '存在' : '不存在' }})</div>
              <div>数据库：{{ checkResult.db_path }} ({{ checkResult.db_exists ? '存在' : '不存在' }})</div>
              <div>操作系统：{{ checkResult.php_os }} | Python：{{ checkResult.python_version || '未检测到' }}</div>
              <div>popen：{{ checkResult.popen_available ? '可用' : '禁用' }} | shell_exec：{{ checkResult.shell_exec_available ? '可用' : '禁用' }}</div>
            </div>
          </n-alert>
        </template>
        <n-space vertical size="medium">
          <div class="sec-summary">
            <div class="sec-stat sec-stat--warn">
              <div class="sec-stat-value">{{ secStatus.total_abnormal ?? 0 }}</div>
              <div class="sec-stat-label">异常 IP 总数</div>
            </div>
            <div class="sec-stat sec-stat--danger">
              <div class="sec-stat-value">{{ secStatus.high_risk ?? 0 }}</div>
              <div class="sec-stat-label">高度可疑</div>
            </div>
            <div class="sec-stat sec-stat--info">
              <div class="sec-stat-value">{{ secStatus.total_ip_count ?? '-' }}</div>
              <div class="sec-stat-label">总 IP 数</div>
            </div>
          </div>
          <n-data-table
            :columns="secColumns"
            :data="secIpList"
            :pagination="secPaginationProps"
            :loading="secLoading"
            :bordered="false"
            size="small"
            scroll-x="1100"
          />
        </n-space>
      </n-card>
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

// 资料分布数据
const materialSubjectData = computed(() => {
  const list = statistics.value.material_by_subject ?? []
  if (Array.isArray(list) && list.length) {
    return list.map(item => ({
      name: item.subject ?? item.name ?? '未分类',
      value: numberValue(item.count ?? item.total ?? item.value),
    }))
  }
  const total = numberValue(statistics.value.material_count)
  if (total > 0) return [{ name: '资料总数', value: total }]
  return []
})

const materialSubjectOption = computed(() => ({
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
  color: ['#27D2BF', '#FF7A45', '#3C2ECA', '#F6A623', '#6C5CE7', '#E8463A', '#1DC981', '#9B59B6'],
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
    data: materialSubjectData.value,
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

// ========== 安全监控 ==========
const secStatus = ref({})
const secIpList = ref([])
const secLoading = ref(false)
const secScanning = ref(false)
const secScanningFull = ref(false)
const secChecking = ref(false)
const checkResult = ref(null)
const checkVisible = ref(false)
const secPage = ref(1)
const secPageSize = ref(10)
const secTotal = ref(0)

const secColumns = [
  { title: 'IP 地址', key: 'ip', width: 150, fixed: 'left' },
  { title: '风险等级', key: 'risk_level', width: 100, render: row => {
    if (row.risk_level === 'high') return h('n-tag', { type: 'error', size: 'small', round: true }, { default: () => '高度可疑' })
    return h('n-tag', { type: 'warning', size: 'small', round: true }, { default: () => '异常' })
  }},
  { title: '触发场景', key: 'scenarios', width: 200, render: row => {
    const scenarioNames = {
      'api_rate_abuse': 'API速率滥用',
      'path_scanning': '路径扫描',
      'high_total_volume': '高请求量',
      'scan_pattern_dense': '扫描特征密集',
    }
    let list = []
    if (Array.isArray(row.scenarios)) {
      list = row.scenarios
    } else if (typeof row.scenarios === 'string' && row.scenarios) {
      try { list = JSON.parse(row.scenarios) } catch { list = [] }
    }
    if (!list.length) return h('span', { style: 'color: var(--n-text-color-3)' }, '-')
    return h('div', { class: 'sec-scenarios' }, list.map(sid =>
      h('n-tag', {
        size: 'small',
        type: sid === 'path_scanning' || sid === 'scan_pattern_dense' ? 'error' : 'warning',
        style: 'margin-right: 4px; margin-bottom: 4px;',
        round: true,
      }, { default: () => scenarioNames[sid] || sid })
    ))
  }},
  { title: 'API 请求', key: 'api_count', width: 90, render: row => row.api_count?.toLocaleString() },
  { title: '扫描特征', key: 'scan_count', width: 90, render: row => row.scan_count?.toLocaleString() },
  { title: '404 次数', key: 'status_404', width: 90, render: row => row.status_404?.toLocaleString() },
  { title: '总请求', key: 'total_count', width: 90, render: row => row.total_count?.toLocaleString() },
  { title: '地理位置', key: 'location', ellipsis: { tooltip: true } },
  { title: '关联用户', key: 'users', width: 160, render: row => {
    if (!row.users?.length) return h('span', { style: 'color: var(--n-text-color-3)' }, '无')
    return h('div', { class: 'sec-users' }, row.users.map(u =>
      h('n-tag', { size: 'small', style: 'margin-right: 4px; margin-bottom: 4px;' }, { default: () => u.username })
    ))
  }},
  { title: '最近活跃', key: 'last_seen', width: 150, render: row => formatTimeAgo(row.last_seen) },
]

const secPaginationProps = computed(() => ({
  page: secPage.value,
  pageSize: secPageSize.value,
  itemCount: secTotal.value,
  showSizePicker: false,
  onChange: (page) => { secPage.value = page; loadSecIpList() },
}))

function formatTimeAgo(t) {
  if (!t) return '-'
  try {
    const d = new Date(t.replace(' ', 'T'))
    if (isNaN(d.getTime())) return t
    const now = new Date()
    const diff = (now - d) / 1000
    if (diff < 0) return t.slice(5, 16)
    if (diff < 60) return '刚刚'
    if (diff < 3600) return `${Math.floor(diff / 60)} 分钟前`
    if (diff < 86400) return `${Math.floor(diff / 3600)} 小时前`
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} 天前`
    return t.slice(0, 16) // YYYY-MM-DD HH:mm
  }
  catch {
    return t
  }
}

function formatScanTime(t) {
  if (!t) return '从未扫描'
  return formatTimeAgo(t)
}

async function loadSecStatus() {
  if (!session.isRoot) return
  try {
    const result = await phpAdminApi.securityScanStatus()
    secStatus.value = result.data || {}
  }
  catch (e) {
    console.error('[安全监控] 加载状态失败:', e)
  }
}

async function loadSecIpList() {
  if (!session.isRoot) return
  secLoading.value = true
  try {
    const result = await phpAdminApi.securityIpList({
      page: secPage.value,
      page_size: secPageSize.value,
    })
    secIpList.value = result.data?.list || []
    secTotal.value = result.data?.total || 0
  }
  catch (e) {
    console.error('[安全监控] 加载IP列表失败:', e)
  }
  finally {
    secLoading.value = false
  }
}

// 快速扫描（PHP模式，同步执行，秒级出结果）
async function quickScan() {
  secScanning.value = true
  try {
    const result = await phpAdminApi.securityScanQuick()
    await loadSecStatus()
    await loadSecIpList()
    const count = secStatus.value.total_abnormal ?? 0
    $message.success(count > 0 ? `扫描完成，发现 ${count} 个异常 IP` : '扫描完成，未发现异常 IP')
  }
  catch (e) {
    console.error('快速扫描失败:', e)
    $message.error(e.message || '扫描失败')
  }
  finally {
    secScanning.value = false
  }
}

async function forceScan() {
  secScanningFull.value = true
  try {
    const result = await phpAdminApi.securityScanForce()
    $message.success(result.launch_info || '完整扫描已启动，正在后台处理...')
    // 打印调试信息到 console
    if (result.debug) {
      console.log('[安全扫描调试信息]', result.debug)
    }
    // 轮询扫描状态，直到完成或超时
    // 注意：扫描中只查状态，不刷列表，避免频繁请求
    const maxWait = 60000 // 最多等 60 秒（主流程应该几秒就完了）
    const interval = 5000 // 每 5 秒查一次状态（避免过于频繁）
    const startTime = Date.now()

    const poll = async () => {
      try {
        await loadSecStatus()
        const status = secStatus.value.last_scan?.status
        if (status === 'finished' || status === 'failed') {
          // 扫描完成，刷新一次列表
          await loadSecIpList()
          if (status === 'finished') {
            const count = secStatus.value.total_abnormal ?? 0
            $message.success(count > 0 ? `扫描完成，发现 ${count} 个异常 IP` : '扫描完成，未发现异常 IP')
          } else {
            const reason = secStatus.value.last_scan?.result_info || '未知原因'
            $message.warning('扫描失败：' + reason)
          }
          secScanningFull.value = false
          return
        }
        // 继续轮询（只查状态，不刷列表，减少请求次数）
        if (Date.now() - startTime < maxWait) {
          setTimeout(poll, interval)
        } else {
          // 超时了
          secScanningFull.value = false
          $message.warning('扫描仍在进行中（补充地理位置信息较慢），请稍后刷新查看')
        }
      } catch (e) {
        console.error('轮询扫描状态出错:', e)
        secScanningFull.value = false
      }
    }
    poll() // 立即开始第一次轮询
  }
  catch (e) {
    console.error('启动扫描失败:', e)
    $message.error(e.message || '启动扫描失败')
    secScanningFull.value = false
  }
}

async function runCheck() {
  secChecking.value = true
  try {
    const result = await phpAdminApi.securityScanCheck()
    checkResult.value = result.data || {}
    checkVisible.value = true
  }
  catch (e) {
    $message.error(e.message || '自检失败')
  }
  finally {
    secChecking.value = false
  }
}

function formatSize(bytes) {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  if (bytes < 1024 * 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB'
  return (bytes / 1024 / 1024 / 1024).toFixed(2) + ' GB'
}

// 管理员才加载安全数据
if (session.isRoot) {
  loadSecStatus()
  loadSecIpList()
}
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

/* ========== 安全监控样式 ========== */
.sec-header {
  display: flex;
  align-items: center;
  gap: 12px;
}
.sec-scan-time {
  font-size: 12px;
  color: var(--n-text-color-3);
  display: flex;
  align-items: center;
  gap: 6px;
}
.sec-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.sec-stat {
  text-align: center;
  padding: 16px 12px;
  border-radius: 10px;
  background: var(--n-color-hover);
}
.sec-stat-value {
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}
.sec-stat-label {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin-top: 4px;
}
.sec-stat--warn .sec-stat-value {
  color: #F6A623;
}
.sec-stat--danger .sec-stat-value {
  color: #E8463A;
}
.sec-stat--info .sec-stat-value {
  color: #3C2ECA;
}
.sec-users {
  display: flex;
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .trend-chart { height: 240px; }
  .chart-wrap--sm .subject-chart { height: 240px; }
  .stat-value { font-size: 20px; }
  .sec-summary { grid-template-columns: repeat(3, 1fr); gap: 8px; }
  .sec-stat { padding: 12px 8px; }
  .sec-stat-value { font-size: 22px; }
}
</style>
