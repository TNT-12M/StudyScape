import { defineStore } from 'pinia'
import { phpAuthApi, phpProfileApi } from '@/api/php-modules'
import { clearPhpSessionState } from '@/api/php'

export const useSessionStore = defineStore('php-session', {
  state: () => ({
    user: null,
    ready: false,
    checked: false,
  }),
  getters: {
    isAuthenticated: state => Boolean(state.user?.id),
    role: state => state.user?.role || (state.user?.is_admin ? 'root' : 'user'),
    isRoot: state => (state.user?.role || (state.user?.is_admin ? 'root' : 'user')) === 'root',
    isContentAdmin: state => ['root', 'content_admin'].includes(state.user?.role || (state.user?.is_admin ? 'root' : 'user')),
    isAdmin: state => ['root', 'content_admin'].includes(state.user?.role || (state.user?.is_admin ? 'root' : 'user')),
    defaultEducationLevel: state => {
      const grade = state.user?.grade
      if (!grade) return null
      if (['grade7', 'grade8', 'grade9'].includes(grade)) return 'junior'
      if (['high1', 'high2', 'high3'].includes(grade)) return 'senior'
      return null
    },
    can: state => permission => {
      const role = state.user?.role || (state.user?.is_admin ? 'root' : 'user')
      if (role === 'root') return true
      return role === 'content_admin' && ['question_view', 'question_edit', 'question_import', 'material_manage'].includes(permission)
    },
  },
  actions: {
    async refresh() {
      try {
        const result = await phpAuthApi.checkSession()
        const serverUser = result.data?.user || null
        // 如果 setUser 已经设置了用户（如登录后），不要用 refresh 的结果覆盖
        // 避免 session_regenerate_id 后立即二次请求偶发的 cookie 不同步问题
        if (!this._userSetByLogin) {
          this.user = serverUser
        }
      }
      catch {
        if (!this._userSetByLogin) {
          this.user = null
        }
      }
      this.ready = true
      this.checked = true
      return this.user
    },
    setUser(user) {
      this.user = user || null
      this.ready = true
      this.checked = true
      this._userSetByLogin = Boolean(user?.id)
    },
    async ensureReady() {
      if (this.checked) return this.user
      // 避免重复并发请求
      if (!this._refreshPromise) this._refreshPromise = this.refresh().finally(() => { this._refreshPromise = null })
      return this._refreshPromise
    },
    async updateProfile(data) {
      const result = await phpProfileApi.update(data)
      const user = result.data?.user
      if (user?.id) this.setUser(user)
      else await this.refresh()
      return this.user
    },
    async logout() {
      try {
        await phpAuthApi.logout()
      }
      finally {
        clearPhpSessionState()
        this.user = null
        this.ready = true
        this.checked = true
        this._userSetByLogin = false
      }
    },
  },
})
