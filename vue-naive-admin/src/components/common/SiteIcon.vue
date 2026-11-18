<template>
  <img
    v-if="!failed"
    class="site-icon"
    :src="siteIconSrc"
    alt="学境图标"
    @error="failed = true"
  >
  <span v-else class="site-icon-fallback">境</span>
</template>

<script setup>
import { ref, watch } from 'vue'
import { siteIconSrc } from '@/utils/site-icon'

const failed = ref(false)
// 后台更换图标后，已挂载的 Logo 也能立即重新加载
watch(siteIconSrc, () => {
  failed.value = false
})
</script>

<style scoped>
.site-icon {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: inherit;
  object-fit: cover;
}
.site-icon-fallback {
  display: flex;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
  color: inherit;
  font: inherit;
}
</style>
