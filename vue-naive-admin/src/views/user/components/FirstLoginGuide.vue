<template>
  <NModal v-model:show="show" preset="card" title="欢迎来到学境" class="guide-modal" :mask-closable="false" :closable="false">
    <div v-if="videoUrl" class="video-slot">
      <template v-if="isVideoFile">
        <video :src="videoUrl" controls preload="metadata" />
      </template>
      <template v-else>
        <a :href="videoUrl" target="_blank" rel="noopener noreferrer">▶ 打开引导视频</a>
      </template>
    </div>

    <div class="guide-body">
      <div class="step-dots">
        <span v-for="(_, i) in steps" :key="i" class="dot" :class="{ active: i === index }" />
      </div>
      <div class="step-card">
        <span class="step-no">第 {{ index + 1 }} 步 / 共 {{ steps.length }} 步</span>
        <h3>{{ currentStep.title }}</h3>
        <p>{{ currentStep.content }}</p>
      </div>
    </div>

    <template #footer>
      <div class="guide-actions">
        <NButton quaternary @click="finish">
          跳过引导
        </NButton>
        <NSpace>
          <NButton :disabled="index === 0" @click="index -= 1">
            上一步
          </NButton>
          <NButton type="primary" @click="next">
            {{ isLast ? '开始使用' : '下一步' }}
          </NButton>
        </NSpace>
      </div>
    </template>
  </NModal>
</template>

<script setup>
import { NButton, NModal, NSpace } from 'naive-ui'
import { computed, ref } from 'vue'
import { phpSettingsApi } from '@/api/php-modules'

const show = ref(false)
const steps = ref([])
const index = ref(0)
const videoUrl = ref('')

const currentStep = computed(() => steps.value[index.value] || { title: '', content: '' })
const isLast = computed(() => index.value >= steps.value.length - 1)
const isVideoFile = computed(() => /\.(?:mp4|webm|ogg)(?:\?|$)/i.test(videoUrl.value))

async function loadState() {
  try {
    const result = await phpSettingsApi.guideState()
    const data = result.data || {}
    if (!data.enabled || data.seen)
      return
    steps.value = Array.isArray(data.steps) ? data.steps.filter(s => s && (s.title || s.content)) : []
    videoUrl.value = String(data.video_url || '')
    index.value = 0
    if (steps.value.length)
      show.value = true
  }
  catch { /* 引导失败不阻断用户端 */ }
}

function next() {
  if (isLast.value)
    return finish()
  index.value += 1
}

async function finish() {
  show.value = false
  try {
    await phpSettingsApi.guideDone()
  }
  catch { /* 记录失败不阻断 */ }
}

onMounted(loadState)
</script>

<style scoped>
.guide-modal :deep(.n-modal-card) {
  width: min(560px, 92vw);
}
.video-slot {
  margin-bottom: 16px;
  border-radius: 10px;
  overflow: hidden;
  background: var(--n-color-embedded);
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 120px;
}
.video-slot video {
  width: 100%;
  max-height: 320px;
  display: block;
}
.video-slot a {
  color: #316c72;
  font-size: 14px;
  text-decoration: none;
  padding: 12px 18px;
}
.guide-body {
  padding: 4px 2px;
}
.step-dots {
  display: flex;
  gap: 8px;
  margin-bottom: 16px;
}
.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--n-border-color);
  transition: background-color 0.2s;
}
.dot.active {
  background: #316c72;
}
.step-card {
  background: var(--n-color-embedded);
  border-radius: 12px;
  padding: 18px 20px;
  min-height: 120px;
}
.step-no {
  font-size: 11px;
  color: var(--n-text-color-3);
}
.step-card h3 {
  margin: 8px 0 10px;
  font-size: 17px;
}
.step-card p {
  margin: 0;
  color: var(--n-text-color-2);
  line-height: 1.8;
  font-size: 13.5px;
}
.guide-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
</style>
