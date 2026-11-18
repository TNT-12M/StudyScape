<template>
  <div class="slide-captcha" :class="{ 'is-success': success }">
    <div
      ref="trackRef"
      class="slide-track"
      :style="{ width: `${bgWidth}px`, height: `${bgHeight}px`, backgroundImage: `url(${bgImage})` }"
      @mousedown="startDrag"
      @touchstart.prevent="startDragTouch"
    >
      <!-- 滑块：只靠 CSS 画形状，不再依赖后端返回 piece 图 -->
      <div
        class="slide-piece"
        :style="{ left: `${pieceX}px`, top: `${trackY}px`, width: `${markWidth}px`, height: `${markHeight}px` }"
        @mousedown.stop
        @touchstart.stop
      >
        <div class="piece-inner" />
      </div>
      <button
        class="slide-refresh"
        title="换一张验证码"
        type="button"
        @click.stop="$emit('refresh')"
      >
        ↻
      </button>
    </div>
    <div class="slide-bar" :style="{ width: `${bgWidth}px` }">
      <template v-if="success">
        <span class="slide-hint slide-success">✓ 验证通过</span>
      </template>
      <template v-else-if="!dragging && pieceX === 0">
        <span class="slide-hint">← 按住鼠标把滑块拖到缺口处 →</span>
      </template>
      <template v-else-if="dragging">
        <span class="slide-hint">继续拖动…</span>
      </template>
      <template v-else>
        <span class="slide-hint">松开后自动提交</span>
      </template>
    </div>
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
  bgImage: { type: String, default: '' },
  bgWidth: { type: Number, default: 310 },
  bgHeight: { type: Number, default: 150 },
  markWidth: { type: Number, default: 44 },
  markHeight: { type: Number, default: 44 },
  trackY: { type: Number, default: 50 },
})

const emit = defineEmits(['submit', 'refresh'])

const trackRef = ref(null)
const pieceX = ref(0)
const dragging = ref(false)
const success = ref(false)
let startClientX = 0
let dragStartTs = 0
let trackPoints = [] // [{x, y, t}] 轨迹数组

function clamp(v, min, max) {
  return Math.max(min, Math.min(max, v))
}

function recordTrack(x, y) {
  // 只在拖动过程中采样轨迹（节流 ~每 20ms 一个点）
  const now = performance.now()
  if (now - (dragStartTs || now) < 15)
    return
  // x, y 为轨道坐标系内的像素（滑块中心偏移）
  trackPoints.push({ x: Math.round(x), y: Math.round(y), t: Math.round(now - dragStartTs) })
}

function startDrag(e) {
  if (success.value)
    return
  if (e.button !== undefined && e.button !== 0)
    return
  dragging.value = true
  startClientX = e.clientX - pieceX.value
  dragStartTs = performance.now()
  trackPoints = []
  recordTrack(pieceX.value + props.markWidth / 2, props.trackY + props.markHeight / 2)
  window.addEventListener('mousemove', onDrag)
  window.addEventListener('mouseup', onEnd)
}

function startDragTouch(e) {
  if (success.value)
    return
  const t = e.touches[0]
  if (!t)
    return
  dragging.value = true
  startClientX = t.clientX - pieceX.value
  dragStartTs = performance.now()
  trackPoints = []
  recordTrack(pieceX.value + props.markWidth / 2, props.trackY + props.markHeight / 2)
  window.addEventListener('touchmove', onDragTouch, { passive: false })
  window.addEventListener('touchend', onEnd)
  window.addEventListener('touchcancel', onEnd)
}

function onDrag(e) {
  if (!dragging.value)
    return
  const prevX = pieceX.value
  pieceX.value = clamp(e.clientX - startClientX, 0, props.bgWidth - props.markWidth)
  if (pieceX.value !== prevX)
    recordTrack(pieceX.value + props.markWidth / 2, props.trackY + props.markHeight / 2)
}

function onDragTouch(e) {
  if (!dragging.value)
    return
  e.preventDefault()
  const t = e.touches[0]
  if (!t)
    return
  const prevX = pieceX.value
  pieceX.value = clamp(t.clientX - startClientX, 0, props.bgWidth - props.markWidth)
  if (pieceX.value !== prevX)
    recordTrack(pieceX.value + props.markWidth / 2, props.trackY + props.markHeight / 2)
}

function onEnd() {
  if (!dragging.value)
    return
  dragging.value = false
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', onEnd)
  window.removeEventListener('touchmove', onDragTouch)
  window.removeEventListener('touchend', onEnd)
  window.removeEventListener('touchcancel', onEnd)
  recordTrack(pieceX.value + props.markWidth / 2, props.trackY + props.markHeight / 2)
  if (pieceX.value >= 5) {
    // zxf/captcha 后端用 captcha_r 作为滑动偏移量 + slide_track 轨迹数组
    emit('submit', {
      captcha_r: pieceX.value,
      slide_track: trackPoints,
    })
  }
}

onMounted(() => {
  if (trackRef.value)
    trackRef.value.addEventListener('dragstart', e => e.preventDefault())
})

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', onEnd)
  window.removeEventListener('touchmove', onDragTouch)
  window.removeEventListener('touchend', onEnd)
  window.removeEventListener('touchcancel', onEnd)
})

defineExpose({
  markSuccess() { success.value = true },
  reset() {
    success.value = false
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
}
.slide-track {
  position: relative;
  cursor: grab;
  background-repeat: no-repeat;
  background-size: 100% 100%;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
}
.slide-track:active {
  cursor: grabbing;
}
.slide-piece {
  position: absolute;
  pointer-events: auto;
  /* 用 zxf/captcha 包提供的滑块形状图 */
  background-image: url('/StudyScape/vendor/zxf/captcha/resources/assets/images/mark_02.png');
  background-size: 100% 100%;
  background-repeat: no-repeat;
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
  cursor: grab;
}
.slide-piece:active {
  cursor: grabbing;
}
.piece-inner {
  width: 100%;
  height: 100%;
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
  transition:
    background 0.15s,
    transform 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.slide-refresh:hover {
  background: rgba(0, 0, 0, 0.55);
  transform: rotate(-45deg);
}
.slide-bar {
  margin-top: 8px;
  padding: 6px 12px;
  background: var(--n-color-target-transparent, rgba(0, 0, 0, 0.05));
  border-radius: 6px;
  text-align: center;
  font-size: 12px;
  color: var(--n-text-color-3);
}
.slide-success {
  color: var(--n-color-success, #18a058);
  font-weight: 500;
}
</style>
