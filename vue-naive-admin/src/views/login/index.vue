<template>
  <div class="login-page">
    <a class="back-home" @click="router.push('/welcome')">
      <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M11 18l-6-6 6-6" /></svg>
      返回首页
    </a>
    <n-card class="login-card" :bordered="false">
      <div class="login-heading">
        <div class="brand-mark">
          <SiteIcon />
        </div>
        <div><h1>学境・StudyScape</h1><p>学习的新境界</p></div>
      </div>
      <n-tabs v-model:value="mode" animated>
        <n-tab-pane name="login" tab="登录">
          <n-form class="login-form" @submit.prevent="login">
            <n-form-item label="用户名">
              <n-input v-model:value="form.username" placeholder="2-20字，中英文/数字/下划线" maxlength="20" @keydown.enter="login" />
            </n-form-item>
            <n-form-item label="密码">
              <n-input v-model:value="form.password" type="password" show-password-on="mousedown" placeholder="请输入密码" @keydown.enter="login" />
            </n-form-item>
            <div class="form-footer-row">
              <span />
              <a class="forgot-link" @click="goForgot">忘记密码？</a>
            </div>
            <n-button type="primary" block :loading="loading" attr-type="submit">
              登录
            </n-button>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="register" tab="注册">
          <n-form class="login-form" @submit.prevent="register">
            <n-form-item label="用户名">
              <n-input v-model:value="regForm.username" placeholder="2-20字，中英文/数字/下划线" maxlength="20" />
            </n-form-item>
            <n-form-item label="邮箱">
              <n-input v-model:value="regForm.email" placeholder="@example.com" />
            </n-form-item>
            <!-- 滑动验证码：弹窗式，点击按钮弹出 -->
            <div class="auth-captcha-row">
              <div class="captcha-label">
                安全验证：
              </div>
              <n-button
                size="small"
                :type="regCaptchaVerified ? 'success' : 'default'"
                :disabled="!isEmailValid(regForm.email)"
                @click="openCaptchaModal('register')"
              >
                {{ regCaptchaVerified ? '✓ 已验证' : '点击验证' }}
              </n-button>
            </div>
            <div class="email-code-row">
              <n-input v-model:value="regForm.email_code" placeholder="邮箱验证码" maxlength="6" />
              <n-button :disabled="emailCountdown > 0 || !regCaptchaVerified" :loading="emailSending" @click="sendRegEmailCode">
                {{ emailCountdown > 0 ? `${emailCountdown}s 后重发` : '获取验证码' }}
              </n-button>
            </div>
            <n-form-item label="密码">
              <n-input v-model:value="regForm.password" type="password" show-password-on="mousedown" placeholder="至少 6 位" />
            </n-form-item>
            <n-form-item label="确认密码">
              <n-input v-model:value="regForm.confirm_password" type="password" show-password-on="mousedown" placeholder="再次输入密码" />
            </n-form-item>
            <n-button type="primary" block :loading="loading" attr-type="submit">
              创建账号
            </n-button>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="forgot" tab="忘记密码">
          <n-form class="login-form" @submit.prevent="doReset">
            <!-- 未确认阶段: 邮箱 + 滑动验证码 → 自动匹配用户名 -->
            <template v-if="!resetConfirmed">
              <n-form-item label="注册邮箱">
                <n-input v-model:value="resetForm.email" placeholder="请输入注册时使用的邮箱" />
              </n-form-item>
              <div class="auth-captcha-row">
                <div class="captcha-label">
                  安全验证：
                </div>
                <n-button
                  size="small"
                  :type="resetCaptchaVerified ? 'success' : 'default'"
                  @click="openCaptchaModal('forgot_email')"
                >
                  {{ resetCaptchaVerified ? '✓ 已验证' : '点击验证' }}
                </n-button>
              </div>
              <n-button type="primary" block :loading="resetLooking" @click="lookupResetEmail">
                确认邮箱 → 显示账号
              </n-button>
            </template>
            <!-- 已确认阶段: 显示匹配结果 + 发邮箱验证码 + 新的滑动验证码（reset purpose）+ 重置 -->
            <template v-else>
              <n-alert type="info" :bordered="false" style="margin-bottom: 12px">
                <template #header>
                  该邮箱注册的账号
                </template>
                <div>用户名：<b>{{ resetForm.account }}</b><span v-if="resetForm.nickname">（{{ resetForm.nickname }}）</span></div>
              </n-alert>
              <div class="email-code-row">
                <n-input v-model:value="resetForm.email_code" placeholder="邮箱验证码" maxlength="6" />
                <n-button :disabled="resetCountdown > 0" :loading="resetEmailSending" @click="sendResetEmailCode">
                  {{ resetCountdown > 0 ? `${resetCountdown}s 后重发` : '获取验证码' }}
                </n-button>
              </div>
              <div class="auth-captcha-row">
                <div class="captcha-label">
                  安全验证：
                </div>
                <SlideCaptcha
                  ref="resetSubmitCaptchaRef"
                  :bg-image="resetSubmitCaptchaData.bg_image"
                  :tile-image="resetSubmitCaptchaData.tile_image"
                  :bg-width="resetSubmitCaptchaData.bg_width"
                  :bg-height="resetSubmitCaptchaData.bg_height"
                  :mark-width="resetSubmitCaptchaData.mark_width"
                  :mark-height="resetSubmitCaptchaData.mark_height"
                  :track-y="resetSubmitCaptchaData.pos_y"
                  :style="{ display: 'inline-block' }"
                  @submit="onResetSubmitCaptchaSubmit"
                  @refresh="loadResetCaptcha('reset')"
                />
              </div>
              <n-form-item label="新密码">
                <n-input v-model:value="resetForm.new_password" type="password" show-password-on="mousedown" placeholder="至少 6 位" />
              </n-form-item>
              <n-form-item label="确认新密码">
                <n-input v-model:value="resetForm.confirm_new_password" type="password" show-password-on="mousedown" placeholder="再次输入新密码" />
              </n-form-item>
              <n-button type="primary" block :loading="resetLoading" attr-type="submit">
                重置密码
              </n-button>
              <n-button text block type="info" @click="resetConfirmed = false">
                换个邮箱试试
              </n-button>
            </template>
            <div class="back-login-row">
              <a @click="mode = 'login'">← 返回登录</a>
            </div>
          </n-form>
        </n-tab-pane>
      </n-tabs>
    </n-card>

    <!-- 全局滑动验证码弹窗（注册 / 找回密码共用一个 modal） -->
    <n-modal
      v-model:show="captchaModal.show"
      preset="card"
      title="请完成安全验证"
      :mask-closable="false"
      :closable="false"
      style="width: auto; max-width: 380px"
      :content-style="{ padding: '16px 16px 12px' }"
    >
      <div style="text-align: center">
        <SlideCaptcha
          ref="captchaRef"
          :bg-image="captchaModal.data?.bg_image || ''"
          :tile-image="captchaModal.data?.tile_image || ''"
          :bg-width="captchaModal.data?.bg_width || 310"
          :bg-height="captchaModal.data?.bg_height || 150"
          :mark-width="captchaModal.data?.mark_width || 44"
          :mark-height="captchaModal.data?.mark_height || 44"
          :track-y="captchaModal.data?.pos_y ?? 50"
          @submit="captchaSubmit"
          @refresh="loadForModal"
          @done="onModalCaptchaDone"
        />
      </div>
    </n-modal>
  </div>
</template>

<script setup>
import { phpAuthApi } from '@/api/php-modules'
import SiteIcon from '@/components/common/SiteIcon.vue'
import SlideCaptcha from '@/components/common/SlideCaptcha.vue'
import { useSessionStore } from '@/store'

const router = useRouter()
const route = useRoute()
const session = useSessionStore()

const mode = ref(route.query.mode === 'register' ? 'register' : (route.query.mode === 'forgot' ? 'forgot' : 'login'))
const loading = ref(false)

// ===== 登录 =====
const form = reactive({ username: '', password: '' })

async function login() {
  if (loading.value)
    return
  if (!form.username || !form.password)
    return $message.warning('请输入用户名和密码')
  loading.value = true
  try {
    const result = await phpAuthApi.login({ username: form.username, password: form.password })
    const user = result.data?.user
    if (!user?.id)
      throw new Error('登录响应无效，请重试')
    session.setUser(user)
    $message.success(result.message || '登录成功')
    const redirect = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
      ? route.query.redirect
      : null
    const target = redirect || (user.is_admin ? '/StudyScape' : '/app')
    await nextTick()
    try {
      await router.replace(target)
    }
    catch (e) {
      console.warn('[登录] replace 失败:', e)
    }
    if (!isOnTarget(target)) {
      try {
        await router.push(target)
      }
      catch (e) {
        console.warn('[登录] push 失败:', e)
      }
    }
    setTimeout(() => {
      if (!isOnTarget(target))
        window.location.href = target
    }, 300)
  }
  catch (error) {
    $message.error(error.message || '登录失败')
  }
  finally { loading.value = false }
}

function isOnTarget(target) {
  const path = router.currentRoute.value.path
  return path === target || path.startsWith(`${target}/`) || (target === '/StudyScape' && path === '/StudyScape')
}

function goForgot() {
  mode.value = 'forgot'
}

// ===== 全局弹窗式滑动验证码（register / forgot_email / reset 共用一个 modal）=====
const captchaModal = reactive({
  show: false,
  purpose: 'register',
  data: null,
})
const captchaRef = ref(null)

// 各 purpose 的"弹窗验证通过"标记（后端通过 captcha_passed_session 保护）
const regCaptchaVerified = ref(false)
const resetCaptchaVerified = ref(false) // forgot_email 阶段
const resetSubmitCaptchaVerified = ref(false) // reset 提交阶段

function openCaptchaModal(purpose) {
  captchaModal.purpose = purpose
  captchaModal.show = true
  loadForModal()
}

async function loadForModal() {
  try {
    const result = await phpAuthApi.authCaptcha(captchaModal.purpose)
    if (result.success) {
      captchaModal.data = result.data
      await nextTick()
      captchaRef.value?.reset?.()
    }
    else {
      $message.error(result.message || '验证码加载失败')
      captchaModal.show = false
    }
  }
  catch {
    $message.error('验证码加载失败')
    captchaModal.show = false
  }
}

// 弹窗内 SlideCaptcha 松手 emit submit → 一次性调后端 captchaVerify
async function captchaSubmit(payload) {
  const purpose = captchaModal.purpose
  try {
    const verifyRes = await phpAuthApi.captchaVerify({
      purpose,
      captcha_r: payload.captcha_r,
      captcha_y: payload.captcha_y,
      slide_track: JSON.stringify(payload.slide_track || []),
    })
    if (!verifyRes.success) {
      captchaRef.value?.markFail()
      return
    }
    // verify 成功：视觉上显示 ✓，短暂停留后自动关 modal + 触发下一步
    captchaRef.value?.markSuccess(false)
    if (purpose === 'register')
      regCaptchaVerified.value = true
    else if (purpose === 'forgot_email')
      resetCaptchaVerified.value = true
    else if (purpose === 'reset')
      resetSubmitCaptchaVerified.value = true
    await new Promise(r => setTimeout(r, 800))
    captchaModal.show = false
    // 自动触发对应业务动作
    if (purpose === 'register') {
      sendRegEmailCode()
    }
    else if (purpose === 'forgot_email') {
      lookupResetEmail()
    }
    else if (purpose === 'reset') {
      // reset 验证通过后不自动提交，提示用户填密码
      $message.success('验证通过，请设置新密码')
    }
  }
  catch {
    captchaRef.value?.markFail()
  }
}

function isEmailValid(email) {
  return email && /^[^\s@]+@[^\s@][^\s.@]*\.[^\s@]+$/.test(email)
}

// ===== 注册 =====
const regForm = reactive({ username: '', email: '', password: '', confirm_password: '', email_code: '' })
const emailSending = ref(false)
const emailCountdown = ref(0)
let emailTimer = null

function startEmailCountdown() {
  emailCountdown.value = 60
  if (emailTimer)
    clearInterval(emailTimer)
  emailTimer = setInterval(() => {
    emailCountdown.value--
    if (emailCountdown.value <= 0) {
      clearInterval(emailTimer)
      emailTimer = null
    }
  }, 1000)
}

async function sendRegEmailCode() {
  if (!regCaptchaVerified.value)
    return $message.warning('请先完成滑动验证')
  if (!isEmailValid(regForm.email))
    return $message.warning('请填写有效邮箱')
  emailSending.value = true
  try {
    const result = await phpAuthApi.sendEmailCode({ email: regForm.email, purpose: 'register' })
    $message.success(result.message || '验证码已发送')
    startEmailCountdown()
  }
  catch (error) { $message.error(error.message || '发送失败') }
  finally { emailSending.value = false }
}

async function register() {
  if (!regForm.username || !regForm.email || !regForm.password || !regForm.confirm_password || !regForm.email_code)
    return $message.warning('请完整填写注册信息')
  if (!regCaptchaVerified.value)
    return $message.warning('请先完成滑动验证')
  if (!/^[\u4e00-\u9fa5\w\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]+$/u.test(regForm.username))
    return $message.warning('用户名只能包含中文、英文、数字、下划线和表情符号')
  if (regForm.username.length < 2)
    return $message.warning('用户名至少 2 个字符')
  if (regForm.password.length < 6)
    return $message.warning('密码至少 6 位')
  if (regForm.password !== regForm.confirm_password)
    return $message.warning('两次输入的密码不一致')
  if (regForm.email_code.length !== 6)
    return $message.warning('邮箱验证码为 6 位数字')
  loading.value = true
  try {
    const result = await phpAuthApi.register({
      username: regForm.username,
      email: regForm.email,
      password: regForm.password,
      email_code: regForm.email_code,
    })
    $message.success(result.message || '注册成功，请登录')
    mode.value = 'login'
    form.username = regForm.username
    form.password = ''
    regForm.username = ''
    regForm.email = ''
    regForm.password = ''
    regForm.confirm_password = ''
    regForm.email_code = ''
    regCaptchaVerified.value = false
  }
  catch (error) {
    $message.error(error.message || '注册失败')
    regCaptchaVerified.value = false
  }
  finally { loading.value = false }
}

// ===== 忘记密码/重置密码 =====
const resetForm = reactive({ account: '', nickname: '', email: '', email_code: '', new_password: '', confirm_new_password: '' })
const resetLoading = ref(false)
const resetEmailSending = ref(false)
const resetCountdown = ref(0)
const resetConfirmed = ref(false)
const resetLooking = ref(false)
let resetTimer = null

// 切到 forgot/register tab 时重置状态（不再预加载验证码，弹窗打开时才加载）
watch(mode, (m) => {
  if (m === 'forgot') {
    resetConfirmed.value = false
    resetCaptchaVerified.value = false
    resetSubmitCaptchaVerified.value = false
    resetForm.account = ''
    resetForm.nickname = ''
  }
  if (m === 'register') {
    regCaptchaVerified.value = false
  }
})

function startResetCountdown() {
  resetCountdown.value = 60
  if (resetTimer)
    clearInterval(resetTimer)
  resetTimer = setInterval(() => {
    resetCountdown.value--
    if (resetCountdown.value <= 0) {
      clearInterval(resetTimer)
      resetTimer = null
    }
  }, 1000)
}

async function lookupResetEmail() {
  if (!resetForm.email || !isEmailValid(resetForm.email))
    return $message.warning('请填写正确的邮箱')
  if (!resetCaptchaVerified.value)
    return $message.warning('请先完成滑动验证')
  resetLooking.value = true
  try {
    const result = await phpAuthApi.lookupResetEmail({ email: resetForm.email })
    resetForm.account = result.data.username
    resetForm.nickname = result.data.nickname || ''
    resetConfirmed.value = true
    $message.success(result.message || '请确认是这个账号')
  }
  catch (error) {
    $message.error(error.message || '查询失败')
  }
  finally { resetLooking.value = false }
}

async function sendResetEmailCode() {
  if (!resetConfirmed.value)
    return $message.warning('请先确认邮箱对应的账号')
  resetEmailSending.value = true
  try {
    const result = await phpAuthApi.sendEmailCode({ email: resetForm.email, purpose: 'reset' })
    $message.success(result.message || '验证码已发送')
    startResetCountdown()
  }
  catch (error) { $message.error(error.message || '发送失败') }
  finally { resetEmailSending.value = false }
}

async function doReset() {
  if (!resetConfirmed.value || !resetForm.email || !resetForm.email_code || !resetForm.new_password || !resetForm.confirm_new_password)
    return $message.warning('请完整填写所有信息')
  if (!resetSubmitCaptchaVerified.value)
    return $message.warning('请先完成滑动验证')
  if (resetForm.new_password.length < 6)
    return $message.warning('新密码至少 6 位')
  if (resetForm.new_password !== resetForm.confirm_new_password)
    return $message.warning('两次输入的密码不一致')
  if (resetForm.email_code.length !== 6)
    return $message.warning('邮箱验证码为 6 位数字')
  resetLoading.value = true
  try {
    const result = await phpAuthApi.resetPassword({
      email: resetForm.email,
      email_code: resetForm.email_code,
      new_password: resetForm.new_password,
    })
    $message.success(result.message || '密码重置成功')
    mode.value = 'login'
    form.username = resetForm.account
    form.password = ''
    Object.assign(resetForm, { account: '', nickname: '', email: '', email_code: '', new_password: '', confirm_new_password: '' })
    resetConfirmed.value = false
    resetCaptchaVerified.value = false
    resetSubmitCaptchaVerified.value = false
  }
  catch (error) { $message.error(error.message || '重置失败') }
  finally { resetLoading.value = false }
}
</script>

<style scoped>
.login-page {
  min-height: 100%;
  display: grid;
  place-items: center;
  padding: 24px;
  background: linear-gradient(135deg, #eef4ff, #f8fafc 55%, #e8f7f4);
  position: relative;
}
.back-home {
  position: absolute;
  top: 22px;
  left: 26px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 13.5px;
  color: #2563eb;
  cursor: pointer;
  padding: 7px 13px;
  border-radius: 999px;
  background: rgb(255 255 255 / 75%);
  box-shadow: inset 0 0 0 1px #d7e5ff;
  transition: background 0.2s;
}
.back-home:hover {
  background: #fff;
  text-decoration: none;
}
.login-card {
  width: min(440px, 100%);
  padding: 18px 12px;
  box-shadow: 0 20px 60px rgb(28 49 84 / 12%);
}
.login-heading {
  display: flex;
  align-items: center;
  gap: 14px;
  margin: 8px 0 24px;
}
.brand-mark {
  width: 48px;
  height: 48px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: #fff;
  background: #3b82f6;
  font-size: 24px;
  font-weight: 700;
}
.login-heading h1 {
  margin: 0;
  font-size: 24px;
}
.login-heading p {
  margin: 3px 0 0;
  color: var(--n-text-color-3);
  font-size: 13px;
}
.login-form {
  padding-top: 18px;
}
.captcha-label {
  font-size: 13px;
  color: var(--n-text-color-2);
  margin-bottom: 6px;
}
.auth-captcha-row {
  margin-bottom: 18px;
  text-align: center;
}
.email-code-row {
  display: flex;
  gap: 10px;
  margin-bottom: 18px;
}
.email-code-row .n-button {
  flex-shrink: 0;
  min-width: 120px;
}
.form-footer-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 14px;
}
.forgot-link {
  color: #3b82f6;
  cursor: pointer;
  font-size: 13px;
}
.forgot-link:hover {
  text-decoration: underline;
}
.back-login-row {
  text-align: center;
  margin-top: 16px;
  font-size: 13px;
}
.back-login-row a {
  color: #3b82f6;
  cursor: pointer;
}
.back-login-row a:hover {
  text-decoration: underline;
}
@media (max-width: 480px) {
  .login-page {
    padding: 12px;
  }
  .login-card {
    padding: 12px 8px;
  }
  .login-heading h1 {
    font-size: 20px;
  }
  .brand-mark {
    width: 42px;
    height: 42px;
    font-size: 20px;
  }
  .email-code-row .n-button {
    min-width: 100px;
    font-size: 12px;
  }
}
</style>
