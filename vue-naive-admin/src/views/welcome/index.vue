<template>
  <div class="welcome">
    <!-- ===== 顶部导航 ===== -->
    <header class="nav" :class="{ scrolled }">
      <div class="nav-inner">
        <a class="brand" @click="scrollTop">
          <span class="brand-mark">境</span>
          <span class="brand-text">
            <strong>学境 · StudyScape</strong>
            <small>学习的新境界</small>
          </span>
        </a>

        <nav class="nav-links" :class="{ open: menuOpen }">
          <a @click="goSection('features')">核心特点</a>
          <a @click="goSection('flow')">学习闭环</a>
          <a v-if="papers.length" @click="goSection('papers')">最新试卷</a>
          <template v-if="!session.isAuthenticated">
            <a class="nav-login" @click="goLogin">登录</a>
            <button class="btn btn-primary nav-cta" @click="goRegister">
              免费注册
            </button>
          </template>
          <template v-else>
            <button class="btn btn-primary nav-cta" @click="goConsole">
              {{ session.isAdmin ? '进入管理后台' : '进入学习中心' }}
            </button>
          </template>
        </nav>

        <button class="menu-toggle" :class="{ active: menuOpen }" aria-label="菜单" @click="menuOpen = !menuOpen">
          <span /><span /><span />
        </button>
      </div>
    </header>

    <!-- ===== Hero ===== -->
    <section class="hero">
      <div class="hero-bg">
        <span class="blob blob-a" />
        <span class="blob blob-b" />
        <span class="blob blob-c" />
        <span class="sym sym-1">∑</span>
        <span class="sym sym-2">π</span>
        <span class="sym sym-3">√</span>
        <span class="sym sym-4">∫</span>
        <span class="sym sym-5">Δ</span>
        <span class="sym sym-6">θ</span>
        <span class="sym sym-7">∞</span>
      </div>

      <div class="hero-inner">
        <div class="hero-copy">
          <span class="hero-badge">
            <i class="dot" /> 初高中智能学习平台 · 题库 / 组卷 / 考试 / 资料
          </span>
          <h1>
            让每一份试卷<br>
            都成为<span class="grad-text">提分</span>的阶梯
          </h1>
          <p class="hero-sub">
            学境 StudyScape 以 AI 试卷结构化识别为核心，构建「智能录题 — 精品题库 — 在线组卷 —
            考试刷题 — 自评提分」的完整学习闭环，让老师组卷更轻松，让学生刷题更高效。
          </p>
          <div class="hero-actions">
            <template v-if="!session.isAuthenticated">
              <button class="btn btn-primary btn-lg" @click="goRegister">
                免费注册
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
              </button>
              <button class="btn btn-ghost btn-lg" @click="goLogin">
                已有账号，立即登录
              </button>
            </template>
            <template v-else>
              <button class="btn btn-primary btn-lg" @click="goConsole">
                {{ session.isAdmin ? '进入管理后台' : '进入学习中心' }}
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
              </button>
            </template>
          </div>
          <ul class="hero-points">
            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg> 邮箱验证注册，账号安全可靠</li>
            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg> 完美支持 LaTeX 公式与图文混排</li>
            <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg> 免费开放，打开浏览器就能学</li>
          </ul>
        </div>

        <!-- 产品级模拟卡片 -->
        <div class="hero-visual" aria-hidden="true">
          <div class="mock-card float-slow">
            <div class="mock-head">
              <span class="mock-tag">化学 · 单选题</span>
              <span class="mock-ocr">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10" /></svg>
                AI OCR 已识别
              </span>
            </div>
            <p class="mock-stem">
              下列变化中，属于<strong>化学变化</strong>的是（&#12288;）
            </p>
            <ul class="mock-options">
              <li><b>A</b> 冰雪融化成水</li>
              <li class="correct">
                <b>B</b> 纸张燃烧产生二氧化碳 <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
              </li>
              <li><b>C</b> 酒精挥发到空气中</li>
              <li><b>D</b> 玻璃破碎成碎片</li>
            </ul>
            <div class="mock-foot">
              <span>2 分</span>
              <div class="mock-progress">
                <i style="width: 68%" />
              </div>
              <span>第 6 / 20 题</span>
            </div>
          </div>

          <div class="mini-card mini-card-1 float-fast">
            <span class="mini-ico">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V4h16v3M9 20h6M12 4v16" /></svg>
            </span>
            <div>
              <strong>公式完美渲染</strong>
              <small class="formula" v-html="formulaHtml" />
            </div>
          </div>

          <div class="mini-card mini-card-2 float-fast">
            <span class="mini-ico">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6M9 13h6M9 17h4" /></svg>
            </span>
            <div>
              <strong>PDF / 图片一键录题</strong>
              <small>题干 · 选项 · 答案 · 解析自动结构化</small>
            </div>
          </div>
        </div>
      </div>

      <!-- 数据条（上浮卡片） -->
      <div class="stats-wrap">
        <div ref="statsCard" class="stats-card reveal">
          <div v-for="item in statItems" :key="item.key" class="stat">
            <strong class="stat-num">{{ display[item.key] }}</strong>
            <span class="stat-label">{{ item.label }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 核心特点 ===== -->
    <section id="features" class="section">
      <div class="container">
        <div class="section-head reveal">
          <span class="section-kicker">核心特点</span>
          <h2>一个平台，覆盖学习全场景</h2>
          <p>从试卷录入到成绩提升，学境为每一个环节提供专业而顺手的工具</p>
        </div>

        <div class="feature-grid">
          <div v-for="f in features" :key="f.title" class="feature-card reveal">
            <span class="feature-ico" v-html="f.icon" />
            <h3>{{ f.title }}</h3>
            <p>{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 学习闭环 ===== -->
    <section id="flow" class="section section-alt">
      <div class="container">
        <div class="section-head reveal">
          <span class="section-kicker">学习闭环</span>
          <h2>六步运转，从一张试卷到一次提升</h2>
          <p>AI 负责繁琐的结构化工作，人只需要做判断 —— 这就是学境的工作流</p>
        </div>

        <ol class="flow">
          <li v-for="(step, i) in flowSteps" :key="step.title" class="flow-step reveal">
            <span class="flow-no">{{ String(i + 1).padStart(2, '0') }}</span>
            <h3>{{ step.title }}</h3>
            <p>{{ step.desc }}</p>
          </li>
        </ol>
      </div>
    </section>

    <!-- ===== 科目覆盖 ===== -->
    <section v-if="subjects.length" class="section">
      <div class="subjects-box reveal container">
        <div class="subjects-copy">
          <span class="section-kicker">科目全覆盖</span>
          <h2>初高中主科，一套平台全部容纳</h2>
          <p>题库按学段与科目精细组织，刷题、组卷、资料都能按学科快速定位。</p>
        </div>
        <div class="subject-chips">
          <span v-for="s in subjects" :key="s" class="chip">{{ s }}</span>
        </div>
      </div>
    </section>

    <!-- ===== 最新试卷 ===== -->
    <section v-if="papers.length" id="papers" class="section section-alt">
      <div class="container">
        <div class="section-head reveal">
          <span class="section-kicker">最新发布</span>
          <h2>新鲜出炉的在线试卷</h2>
          <p>登录后即可参加在线考试，提交后支持自评与成绩回顾</p>
        </div>
        <div class="paper-grid">
          <div v-for="p in papers" :key="p.id" class="paper-card reveal">
            <div class="paper-top">
              <span class="paper-subject">{{ p.subject || '综合' }}</span>
              <span class="paper-date">{{ formatDate(p.created_at) }}</span>
            </div>
            <h3>{{ p.name }}</h3>
            <p>{{ p.description || '精心组卷的在线练习，登录后即可开始作答。' }}</p>
            <div class="paper-meta">
              <span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></svg>
                {{ p.question_count }} 道题
              </span>
              <span>
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M12 6v6l4 2" /></svg>
                {{ p.duration_minutes }} 分钟
              </span>
            </div>
            <button class="btn btn-light btn-block" @click="goLogin">
              登录后开始作答
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== 底部 CTA ===== -->
    <section class="cta-section">
      <div class="container">
        <div class="cta-panel reveal">
          <div class="cta-glow" />
          <h2>现在开始，进入学习的新境界</h2>
          <p>注册只需一个邮箱，海量题库与在线试卷即刻开放</p>
          <div class="hero-actions center">
            <template v-if="!session.isAuthenticated">
              <button class="btn btn-light btn-lg" @click="goRegister">
                免费注册账号
              </button>
              <button class="btn btn-outline-light btn-lg" @click="goLogin">
                立即登录
              </button>
            </template>
            <button v-else class="btn btn-light btn-lg" @click="goConsole">
              {{ session.isAdmin ? '进入管理后台' : '进入学习中心' }}
            </button>
          </div>
        </div>
      </div>
    </section>

    <footer class="footer">
      <div class="footer-inner container">
        <div class="footer-brand">
          <span class="brand-mark sm">境</span>
          <div>
            <strong>学境 · StudyScape</strong>
            <p>初高中智能学习平台 —— 学习的新境界</p>
          </div>
        </div>
        <div class="footer-links">
          <a v-if="!session.isAuthenticated" @click="goLogin">登录</a>
          <a v-if="!session.isAuthenticated" @click="goRegister">注册</a>
          <a @click="goSection('features')">核心特点</a>
          <a @click="goSection('flow')">学习闭环</a>
        </div>
      </div>
      <div class="copyright container">
        © 2026 StudyScape 学境 · 仅供学习交流使用
      </div>
    </footer>
  </div>
</template>

<script setup>
import katex from 'katex'
import { onBeforeUnmount } from 'vue'
import { phpPublicApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'
import 'katex/dist/katex.min.css'

const router = useRouter()
const session = useSessionStore()

// 求根公式：标准分式排版（分子/分母上下结构）
const formulaHtml = katex.renderToString('x = \\dfrac{-b \\pm \\sqrt{b^{2} - 4ac}}{2a}', {
  throwOnError: false,
  output: 'html',
})

const scrolled = ref(false)
const menuOpen = ref(false)
const statsCard = ref(null)

const stats = ref({ question_count: 0, subject_count: 0, published_paper_count: 0, material_count: 0 })
const subjects = ref([])
const papers = ref([])
const statsReady = ref(false)

const statItems = [
  { key: 'question_count', label: '道精选题目' },
  { key: 'subject_count', label: '个学科覆盖' },
  { key: 'published_paper_count', label: '套在线试卷' },
  { key: 'material_count', label: '份学习资料' },
]

const display = reactive({ question_count: 0, subject_count: 0, published_paper_count: 0, material_count: 0 })

const features = [
  {
    title: 'AI 智能 OCR 录题',
    desc: '上传 PDF 试卷或图片，阿里云教育试卷结构化 OCR 自动识别题干、选项、答案与解析，人工只需审核确认。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/><path d="M9 9h6M9 15h4"/></svg>',
  },
  {
    title: '精品结构化题库',
    desc: '题目按学段、科目、题型精细分类，支持单选、多选、填空、简答，自动查重，批量管理，题库越用越干净。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/></svg>',
  },
  {
    title: '在线组卷与考试',
    desc: '从题库自由挑题组卷，一键发布给学生；限时作答、在线提交，历史成绩受到保护，试卷发布后可追溯。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>',
  },
  {
    title: '自由刷题与自评',
    desc: '按科目随时开始自由刷题，交卷后客观题自动判分，主观题支持学生自评，每一次练习都能看到进步。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9z"/></svg>',
  },
  {
    title: '学习资料中心',
    desc: '资料按学科分类管理，一次性安全链接下载，还可以提交资料需求，由内容团队跟进补充，缺什么补什么。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
  },
  {
    title: 'LaTeX 公式渲染',
    desc: '基于 KaTeX 的公式排版引擎，行内公式与公式块精准呈现，数理化试卷中的上下标、根式、分式不再错乱。',
    icon: '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 5H7l6 7-6 7h11"/></svg>',
  },
]

const flowSteps = [
  { title: '上传试卷', desc: 'PDF 或清晰图片均可，直接上传创建 OCR 批次。' },
  { title: 'AI 结构化识别', desc: '云端 OCR 自动拆题，提取题干、选项、答案、解析与图片。' },
  { title: '人工审核入库', desc: '在线编辑、剔除错题，确认后统一入库，按题干自动查重。' },
  { title: '智能组卷发布', desc: '按科目题型挑题组卷，设置时长与分值，一键发布。' },
  { title: '在线考试刷题', desc: '学生限时参加考试，或按科目自由刷题，随时作答提交。' },
  { title: '自评反馈提分', desc: '客观题自动判分，主观题自评订正，学习记录可回顾追踪。' },
]

function goLogin() {
  menuOpen.value = false
  router.push('/login')
}
function goRegister() {
  menuOpen.value = false
  router.push({ path: '/login', query: { mode: 'register' } })
}
function goConsole() {
  router.push(session.isAdmin ? '/admin' : '/app')
}
function scrollTop() {
  menuOpen.value = false
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
function goSection(id) {
  menuOpen.value = false
  document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
function formatDate(v) {
  if (!v)
    return ''
  return String(v).slice(0, 10)
}

function onScroll() {
  scrolled.value = window.scrollY > 20
}

const numbersPlayed = ref(false)

function animateNumbers() {
  if (numbersPlayed.value)
    return
  if (!statsReady.value)
    return
  numbersPlayed.value = true
  const duration = 1200
  const start = performance.now()
  const targets = { ...stats.value }
  function tick(now) {
    const t = Math.min(1, (now - start) / duration)
    const eased = 1 - (1 - t) ** 3
    for (const key of Object.keys(targets)) {
      display[key] = Math.round(targets[key] * eased).toLocaleString('zh-CN')
    }
    if (t < 1)
      requestAnimationFrame(tick)
  }
  requestAnimationFrame(tick)
}

let revealObserver = null
const revealedSet = new Set()

function observeReveals() {
  if (!revealObserver)
    return
  document.querySelectorAll('.reveal').forEach((el) => {
    if (!revealedSet.has(el)) {
      revealedSet.add(el)
      revealObserver.observe(el)
    }
  })
}

function revealAll() {
  document.querySelectorAll('.reveal').forEach(el => el.classList.add('is-visible'))
}

onMounted(async () => {
  // 全局样式把 html/body 锁为 100% + overflow:hidden（后台内层滚动），
  // 落地页需要整页自然滚动，激活期间解除该约束
  document.documentElement.classList.add('welcome-active')

  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  session.ensureReady?.()

  // 渐入观察器先于数据请求建立，避免接口慢时下方区块一直不可见
  const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches
  if (reduceMotion) {
    revealAll()
    animateNumbers()
  }
  else {
    revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible')
          if (entry.target === statsCard.value && statsReady.value)
            animateNumbers()
          revealObserver?.unobserve(entry.target)
        }
      })
    }, { threshold: 0.1 })
    observeReveals()
    // 兜底：任何原因（快速滚动、观察器异常等）1.5s 后全部显示
    setTimeout(() => {
      revealAll()
      if (statsReady.value)
        animateNumbers()
    }, 1500)
  }

  try {
    const res = await phpPublicApi.overview()
    const d = res.data || {}
    stats.value = { ...stats.value, ...(d.stats || {}) }
    subjects.value = d.subjects || []
    papers.value = (d.published_papers || []).slice(0, 3)
  }
  catch {
    // 公开接口异常时静默降级，不影响落地页展示
  }
  finally {
    statsReady.value = true
    // 减弱动效偏好：数据就绪后直接显示终值
    if (reduceMotion)
      Object.assign(display, stats.value)
  }

  // 数据渲染后新出现的 .reveal（试卷卡片等）补注册
  await nextTick()
  observeReveals()

  // 统计卡已在视口内（或观察器未触发）时直接启动数字动画
  if (statsCard.value && statsReady.value && !reduceMotion) {
    const rect = statsCard.value.getBoundingClientRect()
    if (rect.top < window.innerHeight)
      animateNumbers()
  }
})

onBeforeUnmount(() => {
  document.documentElement.classList.remove('welcome-active')
  window.removeEventListener('scroll', onScroll)
  revealObserver?.disconnect()
})
</script>

<style scoped>
.welcome {
  --brand: #3b82f6;
  --brand-deep: #2563eb;
  --teal: #0d9488;
  --ink: #0f1f38;
  --muted: #5b6b82;
  --line: #e6ecf5;
  --bg-soft: #f5f8ff;
  color: var(--ink);
  font-family:
    -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;
  background: #fff;
  overflow-x: clip;
}

.container {
  width: min(1180px, calc(100% - 48px));
  margin: 0 auto;
}

/* ===== 按钮 ===== */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border: none;
  cursor: pointer;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  line-height: 1;
  padding: 12px 22px;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    background 0.2s ease,
    color 0.2s ease;
  white-space: nowrap;
  text-decoration: none;
}
.btn-lg {
  padding: 15px 30px;
  font-size: 15.5px;
  border-radius: 14px;
}
.btn-primary {
  color: #fff;
  background: linear-gradient(135deg, var(--brand) 0%, var(--teal) 100%);
  box-shadow: 0 10px 24px rgb(59 130 246 / 28%);
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 32px rgb(59 130 246 / 36%);
}
.btn-ghost {
  color: var(--brand-deep);
  background: #fff;
  box-shadow: inset 0 0 0 1.5px #cfe0ff;
}
.btn-ghost:hover {
  background: #eef4ff;
  transform: translateY(-2px);
}
.btn-light {
  color: var(--brand-deep);
  background: #fff;
}
.btn-light:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 26px rgb(15 31 56 / 16%);
}
.btn-outline-light {
  color: #fff;
  background: rgb(255 255 255 / 8%);
  box-shadow: inset 0 0 0 1.5px rgb(255 255 255 / 55%);
}
.btn-outline-light:hover {
  background: rgb(255 255 255 / 18%);
  transform: translateY(-2px);
}
.btn-block {
  width: 100%;
  margin-top: 18px;
}

/* ===== 导航 ===== */
.nav {
  position: fixed;
  inset: 0 0 auto 0;
  z-index: 100;
  transition:
    background 0.25s ease,
    box-shadow 0.25s ease;
}
.nav.scrolled {
  background: rgb(255 255 255 / 86%);
  backdrop-filter: saturate(180%) blur(16px);
  -webkit-backdrop-filter: saturate(180%) blur(16px);
  box-shadow:
    0 1px 0 var(--line),
    0 10px 30px rgb(15 31 56 / 6%);
}
.nav-inner {
  width: min(1180px, calc(100% - 48px));
  margin: 0 auto;
  height: 70px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  cursor: pointer;
}
.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  color: #fff;
  font-size: 21px;
  font-weight: 800;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--brand), var(--teal));
  box-shadow: 0 8px 20px rgb(59 130 246 / 32%);
}
.brand-mark.sm {
  width: 38px;
  height: 38px;
  font-size: 19px;
  border-radius: 10px;
}
.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.25;
}
.brand-text strong {
  font-size: 16.5px;
  letter-spacing: 0.2px;
}
.brand-text small {
  font-size: 11.5px;
  color: var(--muted);
}
.nav-links {
  display: flex;
  align-items: center;
  gap: 26px;
}
.nav-links > a {
  font-size: 14.5px;
  color: #37465e;
  cursor: pointer;
  transition: color 0.2s;
  font-weight: 500;
}
.nav-links > a:hover {
  color: var(--brand-deep);
}
.nav-links .nav-login {
  font-weight: 600;
  color: var(--brand-deep);
}
.nav-cta {
  padding: 10px 18px;
}

.menu-toggle {
  display: none;
  width: 42px;
  height: 42px;
  border: none;
  background: transparent;
  cursor: pointer;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;
  border-radius: 10px;
}
.menu-toggle span {
  width: 21px;
  height: 2px;
  background: var(--ink);
  border-radius: 2px;
  transition: 0.25s;
}
.menu-toggle.active span:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}
.menu-toggle.active span:nth-child(2) {
  opacity: 0;
}
.menu-toggle.active span:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

/* ===== Hero ===== */
.hero {
  position: relative;
  padding: 148px 0 118px;
  background: linear-gradient(180deg, #eef4ff 0%, #f6f9ff 45%, #fff 100%);
}
.hero-bg {
  position: absolute;
  inset: 0;
  overflow: hidden;
  pointer-events: none;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(70px);
  opacity: 0.55;
}
.blob-a {
  width: 460px;
  height: 460px;
  background: #bfdbfe;
  top: -160px;
  left: -110px;
}
.blob-b {
  width: 420px;
  height: 420px;
  background: #99f6e4;
  top: 40px;
  right: -140px;
  opacity: 0.4;
}
.blob-c {
  width: 300px;
  height: 300px;
  background: #ddd6fe;
  bottom: -120px;
  left: 38%;
  opacity: 0.35;
}
.sym {
  position: absolute;
  color: var(--brand);
  font-family: Georgia, 'Times New Roman', serif;
  font-style: italic;
  opacity: 0.14;
  user-select: none;
}
.sym-1 {
  font-size: 64px;
  top: 15%;
  left: 8%;
  animation: drift 9s ease-in-out infinite;
}
.sym-2 {
  font-size: 46px;
  top: 64%;
  left: 4%;
  animation: drift 11s ease-in-out infinite 1s;
}
.sym-3 {
  font-size: 52px;
  top: 20%;
  left: 44%;
  animation: drift 10s ease-in-out infinite 0.5s;
}
.sym-4 {
  font-size: 58px;
  top: 12%;
  right: 30%;
  animation: drift 8s ease-in-out infinite 1.5s;
}
.sym-5 {
  font-size: 40px;
  bottom: 22%;
  left: 46%;
  animation: drift 12s ease-in-out infinite 0.8s;
}
.sym-6 {
  font-size: 44px;
  top: 52%;
  right: 6%;
  animation: drift 9.5s ease-in-out infinite 2s;
}
.sym-7 {
  font-size: 50px;
  bottom: 8%;
  right: 22%;
  animation: drift 10.5s ease-in-out infinite 0.3s;
}
@keyframes drift {
  0%,
  100% {
    transform: translateY(0) rotate(-6deg);
  }
  50% {
    transform: translateY(-22px) rotate(6deg);
  }
}

.hero-inner {
  position: relative;
  width: min(1180px, calc(100% - 48px));
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1.05fr 0.95fr;
  gap: 56px;
  align-items: center;
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 15px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 500;
  color: var(--brand-deep);
  background: rgb(255 255 255 / 80%);
  box-shadow: inset 0 0 0 1px #d7e5ff;
  backdrop-filter: blur(6px);
}
.hero-badge .dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: var(--teal);
  box-shadow: 0 0 0 4px rgb(13 148 136 / 15%);
  animation: pulse 2s infinite;
}
@keyframes pulse {
  50% {
    box-shadow: 0 0 0 7px rgb(13 148 136 / 4%);
  }
}
.hero-copy h1 {
  margin: 22px 0 18px;
  font-size: clamp(34px, 4.4vw, 54px);
  line-height: 1.18;
  letter-spacing: 0.5px;
  font-weight: 800;
}
.grad-text {
  background: linear-gradient(120deg, var(--brand) 10%, var(--teal) 90%);
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.hero-sub {
  margin: 0 0 30px;
  font-size: 16.5px;
  line-height: 1.85;
  color: var(--muted);
  max-width: 560px;
}
.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
}
.hero-actions.center {
  justify-content: center;
}
.hero-points {
  list-style: none;
  padding: 0;
  margin: 28px 0 0;
  display: flex;
  flex-wrap: wrap;
  gap: 10px 24px;
}
.hero-points li {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13.5px;
  color: #4a5a72;
}
.hero-points svg {
  color: var(--teal);
  flex-shrink: 0;
}

/* Hero 视觉 */
.hero-visual {
  position: relative;
  min-height: 430px;
}
.mock-card {
  position: absolute;
  inset: 20px 10px auto auto;
  width: min(390px, 92%);
  background: rgb(255 255 255 / 92%);
  backdrop-filter: blur(14px);
  border: 1px solid rgb(255 255 255 / 90%);
  border-radius: 20px;
  padding: 22px 22px 18px;
  box-shadow: 0 30px 70px rgb(37 99 235 / 16%);
}
.float-slow {
  animation: floaty 6s ease-in-out infinite;
}
.float-fast {
  animation: floaty 5s ease-in-out infinite;
}
@keyframes floaty {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-12px);
  }
}
.mock-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 15px;
}
.mock-tag {
  font-size: 12px;
  font-weight: 700;
  color: var(--brand-deep);
  background: #eaf1ff;
  padding: 4px 11px;
  border-radius: 999px;
}
.mock-ocr {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11.5px;
  color: var(--teal);
  background: #e6faf7;
  padding: 4px 10px;
  border-radius: 999px;
  font-weight: 600;
}
.mock-stem {
  margin: 0 0 14px;
  font-size: 15px;
  line-height: 1.7;
  font-weight: 600;
}
.mock-stem strong {
  color: var(--brand-deep);
}
.mock-options {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 9px;
}
.mock-options li {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13.5px;
  color: #41506a;
  padding: 9px 13px;
  border-radius: 11px;
  background: #f4f7fd;
  border: 1px solid transparent;
}
.mock-options li b {
  display: inline-grid;
  place-items: center;
  width: 22px;
  height: 22px;
  border-radius: 7px;
  background: #e3ebfb;
  color: var(--brand-deep);
  font-size: 12px;
  flex-shrink: 0;
}
.mock-options li.correct {
  background: #e7faf6;
  border-color: #9fe3d8;
  color: #0b6e63;
  font-weight: 600;
}
.mock-options li.correct b {
  background: var(--teal);
  color: #fff;
}
.mock-options li.correct svg {
  margin-left: auto;
}
.mock-options li:not(.correct) svg {
  display: none;
}
.mock-foot {
  display: flex;
  align-items: center;
  gap: 11px;
  margin-top: 16px;
  font-size: 11.5px;
  color: var(--muted);
}
.mock-progress {
  flex: 1;
  height: 6px;
  border-radius: 999px;
  background: #e8eef9;
  overflow: hidden;
}
.mock-progress i {
  display: block;
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--brand), var(--teal));
}

.mini-card {
  position: absolute;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgb(255 255 255 / 94%);
  border: 1px solid #eef2fa;
  border-radius: 15px;
  padding: 13px 16px;
  box-shadow: 0 18px 44px rgb(15 31 56 / 12%);
}
.mini-card .mini-ico {
  width: 40px;
  height: 40px;
  border-radius: 11px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  color: #fff;
  background: linear-gradient(135deg, var(--brand), var(--teal));
}
.mini-card strong {
  display: block;
  font-size: 13px;
}
.mini-card small {
  display: block;
  font-size: 11px;
  color: var(--muted);
  margin-top: 2px;
  white-space: nowrap;
}
.mini-card .formula {
  color: #607089;
  line-height: 1.1;
  margin-top: 4px;
}
.mini-card .formula .katex {
  font-size: 1.15em;
}
.mini-card-1 {
  left: -8px;
  bottom: 78px;
  animation-delay: 1.2s;
}
.mini-card-2 {
  right: -6px;
  bottom: 4px;
  animation-delay: 0.4s;
}

/* ===== 数据条 ===== */
.stats-wrap {
  position: relative;
  width: min(1180px, calc(100% - 48px));
  margin: 64px auto 0;
}
.stats-card {
  background: #fff;
  border-radius: 22px;
  box-shadow:
    0 24px 60px rgb(37 99 235 / 12%),
    0 2px 0 #eef3fc;
  border: 1px solid #edf2fb;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  padding: 34px 20px;
}
.stat {
  text-align: center;
  position: relative;
}
.stat + .stat::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 1px;
  height: 42px;
  background: var(--line);
}
.stat-num {
  display: block;
  font-size: clamp(28px, 3vw, 40px);
  font-weight: 800;
  line-height: 1.1;
  background: linear-gradient(120deg, var(--brand-deep), var(--teal));
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
}
.stat-label {
  display: block;
  margin-top: 8px;
  font-size: 14px;
  color: var(--muted);
}

/* ===== 通用 Section ===== */
.section {
  padding: 104px 0;
}
.section-alt {
  background: var(--bg-soft);
}
.section-head {
  text-align: center;
  max-width: 680px;
  margin: 0 auto 56px;
}
.section-kicker {
  display: inline-block;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 2px;
  color: var(--brand-deep);
  background: #e8f0ff;
  padding: 5px 14px;
  border-radius: 999px;
  margin-bottom: 16px;
}
.section-head h2 {
  margin: 0 0 14px;
  font-size: clamp(26px, 3vw, 36px);
  font-weight: 800;
  letter-spacing: 0.3px;
}
.section-head p {
  margin: 0;
  color: var(--muted);
  font-size: 15.5px;
  line-height: 1.8;
}

.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition:
    opacity 0.7s ease,
    transform 0.7s ease;
}
.reveal.is-visible {
  opacity: 1;
  transform: none;
}

/* ===== 特点卡片 ===== */
.feature-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
.feature-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 30px 26px;
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease,
    border-color 0.25s ease;
}
.feature-card:hover {
  transform: translateY(-6px);
  border-color: #cfe0ff;
  box-shadow: 0 22px 48px rgb(37 99 235 / 11%);
}
.feature-ico {
  display: inline-grid;
  place-items: center;
  width: 54px;
  height: 54px;
  border-radius: 15px;
  color: var(--brand-deep);
  background: linear-gradient(135deg, #e8f0ff, #ddf8f3);
  margin-bottom: 18px;
}
.feature-card h3 {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 700;
}
.feature-card p {
  margin: 0;
  font-size: 14px;
  line-height: 1.85;
  color: var(--muted);
}

/* ===== 学习闭环 ===== */
.flow {
  list-style: none;
  margin: 0;
  padding: 0 10px;
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 14px;
  counter-reset: step;
}
.flow-step {
  position: relative;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 26px 18px 22px;
  text-align: center;
}
.flow-step:not(:last-child)::after {
  content: '';
  position: absolute;
  top: 38px;
  right: -15px;
  width: 16px;
  height: 16px;
  border-top: 2.5px solid #b9ccf3;
  border-right: 2.5px solid #b9ccf3;
  transform: rotate(45deg);
  z-index: 2;
  background: transparent;
}
.flow-no {
  display: inline-grid;
  place-items: center;
  width: 46px;
  height: 46px;
  border-radius: 50%;
  font-size: 16px;
  font-weight: 800;
  color: #fff;
  margin-bottom: 14px;
  background: linear-gradient(135deg, var(--brand), var(--teal));
  box-shadow: 0 8px 18px rgb(59 130 246 / 30%);
}
.flow-step h3 {
  margin: 0 0 8px;
  font-size: 15.5px;
  font-weight: 700;
}
.flow-step p {
  margin: 0;
  font-size: 12.8px;
  line-height: 1.75;
  color: var(--muted);
}

/* ===== 科目 ===== */
.subjects-box {
  display: grid;
  grid-template-columns: 0.9fr 1.1fr;
  gap: 50px;
  align-items: center;
  background: linear-gradient(135deg, #eef4ff, #e8faf7);
  border-radius: 24px;
  padding: 52px 54px;
}
.subjects-copy h2 {
  margin: 12px 0 12px;
  font-size: clamp(24px, 2.6vw, 32px);
  font-weight: 800;
}
.subjects-copy p {
  margin: 0;
  color: var(--muted);
  line-height: 1.8;
  font-size: 15px;
}
.subject-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.chip {
  padding: 10px 20px;
  border-radius: 999px;
  background: #fff;
  font-size: 14px;
  font-weight: 600;
  color: #274670;
  box-shadow: 0 6px 16px rgb(37 99 235 / 8%);
  border: 1px solid #e4edfb;
  transition: transform 0.2s;
}
.chip:hover {
  transform: translateY(-3px);
}

/* ===== 试卷卡片 ===== */
.paper-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}
.paper-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 18px;
  padding: 26px 24px;
  display: flex;
  flex-direction: column;
  transition:
    transform 0.25s ease,
    box-shadow 0.25s ease,
    border-color 0.25s ease;
}
.paper-card:hover {
  transform: translateY(-6px);
  border-color: #cfe0ff;
  box-shadow: 0 22px 48px rgb(37 99 235 / 11%);
}
.paper-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}
.paper-subject {
  font-size: 12px;
  font-weight: 700;
  color: var(--teal);
  background: #e6faf7;
  padding: 4px 12px;
  border-radius: 999px;
}
.paper-date {
  font-size: 12px;
  color: #93a1b8;
}
.paper-card h3 {
  margin: 0 0 10px;
  font-size: 17.5px;
  line-height: 1.45;
  font-weight: 700;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.paper-card p {
  margin: 0 0 16px;
  color: var(--muted);
  font-size: 13.5px;
  line-height: 1.75;
  flex: 1;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.paper-meta {
  display: flex;
  gap: 18px;
  color: #607089;
  font-size: 13px;
}
.paper-meta span {
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

/* ===== CTA ===== */
.cta-section {
  padding: 100px 0 110px;
}
.cta-panel {
  position: relative;
  overflow: hidden;
  text-align: center;
  border-radius: 28px;
  padding: 76px 32px;
  background: linear-gradient(130deg, #1d4ed8 0%, #2563eb 38%, #0d9488 100%);
  box-shadow: 0 30px 80px rgb(37 99 235 / 30%);
}
.cta-glow {
  position: absolute;
  width: 420px;
  height: 420px;
  border-radius: 50%;
  background: rgb(255 255 255 / 12%);
  filter: blur(20px);
  top: -220px;
  right: -80px;
}
.cta-panel h2 {
  position: relative;
  margin: 0 0 14px;
  color: #fff;
  font-size: clamp(26px, 3.2vw, 38px);
  font-weight: 800;
}
.cta-panel p {
  position: relative;
  margin: 0 0 32px;
  color: rgb(255 255 255 / 82%);
  font-size: 16px;
}
.cta-panel .hero-actions {
  position: relative;
}

/* ===== Footer ===== */
.footer {
  background: #0c1830;
  color: #c6d2e5;
  padding: 46px 0 26px;
}
.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.footer-brand {
  display: flex;
  align-items: center;
  gap: 13px;
}
.footer-brand strong {
  color: #fff;
  font-size: 15.5px;
}
.footer-brand p {
  margin: 3px 0 0;
  font-size: 12.5px;
  color: #8da0bd;
}
.footer-links {
  display: flex;
  gap: 24px;
}
.footer-links a {
  font-size: 13.5px;
  color: #c6d2e5;
  cursor: pointer;
  transition: color 0.2s;
}
.footer-links a:hover {
  color: #fff;
}
.copyright {
  margin-top: 26px;
  padding-top: 20px;
  border-top: 1px solid rgb(255 255 255 / 8%);
  font-size: 12.5px;
  color: #7185a4;
  text-align: center;
}

/* ===== 响应式 ===== */
@media (max-width: 1024px) {
  .hero-inner {
    grid-template-columns: 1fr;
    gap: 40px;
  }
  .hero-visual {
    min-height: 400px;
    max-width: 480px;
    margin: 0 auto;
    width: 100%;
  }
  .feature-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .flow {
    grid-template-columns: repeat(3, 1fr);
    gap: 20px 14px;
  }
  .flow-step:nth-child(3)::after {
    display: none;
  }
  .flow-step:not(:last-child)::after {
    display: none;
  }
  .paper-grid {
    grid-template-columns: 1fr;
    max-width: 520px;
    margin: 0 auto;
  }
}

@media (max-width: 720px) {
  .container,
  .nav-inner,
  .hero-inner,
  .stats-wrap {
    width: calc(100% - 36px);
  }
  .hero {
    padding: 118px 0 80px;
  }
  .section {
    padding: 74px 0;
  }

  .menu-toggle {
    display: flex;
  }
  .nav-links {
    position: fixed;
    top: 70px;
    left: 12px;
    right: 12px;
    flex-direction: column;
    align-items: stretch;
    gap: 6px;
    background: #fff;
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 14px;
    box-shadow: 0 24px 60px rgb(15 31 56 / 16%);
    transform: translateY(-12px);
    opacity: 0;
    pointer-events: none;
    transition: 0.22s ease;
  }
  .nav-links.open {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
  }
  .nav-links > a {
    padding: 11px 10px;
    border-radius: 9px;
  }
  .nav-links > a:hover {
    background: #f2f6ff;
  }
  .nav-cta {
    margin-top: 4px;
  }

  .hero-points {
    gap: 8px 18px;
  }
  .stats-card {
    grid-template-columns: repeat(2, 1fr);
    gap: 26px 10px;
    padding: 28px 16px;
  }
  .stat:nth-child(3)::before {
    display: none;
  }
  .stat + .stat::before {
    display: none;
  }
  .feature-grid {
    grid-template-columns: 1fr;
  }
  .flow {
    grid-template-columns: 1fr;
  }
  .subjects-box {
    grid-template-columns: 1fr;
    padding: 36px 26px;
    gap: 26px;
  }
  .cta-panel {
    padding: 56px 22px;
    border-radius: 22px;
  }
  .mini-card-1 {
    left: -4px;
    max-width: 88%;
  }
  .mini-card-2 {
    right: -4px;
    max-width: 88%;
  }
  .mini-card small {
    font-size: 10.5px;
    white-space: normal;
  }
}

@media (max-width: 400px) {
  .brand-text small {
    display: none;
  }
  .hero-actions .btn {
    width: 100%;
  }
}
</style>

<!-- 非 scoped：覆盖 global.css 对 html/body/#app 的锁高与溢出隐藏，仅在落地页激活期间生效 -->
<style>
html.welcome-active,
html.welcome-active body,
html.welcome-active #app {
  height: auto;
  min-height: 100%;
  overflow: visible;
}
html.welcome-active body {
  overflow-x: hidden;
}
</style>
