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
        this.user = result.data?.user || null
      }
      catch {
        this.user = null
      }
      this.ready = true
      this.checked = true
      return this.user
    },
    setUser(user) {
      this.user = user || null
      this.ready = true
      this.checked = true
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
      }
    },
  },
})
