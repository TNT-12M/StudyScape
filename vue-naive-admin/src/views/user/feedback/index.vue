<template>
  <AppPage show-footer>
    <n-card title="开发者反馈" :bordered="false" class="feedback-card">
      <template #header-extra>
        <span class="muted">登录用户可直接提交反馈</span>
      </template>
      <n-alert type="info" :show-icon="false" class="intro">
        遇到题目错误、功能问题或有改进建议，欢迎告诉我们。
      </n-alert>
      <n-form ref="formRef" :model="form" :rules="rules" label-placement="top" @submit.prevent="submit">
        <n-form-item label="反馈内容" path="content">
          <n-input v-model:value="form.content" type="textarea" :rows="8" maxlength="2000" show-count placeholder="请描述你遇到的问题或建议" />
        </n-form-item>
        <n-form-item label="联系方式（可选）" path="contact">
          <n-input v-model:value="form.contact" maxlength="200" placeholder="邮箱、手机号或其他方便联系你的方式" />
        </n-form-item>
        <n-space justify="end">
          <n-button :disabled="submitting" @click="reset">
            清空
          </n-button>
          <n-button type="primary" :loading="submitting" attr-type="submit">
            提交反馈
          </n-button>
        </n-space>
      </n-form>
    </n-card>
    <n-card title="我的反馈" :bordered="false" class="feedback-card history-card">
      <n-list v-if="feedbackItems.length" bordered>
        <n-list-item v-for="item in feedbackItems" :key="item.id">
          <n-thing :title="item.content" :description="item.created_at">
            <template #header-extra><n-tag :type="statusType(item.status)">{{ statusLabel(item.status) }}</n-tag></template>
            <div v-if="item.reply_content" class="reply">管理员回复：{{ item.reply_content }}</div>
          </n-thing>
        </n-list-item>
      </n-list>
      <n-empty v-else description="暂无反馈记录" />
    </n-card>
  </AppPage>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { phpFeedbackApi } from '@/api/php-modules'

const formRef = ref(null)
const feedbackItems = ref([])
const submitting = ref(false)
const form = reactive({ content: '', contact: '' })
const rules = {
  content: { required: true, message: '请填写反馈内容', trigger: ['blur', 'input'] },
  contact: { max: 200, message: '联系方式不能超过 200 个字符', trigger: ['blur', 'input'] },
}

function reset() {
  formRef.value?.restoreValidation()
  Object.assign(form, { content: '', contact: '' })
}
function statusLabel(status) { return status === 'resolved' ? '已采纳' : status === 'closed' ? '已关闭' : status === 'processing' ? '处理中' : '等待处理' }
function statusType(status) { return status === 'resolved' ? 'success' : status === 'closed' ? 'default' : status === 'processing' ? 'warning' : 'info' }
async function loadFeedback() {
  try { feedbackItems.value = (await phpFeedbackApi.mine({ page: 1, page_size: 50 })).data?.items || [] }
  catch (error) { window.$message?.error(error.message || '反馈记录加载失败') }
}

async function submit() {
  try {
    await formRef.value?.validate()
  }
  catch {
    return
  }
  submitting.value = true
  try {
    const result = await phpFeedbackApi.submit({ content: form.content.trim(), contact: form.contact.trim() })
    window.$message?.success(result.message || '反馈提交成功，感谢你的建议')
    reset()
    await loadFeedback()
  }
  catch (error) { window.$message?.error(error.message || '反馈提交失败') }
  finally { submitting.value = false }
}
onMounted(loadFeedback)
</script>

<style scoped>
.feedback-card {
  max-width: 820px;
}
.history-card { margin-top: 20px; }
.reply { margin-top: 8px; color: var(--n-text-color-2); white-space: pre-wrap; }
.intro {
  margin-bottom: 24px;
}
.muted {
  color: var(--n-text-color-3);
  font-size: 12px;
}
</style>
