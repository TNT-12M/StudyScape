<template>
  <AppPage show-footer>
    <n-card title="个人资料" :bordered="false">
      <n-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-placement="left"
        label-width="90"
        require-mark-placement="right-hanging"
        @submit.prevent="save"
      >
        <n-grid :cols="1" :y-gap="18">
          <n-gi>
            <n-form-item label="头像" path="avatar">
              <n-space align="center">
                <n-avatar round :size="72" :src="avatarPreview" @error="avatarPreview = ''">
                  {{ initial }}
                </n-avatar>
                <n-input
                  v-model:value="form.avatar"
                  clearable
                  maxlength="512"
                  show-count
                  placeholder="请输入图片 URL，例如 https://example.com/avatar.png"
                  style="width: min(520px, 100%)"
                />
              </n-space>
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="昵称" path="nickname">
              <n-input v-model:value="form.nickname" maxlength="32" show-count placeholder="请输入昵称" />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="性别" path="gender">
              <n-select v-model:value="form.gender" :options="genderOptions" />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="年级" path="grade">
              <n-select v-model:value="form.grade" clearable :options="gradeOptions" placeholder="请选择年级" />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="用户名">
              <n-input :value="session.user?.username || ''" disabled />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="邮箱">
              <n-input :value="session.user?.email || ''" disabled />
            </n-form-item>
          </n-gi>
        </n-grid>
        <n-space justify="end">
          <n-button attr-type="submit" type="primary" :loading="saving">保存资料</n-button>
        </n-space>
      </n-form>
    </n-card>
  </AppPage>
</template>

<script setup>
import { useSessionStore } from '@/store'

const session = useSessionStore()
const formRef = ref(null)
const saving = ref(false)
const form = reactive({
  nickname: '',
  avatar: '',
  gender: 'secret',
  grade: null,
})

const genderOptions = [
  { label: '保密', value: 'secret' },
  { label: '男', value: 'male' },
  { label: '女', value: 'female' },
]
const gradeOptions = [
  { label: '七年级', value: 'grade7' },
  { label: '八年级', value: 'grade8' },
  { label: '九年级', value: 'grade9' },
  { label: '高一', value: 'high1' },
  { label: '高二', value: 'high2' },
  { label: '高三', value: 'high3' },
]

const isImageUrl = (value) => {
  const url = String(value || '').trim()
  if (!url) return true
  if (url.length > 512 || /[\u0000- <>\u007f]/.test(url)) return false
  try {
    const parsed = new URL(url)
    return ['http:', 'https:'].includes(parsed.protocol)
      && Boolean(parsed.hostname)
      && !parsed.username
      && !parsed.password
      && /\.(jpe?g|png|gif|webp|avif)$/i.test(parsed.pathname)
  }
  catch {
    return false
  }
}

const rules = {
  nickname: {
    validator: (_, value) => {
      const text = String(value || '').trim()
      if (text.length > 32) return new Error('昵称不能超过 32 个字符')
      if (/[\u0000-\u001f\u007f]/.test(text)) return new Error('昵称不能包含控制字符')
      return true
    },
    trigger: ['blur', 'input'],
  },
  avatar: {
    validator: (_, value) => isImageUrl(value) || new Error('请输入有效的 http/https 图片链接') ,
    trigger: ['blur', 'change'],
  },
}

const displayName = computed(() => form.nickname.trim() || session.user?.username || '用户')
const initial = computed(() => displayName.value.slice(0, 1).toUpperCase())
const avatarPreview = ref('')
watch(() => form.avatar, (value) => {
  avatarPreview.value = String(value || '').trim()
}, { immediate: true })
watch(() => session.user, (user) => {
  if (!user) return
  Object.assign(form, {
    nickname: user.nickname || '',
    avatar: user.avatar || '',
    gender: user.gender || 'secret',
    grade: user.grade || null,
  })
}, { immediate: true })

async function save() {
  try {
    await formRef.value?.validate()
    saving.value = true
    await session.updateProfile({
      nickname: form.nickname.trim(),
      avatar: form.avatar.trim(),
      gender: form.gender,
      grade: form.grade || '',
    })
    Object.assign(form, {
      nickname: session.user?.nickname || '',
      avatar: session.user?.avatar || '',
      gender: session.user?.gender || 'secret',
      grade: session.user?.grade || null,
    })
    $message.success('资料已保存')
  }
  catch (error) {
    if (error?.errors) return
    $message.error(error.message || '资料保存失败')
  }
  finally {
    saving.value = false
  }
}
</script>

<style scoped>
:deep(.n-form-item-label) {
  align-self: flex-start;
}
</style>
