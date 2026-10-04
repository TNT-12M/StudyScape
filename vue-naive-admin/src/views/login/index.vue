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
            <n-button type="primary" block :loading="loading" attr-type="submit">登录</n-button>
          </n-form>
        </n-tab-pane>
        <n-tab-pane name="register" tab="注册">
          <n-form class="login-form" @submit.prevent="register">
            <n-form-item label="用户名"><n-input v-model:value="form.username" placeholder="2-20字，中英文/数字/下划线" maxlength="20" /></n-form-item>
            <n-form-item label="邮箱"><n-input v-model:value="form.email" placeholder="@example.com" /></n-form-item>
            <n-form-item label="密码"><n-input v-model:value="form.password" type="password" placeholder="至少 6 位" /></n-form-item>
            <div class="captcha-row"><n-input v-model:value="form.captcha" placeholder="验证码" /><img :src="captchaUrl" alt="验证码" title="点击刷新" @click="refreshCaptcha"></div>
            <n-button type="primary" block :loading="loading" attr-type="submit">创建账号</n-button>
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
const mode = ref(route.query.mode === 'register' ? 'register' : 'login')
const loading = ref(false)
const captchaUrl = ref('')
const form = reactive({ username: '', email: '', password: '', captcha: '' })

function refreshCaptcha() { captchaUrl.value = phpAuthApi.captchaUrl() }
refreshCaptcha()

async function login() {
  if (!form.username || !form.password) return $message.warning('请输入用户名和密码')
  loading.value = true
  try {
    const result = await phpAuthApi.login({ username: form.username, password: form.password })
    const user = result.data?.user
    if (!user?.id) throw new Error('登录响应无效，请重试')
    // 直接使用登录接口返回的用户数据，不再额外 refresh
    // 避免 session_regenerate_id 后立即二次请求偶发的 cookie 不同步问题
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

async function register() {
  if (!form.username || !form.email || !form.password || !form.captcha) return $message.warning('请完整填写注册信息')
  if (!/^[\u4e00-\u9fa5a-zA-Z0-9_]+$/.test(form.username)) return $message.warning('用户名只能包含中文、英文、数字和下划线')
  if (form.username.length < 2) return $message.warning('用户名至少 2 个字符')
  if (form.password.length < 6) return $message.warning('密码至少 6 位')
  loading.value = true
  try {
    const result = await phpAuthApi.register(form)
    $message.success(result.message || '注册成功，请登录')
    mode.value = 'login'
    form.password = ''
    form.captcha = ''
    refreshCaptcha()
  }
  catch (error) { $message.error(error.message || '注册失败'); refreshCaptcha() }
  finally { loading.value = false }
}
</script>

<style scoped>
.login-page { min-height: 100%; display: grid; place-items: center; padding: 24px; background: linear-gradient(135deg, #eef4ff, #f8fafc 55%, #e8f7f4); }
.login-card { width: min(420px, 100%); padding: 18px 12px; box-shadow: 0 20px 60px rgb(28 49 84 / 12%); }
.login-heading { display: flex; align-items: center; gap: 14px; margin: 8px 0 24px; }
.brand-mark { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 12px; color: #fff; background: #3b82f6; font-size: 24px; font-weight: 700; }
.login-heading h1 { margin: 0; font-size: 24px; }.login-heading p { margin: 3px 0 0; color: var(--n-text-color-3); font-size: 13px; }
.login-form { padding-top: 18px; }.captcha-row { display: flex; gap: 10px; margin-bottom: 20px; }.captcha-row img { width: 120px; height: 34px; cursor: pointer; border: 1px solid var(--n-border-color); border-radius: 4px; }
</style>
