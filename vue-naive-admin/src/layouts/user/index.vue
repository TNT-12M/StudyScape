<template>
  <div class="user-shell">
    <aside class="user-sidebar">
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
        <RouterLink to="/app/questions" active-class="is-active">
          <span class="nav-icon">📚</span><span class="nav-text">题库浏览</span>
        </RouterLink>
        <RouterLink to="/app/feedback" active-class="is-active">
          <span class="nav-icon">💬</span><span class="nav-text">开发者反馈</span>
        </RouterLink>
        <RouterLink v-if="session.isAdmin" to="/admin" class="nav-admin-link" active-class="is-active">
          <span class="nav-icon">⚙️</span><span class="nav-text">管理后台</span>
        </RouterLink>
      </nav>
      <RouterLink v-if="session.isAdmin" class="back-admin hide-mobile" to="/admin">
        <span class="nav-icon">⚙️</span><span class="nav-text">进入管理后台</span>
      </RouterLink>
    </aside>
    <section class="user-main">
      <header class="user-header">
        <div class="user-page-title">
          学习中心
        </div>
        <div class="user-actions">
          <n-popover trigger="click" placement="bottom-end" @update:show="value => value && loadNotifications()"><template #trigger><n-badge :value="unreadCount" :max="99"><n-button quaternary size="small">通知</n-button></n-badge></template><n-space vertical style="max-width:340px"><n-text v-if="!notifications.length" depth="3">暂无通知</n-text><div v-else class="notify-list-wrap"><n-list size="small"><n-list-item v-for="item in notifications" :key="item.id"><n-thing :title="item.title" :description="item.body" /></n-list-item></n-list></div><n-button v-if="unreadCount" size="small" @click="readNotifications">全部标记已读</n-button></n-space></n-popover>
          <SessionProfileLink to="/app/profile" />
          <n-button quaternary size="small" @click="logout">
            退出
          </n-button>
        </div>
      </header>
      <main class="user-content">
        <RouterView />
      </main>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { phpNotificationApi } from '@/api/php-modules'
import { SessionProfileLink } from '@/layouts/components'
import { useSessionStore } from '@/store'

const router = useRouter()
const session = useSessionStore()
const notifications = ref([])
const unreadCount = ref(0)
async function loadNotifications() {
  try {
    const result = await phpNotificationApi.list({ page: 1, page_size: 10 })
    notifications.value = result.data?.items || []
    unreadCount.value = Number(result.data?.unread_count || 0)
  } catch { /* 通知失败不阻断学习端 */ }
}
async function readNotifications() {
  await phpNotificationApi.markRead()
  await loadNotifications()
}
onMounted(loadNotifications)

async function logout() {
  await session.logout()
  router.replace('/login')
}
</script>

<style scoped>
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
.nav-text { flex: 1; }
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
  transition:
    background-color 0.15s,
    color 0.15s;
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
@media (max-width: 800px) {
  .user-shell {
    display: block;
  }
  .user-sidebar {
    width: auto;
    min-height: auto;
    padding: 12px 14px 8px;
    position: sticky;
    top: 0;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  }
  .user-brand {
    padding: 0 0 10px;
  }
  .user-brand strong {
    font-size: 16px;
  }
  .user-brand span {
    display: none;
  }
  .user-menu {
    flex-direction: row;
    overflow-x: auto;
    gap: 6px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }
  .user-menu::-webkit-scrollbar { display: none; }
  .user-menu a {
    flex: 0 0 auto;
    padding: 8px 14px;
    white-space: nowrap;
    font-size: 13px;
    border-radius: 20px;
  }
  .back-admin {
    display: none; /* 移动端侧边栏隐藏 */
  }
  .hide-mobile { display: none; }
  .user-header {
    padding: 0 14px;
    height: 52px;
  }
  .user-page-title {
    font-size: 14px;
  }
  .user-actions {
    gap: 6px;
  }
  .user-content {
    padding: 16px 14px 32px;
  }
}
/* 通知列表滚动 */
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
