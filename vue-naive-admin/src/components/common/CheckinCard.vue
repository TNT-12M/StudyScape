<template>
  <n-card :bordered="true" class="checkin-card">
    <template #header>
      <div class="checkin-head">
        <span class="checkin-title">每日签到</span>
        <n-tag size="small" :type="checkedInToday ? 'success' : 'info'">
          {{ checkedInToday ? '今日已签到' : '今日未签到' }}
        </n-tag>
      </div>
    </template>

    <div class="checkin-summary">
      <div class="stat">
        <div class="stat-num">
          {{ status?.points ?? 0 }}
        </div>
        <div class="stat-label">
          我的积分
        </div>
      </div>
      <div class="stat">
        <div class="stat-num streak">
          🔥 {{ status?.streak ?? 0 }}
        </div>
        <div class="stat-label">
          连续签到（天）
        </div>
      </div>
      <div class="stat">
        <div class="stat-num">
          {{ status?.total_checkins ?? 0 }}
        </div>
        <div class="stat-label">
          累计签到
        </div>
      </div>
    </div>

    <div v-if="checkedInToday" class="checkin-done">
      <p class="done-line">
        今日已签到，获得 <b>{{ status?.points_awarded_today ?? 0 }}</b> 积分。
      </p>
      <p v-if="availableAtLabel" class="done-next">
        下次可签到时间：<b>{{ availableAtLabel }}</b>
      </p>
    </div>

    <div v-else-if="captchaReady" class="checkin-captcha-area">
      <SlideCaptcha
        ref="captchaRef"
        :bg-image="captchaBg"
        :tile-image="captchaTile"
        :bg-width="captchaBgWidth"
        :bg-height="captchaBgHeight"
        :mark-width="captchaMarkWidth"
        :mark-height="captchaMarkHeight"
        :track-y="captchaPosY"
        :style="{ display: 'inline-block' }"
        @submit="onCaptchaSubmit"
        @refresh="loadCaptcha"
      />
      <p class="captcha-tip">
        {{ captchaHint || '把滑块拖到缺口位置即可完成验证' }}。积分奖励 = 5 + 连续奖励（封顶 7 天）
      </p>
    </div>

    <div v-else-if="loadingCaptcha" class="checkin-loading">
      <n-spin size="medium" />
      <span>正在加载验证码…</span>
    </div>

    <div v-else class="checkin-ready">
      <p class="ready-tip">
        今日还没签到，签到可获得 5 积分 + 连续签到加成。
      </p>
      <n-button type="primary" size="large" block :loading="submitting" @click="startCheckin">
        开始签到
      </n-button>
    </div>
  </n-card>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { phpCheckinApi } from '@/api/php-modules'
import SlideCaptcha from '@/components/common/SlideCaptcha.vue'

const status = ref(null)
const captchaBg = ref('')
const captchaTile = ref('')
const captchaBgWidth = ref(310)
const captchaBgHeight = ref(150)
const captchaMarkWidth = ref(44)
const captchaMarkHeight = ref(44)
const captchaPosY = ref(50)
const captchaHint = ref('')
const captchaReady = ref(false)
const loadingCaptcha = ref(false)
const submitting = ref(false)
const captchaRef = ref(null)
let refreshTimer = null

const checkedInToday = computed(() => !!status.value?.checked_in_today)

const availableAtLabel = computed(() => {
  const ts = status.value?.available_at
  if (!ts)
    return ''
  const d = new Date(ts * 1000)
  const pad = n => String(n).padStart(2, '0')
  return `${d.getMonth() + 1}月${d.getDate()}日 ${pad(d.getHours())}:${pad(d.getMinutes())}`
})

async function loadStatus() {
  try {
    const result = await phpCheckinApi.status()
    if (result.success)
      status.value = result.data
  }
  catch { /* 静默 */ }
}

async function loadCaptcha() {
  captchaReady.value = false
  loadingCaptcha.value = true
  try {
    const result = await phpCheckinApi.captcha()
    if (!result.success) {
      $message.error(result.message || '验证码加载失败')
      return
    }
    // phgors/gocaptcha 返回 masterImage(完整背景) + tileImage(拼图块)
    captchaBg.value = result.data.bg_image
    captchaTile.value = result.data.tile_image
    captchaBgWidth.value = result.data.bg_width || 310
    captchaBgHeight.value = result.data.bg_height || 150
    captchaMarkWidth.value = result.data.mark_width || 44
    captchaMarkHeight.value = result.data.mark_height || 44
    captchaPosY.value = 50
    captchaHint.value = ''
    captchaReady.value = true
  }
  catch {
    $message.error('验证码加载失败，请检查网络')
  }
  finally {
    loadingCaptcha.value = false
  }
}

function startCheckin() {
  if (checkedInToday.value)
    return
  loadCaptcha()
}

// SlideCaptcha 现在 emit { captcha_r, slide_track }
async function onCaptchaSubmit(payload) {
  if (submitting.value)
    return
  submitting.value = true
  try {
    const result = await phpCheckinApi.submit({
      captcha_r: payload.captcha_r,
      slide_track: JSON.stringify(payload.slide_track || []),
    })
    if (result.success) {
      $message.success(result.message)
      status.value = {
        ...status.value,
        checked_in_today: true,
        points: result.data?.total_points ?? status.value?.points ?? 0,
        streak: result.data?.streak ?? status.value?.streak ?? 1,
        total_checkins: (status.value?.total_checkins ?? 0) + 1,
        points_awarded_today: result.data?.awarded ?? 0,
      }
      captchaReady.value = false
      captchaRef.value?.markSuccess?.()
    }
    else {
      $message.error(result.message || '验证未通过，请重试')
      loadCaptcha()
    }
  }
  catch {
    $message.error('网络异常，请稍后重试')
  }
  finally {
    submitting.value = false
  }
}

// 自动刷新验证码（防过期）
watch(captchaReady, (val) => {
  if (!val)
    return
  if (refreshTimer)
    clearTimeout(refreshTimer)
  refreshTimer = setTimeout(() => {
    if (captchaReady.value && !checkedInToday.value)
      loadCaptcha()
  }, 180_000)
})

onMounted(() => {
  loadStatus()
})
</script>

<style scoped>
.checkin-card {
  border-radius: 10px;
}
.checkin-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.checkin-title {
  font-weight: 600;
  font-size: 15px;
}
.checkin-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}
.stat {
  text-align: center;
  padding: 10px 6px;
  background: var(--n-color-target-transparent, rgba(0, 0, 0, 0.04));
  border-radius: 8px;
}
.stat-num {
  font-size: 22px;
  font-weight: 700;
  color: var(--n-color-primary);
  line-height: 1.2;
}
.stat-num.streak {
  font-size: 18px;
}
.stat-label {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin-top: 2px;
}
.checkin-done {
  padding: 8px 0;
  text-align: center;
}
.done-line {
  color: var(--n-color-success, #18a058);
  font-size: 14px;
  margin: 0 0 4px;
}
.done-next {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin: 0;
}
.checkin-captcha-area {
  text-align: center;
}
.captcha-tip {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin-top: 8px;
  text-align: center;
}
.checkin-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 16px;
  color: var(--n-text-color-3);
}
.checkin-ready {
  text-align: center;
}
.ready-tip {
  font-size: 13px;
  color: var(--n-text-color-2);
  margin: 0 0 12px;
}
</style>
