<template>
  <div class="admin-shell">
    <aside class="admin-sidebar">
      <div class="admin-logo">学境・StudyScape</div>
      <nav class="admin-menu">
        <RouterLink to="/admin">平台概览</RouterLink>
        <RouterLink v-if="session.isRoot" to="/admin/users">用户授权</RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/admin/questions">题库管理</RouterLink>
        <RouterLink v-if="session.isRoot" to="/admin/papers">考试管理</RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/admin/materials">资料管理</RouterLink>
        <RouterLink v-if="session.isRoot" to="/admin/feedback">反馈处理</RouterLink>
        <RouterLink v-if="session.isRoot" to="/admin/announcements">公告管理</RouterLink>
        <RouterLink v-if="session.isContentAdmin" to="/admin/ocr">智能 OCR 审核</RouterLink>
      </nav>
      <RouterLink class="back-user" to="/app">返回用户端</RouterLink>
    </aside>
    <section class="admin-main">
      <header class="admin-header">
        <span>管理后台</span>
        <div class="admin-user"><n-popover trigger="click" placement="bottom-end" @update:show="value => value && loadNotifications()"><template #trigger><n-badge :value="unreadCount" :max="99"><n-button quaternary size="small">通知</n-button></n-badge></template><n-space vertical style="max-width:320px"><n-text v-if="!notifications.length" depth="3">暂无通知</n-text><n-list v-else size="small"><n-list-item v-for="item in notifications" :key="item.id"><n-thing :title="item.title" :description="item.body" /></n-list-item></n-list><n-button v-if="unreadCount" size="small" @click="readNotifications">全部标记已读</n-button></n-space></n-popover><span>{{ session.user?.username }} · {{ session.isRoot ? 'root' : '内容管理员' }}</span><n-button quaternary size="small" @click="logout">退出</n-button></div>
      </header>
      <main class="admin-content"><RouterView /></main>
    </section>
  </div>
</template>

<script setup>
import { phpNotificationApi } from '@/api/php-modules'
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
  } catch { /* 通知失败不阻断后台 */ }
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
.admin-shell { height: 100%; min-height: 100vh; display: flex; overflow: hidden; background: var(--n-color); }
.admin-sidebar { width: 220px; flex: 0 0 220px; height: 100vh; padding: 24px 14px; display: flex; flex-direction: column; overflow-y: auto; background: #172033; color: #fff; }
.admin-logo { padding: 0 14px 28px; font-size: 20px; font-weight: 700; }
.admin-menu { display: flex; flex-direction: column; gap: 4px; }
.admin-menu a, .back-user { padding: 11px 14px; color: #b9c3d6; text-decoration: none; border-radius: 6px; }
.admin-menu a:hover, .admin-menu a.router-link-active, .back-user:hover { color: #fff; background: #2a3957; }
.back-user { margin-top: auto; font-size: 13px; }
.admin-main { min-width: 0; min-height: 0; display: flex; flex-direction: column; flex: 1; }
.admin-header { height: 64px; flex: 0 0 64px; padding: 0 28px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--n-border-color); background: var(--n-color); font-weight: 600; }
.admin-user { display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 400; color: var(--n-text-color-2); }
.admin-content { min-height: 0; flex: 1; overflow-y: auto; overflow-x: hidden; max-width: 1400px; width: 100%; padding: 28px; box-sizing: border-box; }
@media (max-width: 800px) { .admin-sidebar { width: 72px; flex-basis: 72px; padding: 20px 8px; } .admin-logo { padding: 0 5px 28px; font-size: 0; } .admin-logo::after { content: '学'; font-size: 20px; } .admin-menu a, .back-user { padding: 11px 5px; font-size: 0; text-align: center; } .admin-menu a::first-letter, .back-user::first-letter { font-size: 16px; } .admin-header, .admin-content { padding-left: 16px; padding-right: 16px; } }
</style>
