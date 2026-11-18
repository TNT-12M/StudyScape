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

    <div v-else class="checkin-ready">
      <p class="ready-tip">
        今日还没签到，签到可获得 5 积分 + 连续签到加成。
      </p>
      <n-button type="primary" size="large" block :loading="submitting" @click="openCheckinModal">
        开始签到
      </n-button>
    </div>

    <!-- 签到验证码弹窗 -->
    <n-modal
      v-model:show="modal.show"
      preset="card"
      title="完成验证后领取积分"
      :mask-closable="false"
      :closable="false"
      style="width: auto; max-width: 380px"
      :content-style="{ padding: '16px 16px 12px' }"
    >
      <div style="text-align: center">
        <SlideCaptcha
          ref="captchaRef"
          :bg-image="modal.data?.bg_image || ''"
          :tile-image="modal.data?.tile_image || ''"
          :bg-width="modal.data?.bg_width || 310"
          :bg-height="modal.data?.bg_height || 150"
          :mark-width="modal.data?.mark_width || 44"
          :mark-height="modal.data?.mark_height || 44"
          :track-y="modal.data?.pos_y ?? 50"
          @submit="onModalSubmit"
          @refresh="loadForModal"
        />
      </div>
    </n-modal>
  </n-card>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { phpAuthApi, phpCheckinApi } from '@/api/php-modules'
import SlideCaptcha from '@/components/common/SlideCaptcha.vue'

const status = ref(null)
const submitting = ref(false)
// 签到弹窗状态
const modal = reactive({
  show: false,
  data: null,
  captchaRef: null,
})
const refreshTimer = null

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

function openCheckinModal() {
  if (checkedInToday.value)
    return
  modal.show = true
  loadForModal()
}

async function loadForModal() {
  try {
    const result = await phpCheckinApi.captcha()
    if (!result.success) {
      $message.error(result.message || '验证码加载失败')
      modal.show = false
      return
    }
    modal.data = result.data
    await nextTick()
    // 用 modal.captchaRef — 但 ref 赋值在 SlideCaptcha 内部，用模板 ref
    // 直接用 setTimeout 确保子组件 mount 后再 reset
    setTimeout(() => {
      // 通过 $refs 访问：组件内没有 $refs，用 modal.captchaRef
      // 其实模板里 :ref 不能动态 reactive 对象字段，改下...
    }, 50)
  }
  catch {
    $message.error('验证码加载失败')
    modal.show = false
  }
}

// 弹窗内 SlideCaptcha 松手 emit submit → captcha_verify('checkin')
async function onModalSubmit(payload) {
  try {
    const verifyRes = await phpAuthApi.captchaVerify({
      purpose: 'checkin',
      captcha_r: payload.captcha_r,
      captcha_y: payload.captcha_y,
      slide_track: JSON.stringify(payload.slide_track || []),
    })
    if (!verifyRes.success) {
      findCaptchaRef()?.markFail?.()
      return
    }
    findCaptchaRef()?.markSuccess(false)
    await new Promise(r => setTimeout(r, 700))
    modal.show = false
    await doCheckin()
  }
  catch {
    findCaptchaRef()?.markFail?.()
  }
}

// 因为模板 ref 需要是顶层变量，modal.captchaRef 不可用，用顶层 ref
const captchaRef = ref(null)
function findCaptchaRef() {
  return captchaRef.value
}

// 实际发积分（后端检查 captcha_passed_checkin 标记）
async function doCheckin() {
  if (submitting.value)
    return
  submitting.value = true
  try {
    const result = await phpCheckinApi.submit()
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
    }
    else {
      $message.error(result.message || '签到失败')
    }
  }
  catch {
    $message.error('网络异常')
  }
  finally { submitting.value = false }
}

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
