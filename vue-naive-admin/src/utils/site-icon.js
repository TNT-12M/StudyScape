import { computed, ref } from 'vue'

const version = ref(0)

function baseUrl() {
  return `${import.meta.env.VITE_PHP_API_URL || '/api.php'}?action=site_icon`
}

export const siteIconSrc = computed(() => {
  const url = baseUrl()
  return version.value ? `${url}&v=${version.value}` : url
})

export function setSiteIconVersion(v) {
  version.value = Number(v) || Date.now()
}

// 把上传后的图标同步为浏览器标签页图标
export function applyFavicon() {
  const probe = new Image()
  probe.onload = () => {
    let link = document.querySelector('link[rel="icon"]')
    if (!link) {
      link = document.createElement('link')
      link.rel = 'icon'
      document.head.appendChild(link)
    }
    link.href = siteIconSrc.value
  }
  probe.src = siteIconSrc.value
}
