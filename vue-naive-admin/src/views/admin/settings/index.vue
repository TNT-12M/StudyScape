<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="首次登录引导" segmented>
        <n-alert type="info" :show-icon="false" class="mb-16">
          用户首次登录学习中心时自动弹出引导弹窗；关闭或完成后不再重复弹出。后续可在此处替换为引导视频。
        </n-alert>
        <n-form label-placement="left" label-width="110">
          <n-form-item label="启用引导">
            <n-switch v-model:value="enabled" />
            <span class="hint">关闭后所有用户不再看到引导弹窗</span>
          </n-form-item>
          <n-form-item label="引导视频">
            <n-input v-model:value="videoUrl" placeholder="可选：https://... 视频链接或 .mp4 直链，留空则不显示视频" clearable />
          </n-form-item>
          <n-form-item label="引导步骤">
            <div class="steps">
              <div v-for="(step, index) in steps" :key="index" class="step-row">
                <div class="step-head">
                  <strong>第 {{ index + 1 }} 步</strong>
                  <n-button size="tiny" quaternary type="error" :disabled="steps.length <= 1" @click="removeStep(index)">
                    删除
                  </n-button>
                </div>
                <n-input v-model:value="step.title" maxlength="40" show-count placeholder="标题，最多 40 字" />
                <n-input v-model:value="step.content" type="textarea" :rows="2" maxlength="200" show-count placeholder="内容说明，最多 200 字" />
              </div>
              <n-button size="small" dashed :disabled="steps.length >= 5" @click="addStep">
                + 添加一步（最多 5 步）
              </n-button>
            </div>
          </n-form-item>
        </n-form>
        <n-space justify="end">
          <n-button type="primary" :loading="saving" @click="save">
            保存引导配置
          </n-button>
        </n-space>
      </n-card>
    </n-space>
  </AppPage>
</template>

<script setup>
import { phpAdminGuideApi } from '@/api/php-modules'

const message = window.$message
const loading = ref(false)
const saving = ref(false)
const enabled = ref(true)
const videoUrl = ref('')
const steps = ref([])

const DEFAULT_STEPS = [
  { title: '在线刷题', content: '按学科和学段选择题目，提交后自动判分，错题可回看解析。' },
  { title: '资料中心', content: '下载的 PDF 会自动带上「学境」水印与官网二维码，方便转发给同学。' },
  { title: '开发者反馈', content: '遇到题目错误或功能建议，直接在这里留言，开发者会回复你。' },
]

function addStep() {
  if (steps.value.length >= 5)
    return
  steps.value.push({ title: '', content: '' })
}
function removeStep(index) {
  if (steps.value.length <= 1)
    return
  steps.value.splice(index, 1)
}
async function load() {
  loading.value = true
  try {
    const result = await phpAdminGuideApi.get()
    const data = result.data || {}
    enabled.value = data.enabled !== false
    videoUrl.value = String(data.video_url || '')
    steps.value = Array.isArray(data.steps) && data.steps.length
      ? data.steps.map(s => ({ title: String(s.title || ''), content: String(s.content || '') }))
      : DEFAULT_STEPS.map(s => ({ ...s }))
  }
  catch (error) { message.error(error.message || '引导配置加载失败') }
  finally { loading.value = false }
}
async function save() {
  const clean = steps.value
    .map(s => ({ title: String(s.title || '').trim(), content: String(s.content || '').trim() }))
    .filter(s => s.title || s.content)
  if (!clean.length)
    return message.warning('至少保留一个引导步骤')
  if (videoUrl.value && !/^https?:\/\//i.test(videoUrl.value))
    return message.warning('视频链接必须是 http/https 地址')
  saving.value = true
  try {
    await phpAdminGuideApi.save({ enabled: enabled.value ? '1' : '0', video_url: videoUrl.value.trim(), steps: clean })
    message.success('引导配置已保存')
  }
  catch (error) { message.error(error.message || '保存失败') }
  finally { saving.value = false }
}

onMounted(load)
</script>

<style scoped>
.mb-16 {
  margin-bottom: 16px;
}
.hint {
  margin-left: 12px;
  font-size: 12px;
  color: var(--n-text-color-3);
}
.steps {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: min(720px, 100%);
}
.step-row {
  border: 1px solid var(--n-border-color);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.step-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 13px;
}
</style>
