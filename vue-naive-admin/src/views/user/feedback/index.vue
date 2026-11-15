<template>
  <AppPage show-footer>
    <n-card title="开发者反馈" :bordered="false" class="feedback-card">
      <template #header-extra>
        <span class="muted">登录用户可直接提交反馈</span>
      </template>
      <n-alert type="info" :show-icon="false" class="intro">
        遇到题目错误、功能问题或有改进建议，欢迎告诉我们。提交后可在下方继续回复开发者。
      </n-alert>
      <n-form ref="formRef" :model="form" :rules="rules" label-placement="top" @submit.prevent="submit">
        <n-form-item label="反馈内容" path="content">
          <n-input v-model:value="form.content" type="textarea" :rows="6" maxlength="300" show-count placeholder="请描述你遇到的问题或建议（最多300字）" />
        </n-form-item>
        <n-form-item label="联系方式（可选）" path="contact">
          <n-input v-model:value="form.contact" maxlength="50" placeholder="邮箱或其他联系方式（最多50字）" />
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
            <template #header-extra>
              <n-space align="center">
                <n-tag :type="statusType(item.status)">
                  {{ statusLabel(item.status) }}
                </n-tag>
                <n-button size="tiny" quaternary @click="loadFeedback">
                  刷新
                </n-button>
              </n-space>
            </template>
            <n-text v-if="item.contact" depth="3" style="font-size:12px">
              联系方式：{{ item.contact }}
            </n-text>

            <div v-if="item.replies?.length" class="thread">
              <div v-for="reply in item.replies" :key="reply.id" class="bubble" :class="reply.author_role === 'admin' ? 'from-admin' : 'from-me'">
                <div class="bubble-meta">
                  {{ reply.author_role === 'admin' ? '开发者' : '我' }} · {{ reply.created_at }}
                </div>
                <div class="bubble-body">
                  {{ reply.content }}
                </div>
              </div>
            </div>
            <div v-else-if="item.reply_content" class="reply">
              管理员回复：{{ item.reply_content }}
            </div>

            <div class="reply-box">
              <n-input v-model:value="replyDrafts[item.id]" type="textarea" :rows="2" maxlength="500" show-count :placeholder="replyPlaceholder(item)" :disabled="!canReply(item)" />
              <div class="reply-actions">
                <span class="muted">{{ replyHint(item) }}</span>
                <n-button size="small" type="primary" :disabled="!canReply(item)" :loading="replyingId === item.id" @click="sendReply(item)">
                  回复开发者
                </n-button>
              </div>
            </div>
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
const replyingId = ref(null)
const replyDrafts = reactive({})
const form = reactive({ content: '', contact: '' })
const rules = {
  content: [
    { required: true, message: '请填写反馈内容', trigger: ['blur', 'input'] },
    {
      validator: (_, value) => {
        const text = String(value || '').trim()
        if (text.length < 2)
          return new Error('反馈内容至少 2 个字符')
        if ([...text].length > 300)
          return new Error('反馈内容不能超过 300 个字符')
        if (/<[a-z][\s\S]*>/i.test(text))
          return new Error('反馈内容不能包含 HTML 标签')
        return true
      },
      trigger: ['blur', 'input'],
    },
  ],
  contact: { max: 50, message: '联系方式不能超过 50 个字符', trigger: ['blur', 'input'] },
}

const MAX_ROUNDS = 5
const REPLY_INTERVAL = 10 * 60 * 1000

function reset() {
  formRef.value?.restoreValidation()
  Object.assign(form, { content: '', contact: '' })
}
function statusLabel(status) {
  return status === 'resolved' ? '已采纳' : status === 'closed' ? '已关闭' : status === 'processing' ? '处理中' : '等待处理'
}
function statusType(status) {
  return status === 'resolved' ? 'success' : status === 'closed' ? 'default' : status === 'processing' ? 'warning' : 'info'
}

function lastUserReplyTime(item) {
  const mine = (item.replies || []).filter(r => r.author_role === 'user')
  if (!mine.length)
    return item.created_at ? new Date(String(item.created_at).replace(' ', 'T')).getTime() : 0
  return new Date(String(mine[mine.length - 1].created_at).replace(' ', 'T')).getTime()
}
function canReply(item) {
  if ((item.user_rounds || 0) >= MAX_ROUNDS)
    return false
  const last = lastUserReplyTime(item)
  return !last || Date.now() - last >= REPLY_INTERVAL
}
function replyHint(item) {
  if ((item.user_rounds || 0) >= MAX_ROUNDS)
    return '该反馈已达 5 轮回复上限'
  const last = lastUserReplyTime(item)
  if (!last)
    return '首次回复无间隔限制（最多 5 轮）'
  const left = REPLY_INTERVAL - (Date.now() - last)
  if (left > 0)
    return `请 ${Math.ceil(left / 60000)} 分钟后再次回复`
  return `还可回复 ${MAX_ROUNDS - (item.user_rounds || 0)} 次`
}
function replyPlaceholder(item) {
  if (!canReply(item))
    return replyHint(item)
  return '继续向开发者补充说明（最多 500 字）'
}

async function loadFeedback() {
  try {
    feedbackItems.value = (await phpFeedbackApi.mine({ page: 1, page_size: 50 })).data?.items || []
  }
  catch (error) {
    window.$message?.error(error.message || '反馈记录加载失败')
  }
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

async function sendReply(item) {
  const content = String(replyDrafts[item.id] || '').trim()
  if (content.length < 2)
    return window.$message?.warning('回复内容至少 2 个字符')
  if ([...content].length > 500)
    return window.$message?.warning('单条回复不能超过 500 个字符')
  replyingId.value = item.id
  try {
    await phpFeedbackApi.reply({ id: item.id, content })
    replyDrafts[item.id] = ''
    window.$message?.success('回复已发送')
    await loadFeedback()
  }
  catch (error) { window.$message?.error(error.message || '回复失败') }
  finally { replyingId.value = null }
}

onMounted(loadFeedback)
</script>

<style scoped>
.feedback-card {
  max-width: 820px;
}
.history-card {
  margin-top: 20px;
}
.reply {
  margin-top: 8px;
  color: var(--n-text-color-2);
  white-space: pre-wrap;
}
.intro {
  margin-bottom: 24px;
}
.muted {
  color: var(--n-text-color-3);
  font-size: 12px;
}
.thread {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.bubble {
  max-width: 92%;
  padding: 8px 12px;
  border-radius: 10px;
  background: var(--n-color-embedded);
}
.bubble.from-admin {
  background: rgba(49, 108, 114, 0.1);
}
.bubble.from-me {
  align-self: flex-end;
  background: rgba(24, 160, 88, 0.1);
}
.bubble-meta {
  font-size: 11px;
  color: var(--n-text-color-3);
  margin-bottom: 2px;
}
.bubble-body {
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 13px;
}
.reply-box {
  margin-top: 12px;
}
.reply-actions {
  margin-top: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
</style>
