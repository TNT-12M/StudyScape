<template>
  <div class="user-shell" :class="{ 'is-mobile': isMobile, 'is-tablet': isTablet }">
    <!-- ============ DESKTOP: 侧边栏 ============ -->
    <aside
      v-if="!isMobile"
      class="user-sidebar"
      :style="prefs.wallpaperUrl
        ? {
          backgroundImage: `linear-gradient(rgba(15,26,40,.72), rgba(15,26,40,.9)), url(${prefs.wallpaperUrl})`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
        }
        : undefined"
    >
      <div class="user-brand">
        <strong>学境・StudyScape</strong>
        <span>学习的新境界</span>
      </div>
      <nav class="user-menu" aria-label="用户导航">
        <RouterLink to="/app" exact-active-class="is-active">
          <span class="nav-icon">🏠</span><span class="nav-text">学习首页</span>
        </RouterLink>
        <RouterLink to="/app/practice" active-class="is-active">
          <span class="nav-icon">✏️</span><span class="nav-text">在线刷题</span>
        </RouterLink>
        <RouterLink to="/app/papers" active-class="is-active">
          <span class="nav-icon">📝</span><span class="nav-text">在线考试</span>
        </RouterLink>
        <RouterLink to="/app/materials" active-class="is-active">
          <span class="nav-icon">📁</span><span class="nav-text">资料中心</span>
        </RouterLink>
        <RouterLink to="/app/material-requests" active-class="is-active">
          <span class="nav-icon">🎯</span><span class="nav-text">资料需求</span>
        </RouterLink>
        <RouterLink to="/app/questions" active-class="is-active">
          <span class="nav-icon">📚</span><span class="nav-text">题库浏览</span>
        </RouterLink>
        <RouterLink to="/app/feedback" active-class="is-active">
          <span class="nav-icon">💬</span><span class="nav-text">开发者反馈</span>
        </RouterLink>
        <RouterLink to="/app/about" active-class="is-active">
          <span class="nav-icon">🤝</span><span class="nav-text">加入学境</span>
        </RouterLink>
        <RouterLink v-if="session.isAdmin" to="/StudyScape" class="nav-admin-link" active-class="is-active">
          <span class="nav-icon">⚙️</span><span class="nav-text">管理后台</span>
        </RouterLink>
      </nav>
      <RouterLink v-if="session.isAdmin" class="back-admin" to="/StudyScape">
        <span class="nav-icon">⚙️</span><span class="nav-text">进入管理后台</span>
      </RouterLink>
    </aside>

    <!-- ============ MAIN CONTENT ============ -->
    <section class="user-main">
      <!-- 顶部 navbar（desktop + mobile 共用，但内容不同） -->
      <header v-if="!isMobile" class="user-header">
        <div class="user-page-title">
          学习中心
        </div>
        <div class="user-actions">
          <n-popover trigger="click" placement="bottom-end" @update:show="v => v && loadNotifications()">
            <template #trigger>
              <n-badge :value="unreadCount" :max="99">
                <n-button quaternary size="small">
                  通知
                </n-button>
              </n-badge>
            </template>
            <n-space vertical style="max-width:340px">
              <n-text v-if="!notifications.length" depth="3">
                暂无通知
              </n-text>
              <div v-else class="notify-list-wrap">
                <n-list size="small">
                  <n-list-item v-for="item in notifications" :key="item.id">
                    <n-thing :title="item.title" :description="item.body" />
                  </n-list-item>
                </n-list>
              </div>
              <n-button v-if="unreadCount" size="small" @click="readNotifications">
                全部标记已读
              </n-button>
            </n-space>
          </n-popover>
          <SessionProfileLink to="/app/profile" />
          <n-button quaternary size="small" @click="logout">
            退出
          </n-button>
        </div>
      </header>

      <!-- ============ MOBILE: 顶部 navbar + 汉堡菜单 ============ -->
      <header v-else class="mobile-header">
        <button class="mobile-header-btn" aria-label="菜单" @click="mobileDrawerShow = true">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6" /><line x1="3" y1="12" x2="21" y2="12" /><line x1="3" y1="18" x2="21" y2="18" />
          </svg>
        </button>
        <div class="mobile-header-title">
          {{ currentTitle }}
        </div>
        <button class="mobile-header-btn" @click="mobileShowNotifications = true">
          <n-badge :value="unreadCount" :max="99" :show="unreadCount > 0">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9" /><path d="M13.73 21a2 2 0 01-3.46 0" />
            </svg>
          </n-badge>
        </button>
      </header>

      <main class="user-content" :class="{ 'user-content--mobile': isMobile }">
        <RouterView v-slot="{ Component }">
          <transition name="page-fade" mode="out-in">
            <component :is="Component" :key="$route.fullPath" />
          </transition>
        </RouterView>
      </main>

      <!-- ============ MOBILE: 底部 tab bar ============ -->
      <nav v-if="isMobile" class="mobile-tabbar">
        <RouterLink to="/app" exact-active-class="is-active" class="mobile-tab-item">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" /><polyline points="9 22 9 12 15 12 15 22" /></svg>
          <span>首页</span>
        </RouterLink>
        <RouterLink to="/app/practice" active-class="is-active" class="mobile-tab-item">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" /></svg>
          <span>刷题</span>
        </RouterLink>
        <RouterLink to="/app/papers" active-class="is-active" class="mobile-tab-item">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="8" y1="13" x2="16" y2="13" /><line x1="8" y1="17" x2="16" y2="17" /></svg>
          <span>考试</span>
        </RouterLink>
        <RouterLink to="/app/materials" active-class="is-active" class="mobile-tab-item">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z" /></svg>
          <span>资料</span>
        </RouterLink>
        <RouterLink to="/app/profile" active-class="is-active" class="mobile-tab-item">
          <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
          <span>我的</span>
        </RouterLink>
      </nav>
    </section>

    <FirstLoginGuide />

    <!-- ============ MOBILE: 汉堡抽屉（完整导航 + 用户菜单） ============ -->
    <n-drawer v-if="isMobile" v-model:show="mobileDrawerShow" :width="280" placement="left" :mask-closable="true">
      <template #header>
        <div class="mobile-drawer-brand">
          <strong>学境・StudyScape</strong>
          <span>{{ session.user?.username || '未登录' }}</span>
        </div>
      </template>
      <div class="mobile-drawer-menu">
        <div class="drawer-section-title">
          学习
        </div>
        <RouterLink to="/app" exact-active-class="is-active" @click="mobileDrawerShow = false">
          <span>🏠</span> 学习首页
        </RouterLink>
        <RouterLink to="/app/practice" active-class="is-active" @click="mobileDrawerShow = false">
          <span>✏️</span> 在线刷题
        </RouterLink>
        <RouterLink to="/app/papers" active-class="is-active" @click="mobileDrawerShow = false">
          <span>📝</span> 在线考试
        </RouterLink>
        <RouterLink to="/app/questions" active-class="is-active" @click="mobileDrawerShow = false">
          <span>📚</span> 题库浏览
        </RouterLink>

        <div class="drawer-section-title">
          资源
        </div>
        <RouterLink to="/app/materials" active-class="is-active" @click="mobileDrawerShow = false">
          <span>📁</span> 资料中心
        </RouterLink>
        <RouterLink to="/app/material-requests" active-class="is-active" @click="mobileDrawerShow = false">
          <span>🎯</span> 资料需求
        </RouterLink>

        <div class="drawer-section-title">
          我的
        </div>
        <RouterLink to="/app/attempts" active-class="is-active" @click="mobileDrawerShow = false">
          <span>📊</span> 我的作答
        </RouterLink>
        <RouterLink to="/app/profile" active-class="is-active" @click="mobileDrawerShow = false">
          <span>👤</span> 个人资料
        </RouterLink>
        <RouterLink to="/app/feedback" active-class="is-active" @click="mobileDrawerShow = false">
          <span>💬</span> 开发者反馈
        </RouterLink>
        <RouterLink to="/app/about" active-class="is-active" @click="mobileDrawerShow = false">
          <span>🤝</span> 加入学境
        </RouterLink>

        <RouterLink v-if="session.isAdmin" to="/StudyScape" class="drawer-admin-link" @click="mobileDrawerShow = false">
          <span>⚙️</span> 管理后台
        </RouterLink>
        <button class="drawer-logout" @click="logout">
          🚪 退出登录
        </button>
      </div>
    </n-drawer>

    <!-- ============ MOBILE: 通知抽屉 ============ -->
    <n-drawer v-if="isMobile" v-model:show="mobileShowNotifications" :width="320" placement="right">
      <template #header>
        <div class="mobile-notify-header">
          <span>通知</span>
          <n-button v-if="unreadCount" size="small" @click="readNotifications">
            全部已读
          </n-button>
        </div>
      </template>
      <div v-if="!notifications.length" class="mobile-notify-empty">
        暂无通知
      </div>
      <n-list v-else>
        <n-list-item v-for="item in notifications" :key="item.id">
          <n-thing :title="item.title" :description="item.body" />
        </n-list-item>
      </n-list>
    </n-drawer>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { phpNotificationApi } from '@/api/php-modules'
import { useIsMobile } from '@/composables/useIsMobile'
import { SessionProfileLink } from '@/layouts/components'
import { usePrefsStore, useSessionStore } from '@/store'
import FirstLoginGuide from '@/views/user/components/FirstLoginGuide.vue'

const { isMobile, isTablet } = useIsMobile()
const route = useRoute()
const router = useRouter()
const session = useSessionStore()
const prefs = usePrefsStore()

const notifications = ref([])
const unreadCount = ref(0)
const mobileDrawerShow = ref(false)
const mobileShowNotifications = ref(false)

async function loadNotifications() {
  try {
    const result = await phpNotificationApi.list({ page: 1, page_size: 10 })
    notifications.value = result.data?.items || []
    unreadCount.value = Number(result.data?.unread_count || 0)
  }
  catch { /* 通知失败不阻断 */ }
}
async function readNotifications() {
  await phpNotificationApi.markRead()
  await loadNotifications()
}

// 移动端顶部标题动态显示
const routeTitleMap = {
  '/app': '学习首页',
  '/app/practice': '在线刷题',
  '/app/papers': '在线考试',
  '/app/materials': '资料中心',
  '/app/material-requests': '资料需求',
  '/app/questions': '题库浏览',
  '/app/feedback': '开发者反馈',
  '/app/about': '加入学境',
  '/app/profile': '个人资料',
  '/app/attempts': '我的作答',
}
const currentTitle = computed(() => {
  const path = route.path
  if (route.name === 'practice-run')
    return '答题中'
  if (path.startsWith('/app/paper/'))
    return '考试中'
  if (path.startsWith('/app/profile'))
    return '个人资料'
  return routeTitleMap[path] || '学习中心'
})

onMounted(() => {
  loadNotifications()
  prefs.load()
})

async function logout() {
  mobileDrawerShow.value = false
  await session.logout()
  router.replace('/welcome')
}
</script>

<style scoped>
/* ==================== DESKTOP 基础布局 ==================== */
.user-shell {
  height: 100%;
  min-height: 100%;
  display: flex;
  background: var(--n-color);
}
.user-sidebar {
  width: 232px;
  flex: 0 0 232px;
  min-height: 100vh;
  padding: 26px 14px;
  background: #172033;
  color: #fff;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
}
.user-brand {
  padding: 0 14px 30px;
}
.user-brand strong {
  display: block;
  font-size: 19px;
  white-space: nowrap;
}
.user-brand span {
  display: block;
  margin-top: 7px;
  color: #9eabc1;
  font-size: 12px;
}
.user-menu {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.user-menu a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  color: #b9c3d6;
  text-decoration: none;
  border-radius: 6px;
  font-size: 14px;
  transition:
    background-color 0.15s,
    color 0.15s;
}
.nav-icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
  flex-shrink: 0;
}
.nav-text {
  flex: 1;
}
.user-menu a:hover,
.user-menu a.is-active {
  color: #fff;
  background: #2a3957;
}
.back-admin {
  margin-top: auto;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 14px;
  color: #b9c3d6;
  text-decoration: none;
  border-radius: 6px;
  font-size: 13px;
}
.back-admin:hover {
  color: #fff;
  background: #2a3957;
}

.user-main {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.user-header {
  height: 64px;
  padding: 0 32px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--n-border-color);
  background: var(--n-color);
  position: sticky;
  top: 0;
  z-index: 10;
}
.user-page-title {
  color: var(--n-text-color);
  font-size: 16px;
  font-weight: 600;
}
.user-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--n-text-color-2);
  font-size: 13px;
}

.user-content {
  max-width: 1280px;
  width: 100%;
  margin: 0 auto;
  padding: 28px 32px 48px;
  flex: 1;
  min-height: 0;
  overflow: hidden;
}

/* ==================== MOBILE 布局 ==================== */
.is-mobile .user-content {
  padding: 0;
  max-width: 100%;
  overflow-x: hidden;
}
.user-content--mobile {
  padding-bottom: 72px !important;
} /* 给底部 tab bar 留空间 */

.mobile-header {
  height: 52px;
  padding: 0 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--n-border-color);
  background: var(--n-color);
  position: sticky;
  top: 0;
  z-index: 10;
}
.mobile-header-btn {
  width: 40px;
  height: 40px;
  border: none;
  background: transparent;
  color: var(--n-text-color);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 0.15s;
}
.mobile-header-btn:hover {
  background: var(--n-color-hover);
}
.mobile-header-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--n-text-color);
  flex: 1;
  text-align: center;
}

/* 底部 tab bar */
.mobile-tabbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 58px;
  display: flex;
  background: var(--n-color);
  border-top: 1px solid var(--n-border-color);
  box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.06);
  z-index: 100;
  padding-bottom: env(safe-area-inset-bottom, 0);
}
.mobile-tab-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  color: var(--n-text-color-3);
  text-decoration: none;
  font-size: 11px;
  padding: 6px 0;
  transition: color 0.15s;
}
.mobile-tab-item:hover {
  color: var(--n-primary-color);
}
.mobile-tab-item.is-active {
  color: var(--n-primary-color);
}

/* 汉堡抽屉 */
.mobile-drawer-brand {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 8px 4px;
}
.mobile-drawer-brand strong {
  font-size: 16px;
}
.mobile-drawer-brand span {
  font-size: 12px;
  color: var(--n-text-color-3);
}
.mobile-drawer-menu {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 16px 4px;
}
.drawer-section-title {
  font-size: 11px;
  color: var(--n-text-color-3);
  padding: 12px 14px 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.mobile-drawer-menu a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 14px;
  color: var(--n-text-color);
  text-decoration: none;
  border-radius: 8px;
  font-size: 14px;
  transition: background 0.15s;
}
.mobile-drawer-menu a:hover {
  background: var(--n-color-hover);
}
.mobile-drawer-menu a.is-active {
  background: var(--n-color-hover);
  color: var(--n-primary-color);
}
.drawer-admin-link {
  margin-top: 8px;
}
.drawer-logout {
  margin-top: 16px;
  width: 100%;
  padding: 11px 14px;
  border: none;
  background: transparent;
  color: #d03050;
  font-size: 14px;
  border-radius: 8px;
  text-align: left;
  cursor: pointer;
}
.drawer-logout:hover {
  background: rgba(208, 48, 80, 0.08);
}

/* 通知抽屉 */
.mobile-notify-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 600;
}
.mobile-notify-empty {
  color: var(--n-text-color-3);
  text-align: center;
  padding: 32px 0;
}

/* 页面切换动画 */
.page-fade-enter-active,
.page-fade-leave-active {
  transition: opacity 0.15s ease;
}
.page-fade-enter-from,
.page-fade-leave-to {
  opacity: 0;
}

/* 桌面端窄屏兼容（800-1024px 平板） */
@media (min-width: 800px) and (max-width: 1024px) {
  .user-sidebar {
    width: 180px;
    flex-basis: 180px;
    padding: 18px 10px;
  }
  .user-brand {
    padding: 0 8px 18px;
  }
  .user-brand strong {
    font-size: 16px;
  }
  .user-menu a {
    padding: 10px 10px;
    font-size: 13px;
  }
  .user-content {
    padding: 20px 20px 36px;
  }
}

/* 通知列表滚动（桌面） */
.notify-list-wrap {
  max-height: 60vh;
  overflow-y: auto;
  overflow-x: hidden;
}
.notify-list-wrap :deep(.n-list-item) {
  padding: 10px 4px;
}
.notify-list-wrap :deep(.n-thing-main) {
  max-width: 300px;
}
.notify-list-wrap :deep(.n-thing-content) {
  white-space: normal;
  word-break: break-all;
  font-size: 12px;
}
</style>
