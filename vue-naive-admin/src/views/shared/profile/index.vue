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
              <n-space align="center" :wrap="true">
                <n-avatar round :size="72" :src="avatarPreview" @error="onAvatarPreviewError">
                  <template v-if="!avatarPreview">
                    {{ initial }}
                  </template>
                </n-avatar>
                <div class="avatar-controls">
                  <n-input
                    v-model:value="avatarInput"
                    clearable
                    maxlength="512"
                    show-count
                    :placeholder="localAvatar ? '已使用上传的头像；如需改用外链，请粘贴图片 URL' : '可粘贴图片 URL，或点击「上传图片」'"
                    style="width: min(520px, 100%)"
                    @update:value="onAvatarInput"
                  />
                  <n-space align="center">
                    <n-button size="small" :loading="uploadingAvatar" @click="pickAvatarFile">
                      上传图片
                    </n-button>
                    <n-tag v-if="localAvatar" size="small" type="success">
                      已使用上传的头像
                    </n-tag>
                    <span class="avatar-tip">支持 JPG / PNG / WEBP，≤2MB，自动压缩为 256×256</span>
                  </n-space>
                  <span v-if="avatarLoadFailed" class="avatar-error">头像加载失败，请检查网络后重新上传，或保存后重试</span>
                  <input
                    id="avatar-file-input"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    hidden
                    @change="onAvatarFileChange"
                  >
                </div>
              </n-space>
            </n-form-item>
          </n-gi>
          <n-gi>
            <n-form-item label="用户名" path="username">
              <n-input v-model:value="form.username" maxlength="20" show-count placeholder="中英文/数字/下划线，最多20字，每月可改 5 次" />
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
            <n-form-item label="邮箱">
              <n-input :value="session.user?.email || ''" disabled />
            </n-form-item>
          </n-gi>
        </n-grid>
        <n-space justify="end">
          <n-button attr-type="submit" type="primary" :loading="saving">
            保存资料
          </n-button>
        </n-space>
      </n-form>
    </n-card>

    <n-card title="学境壁纸" :bordered="false" style="margin-top: 16px;">
      <n-alert type="info" :show-icon="false" style="margin-bottom: 14px;">
        壁纸会显示在学习中心左侧导航栏。图片由管理员放入 <code>assets/wallpapers/</code> 目录，缺失时显示占位色块。
      </n-alert>
      <div class="wallpaper-grid">
        <div class="wallpaper-item" :class="{ active: wpDraft === '' }" @click="wpDraft = ''">
          <div class="wp-thumb wp-default">
            默认
          </div>
          <span>默认深色</span>
        </div>
        <div v-for="i in 4" :key="i" class="wallpaper-item" :class="{ active: wpDraft === `wallpaper-${i}` }" @click="wpDraft = `wallpaper-${i}`">
          <div class="wp-thumb">
            <img v-if="!wpFailed[`wallpaper-${i}`]" :src="wallpaperSrc(`wallpaper-${i}`)" alt="" @error="wpFailed[`wallpaper-${i}`] = true">
            <span v-else>{{ i }}</span>
          </div>
          <span>壁纸 {{ i }}</span>
        </div>
      </div>
      <n-space justify="end" style="margin-top: 14px;">
        <n-button type="primary" :loading="wpSaving" @click="saveWallpaper">
          保存壁纸
        </n-button>
      </n-space>
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
          <n-button attr-type="submit" type="primary" :loading="pwdSaving">
            修改密码
          </n-button>
        </n-space>
      </n-form>
    </n-card>
  </AppPage>
</template>

<script setup>
import { phpProfileApi } from '@/api/php-modules'
import { usePrefsStore, useSessionStore } from '@/store'

const session = useSessionStore()
const prefs = usePrefsStore()
const router = useRouter()
const wpDraft = ref('')
const wpSaving = ref(false)
const wpFailed = reactive({})

function wallpaperSrc(name) {
  return `${import.meta.env.BASE_URL}assets/wallpapers/${name}.jpg`
}
async function saveWallpaper() {
  wpSaving.value = true
  try {
    await prefs.save(wpDraft.value)
    $message.success('壁纸已保存')
  }
  catch (error) { $message.error(error.message || '壁纸保存失败') }
  finally { wpSaving.value = false }
}
watch(() => prefs.wallpaper, (value) => {
  wpDraft.value = value || ''
}, { immediate: true })
const formRef = ref(null)
const saving = ref(false)
const form = reactive({
  username: '',
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
      if (!value)
        return new Error('请输入新密码')
      if (value.length < 6)
        return new Error('新密码至少 6 位')
      if (value === pwdForm.old_password)
        return new Error('新密码不能与旧密码相同')
      return true
    },
    trigger: 'blur',
  },
  confirm_password: {
    validator: (_, value) => {
      if (!value)
        return new Error('请再次输入新密码')
      if (value !== pwdForm.new_password)
        return new Error('两次输入的密码不一致')
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
    if (error?.errors)
      return
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
  if (!grade)
    return null
  if (['grade7', 'grade8', 'grade9'].includes(grade))
    return 'junior'
  if (['high1', 'high2', 'high3'].includes(grade))
    return 'senior'
  return null
}
function gradeLabel(grade) {
  const g = gradeOptions.find(o => o.value === grade)
  return g?.label || grade
}

function isImageUrl(value) {
  const url = String(value || '').trim()
  if (!url)
    return true
  if (url.length > 512 || /[<>"]/.test(url) || Array.from(url).some(ch => ch.charCodeAt(0) <= 0x20 || ch.charCodeAt(0) === 0x7F))
    return false
  // 站内上传的头像（固定接口格式）
  if (/^\/api\.php\?action=avatar_image&uid=\d+&v=\d+$/.test(url))
    return true
  try {
    const parsed = new URL(url)
    return ['http:', 'https:'].includes(parsed.protocol)
      && Boolean(parsed.hostname)
      && !parsed.username
      && !parsed.password
      && /\.(?:jpe?g|png|gif|webp|avif)$/i.test(parsed.pathname)
  }
  catch {
    return false
  }
}

const avatarInput = ref('')
const localAvatar = ref('')

const rules = {
  username: {
    validator: (_, value) => {
      const text = String(value || '').trim()
      if (text === '')
        return new Error('用户名不能为空')
      if ([...text].length < 2)
        return new Error('用户名至少 2 个字符')
      if ([...text].length > 20)
        return new Error('用户名最多 20 个字符')
      if (!/^[\u4e00-\u9fa5\w]+$/.test(text))
        return new Error('用户名只能包含中文、英文、数字、下划线')
      return true
    },
    trigger: ['blur', 'input'],
  },
  avatar: {
    validator: () => isImageUrl(avatarInput.value) || new Error('请输入有效的 http/https 图片链接'),
    trigger: ['blur', 'change'],
  },
}

const displayName = computed(() => form.username.trim() || session.user?.username || '用户')
const initial = computed(() => displayName.value.slice(0, 1).toUpperCase())
const avatarPreview = ref('')
const avatarLoadFailed = ref(false)
const uploadingAvatar = ref(false)

function onAvatarPreviewError() {
  avatarPreview.value = ''
  avatarLoadFailed.value = true
}

function isInternalAvatar(value) {
  return /^\/api\.php\?action=avatar_image&(?:f=[0-9a-f]{32}|uid=\d+&v=\d+)$/.test(String(value || ''))
}
function applyAvatar(value) {
  const url = String(value || '').trim()
  form.avatar = url
  const isLocal = isInternalAvatar(url)
  localAvatar.value = isLocal ? url : ''
  avatarInput.value = isLocal ? '' : url
  avatarPreview.value = url
  avatarLoadFailed.value = false
  if (!url)
    avatarPreview.value = ''
}
function onAvatarInput(value) {
  const url = String(value || '').trim()
  form.avatar = url || localAvatar.value
  avatarPreview.value = form.avatar
  avatarLoadFailed.value = false
}
function pickAvatarFile() {
  document.getElementById('avatar-file-input')?.click()
}
async function onAvatarFileChange(event) {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''
  if (!file)
    return
  if (!/^image\/(?:jpeg|png|webp)$/.test(file.type)) {
    $message.warning('仅支持 JPG / PNG / WEBP 格式的头像')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    $message.warning('头像图片不能超过 2MB，请压缩后重试')
    return
  }
  uploadingAvatar.value = true
  try {
    const result = await phpProfileApi.uploadAvatar({ file })
    const avatarUrl = String(result.data?.avatar || '')
    applyAvatar(avatarUrl)
    // 上传已直接写库，立即同步到会话，顶部头像无需再点保存即可生效
    if (avatarUrl && session.user)
      session.setUser({ ...session.user, avatar: avatarUrl })
    $message.success('头像已更新')
  }
  catch (error) {
    $message.error(error.message || '头像上传失败')
  }
  finally {
    uploadingAvatar.value = false
  }
}
watch(() => session.user, (user) => {
  if (!user)
    return
  Object.assign(form, {
    username: user.username || '',
    gender: user.gender || 'secret',
    grade: user.grade || null,
  })
  applyAvatar(user.avatar || '')
}, { immediate: true })

async function save() {
  try {
    // 输入框为空时沿用已上传的站内头像
    form.avatar = avatarInput.value.trim() || localAvatar.value
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
      }
      catch (e) {
        if (e.message === 'cancel')
          return
      }
    }

    saving.value = true
    await session.updateProfile({
      username: form.username.trim(),
      nickname: '', // 不再改昵称，留空让后端保持原值
      avatar: form.avatar.trim(),
      gender: form.gender,
      grade: form.grade || '',
    })
    Object.assign(form, {
      username: session.user?.username || '',
      avatar: session.user?.avatar || '',
      gender: session.user?.gender || 'secret',
      grade: session.user?.grade || null,
    })
    $message.success(gradeChanged ? '年级已设置，内容已按学段过滤' : '资料已保存')
  }
  catch (error) {
    if (error?.errors)
      return
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
.wallpaper-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.avatar-controls {
  flex: 1;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.avatar-tip {
  font-size: 12px;
  color: var(--n-text-color-3);
}
.avatar-error {
  font-size: 12px;
  color: var(--n-color-target);
}
.wallpaper-item {
  width: 84px;
  cursor: pointer;
  text-align: center;
  font-size: 12px;
  color: var(--n-text-color-2);
}
.wp-thumb {
  width: 84px;
  height: 120px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  background: #172033;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 6px;
  transition: border-color 0.15s;
}
.wp-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.wp-thumb span {
  color: #9eabc1;
  font-size: 18px;
}
.wp-default {
  background: linear-gradient(160deg, #24334d, #172033);
  color: #9eabc1;
}
.wallpaper-item.active .wp-thumb {
  border-color: #316c72;
}
.wallpaper-item.active span {
  color: #316c72;
  font-weight: 600;
}
</style>
