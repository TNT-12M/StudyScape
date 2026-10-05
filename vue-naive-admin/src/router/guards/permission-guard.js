import { useSessionStore } from '@/store'

const PUBLIC_PATHS = ['/login', '/403']

export function createPermissionGuard(router) {
  router.beforeEach(async (to) => {
    const session = useSessionStore()
    // 确保会话状态已就绪；如果已登录（setUser 已调用）则直接使用，不再发请求
    if (!session.checked) await session.ensureReady()

    if (PUBLIC_PATHS.includes(to.path) || to.meta.public) {
      if (to.path === '/login' && session.isAuthenticated) return session.isAdmin ? '/admin' : '/app'
      return true
    }

    if (to.meta.requiresAuth && !session.isAuthenticated) {
      return { path: '/login', query: { redirect: to.fullPath } }
    }

    if (to.meta.requiresRoot && !session.isRoot) return '/403'
    if (to.meta.requiresAdmin && !session.isAdmin) return '/403'
    if (to.meta.requiresPermission && !session.can(to.meta.requiresPermission)) return '/403'
    return true
  })
}
