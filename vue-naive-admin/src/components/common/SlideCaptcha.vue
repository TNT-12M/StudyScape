<template>
  <div class="slide-captcha" :class="{ 'is-success': success, 'is-disabled': disabled }">
    <!-- 图片区域：背景 + 拼图块 -->
    <div
      ref="trackRef"
      class="slide-track"
      :style="{ width: `${bgWidth}px`, height: `${bgHeight}px`, backgroundImage: `url(${bgImage})` }"
    >
      <!-- 拼图块：用后端 gocaptcha 返回的 tileImage（PNG 带 mask） -->
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
      <button class="slide-refresh" title="换一张验证码" type="button" @click.stop="onRefresh">
        ↻
      </button>
    </div>

    <!-- 底部滑轨 + 滑块手柄（经典样式） -->
    <div class="slide-bar" :style="{ width: `${bgWidth}px` }">
      <div class="slide-bar-track">
        <div class="slide-bar-fill" :style="{ width: `${Math.min(handleX + handleWidth, bgWidth)}px` }" />
        <span class="slide-bar-hint" :class="{ 'slide-bar-hint--hidden': handleX > 0 }">
          ← 按住滑块拖到缺口处 →
        </span>
        <div
          class="slide-handle"
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
  // 拖完是否禁止再拖（等后端返回结果）；默认 true 防重复提交
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['submit', 'refresh'])

const trackRef = ref(null)
// handleX = 滑块手柄左边缘位置；拼图块 pieceX = handleX（1:1 映射）
const handleX = ref(0)
const pieceX = ref(0)
const dragging = ref(false)
const success = ref(false)

const handleWidth = 40 // 滑块手柄宽度 px
let startClientX = 0
let trackPoints = [] // [{x, y, t}] 轨迹

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
  handleX.value = 0
  pieceX.value = 0
  dragging.value = false
  trackPoints = []
}

function startHandleDrag(e) {
  if (success.value || props.disabled)
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
  if (success.value || props.disabled)
    return
  const t = e.touches[0]
  if (!t)
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
  // 轨迹采样（节流 ~15ms）
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
  const t = e.touches[0]
  if (!t)
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
  // 清理 t_ref（内部用，不提交）
  trackPoints = trackPoints.map(p => ({ x: p.x, y: p.y, t: p.t }))
  // 必须真的拖过才 emit
  if (pieceX.value >= 5 && trackPoints.length >= 3) {
    emit('submit', {
      captcha_r: pieceX.value,
      slide_track: trackPoints,
    })
  }
  else {
    // 没真拖就弹回原位
    handleX.value = 0
    pieceX.value = 0
    trackPoints = []
  }
}

// 监听 bgImage 变化（父组件 load 新图），自动 reset 状态
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
})

defineExpose({
  // 后端返回"验证通过"后调
  markSuccess() {
    success.value = true
    handleX.value = props.bgWidth - handleWidth
    pieceX.value = props.bgWidth - props.markWidth
  },
  // 换图 / 后端返回失败时调
  reset() {
    success.value = false
    handleX.value = 0
    pieceX.value = 0
    dragging.value = false
    trackPoints = []
  },
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
  opacity: 0.5;
}

/* 图片区 */
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
  transition: none;
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
  transition: background 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.slide-refresh:hover {
  background: rgba(0, 0, 0, 0.55);
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
  transition: width 0.05s linear;
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

/* 滑块手柄 */
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
  transition: box-shadow 0.15s;
  z-index: 2;
}
.slide-handle:hover {
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
}
.slide-handle:active {
  cursor: grabbing;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.45);
}
.handle-icon {
  color: #94a3b8;
  transition: color 0.15s;
}
.slide-handle:hover .handle-icon,
.slide-handle:active .handle-icon {
  color: #3b82f6;
}

/* 成功态 */
.slide-captcha.is-success .slide-bar-fill {
  background: linear-gradient(90deg, #18a058, #34d399);
}
.slide-captcha.is-success .slide-handle {
  border-color: #18a058;
  color: #18a058;
}
.slide-captcha.is-success .slide-bar-hint {
  opacity: 1;
  color: #18a058;
  font-weight: 500;
  content: '✓ 验证通过';
}
</style>
