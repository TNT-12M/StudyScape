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
              <n-input v-model:value="form.nickname" maxlength="20" show-count placeholder="中英文/数字/下划线，最多20字" />
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

    <n-card title="修改密码" :bordered="false" style="margin-top: 16px;">
      <n-form
        ref="pwdFormRef"
        :model="pwdForm"
        :rules="pwdRules"
        label-placement="left"
        label-width="100"
        require-mark-placement="right-hanging"
        @submit.prevent="changePassword"
      >
        <n-grid :cols="1" :y-gap="18">
          <n-gi>
            <n-form-item label="当前密码" path="old_password">
              <n-input v-model:value="pwdForm.old_password" type="password" show-password-on="mousedown" placeholder="请输入当前密码" />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="新密码" path="new_password">
              <n-input v-model:value="pwdForm.new_password" type="password" show-password-on="mousedown" placeholder="至少 6 位" />
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="确认新密码" path="confirm_password">
              <n-input v-model:value="pwdForm.confirm_password" type="password" show-password-on="mousedown" placeholder="再次输入新密码" />
            </n-form-item>
          </n-gi>
        </n-grid>
        <n-space justify="end">
          <n-button attr-type="submit" type="primary" :loading="pwdSaving">修改密码</n-button>
        </n-space>
      </n-form>
    </n-card>
  </AppPage>
</template>

<script setup>
import { useSessionStore } from '@/store'
import { phpProfileApi } from '@/api/php-modules'

const session = useSessionStore()
const router = useRouter()
const formRef = ref(null)
const saving = ref(false)
const form = reactive({
  nickname: '',
  avatar: '',
  gender: 'secret',
  grade: null,
})

// ===== 修改密码 =====
const pwdFormRef = ref(null)
const pwdSaving = ref(false)
const pwdForm = reactive({
  old_password: '',
  new_password: '',
  confirm_password: '',
})

const pwdRules = {
  old_password: {
    required: true,
    message: '请输入当前密码',
    trigger: 'blur',
  },
  new_password: {
    validator: (_, value) => {
      if (!value) return new Error('请输入新密码')
      if (value.length < 6) return new Error('新密码至少 6 位')
      if (value === pwdForm.old_password) return new Error('新密码不能与旧密码相同')
      return true
    },
    trigger: 'blur',
  },
  confirm_password: {
    validator: (_, value) => {
      if (!value) return new Error('请再次输入新密码')
      if (value !== pwdForm.new_password) return new Error('两次输入的密码不一致')
      return true
    },
    trigger: 'blur',
  },
}

async function changePassword() {
  try {
    await pwdFormRef.value?.validate()
    pwdSaving.value = true
    await phpProfileApi.changePassword({
      old_password: pwdForm.old_password,
      new_password: pwdForm.new_password,
      confirm_password: pwdForm.confirm_password,
    })
    $message.success('密码修改成功，请使用新密码重新登录')
    // 密码修改后强制退出登录，使用新密码重新登录
    await session.logout()
    await router.replace('/login')
  }
  catch (error) {
    if (error?.errors) return
    $message.error(error.message || '密码修改失败')
    pwdSaving.value = false
  }
}

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

// 根据年级推导学段
function gradeToLevel(grade) {
  if (!grade) return null
  if (['grade7', 'grade8', 'grade9'].includes(grade)) return 'junior'
  if (['high1', 'high2', 'high3'].includes(grade)) return 'senior'
  return null
}
function gradeLabel(grade) {
  const g = gradeOptions.find(o => o.value === grade)
  return g?.label || grade
}

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
      if (text === '') return true
      if ([...text].length > 20) return new Error('昵称不能超过 20 个字符')
      if (!/^[\u4e00-\u9fa5a-zA-Z0-9_\s\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]+$/u.test(text)) return new Error('昵称只能包含中文、英文、数字、下划线、空格和表情符号')
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
    const oldGrade = session.user?.grade || null
    const newGrade = form.grade || null
    const gradeChanged = oldGrade !== newGrade

    // 年级变化时弹确认框
    if (gradeChanged && newGrade) {
      const level = gradeToLevel(newGrade)
      const levelText = level === 'junior' ? '初中' : level === 'senior' ? '高中' : ''
      try {
        await new Promise((resolve, reject) => {
          $dialog.warning({
            title: '确认设置年级',
            content: `设置为${gradeLabel(newGrade)}后，系统将优先为您展示${levelText}阶段的题库和资料，确定继续吗？`,
            positiveText: '确认设置',
            negativeText: '再想想',
            onPositiveClick: () => resolve(),
            onNegativeClick: () => reject(new Error('cancel')),
            onClose: () => reject(new Error('cancel')),
          })
        })
      } catch (e) {
        if (e.message === 'cancel') return
      }
    }

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
    $message.success(gradeChanged ? '年级已设置，内容已按学段过滤' : '资料已保存')
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
