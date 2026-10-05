<template>
  <div class="user-shell">
    <aside class="user-sidebar">
      <div class="user-brand">
        <strong>学境・StudyScape</strong>
        <span>学习的新境界</span>
      </div>
      <nav class="user-menu" aria-label="用户导航">
        <RouterLink to="/app" exact-active-class="is-active">
          <span>学习首页</span>
        </RouterLink>
        <RouterLink to="/app/practice" active-class="is-active">
          <span>在线刷题</span>
        </RouterLink>
        <RouterLink to="/app/papers" active-class="is-active">
          <span>在线考试</span>
        </RouterLink>
        <RouterLink to="/app/materials" active-class="is-active">
          <span>资料中心</span>
        </RouterLink>
        <RouterLink to="/app/questions" active-class="is-active">
          <span>题库浏览</span>
        </RouterLink>
        <RouterLink to="/app/feedback" active-class="is-active">
          <span>开发者反馈</span>
        </RouterLink>
      </nav>
      <RouterLink v-if="session.isAdmin" class="back-admin" to="/admin">
        <span>进入管理后台</span>
      </RouterLink>
    </aside>
    <section class="user-main">
      <header class="user-header">
        <div class="user-page-title">
          学习中心
        </div>
        <div class="user-actions">
          <n-popover trigger="click" placement="bottom-end" @update:show="value => value && loadNotifications()"><template #trigger><n-badge :value="unreadCount" :max="99"><n-button quaternary size="small">通知</n-button></n-badge></template><n-space vertical style="max-width:320px"><n-text v-if="!notifications.length" depth="3">暂无通知</n-text><n-list v-else size="small"><n-list-item v-for="item in notifications" :key="item.id"><n-thing :title="item.title" :description="item.body" /></n-list-item></n-list><n-button v-if="unreadCount" size="small" @click="readNotifications">全部标记已读</n-button></n-space></n-popover>
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
  padding: 12px 14px;
  color: #b9c3d6;
  text-decoration: none;
  border-radius: 6px;
  font-size: 14px;
  transition:
    background-color 0.15s,
    color 0.15s;
}
.user-menu a:hover,
.user-menu a.is-active {
  color: #fff;
  background: #2a3957;
}
.back-admin {
  margin-top: auto;
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
    padding: 14px 16px 10px;
  }
  .user-brand {
    padding: 0 0 12px;
  }
  .user-brand strong {
    font-size: 17px;
  }
  .user-brand span {
    display: none;
  }
  .user-menu {
    flex-direction: row;
    overflow-x: auto;
    gap: 4px;
  }
  .user-menu a {
    flex: 0 0 auto;
    padding: 9px 12px;
    white-space: nowrap;
  }
  .back-admin {
    margin-top: 8px;
    padding: 9px 12px;
    text-align: center;
  }
  .user-header {
    padding: 0 16px;
  }
  .user-content {
    padding: 20px 16px;
  }
}
</style>
