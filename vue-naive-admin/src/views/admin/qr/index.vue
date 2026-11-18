<template>
  <AppPage>
    <n-card title="网站图标（全站生效）" segmented class="icon-card">
      <n-alert type="info" :show-icon="false" class="tip">
        仅最高权限 root 可上传。上传一张图标后，浏览器标签页图标、侧边栏 Logo、登录页与官网首页标识会同时生效。
      </n-alert>
      <div class="icon-row">
        <div class="icon-frame">
          <img v-if="!iconFailed" :src="siteIconSrc" alt="网站图标" @error="iconFailed = true">
          <span v-else class="icon-placeholder">未上传</span>
        </div>
        <div class="icon-meta">
          <p class="muted">
            支持 PNG / JPG / WEBP，≤2MB，建议上传正方形图片（最小 64×64）。服务器会自动居中裁剪并压缩为 256×256 PNG。
          </p>
          <div class="icon-actions">
            <n-tag :type="iconFailed ? 'warning' : 'success'" size="small">
              {{ iconFailed ? '未上传' : '已上传' }}
            </n-tag>
            <n-button size="small" type="primary" :loading="iconUploading" @click="pickIcon">
              上传/更换图标
            </n-button>
          </div>
          <input
            id="admin-site-icon-file"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            hidden
            @change="onIconChange"
          >
        </div>
      </div>
    </n-card>

    <n-card title="群聊 / 公众号二维码" segmented>
      <n-alert type="info" :show-icon="false" class="tip">
        仅最高权限 root 可上传或更换二维码，普通用户与内容管理员只能查看。图片保存在服务器 <code>data/qr</code> 目录，重新部署前端不会丢失。
      </n-alert>
      <div class="qr-grid">
        <div v-for="item in qrItems" :key="item.key" class="qr-item">
          <h3>{{ item.title }}</h3>
          <div class="qr-frame">
            <img v-if="!failed[item.key]" :src="qrSrc(item.key)" :alt="item.title" @error="onImgError(item.key)">
            <div v-else class="placeholder">
              <span>未上传</span>
              <small>{{ item.key }}</small>
            </div>
          </div>
          <p class="muted">
            {{ item.desc }}
          </p>
          <div class="item-footer">
            <n-tag :type="failed[item.key] ? 'warning' : 'success'" size="small">
              {{ failed[item.key] ? '未上传' : '已上传' }}
            </n-tag>
            <n-button size="small" type="primary" :loading="uploadingKey === item.key" @click="pickFile(item.key)">
              上传/更换
            </n-button>
          </div>
          <input
            :id="`admin-qr-file-${item.key}`"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            hidden
            @change="onFileChange(item.key, $event)"
          >
        </div>
      </div>
    </n-card>
  </AppPage>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { phpAdminApi } from '@/api/php-modules'
import { applyFavicon, setSiteIconVersion, siteIconSrc } from '@/utils/site-icon'

const failed = reactive({ 'wechat-group': false, 'official-account': false })
const versions = reactive({ 'wechat-group': 0, 'official-account': 0 })
const uploadingKey = ref('')
const iconFailed = ref(false)
const iconUploading = ref(false)

const qrItems = [
  { key: 'wechat-group', title: '学境学习群', desc: '显示在学习中心「加入学境」页，学生扫码进群。' },
  { key: 'official-account', title: '学境公众号', desc: '显示在学习中心「加入学境」页，学生扫码关注。' },
]

function qrSrc(key) {
  const base = `${import.meta.env.VITE_PHP_API_URL || '/api.php'}?action=qr_image&key=${key}`
  const version = versions[key]
  return version ? `${base}&v=${version}` : base
}
function onImgError(key) {
  failed[key] = true
}
function pickFile(key) {
  const input = document.getElementById(`admin-qr-file-${key}`)
  if (input)
    input.click()
}

function pickIcon() {
  document.getElementById('admin-site-icon-file')?.click()
}

async function onIconChange(event) {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''
  if (!file)
    return
  if (!/^image\/(?:png|jpeg|webp)$/.test(file.type)) {
    window.$message?.warning('仅支持 PNG / JPG / WEBP 图片')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    window.$message?.warning('图标图片不能超过 2MB')
    return
  }
  iconUploading.value = true
  try {
    const result = await phpAdminApi.uploadSiteIcon({ file })
    iconFailed.value = false
    setSiteIconVersion(result.data?.version)
    applyFavicon()
    window.$message?.success('网站图标已更新，全站生效')
  }
  catch (error) {
    window.$message?.error(error.message || '图标上传失败')
  }
  finally {
    iconUploading.value = false
  }
}
async function onFileChange(key, event) {
  const input = event.target
  const file = input.files?.[0]
  input.value = ''
  if (!file)
    return
  if (!/^image\/(?:png|jpeg|webp)$/.test(file.type)) {
    window.$message?.warning('仅支持 PNG / JPG / WEBP 图片')
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    window.$message?.warning('二维码图片不能超过 2MB')
    return
  }
  uploadingKey.value = key
  try {
    const result = await phpAdminApi.uploadQr({ key, file })
    failed[key] = false
    versions[key] = Number(result.data?.version || Date.now())
    window.$message?.success('二维码已更新')
  }
  catch (error) {
    window.$message?.error(error.message || '二维码上传失败')
  }
  finally {
    uploadingKey.value = ''
  }
}
</script>

<style scoped>
.icon-card {
  margin-bottom: 18px;
}
.icon-row {
  display: flex;
  align-items: center;
  gap: 20px;
  flex-wrap: wrap;
}
.icon-frame {
  width: 96px;
  height: 96px;
  flex-shrink: 0;
  border-radius: 16px;
  background: var(--n-color-embedded);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.icon-frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.icon-placeholder {
  color: var(--n-text-color-3);
  font-size: 12px;
}
.icon-meta {
  flex: 1;
  min-width: 220px;
}
.icon-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
.tip {
  margin-bottom: 20px;
}
.qr-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}
.qr-item {
  border: 1px solid var(--n-border-color);
  border-radius: 12px;
  padding: 18px;
  text-align: center;
}
.qr-item h3 {
  margin: 0 0 12px;
  font-size: 15px;
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
.placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: var(--n-text-color-3);
  font-size: 13px;
}
.placeholder small {
  font-size: 11px;
}
.muted {
  color: var(--n-text-color-3);
  font-size: 12px;
  margin: 0 0 14px;
  line-height: 1.7;
}
.item-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
@media (max-width: 720px) {
  .qr-grid {
    grid-template-columns: 1fr;
  }
}
</style>
