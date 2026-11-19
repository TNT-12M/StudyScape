<template>
  <div class="admin-shell">
    <!-- 移动端遮罩 -->
    <div v-if="mobileSidebarOpen" class="sidebar-mask" @click="mobileSidebarOpen = false" />

    <aside
      class="admin-sidebar"
      :class="{ 'is-open': mobileSidebarOpen }"
      :style="prefs.wallpaperUrl
        ? {
          backgroundImage: `linear-gradient(rgba(15,26,40,.72), rgba(15,26,40,.9)), url(${prefs.wallpaperUrl})`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
        }
        : undefined"
    >
      <div class="admin-logo">
        <span class="logo-icon">📚</span>
        <span class="logo-text">学境・管理</span>
      </div>
      <nav class="admin-menu">
        <RouterLink to="/StudyScape" @click="closeOnMobile">
          <span class="menu-icon">📊</span><span class="menu-text">平台概览</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/users" @click="closeOnMobile">
          <span class="menu-icon">👥</span><span class="menu-text">用户授权</span>
        </RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/StudyScape/questions" @click="closeOnMobile">
          <span class="menu-icon">📚</span><span class="menu-text">题库管理</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/papers" @click="closeOnMobile">
          <span class="menu-icon">📝</span><span class="menu-text">考试管理</span>
        </RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/StudyScape/materials" @click="closeOnMobile">
          <span class="menu-icon">📁</span><span class="menu-text">资料管理</span>
        </RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/StudyScape/material-requests" @click="closeOnMobile">
          <span class="menu-icon">📝</span><span class="menu-text">资料需求</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/feedback" @click="closeOnMobile">
          <span class="menu-icon">💬</span><span class="menu-text">反馈处理</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/announcements" @click="closeOnMobile">
          <span class="menu-icon">📢</span><span class="menu-text">公告管理</span>
        </RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/StudyScape/ocr" @click="closeOnMobile">
          <span class="menu-icon">🔍</span><span class="menu-text">智能 OCR 审核</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/settings" @click="closeOnMobile">
          <span class="menu-icon">🧭</span><span class="menu-text">引导设置</span>
        </RouterLink>
        <RouterLink v-if="session.isRoot" to="/StudyScape/qr" @click="closeOnMobile">
          <span class="menu-icon">📱</span><span class="menu-text">二维码管理</span>
        </RouterLink>
      </nav>
      <RouterLink class="back-user" to="/app" @click="closeOnMobile">
        <span class="menu-icon">←</span><span class="menu-text">返回用户端</span>
      </RouterLink>
    </aside>

    <section class="admin-main">
      <header class="admin-header">
        <div class="header-left">
          <button class="mobile-menu-btn" aria-label="菜单" @click="mobileSidebarOpen = !mobileSidebarOpen">
            <span class="hamburger" />
          </button>
          <span class="page-title">管理后台</span>
        </div>
        <div class="admin-user">
          <n-popover trigger="click" placement="bottom-end" @update:show="value => value && loadNotifications()">
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
              <template v-else>
                <!-- 公告（置顶） -->
                <div v-if="announcements.length" class="notify-section">
                  <n-text depth="3" style="font-size:12px">
                    📢 公告
                  </n-text>
                  <div class="notify-list-wrap notify-list-wrap--sm">
                    <n-list size="small">
                      <n-list-item v-for="item in announcements" :key="`ann-${item.id}`">
                        <n-thing :title="item.title" :description="item.body" />
                      </n-list-item>
                    </n-list>
                  </div>
                </div>
                <!-- 普通通知 -->
                <div v-if="personalNotifs.length" class="notify-section">
                  <n-text depth="3" style="font-size:12px">
                    🔔 通知
                  </n-text>
                  <div class="notify-list-wrap">
                    <n-list size="small">
                      <n-list-item v-for="item in personalNotifs" :key="`not-${item.id}`" class="notify-item">
                        <n-thing :title="item.title" :description="item.body" />
                        <button class="notify-del-btn" title="删除这条通知" @click.stop="clearNotification(item.id)">
                          ×
                        </button>
                      </n-list-item>
                    </n-list>
                  </div>
                </div>
              </template>
              <n-space>
                <n-button v-if="unreadCount" size="small" @click="readNotifications">
                  全部标记已读
                </n-button>
                <n-button v-if="personalNotifs.length" size="small" type="error" ghost @click="clearAllNotifications">
                  🗑️ 清空通知
                </n-button>
              </n-space>
            </n-space>
          </n-popover>
          <SessionProfileLink to="/StudyScape/profile" />
          <span class="role-tag hide-mobile">{{ session.isRoot ? 'root' : '内容管理员' }}</span>
          <n-button quaternary size="small" @click="logout">
            退出
          </n-button>
        </div>
      </header>
      <main class="admin-content">
        <RouterView />
      </main>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { phpNotificationApi } from '@/api/php-modules'
import { useIsMobile } from '@/composables/useIsMobile'
import { SessionProfileLink } from '@/layouts/components'
import { usePrefsStore, useSessionStore } from '@/store'

const router = useRouter()
const route = useRoute()
const session = useSessionStore()
const prefs = usePrefsStore()
const { isMobile } = useIsMobile()
const notifications = ref([])
const unreadCount = ref(0)
const mobileSidebarOpen = ref(false)

// 切路由时自动关闭移动侧边栏
watch(() => route.path, () => {
  if (isMobile.value)
    mobileSidebarOpen.value = false
})

// 切到 desktop 时自动关闭抽屉
watch(isMobile, (v) => {
  if (!v)
    mobileSidebarOpen.value = false
})

// 公告（置顶）
const announcements = computed(() =>
  notifications.value.filter(n => n.type === 'announcement').slice(0, 3),
)
// 普通通知（安全预警等）
const personalNotifs = computed(() =>
  notifications.value.filter(n => n.type !== 'announcement'),
)

function closeOnMobile() {
  if (isMobile.value)
    mobileSidebarOpen.value = false
}

async function loadNotifications() {
  try {
    const result = await phpNotificationApi.list({ page: 1, page_size: 20 })
    notifications.value = result.data?.items || []
    unreadCount.value = Number(result.data?.unread_count || 0)
  }
  catch { /* 通知失败不阻断后台 */ }
}
async function readNotifications() {
  await phpNotificationApi.markRead()
  await loadNotifications()
}
async function clearNotification(id) {
  try {
    await phpNotificationApi.clear(id)
    await loadNotifications()
  }
  catch { /* 静默失败 */ }
}
async function clearAllNotifications() {
  if (!confirm('确认清空所有通知吗？（公告不会被清除）'))
    return
  try {
    await phpNotificationApi.clear()
    await loadNotifications()
  }
  catch { /* 静默失败 */ }
}

onMounted(() => {
  loadNotifications()
  prefs.load()
})

async function logout() {
  await session.logout()
  router.replace('/welcome')
}
</script>

<style scoped>
.admin-shell {
  height: 100%;
  min-height: 100vh;
  display: flex;
  overflow: hidden;
  background: var(--n-color);
  position: relative;
}
.admin-sidebar {
  width: 220px;
  flex: 0 0 220px;
  height: 100vh;
  padding: 20px 12px;
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  background: #172033;
  color: #fff;
  transition: transform 0.25s ease;
  z-index: 100;
}
.admin-logo {
  padding: 6px 12px 22px;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 17px;
  font-weight: 700;
  white-space: nowrap;
}
.logo-icon {
  font-size: 22px;
}
.logo-text {
  background: linear-gradient(135deg, #fff, #b9c3d6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.admin-menu {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.admin-menu a,
.back-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  color: #b9c3d6;
  text-decoration: none;
  border-radius: 8px;
  font-size: 14px;
  transition:
    background-color 0.15s,
    color 0.15s;
}
.admin-menu a:hover,
.admin-menu a.router-link-active,
.back-user:hover {
  color: #fff;
  background: #2a3957;
}
.menu-icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
  flex-shrink: 0;
}
.menu-text {
  flex: 1;
  min-width: 0;
}
.back-user {
  margin-top: auto;
  font-size: 13px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 14px;
  margin-top: 14px;
}
.admin-main {
  min-width: 0;
  min-height: 0;
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}
.admin-header {
  height: 60px;
  flex: 0 0 60px;
  padding: 0 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--n-border-color);
  background: var(--n-color);
  font-weight: 600;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.page-title {
  font-size: 15px;
}
.admin-user {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  font-weight: 400;
  color: var(--n-text-color-2);
}
.role-tag {
  padding: 2px 8px;
  border-radius: 4px;
  background: var(--n-color-info);
  color: var(--n-text-color);
  font-size: 12px;
}
.admin-content {
  min-height: 0;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  max-width: 1400px;
  width: 100%;
  padding: 24px 28px;
  box-sizing: border-box;
}

/* 移动端菜单按钮 */
.mobile-menu-btn {
  display: none;
  width: 36px;
  height: 36px;
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
}
.mobile-menu-btn:hover {
  background: var(--n-color-hover);
}
.hamburger {
  display: block;
  width: 100%;
  height: 2px;
  background: var(--n-text-color-2);
  position: relative;
  border-radius: 2px;
}
.hamburger::before,
.hamburger::after {
  content: '';
  position: absolute;
  left: 0;
  width: 100%;
  height: 2px;
  background: var(--n-text-color-2);
  border-radius: 2px;
}
.hamburger::before {
  top: -6px;
}
.hamburger::after {
  top: 6px;
}

.sidebar-mask {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 99;
}

/* 平板适配 */
@media (max-width: 1024px) {
  .admin-sidebar {
    width: 200px;
    flex-basis: 200px;
  }
  .admin-content {
    padding: 20px;
  }
  .admin-header {
    padding: 0 20px;
  }
}

/* 移动端适配 */
@media (max-width: 768px) {
  .mobile-menu-btn {
    display: block;
  }
  .hide-mobile {
    display: none !important;
  }

  .admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 240px;
    transform: translateX(-100%);
    box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15);
  }
  .admin-sidebar.is-open {
    transform: translateX(0);
  }
  .sidebar-mask {
    display: block;
  }

  .admin-header {
    padding: 0 14px;
    height: 56px;
  }
  .admin-content {
    padding: 16px 14px;
  }
  .page-title {
    font-size: 14px;
  }
  .admin-user {
    gap: 6px;
  }
}
/* 通知列表滚动 */
.notify-section {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.notify-list-wrap {
  max-height: 50vh;
  overflow-y: auto;
  overflow-x: hidden;
}
.notify-list-wrap--sm {
  max-height: 20vh;
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
.notify-item {
  position: relative;
  padding-right: 24px !important;
}
.notify-del-btn {
  position: absolute;
  top: 8px;
  right: 4px;
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  color: var(--n-text-color-3);
  cursor: pointer;
  border-radius: 4px;
  font-size: 14px;
  line-height: 1;
  display: none;
  align-items: center;
  justify-content: center;
  transition:
    background 0.15s,
    color 0.15s;
}
.notify-item:hover .notify-del-btn {
  display: flex;
}
.notify-del-btn:hover {
  color: #fff !important;
  background: var(--n-error-color);
}
</style>
