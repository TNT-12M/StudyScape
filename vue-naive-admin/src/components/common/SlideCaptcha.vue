<template>
  <div class="slide-captcha" :class="{ 'is-success': success, 'is-fail': fail, 'is-disabled': verifying || disabled }">
    <!-- 图片区：背景 + 拼图块 + 成功/失败叠层 -->
    <div
      ref="trackRef"
      class="slide-track"
      :style="{ width: `${bgWidth}px`, height: `${bgHeight}px`, backgroundImage: `url(${bgImage})` }"
    >
      <div
        class="slide-piece"
        :style="{
          left: `${pieceX}px`,
          top: `${trackY}px`,
          width: `${markWidth}px`,
          height: `${markHeight}px`,
          backgroundImage: tileImage ? `url(${tileImage})` : 'none',
        }"
      >
        <div v-if="!tileImage" class="piece-inner" />
      </div>
      <button class="slide-refresh" title="换一张" type="button" :disabled="verifying" @click.stop="onRefresh">
        ↻
      </button>

      <!-- 成功/失败叠层：后端接口返回后显示 -->
      <div v-if="success || fail" class="slide-result">
        <div class="result-icon" :class="[success ? 'result-ok' : 'result-bad']">
          <svg v-if="success" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="4 12 10 18 20 6" />
          </svg>
          <svg v-else viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
            <line x1="6" y1="6" x2="18" y2="18" />
            <line x1="6" y1="18" x2="18" y2="6" />
          </svg>
        </div>
        <div class="result-text" :class="[success ? 'result-ok' : 'result-bad']">
          {{ success ? '验证通过' : '验证失败' }}
        </div>
      </div>
    </div>

    <!-- 底部滑轨 -->
    <div class="slide-bar" :style="{ width: `${bgWidth}px` }">
      <div class="slide-bar-track">
        <div class="slide-bar-fill" :class="{ 'is-ok': success, 'is-bad': fail }" :style="{ width: `${Math.min(handleX + handleWidth, bgWidth)}px` }" />
        <span class="slide-bar-hint" :class="{ 'slide-bar-hint--hidden': handleX > 0 || success || fail }">
          ← 按住滑块拖到缺口处 →
        </span>
        <span v-if="verifying" class="slide-bar-hint slide-bar-hint--center">
          验证中…
        </span>
        <div
          class="slide-handle"
          :class="{ 'is-ok': success, 'is-bad': fail }"
          :style="{ left: `${handleX}px` }"
          @mousedown.prevent.stop="startHandleDrag"
          @touchstart.prevent.stop="startHandleDragTouch"
        >
          <svg class="handle-icon" viewBox="0 0 20 20" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M8 4l4 6-4 6" />
            <path d="M12 4l4 6-4 6" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
  bgImage: { type: String, default: '' },
  tileImage: { type: String, default: '' },
  bgWidth: { type: Number, default: 310 },
  bgHeight: { type: Number, default: 150 },
  markWidth: { type: Number, default: 44 },
  markHeight: { type: Number, default: 44 },
  trackY: { type: Number, default: 50 },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'refresh', 'done'])

const trackRef = ref(null)
const handleX = ref(0)
const pieceX = ref(0)
const dragging = ref(false)
const success = ref(false)
const fail = ref(false)
const verifying = ref(false)

const handleWidth = 40
let startClientX = 0
let trackPoints = []
let doneTimer = null

function onRefresh() {
  reset()
  emit('refresh')
}

function clamp(v, min, max) {
  return Math.max(min, Math.min(max, v))
}

function pieceFromHandle(hx) {
  const maxPiece = props.bgWidth - props.markWidth
  const ratio = props.bgWidth > handleWidth ? hx / (props.bgWidth - handleWidth) : 0
  return Math.round(ratio * maxPiece)
}

function reset() {
  success.value = false
  fail.value = false
  handleX.value = 0
  pieceX.value = 0
  dragging.value = false
  trackPoints = []
  verifying.value = false
  if (doneTimer) {
    clearTimeout(doneTimer)
    doneTimer = null
  }
}

function startHandleDrag(e) {
  if (success.value || fail.value || verifying.value || props.disabled)
    return
  if (e.button !== undefined && e.button !== 0)
    return
  dragging.value = true
  startClientX = e.clientX - handleX.value
  trackPoints = []
  window.addEventListener('mousemove', onHandleDrag)
  window.addEventListener('mouseup', onHandleEnd)
}

function startHandleDragTouch(e) {
  if (success.value || fail.value || verifying.value || props.disabled)
    return
  const t = e.touches[0]; if (!t)
    return
  dragging.value = true
  startClientX = t.clientX - handleX.value
  trackPoints = []
  window.addEventListener('touchmove', onHandleDragTouch, { passive: false })
  window.addEventListener('touchend', onHandleEnd)
  window.addEventListener('touchcancel', onHandleEnd)
}

function onHandleDrag(e) {
  if (!dragging.value)
    return
  e.preventDefault()
  handleX.value = clamp(e.clientX - startClientX, 0, props.bgWidth - handleWidth)
  pieceX.value = pieceFromHandle(handleX.value)
  const now = performance.now()
  if (trackPoints.length === 0 || now - (trackPoints[trackPoints.length - 1].t_ref ?? 0) > 15) {
    trackPoints.push({
      x: pieceX.value + props.markWidth / 2,
      y: props.trackY + props.markHeight / 2,
      t: Math.round(now),
      t_ref: now,
    })
  }
}

function onHandleDragTouch(e) {
  if (!dragging.value)
    return
  e.preventDefault()
  const t = e.touches[0]; if (!t)
    return
  handleX.value = clamp(t.clientX - startClientX, 0, props.bgWidth - handleWidth)
  pieceX.value = pieceFromHandle(handleX.value)
  const now = performance.now()
  if (trackPoints.length === 0 || now - (trackPoints[trackPoints.length - 1].t_ref ?? 0) > 15) {
    trackPoints.push({
      x: pieceX.value + props.markWidth / 2,
      y: props.trackY + props.markHeight / 2,
      t: Math.round(now),
      t_ref: now,
    })
  }
}

function onHandleEnd() {
  if (!dragging.value)
    return
  dragging.value = false
  window.removeEventListener('mousemove', onHandleDrag)
  window.removeEventListener('mouseup', onHandleEnd)
  window.removeEventListener('touchmove', onHandleDragTouch)
  window.removeEventListener('touchend', onHandleEnd)
  window.removeEventListener('touchcancel', onHandleEnd)
  trackPoints = trackPoints.map(p => ({ x: p.x, y: p.y, t: p.t }))
  if (pieceX.value >= 5 && trackPoints.length >= 3) {
    verifying.value = true
    emit('submit', {
      captcha_r: pieceX.value,
      captcha_y: props.trackY,
      slide_track: trackPoints,
    })
  }
  else {
    handleX.value = 0
    pieceX.value = 0
    trackPoints = []
  }
}

// 监听 bgImage 变化自动 reset（父组件 load 新图时）
watch(() => props.bgImage, (newVal, oldVal) => {
  if (newVal && newVal !== oldVal)
    reset()
})

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onHandleDrag)
  window.removeEventListener('mouseup', onHandleEnd)
  window.removeEventListener('touchmove', onHandleDragTouch)
  window.removeEventListener('touchend', onHandleEnd)
  window.removeEventListener('touchcancel', onHandleEnd)
  if (doneTimer)
    clearTimeout(doneTimer)
})

defineExpose({
  // 后端返回成功后调：显示 ✓ → 1.5s 后 emit('done', true) 让父组件关弹窗
  markSuccess(done = true) {
    success.value = true
    fail.value = false
    verifying.value = false
    handleX.value = props.bgWidth - handleWidth
    pieceX.value = props.bgWidth - props.markWidth
    if (done) {
      if (doneTimer)
        clearTimeout(doneTimer)
      doneTimer = setTimeout(emit, 1500, 'done', { success: true })
    }
  },
  // 后端返回失败后调：显示 ✗ → 2s 后自动 reset 换新图（通过父组件 emit refresh）
  markFail(done = true) {
    fail.value = true
    success.value = false
    verifying.value = false
    if (done) {
      if (doneTimer)
        clearTimeout(doneTimer)
      doneTimer = setTimeout(() => {
        emit('done', { success: false })
        reset()
      }, 1500)
    }
  },
  reset,
})
</script>

<style scoped>
.slide-captcha {
  user-select: none;
  -webkit-user-select: none;
  display: inline-block;
}
.slide-captcha.is-disabled .slide-handle {
  cursor: not-allowed;
}

.slide-track {
  position: relative;
  background-repeat: no-repeat;
  background-size: 100% 100%;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
  background-color: #e5e7eb;
}
.slide-piece {
  position: absolute;
  background-size: 100% 100%;
  background-repeat: no-repeat;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
}
.piece-inner {
  width: 100%;
  height: 100%;
  background: rgba(30, 64, 175, 0.75);
  border-radius: 4px;
}
.slide-refresh {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 26px;
  height: 26px;
  border: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.35);
  color: #fff;
  font-size: 16px;
  line-height: 1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.15s;
}
.slide-refresh:hover {
  background: rgba(0, 0, 0, 0.55);
}

/* 成功/失败叠层 */
.slide-result {
  position: absolute;
  inset: 0;
  background: rgba(255, 255, 255, 0.85);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
.result-icon {
  animation: pop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes pop {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}
.result-ok {
  color: #18a058;
}
.result-bad {
  color: #d03050;
}
.result-text {
  font-size: 14px;
  font-weight: 500;
}

/* 底部滑轨 */
.slide-bar {
  margin-top: 8px;
  height: 40px;
}
.slide-bar-track {
  position: relative;
  width: 100%;
  height: 40px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
}
.slide-bar-fill {
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
  border-radius: 6px 0 0 6px;
}
.slide-bar-fill.is-ok {
  background: linear-gradient(90deg, #18a058, #34d399);
}
.slide-bar-fill.is-bad {
  background: linear-gradient(90deg, #d03050, #f87171);
}
.slide-bar-hint {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #64748b;
  pointer-events: none;
  transition: opacity 0.15s;
}
.slide-bar-hint--hidden {
  opacity: 0;
}
.slide-bar-hint--center {
  color: #94a3b8;
}

.slide-handle {
  position: absolute;
  top: 0;
  left: 0;
  width: 40px;
  height: 40px;
  background: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
  cursor: grab;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #3b82f6;
  z-index: 2;
}
.slide-handle.is-ok {
  border-color: #18a058;
  color: #18a058;
}
.slide-handle.is-bad {
  border-color: #d03050;
  color: #d03050;
}
.slide-handle:active {
  cursor: grabbing;
}
.handle-icon {
  color: #94a3b8;
}
.slide-handle:hover .handle-icon,
.slide-handle:active .handle-icon {
  color: #3b82f6;
}
</style>
