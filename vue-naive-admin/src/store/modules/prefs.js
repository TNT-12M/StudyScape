import { defineStore } from 'pinia'
import { phpSettingsApi } from '@/api/php-modules'

// 用户个性化偏好（壁纸）。本地持久化保证切换页面即时生效，登录后以服务端为准。
export const usePrefsStore = defineStore('user-prefs', {
  state: () => ({
    wallpaper: '',
    loaded: false,
  }),
  getters: {
    wallpaperUrl: state => (state.wallpaper
      ? `${import.meta.env.BASE_URL}assets/wallpapers/${state.wallpaper}.jpg`
      : ''),
  },
  actions: {
    async load() {
      if (this.loaded)
        return this.wallpaper
      try {
        const result = await phpSettingsApi.get()
        this.wallpaper = String(result.data?.wallpaper || '')
      }
      catch { /* 读取失败沿用本地值 */ }
      this.loaded = true
      return this.wallpaper
    },
    async save(wallpaper) {
      const value = String(wallpaper || '')
      await phpSettingsApi.save({ wallpaper: value })
      this.wallpaper = value
    },
  },
  persist: {
    pick: ['wallpaper'],
    storage: localStorage,
  },
})
