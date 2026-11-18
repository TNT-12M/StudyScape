<template>
  <div class="daily-quote" :class="{ loading, error }">
    <div v-if="loading" class="dq-body dq-body--loading">
      <div class="dq-skeleton dq-skeleton--line" />
      <div class="dq-skeleton dq-skeleton--short" />
    </div>
    <template v-else>
      <div class="dq-quote">
        <span class="dq-mark dq-mark--left">“</span>
        <span class="dq-text">{{ quote }}</span>
        <span class="dq-mark dq-mark--right">”</span>
      </div>
      <div v-if="from" class="dq-meta">
        —— {{ from }}
      </div>
    </template>
  </div>
</template>

<script setup>
// 每日一言组件：浏览器端直接请求 hitokoto.cn，按本地日期缓存
// 选 c(漫画) d(文学) i(互联网) k(哲学)，鼓励学习励志类
const HITOKOTO_URL = 'https://v1.hitokoto.cn/?c=c&c=d&c=i&c=k&encode=json'
const CACHE_KEY = 'studyscape_daily_quote'

// 今日日期字符串（本地时区 YYYY-MM-DD），作为缓存过期依据
function todayKey() {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

// 硬编码兜底（网络/API 异常时显示，保持积极气氛）
const FALLBACKS = [
  { hitokoto: '种一棵树最好的时间是十年前，其次是现在。', from: '谚语' },
  { hitokoto: '不怕慢，只怕站。不怕拙，只怕藏。', from: '谚语' },
  { hitokoto: '你今日的努力，是明日你手里的光。', from: '学境 StudyScape' },
  { hitokoto: '每一道错题，都是通往高分的路标。', from: '学境 StudyScape' },
  { hitokoto: '读书是在别人的思想里跑马。', from: '别林斯基' },
  { hitokoto: '天才就是百分之一的灵感加百分之九十九的汗水。', from: '爱迪生' },
  { hitokoto: '纸上得来终觉浅，绝知此事要躬行。', from: '陆游' },
]

function pickFallback() {
  const idx = new Date().getDate() % FALLBACKS.length
  return FALLBACKS[idx]
}

const quote = ref('')
const from = ref('')
const loading = ref(true)
const error = ref(false)

function apply(data) {
  quote.value = String(data?.hitokoto || '').trim()
  from.value = String(data?.from_who || data?.from || '').trim()
}

async function fetchQuote() {
  // 1) 查缓存
  try {
    const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null')
    if (cached && cached.date === todayKey() && cached.hitokoto) {
      apply(cached)
      loading.value = false
      return
    }
  }
  catch { /* ignore */ }

  // 2) 请求 API
  try {
    const ctrl = new AbortController()
    const timer = setTimeout(() => ctrl.abort(), 5000)
    const resp = await fetch(HITOKOTO_URL, {
      signal: ctrl.signal,
      cache: 'no-cache',
    })
    clearTimeout(timer)
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
    const data = await resp.json()
    if (!data?.hitokoto) throw new Error('空响应')
    apply(data)
    // 写缓存
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify({
        date: todayKey(),
        hitokoto: data.hitokoto,
        from_who: data.from_who || '',
        from: data.from || '',
      }))
    }
    catch { /* localStorage 满或禁用，忽略 */ }
  }
  catch {
    // 3) 兜底
    apply(pickFallback())
    error.value = true
  }
  finally {
    loading.value = false
  }
}

onMounted(fetchQuote)
</script>

<style scoped>
.daily-quote {
  position: relative;
  padding: 16px 20px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.06), rgba(13, 148, 136, 0.06));
  border: 1px solid rgba(59, 130, 246, 0.12);
  overflow: hidden;
}
.daily-quote::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(180deg, #3b82f6, #0d9488);
  border-radius: 3px 0 0 3px;
}
.daily-quote.error {
  border-color: rgba(246, 166, 35, 0.25);
  background: linear-gradient(135deg, rgba(246, 166, 35, 0.05), rgba(59, 130, 246, 0.04));
}
.dq-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.dq-body--loading {
  padding-top: 4px;
}
.dq-quote {
  display: flex;
  align-items: flex-start;
  gap: 2px;
  font-size: 15px;
  line-height: 1.9;
  color: var(--n-text-color, #1f2937);
  font-weight: 500;
}
.dq-mark {
  color: #3b82f6;
  font-size: 24px;
  line-height: 1;
  font-weight: 700;
  opacity: 0.6;
  flex-shrink: 0;
  user-select: none;
}
.dq-mark--left {
  margin-top: 2px;
  margin-right: 2px;
}
.dq-mark--right {
  margin-bottom: -4px;
  align-self: flex-end;
}
.dq-text {
  flex: 1;
  letter-spacing: 0.3px;
}
.dq-meta {
  font-size: 12.5px;
  color: var(--n-text-color-3, #9ca3af);
  text-align: right;
  margin-top: 2px;
  padding-right: 4px;
}
/* skeleton */
.dq-skeleton {
  height: 14px;
  border-radius: 4px;
  background: linear-gradient(90deg, rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.05));
  background-size: 200% 100%;
  animation: dq-shimmer 1.4s ease-in-out infinite;
}
.dq-skeleton--line {
  width: 92%;
}
.dq-skeleton--short {
  width: 48%;
  height: 12px;
  align-self: flex-end;
}
@keyframes dq-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
