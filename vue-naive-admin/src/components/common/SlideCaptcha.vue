<template>
  <div class="slide-captcha" :class="{ 'is-success': success }">
    <div
      ref="trackRef"
      class="slide-track"
      :style="{ width: `${bgWidth}px`, height: `${bgHeight}px`, backgroundImage: `url(${bgImage})` }"
      @mousedown="startDrag"
      @touchstart.prevent="startDragTouch"
    >
      <img
        class="slide-piece"
        :src="pieceImage"
        :style="{ left: `${pieceX}px`, top: `${trackY}px`, width: `${pieceSize}px`, height: `${pieceSize}px` }"
        draggable="false"
        alt="拼图块"
      >
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
  pieceImage: { type: String, default: '' },
  bgWidth: { type: Number, default: 310 },
  bgHeight: { type: Number, default: 150 },
  pieceSize: { type: Number, default: 44 },
  trackY: { type: Number, default: 50 },
})

const emit = defineEmits(['submit', 'refresh'])

const trackRef = ref(null)
const pieceX = ref(0)
const dragging = ref(false)
const success = ref(false)
let startClientX = 0

function clamp(v, min, max) {
  return Math.max(min, Math.min(max, v))
}

function startDrag(e) {
  if (success.value)
    return
  if (e.button !== undefined && e.button !== 0)
    return
  dragging.value = true
  startClientX = e.clientX - pieceX.value
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
  window.addEventListener('touchmove', onDragTouch, { passive: false })
  window.addEventListener('touchend', onEnd)
  window.addEventListener('touchcancel', onEnd)
}

function onDrag(e) {
  if (!dragging.value)
    return
  pieceX.value = clamp(e.clientX - startClientX, 0, props.bgWidth - props.pieceSize)
}

function onDragTouch(e) {
  if (!dragging.value)
    return
  e.preventDefault()
  const t = e.touches[0]
  if (!t)
    return
  pieceX.value = clamp(t.clientX - startClientX, 0, props.bgWidth - props.pieceSize)
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
  // 防抖：必须有明显位移才提交
  if (pieceX.value >= 5) {
    emit('submit', pieceX.value)
  }
}

onMounted(() => {
  // 防止文本选中导致拖动卡顿
  if (trackRef.value) {
    trackRef.value.addEventListener('dragstart', e => e.preventDefault())
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('mousemove', onDrag)
  window.removeEventListener('mouseup', onEnd)
  window.removeEventListener('touchmove', onDragTouch)
  window.removeEventListener('touchend', onEnd)
  window.removeEventListener('touchcancel', onEnd)
})

// 外部调用：标记验证成功（禁用后续拖动）
defineExpose({
  markSuccess() {
    success.value = true
  },
  reset() {
    success.value = false
    pieceX.value = 0
    dragging.value = false
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
  pointer-events: none;
  image-rendering: -webkit-optimize-contrast;
  image-rendering: crisp-edges;
  border-radius: 4px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
  transition: box-shadow 0.15s;
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
