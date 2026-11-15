<template>
  <div class="about-page">
    <header class="about-nav">
      <a class="brand" @click="router.push('/welcome')">
        <span class="brand-mark">境</span>
        <strong>学境 · StudyScape</strong>
      </a>
      <a class="back" @click="router.push('/welcome')">返回首页</a>
    </header>

    <main class="about-main">
      <h1>加入学境</h1>
      <p class="subtitle">
        扫码加入学习群或关注公众号，获取资料更新、答疑与活动通知。
      </p>

      <div class="qr-grid">
        <div v-for="item in qrItems" :key="item.key" class="qr-card" @click="openPreview(item)">
          <div class="qr-frame">
            <img v-if="!failed[item.key]" :src="qrSrc(item.key)" :alt="item.title" @error="onImgError(item.key)">
            <div v-else class="qr-placeholder">
              <span>二维码待上传</span>
              <small>{{ item.key }}.png</small>
            </div>
          </div>
          <h2>{{ item.title }}</h2>
          <p>{{ item.desc }}</p>
        </div>
      </div>

      <div class="about-tip">
        <p>如果二维码过期或无法加入，请通过「开发者反馈」告诉我们，或发送邮件至官方邮箱。</p>
        <p class="domain">
          官网：<strong>x9b1.cc</strong>
        </p>
      </div>
    </main>

    <footer class="about-footer">
      © 2026 学境 StudyScape · 仅供学习交流使用
    </footer>

    <NModal v-model:show="showPreview" preset="card" :title="previewTitle" class="qr-modal">
      <div class="qr-preview">
        <img v-if="previewSrc && !failed[previewKey]" :src="previewSrc" :alt="previewTitle" @error="onImgError(previewKey)">
        <div v-else class="qr-placeholder">
          <span>二维码待上传</span>
          <small>{{ previewKey }}.png</small>
        </div>
      </div>
    </NModal>
  </div>
</template>

<script setup>
import { NModal } from 'naive-ui'
import { onBeforeUnmount, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const failed = reactive({ 'wechat-group': false, 'official-account': false })
const showPreview = ref(false)
const previewKey = ref('wechat-group')

const qrItems = [
  { key: 'wechat-group', title: '学境学习群', desc: '和同学一起刷题打卡，遇到问题群里互相讨论。' },
  { key: 'official-account', title: '学境公众号', desc: '关注后接收资料更新、考试提醒与学习方法。' },
]

const previewTitle = computed(() => (previewKey.value === 'wechat-group' ? '学境学习群' : '学境公众号'))
const previewSrc = computed(() => (failed[previewKey.value] ? '' : qrSrc(previewKey.value)))

function qrSrc(name) {
  return `${import.meta.env.BASE_URL}assets/qr/${name}.png`
}
function onImgError(key) {
  failed[key] = true
}
function openPreview(item) {
  previewKey.value = item.key
  showPreview.value = true
}

onMounted(() => {
  document.documentElement.classList.add('about-active')
})
onBeforeUnmount(() => {
  document.documentElement.classList.remove('about-active')
})
</script>

<style scoped>
.about-page {
  min-height: 100vh;
  background: radial-gradient(circle at 20% 0%, #1d4a4f 0%, #0f2027 45%, #0b1518 100%);
  color: #e8f3f4;
  display: flex;
  flex-direction: column;
}
.about-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 32px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.brand {
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  color: #fff;
  text-decoration: none;
}
.brand-mark {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: linear-gradient(135deg, #3f8a90, #316c72);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: #fff;
}
.brand strong {
  font-size: 16px;
}
.back {
  cursor: pointer;
  color: #9ec6c9;
  font-size: 13px;
  text-decoration: none;
}
.back:hover {
  color: #fff;
}
.about-main {
  flex: 1;
  width: min(900px, 92vw);
  margin: 0 auto;
  padding: 64px 0 48px;
  text-align: center;
}
h1 {
  font-size: 32px;
  margin: 0 0 12px;
  letter-spacing: 2px;
}
.subtitle {
  color: #9ec6c9;
  font-size: 14px;
  line-height: 1.8;
  margin-bottom: 44px;
}
.qr-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}
.qr-card {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 26px 18px;
  cursor: pointer;
  transition:
    transform 0.15s,
    border-color 0.15s;
}
.qr-card:hover {
  transform: translateY(-3px);
  border-color: rgba(90, 200, 190, 0.5);
}
.qr-frame {
  width: 210px;
  height: 210px;
  margin: 0 auto 18px;
  background: #fff;
  border-radius: 12px;
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
}
.qr-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  color: #7d8f90;
  font-size: 13px;
}
.qr-placeholder small {
  font-size: 11px;
  color: #a5b4b5;
}
.qr-card h2 {
  font-size: 16px;
  margin: 0 0 8px;
}
.qr-card p {
  color: #9ec6c9;
  font-size: 12.5px;
  line-height: 1.7;
  margin: 0;
}
.about-tip {
  margin-top: 46px;
  padding-top: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  color: #8fb3b6;
  font-size: 12.5px;
  line-height: 1.9;
}
.domain strong {
  color: #6fd3c8;
  font-size: 14px;
  letter-spacing: 1px;
}
.about-footer {
  text-align: center;
  padding: 22px;
  color: #6f8b8e;
  font-size: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}
.qr-modal .qr-preview {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
}
.qr-preview img {
  width: min(340px, 80vw);
  height: min(340px, 80vw);
  object-fit: contain;
}
:deep(.qr-modal .n-modal-card) {
  width: min(460px, 92vw) !important;
}
@media (max-width: 720px) {
  .qr-grid {
    grid-template-columns: 1fr;
  }
  .about-main {
    padding-top: 40px;
  }
  h1 {
    font-size: 26px;
  }
}
</style>

<style>
html.about-active,
html.about-active body,
html.about-active #app {
  height: auto;
  min-height: 100%;
  overflow: visible;
}
html.about-active body {
  overflow-y: auto;
}
</style>
