<template>
  <AppPage show-footer>
    <n-card title="加入学境" :bordered="false" class="about-card">
      <template #header-extra>
        <span class="muted">扫码加入学习群或关注公众号</span>
      </template>
      <n-alert type="info" :show-icon="false" class="intro">
        扫码加入学习群或关注公众号，获取资料更新、答疑与活动通知。二维码过期或无法加入时，请通过「开发者反馈」告诉我们。
      </n-alert>

      <div class="qr-grid">
        <div v-for="item in qrItems" :key="item.key" class="qr-card" @click="openPreview(item)">
          <div class="qr-frame">
            <img v-if="!failed[item.key]" :src="qrSrc(item.key)" :alt="item.title" @error="onImgError(item.key)">
            <div v-else class="qr-placeholder">
              <span>二维码待上传</span>
              <small>{{ item.key }}</small>
            </div>
          </div>
          <h3>{{ item.title }}</h3>
          <p class="muted">
            {{ item.desc }}
          </p>
        </div>
      </div>

      <div class="domain-tip">
        官网：<strong>x9b1.cc</strong>
      </div>
    </n-card>

    <n-modal
      v-model:show="showPreview"
      preset="card"
      :title="previewTitle"
      style="width: min(420px, 92vw)"
    >
      <div class="qr-preview">
        <img v-if="previewSrc && !failed[previewKey]" :src="previewSrc" :alt="previewTitle" @error="onImgError(previewKey)">
        <div v-else class="qr-placeholder">
          <span>二维码待上传</span>
          <small>{{ previewKey }}</small>
        </div>
      </div>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { reactive, ref } from 'vue'

const failed = reactive({ 'wechat-group': false, 'official-account': false })
const showPreview = ref(false)
const previewKey = ref('wechat-group')

const qrItems = [
  { key: 'wechat-group', title: '学境学习群', desc: '和同学一起刷题打卡，遇到问题群里互相讨论。' },
  { key: 'official-account', title: '学境公众号', desc: '关注后接收资料更新、考试提醒与学习方法。' },
]

const previewTitle = computed(() => (previewKey.value === 'wechat-group' ? '学境学习群' : '学境公众号'))
const previewSrc = computed(() => (failed[previewKey.value] ? '' : qrSrc(previewKey.value)))

function qrSrc(key) {
  return `${import.meta.env.VITE_PHP_API_URL || '/api.php'}?action=qr_image&key=${key}`
}
function onImgError(key) {
  failed[key] = true
}
function openPreview(item) {
  previewKey.value = item.key
  showPreview.value = true
}
</script>

<style scoped>
.about-card {
  max-width: 860px;
}
.intro {
  margin-bottom: 20px;
}
.muted {
  color: var(--n-text-color-3);
  font-size: 12px;
}
.qr-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}
.qr-card {
  border: 1px solid var(--n-border-color);
  border-radius: 12px;
  padding: 18px;
  text-align: center;
  cursor: pointer;
  transition:
    border-color 0.15s,
    transform 0.15s;
}
.qr-card:hover {
  border-color: #316c72;
  transform: translateY(-2px);
}
.qr-frame {
  width: 200px;
  height: 200px;
  margin: 0 auto 12px;
  border-radius: 10px;
  background: var(--n-color-embedded);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.qr-frame img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
  background: #fff;
}
.qr-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: var(--n-text-color-3);
  font-size: 13px;
  background: var(--n-color-embedded);
}
.qr-placeholder small {
  font-size: 11px;
}
.qr-card h3 {
  margin: 0 0 6px;
  font-size: 15px;
}
.qr-card p {
  margin: 0;
  line-height: 1.7;
}
.domain-tip {
  margin-top: 22px;
  padding-top: 16px;
  border-top: 1px solid var(--n-border-color);
  color: var(--n-text-color-3);
  font-size: 12.5px;
}
.domain-tip strong {
  color: #316c72;
  letter-spacing: 1px;
}
.qr-preview {
  width: min(320px, 80vw);
  height: min(320px, 80vw);
  margin: 0 auto;
}
.qr-preview img,
.qr-preview .qr-placeholder {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
@media (max-width: 720px) {
  .qr-grid {
    grid-template-columns: 1fr;
  }
  .qr-frame {
    width: 180px;
    height: 180px;
  }
}
</style>
