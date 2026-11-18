import { computed, ref } from 'vue'

/**
 * 响应式移动端检测
 * 三重保险：
 *  1. 视口宽度 < 768px
 *  2. UA 包含 mobile/Android/iPhone/iPad/iPod
 *  3. URL ?mobile=1 强制 mobile，?mobile=0 强制 desktop
 * 返回 { isMobile, forceMobile, forceDesktop } 三种状态
 */
const WIDTH_BREAKPOINT = 768
const TABLET_BREAKPOINT = 1024

const state = {
  isMobile: ref(false),
  isTablet: ref(false),
  width: ref(typeof window !== 'undefined' ? window.innerWidth : 1280),
  height: ref(typeof window !== 'undefined' ? window.innerHeight : 800),
  force: null, // 'mobile' | 'desktop' | null
  listenersBound: false,
}

function checkUA() {
  if (typeof navigator === 'undefined')
    return false
  const ua = navigator.userAgent.toLowerCase()
  return /mobile|android|iphone|ipod|ipad|blackberry|operamini|iemobile|silk|windows phone/.test(ua)
}

function checkForce() {
  if (typeof window === 'undefined')
    return null
  const params = new URLSearchParams(window.location.search)
  const m = params.get('mobile')
  if (m === '1' || m === 'true')
    return 'mobile'
  if (m === '0' || m === 'false')
    return 'desktop'
  return null
}

function updateFromWidth() {
  const w = window.innerWidth
  state.width.value = w
  state.height.value = window.innerHeight
  state.isTablet.value = w >= WIDTH_BREAKPOINT && w < TABLET_BREAKPOINT
  state.isMobile.value = w < WIDTH_BREAKPOINT
}

function bindListeners() {
  if (state.listenersBound)
    return
  state.listenersBound = true
  window.addEventListener('resize', updateFromWidth)
  window.addEventListener('orientationchange', updateFromWidth)
}

export function useIsMobile() {
  // 强制模式优先
  const force = checkForce()
  state.force = force
  if (force === 'mobile') {
    state.isMobile.value = true
    state.isTablet.value = false
  }
  else if (force === 'desktop') {
    state.isMobile.value = false
  }
  else {
    updateFromWidth()
    // 桌面浏览器但 UA 是 mobile（比如手机上的 Chrome DevTools 模拟）→ 也当 mobile
    if (checkUA())
      state.isMobile.value = true
  }
  bindListeners()

  return {
    isMobile: computed(() => state.isMobile.value),
    isTablet: computed(() => state.isTablet.value),
    isForceMobile: computed(() => state.force === 'mobile'),
    isForceDesktop: computed(() => state.force === 'desktop'),
    width: computed(() => state.width.value),
    height: computed(() => state.height.value),
    // 工具：临时强制切换（存 sessionStorage 刷新后也保持）
    toggleForce() {
      const cur = state.force ?? (state.isMobile.value ? 'mobile' : 'desktop')
      const next = cur === 'mobile' ? 'desktop' : 'mobile'
      sessionStorage.setItem('studyscape_mobile_force', next)
      state.force = next
      state.isMobile.value = next === 'mobile'
    },
  }
}
