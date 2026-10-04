<template>
  <button class="profile-link" type="button" :aria-label="`打开${displayName}的个人资料`" @click="router.push(to)">
    <n-avatar
      round
      :size="36"
      :src="avatarSrc"
      :fallback-src="fallbackAvatar"
      @error="avatarSrc = ''"
    >
      {{ initial }}
    </n-avatar>
    <span class="profile-link__name">{{ displayName }}</span>
  </button>
</template>

<script setup>
import { useSessionStore } from '@/store'

const props = defineProps({
  to: {
    type: String,
    required: true,
  },
})

const router = useRouter()
const session = useSessionStore()
const avatarSrc = ref('')

const displayName = computed(() => session.user?.nickname?.trim() || session.user?.username || '用户')
const initial = computed(() => displayName.value.slice(0, 1).toUpperCase())
const fallbackAvatar = computed(() => `https://api.dicebear.com/9.x/initials/svg?seed=${encodeURIComponent(displayName.value)}`)

watch(() => session.user?.avatar, (value) => {
  avatarSrc.value = value || ''
}, { immediate: true })

watch(() => props.to, () => {
  avatarSrc.value = session.user?.avatar || ''
})
</script>

<style scoped>
.profile-link {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  min-width: 0;
  padding: 3px 6px;
  color: inherit;
  background: transparent;
  border: 0;
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}
.profile-link:hover {
  background: var(--n-color-hover);
}
.profile-link__name {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
