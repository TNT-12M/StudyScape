<template>
  <div class="login-page">
    <n-card class="login-card" :bordered="false">
      <div class="login-heading">
        <div class="brand-mark">境</div>
        <div><h1>学境・StudyScape</h1><p>学习的新境界</p></div>
      </div>
      <n-tabs v-model:value="mode" animated>
        <n-tab-pane name="login" tab="登录">
          <n-form class="login-form" @submit.prevent="login">
            <n-form-item label="用户名"><n-input v-model:value="form.username" placeholder="2-20字，中英文/数字/下划线" maxlength="20" @keydown.enter="login" /></n-form-item>
            <n-form-item label="密码"><n-input v-model:value="form.password" type="password" show-password-on="mousedown" placeholder="请输入密码" @keydown.enter="login" /></n-form-item>
            <div class="form-footer-row">
              <span></span>
              <a class="forgot-link" @click="goForgot">忘记密码？</a>
            </div>
            <n-button type="primary" block :loading="loading" attr-type="submit">登录</n-button>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="register" tab="注册">
          <n-form class="login-form" @submit.prevent="register">
            <n-form-item label="用户名"><n-input v-model:value="regForm.username" placeholder="2-20字，中英文/数字/下划线" maxlength="20" /></n-form-item>
            <n-form-item label="邮箱"><n-input v-model:value="regForm.email" placeholder="@example.com" /></n-form-item>
            <div class="captcha-row">
              <n-input v-model:value="regForm.captcha" placeholder="图片验证码" maxlength="4" />
              <img :src="regCaptchaUrl" alt="验证码" title="点击刷新" @click="refreshRegCaptcha">
            </div>
            <div class="email-code-row">
              <n-input v-model:value="regForm.email_code" placeholder="邮箱验证码" maxlength="6" />
              <n-button :disabled="emailCountdown > 0 || !canSendEmailCode" @click="sendRegEmailCode" :loading="emailSending">
                {{ emailCountdown > 0 ? `${emailCountdown}s 后重发` : '获取验证码' }}
              </n-button>
            </div>
            <n-form-item label="密码"><n-input v-model:value="regForm.password" type="password" placeholder="至少 6 位" /></n-form-item>
            <n-button type="primary" block :loading="loading" attr-type="submit">创建账号</n-button>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="forgot" tab="忘记密码">
          <n-form class="login-form" @submit.prevent="doReset">
            <n-form-item label="账号"><n-input v-model:value="resetForm.account" placeholder="用户名或邮箱" /></n-form-item>
            <n-form-item label="绑定邮箱"><n-input v-model:value="resetForm.email" placeholder="注册时使用的邮箱" /></n-form-item>
            <div class="captcha-row">
              <n-input v-model:value="resetForm.captcha" placeholder="图片验证码" maxlength="4" />
              <img :src="resetCaptchaUrl" alt="验证码" title="点击刷新" @click="refreshResetCaptcha">
            </div>
            <div class="email-code-row">
              <n-input v-model:value="resetForm.email_code" placeholder="邮箱验证码" maxlength="6" />
              <n-button :disabled="resetCountdown > 0 || !canSendResetCode" @click="sendResetEmailCode" :loading="resetEmailSending">
                {{ resetCountdown > 0 ? `${resetCountdown}s 后重发` : '获取验证码' }}
              </n-button>
            </div>
            <n-form-item label="新密码"><n-input v-model:value="resetForm.new_password" type="password" show-password-on="mousedown" placeholder="至少 6 位" /></n-form-item>
            <n-button type="primary" block :loading="resetLoading" attr-type="submit">重置密码</n-button>
            <div class="back-login-row"><a @click="mode = 'login'">← 返回登录</a></div>
          </n-form>
        </n-tab-pane>
      </n-tabs>
    </n-card>
  </div>
</template>

<script setup>
import { phpAuthApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'

const router = useRouter()
const route = useRoute()
const session = useSessionStore()

const mode = ref(route.query.mode === 'register' ? 'register' : (route.query.mode === 'forgot' ? 'forgot' : 'login'))
const loading = ref(false)

// ===== 登录 =====
const form = reactive({ username: '', password: '' })

async function login() {
  if (!form.username || !form.password) return $message.warning('请输入用户名和密码')
  loading.value = true
  try {
    const result = await phpAuthApi.login({ username: form.username, password: form.password })
    const user = result.data?.user
    if (!user?.id) throw new Error('登录响应无效，请重试')
    session.setUser(user)
    $message.success(result.message || '登录成功')
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
      ? route.query.redirect
      : null
    router.replace(redirect || (user.is_admin ? '/admin' : '/app'))
  }
  catch (error) { $message.error(error.message || '登录失败') }
  finally { loading.value = false }
}

function goForgot() { mode.value = 'forgot' }

// ===== 注册 =====
const regForm = reactive({ username: '', email: '', password: '', captcha: '', email_code: '' })
const regCaptchaUrl = ref('')
const emailSending = ref(false)
const emailCountdown = ref(0)
let emailTimer = null

const canSendEmailCode = computed(() => regForm.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(regForm.email) && regForm.captcha.length >= 4)

function refreshRegCaptcha() { regCaptchaUrl.value = phpAuthApi.captchaUrl() }
refreshRegCaptcha()

function startEmailCountdown() {
  emailCountdown.value = 60
  if (emailTimer) clearInterval(emailTimer)
  emailTimer = setInterval(() => {
    emailCountdown.value--
    if (emailCountdown.value <= 0) { clearInterval(emailTimer); emailTimer = null }
  }, 1000)
}

async function sendRegEmailCode() {
  if (!canSendEmailCode.value) return $message.warning('请先填写邮箱和图片验证码')
  emailSending.value = true
  try {
    const result = await phpAuthApi.sendEmailCode({ email: regForm.email, captcha: regForm.captcha, purpose: 'register' })
    $message.success(result.message || '验证码已发送')
    startEmailCountdown()
    refreshRegCaptcha()
  }
  catch (error) { $message.error(error.message || '发送失败'); refreshRegCaptcha() }
  finally { emailSending.value = false }
}

async function register() {
  if (!regForm.username || !regForm.email || !regForm.password || !regForm.captcha || !regForm.email_code)
    return $message.warning('请完整填写注册信息')
  if (!/^[\u4e00-\u9fa5a-zA-Z0-9_\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]+$/u.test(regForm.username))
    return $message.warning('用户名只能包含中文、英文、数字、下划线和表情符号')
  if (regForm.username.length < 2) return $message.warning('用户名至少 2 个字符')
  if (regForm.password.length < 6) return $message.warning('密码至少 6 位')
  if (regForm.email_code.length !== 6) return $message.warning('邮箱验证码为 6 位数字')
  loading.value = true
  try {
    const result = await phpAuthApi.register(regForm)
    $message.success(result.message || '注册成功，请登录')
    mode.value = 'login'
    form.username = regForm.username
    form.password = ''
    refreshRegCaptcha()
  }
  catch (error) { $message.error(error.message || '注册失败'); refreshRegCaptcha() }
  finally { loading.value = false }
}

// ===== 忘记密码/重置密码 =====
const resetForm = reactive({ account: '', email: '', captcha: '', email_code: '', new_password: '' })
const resetCaptchaUrl = ref('')
const resetLoading = ref(false)
const resetEmailSending = ref(false)
const resetCountdown = ref(0)
let resetTimer = null

const canSendResetCode = computed(() => resetForm.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(resetForm.email) && resetForm.captcha.length >= 4)

function refreshResetCaptcha() { resetCaptchaUrl.value = phpAuthApi.captchaUrl() }
// 切到忘记密码 tab 时再初始化验证码
watch(mode, m => { if (m === 'forgot' && !resetCaptchaUrl.value) refreshResetCaptcha() })

function startResetCountdown() {
  resetCountdown.value = 60
  if (resetTimer) clearInterval(resetTimer)
  resetTimer = setInterval(() => {
    resetCountdown.value--
    if (resetCountdown.value <= 0) { clearInterval(resetTimer); resetTimer = null }
  }, 1000)
}

async function sendResetEmailCode() {
  if (!canSendResetCode.value) return $message.warning('请先填写邮箱和图片验证码')
  if (!resetForm.account) return $message.warning('请先填写账号')
  resetEmailSending.value = true
  try {
    const result = await phpAuthApi.sendEmailCode({ email: resetForm.email, captcha: resetForm.captcha, purpose: 'reset' })
    $message.success(result.message || '验证码已发送')
    startResetCountdown()
    refreshResetCaptcha()
  }
  catch (error) { $message.error(error.message || '发送失败'); refreshResetCaptcha() }
  finally { resetEmailSending.value = false }
}

async function doReset() {
  if (!resetForm.account || !resetForm.email || !resetForm.captcha || !resetForm.email_code || !resetForm.new_password)
    return $message.warning('请完整填写所有信息')
  if (resetForm.new_password.length < 6) return $message.warning('新密码至少 6 位')
  if (resetForm.email_code.length !== 6) return $message.warning('邮箱验证码为 6 位数字')
  resetLoading.value = true
  try {
    const result = await phpAuthApi.resetPassword(resetForm)
    $message.success(result.message || '密码重置成功')
    mode.value = 'login'
    form.username = resetForm.account
    form.password = ''
    refreshResetCaptcha()
  }
  catch (error) { $message.error(error.message || '重置失败'); refreshResetCaptcha() }
  finally { resetLoading.value = false }
}
</script>

<style scoped>
.login-page { min-height: 100%; display: grid; place-items: center; padding: 24px; background: linear-gradient(135deg, #eef4ff, #f8fafc 55%, #e8f7f4); }
.login-card { width: min(440px, 100%); padding: 18px 12px; box-shadow: 0 20px 60px rgb(28 49 84 / 12%); }
.login-heading { display: flex; align-items: center; gap: 14px; margin: 8px 0 24px; }
.brand-mark { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 12px; color: #fff; background: #3b82f6; font-size: 24px; font-weight: 700; }
.login-heading h1 { margin: 0; font-size: 24px; }.login-heading p { margin: 3px 0 0; color: var(--n-text-color-3); font-size: 13px; }
.login-form { padding-top: 18px; }
.captcha-row { display: flex; gap: 10px; margin-bottom: 18px; }
.captcha-row img { width: 120px; height: 34px; cursor: pointer; border: 1px solid var(--n-border-color); border-radius: 4px; flex-shrink: 0; }
.email-code-row { display: flex; gap: 10px; margin-bottom: 18px; }
.email-code-row .n-button { flex-shrink: 0; min-width: 120px; }
.form-footer-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.forgot-link { color: #3b82f6; cursor: pointer; font-size: 13px; }
.forgot-link:hover { text-decoration: underline; }
.back-login-row { text-align: center; margin-top: 16px; font-size: 13px; }
.back-login-row a { color: #3b82f6; cursor: pointer; }
.back-login-row a:hover { text-decoration: underline; }
@media (max-width: 480px) {
  .login-page { padding: 12px; }
  .login-card { padding: 12px 8px; }
  .login-heading h1 { font-size: 20px; }
  .brand-mark { width: 42px; height: 42px; font-size: 20px; }
  .captcha-row img { width: 100px; }
  .email-code-row .n-button { min-width: 100px; font-size: 12px; }
}
</style>
