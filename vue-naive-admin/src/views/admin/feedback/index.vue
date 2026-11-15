<template>
  <AppPage>
    <NSpace vertical size="large">
      <n-card title="反馈筛选" segmented>
        <n-form inline label-placement="left">
          <n-form-item label="处理状态">
            <n-select v-model:value="filters.status" clearable :options="statusOptions" style="width: 150px" @update:value="loadFeedback" />
          </n-form-item>
          <n-form-item label="关键词">
            <n-input v-model:value="filters.keyword" clearable placeholder="内容、联系方式或用户" @keyup.enter="loadFeedback" />
          </n-form-item>
          <n-form-item>
            <NButton type="primary" :loading="loading" @click="loadFeedback">
              查询
            </NButton>
          </n-form-item>
        </n-form>
      </n-card>
      <n-card title="用户反馈" segmented>
        <n-data-table :columns="columns" :data="feedback" :loading="loading" :pagination="pagination" />
        <n-empty v-if="!loading && !feedback.length" description="暂无反馈" />
      </n-card>
    </NSpace>
    <n-modal v-model:show="showDetail" preset="card" title="反馈详情" class="feedback-modal">
      <n-descriptions v-if="selected" bordered :column="1" label-placement="top">
        <n-descriptions-item label="用户">
          {{ selected.username || selected.email || selected.user_id || '-' }}
        </n-descriptions-item>
        <n-descriptions-item label="联系方式">
          {{ selected.contact || '未提供' }}
        </n-descriptions-item>
        <n-descriptions-item label="提交时间">
          {{ selected.created_at || selected.createdAt || '-' }}
        </n-descriptions-item>
        <n-descriptions-item label="反馈内容">
          <div class="content">
            {{ selected.content || selected.message || '-' }}
          </div>
        </n-descriptions-item>
        <n-descriptions-item label="处理状态">
          <n-select v-model:value="editForm.status" :options="statusOptions" />
        </n-descriptions-item>
        <n-descriptions-item label="内部备注">
          <n-input v-model:value="editForm.admin_note" type="textarea" :rows="3" placeholder="内部备注，用户不可见" />
        </n-descriptions-item>
      </n-descriptions>

      <n-divider title-placement="left">
        对话记录
      </n-divider>
      <div v-if="replies.length" class="thread">
        <div v-for="reply in replies" :key="reply.id" class="bubble" :class="reply.author_role === 'admin' ? 'from-admin' : 'from-user'">
          <div class="bubble-meta">
            {{ reply.author_role === 'admin' ? '开发者' : (selected?.username || '用户') }} · {{ reply.created_at }}
          </div>
          <div class="bubble-body">
            {{ reply.content }}
          </div>
        </div>
      </div>
      <n-empty v-else size="small" description="暂无对话记录" />

      <div class="reply-box">
        <n-input v-model:value="editForm.reply_content" type="textarea" :rows="3" maxlength="500" show-count placeholder="自定义回复内容（最多 500 字，可留空使用默认采纳文案）" />
        <n-text depth="3" style="font-size:12px">
          回复会通过站内通知提醒用户；用户每 10 分钟最多回复 1 次，双方各最多 5 轮。
        </n-text>
      </div>

      <template #footer>
        <div class="modal-actions">
          <NButton @click="showDetail = false">
            取消
          </NButton>
          <NButton v-if="selected?.status !== 'closed'" type="warning" :loading="saving" @click="quickAction('ignore')">
            忽略
          </NButton>
          <NButton v-if="selected?.status === 'closed' || selected?.status === 'resolved'" :loading="saving" @click="quickAction('reopen')">
            重新处理
          </NButton>
          <NButton v-if="selected?.status !== 'resolved'" type="success" :loading="saving" @click="adoptFeedback">
            采纳并回复
          </NButton>
          <NButton type="primary" :loading="saving" :disabled="!editForm.reply_content.trim()" @click="sendReply">
            发送回复
          </NButton>
          <NButton :loading="saving" @click="saveFeedback">
            仅保存状态
          </NButton>
        </div>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { NButton, NSpace, NTag } from 'naive-ui'
import { phpAdminFeedbackApi } from '@/api/php-modules'

const message = window.$message
const feedback = ref([])
const loading = ref(false)
const saving = ref(false)
const showDetail = ref(false)
const selected = ref(null)
const replies = ref([])
const filters = reactive({ status: null, keyword: '' })
const editForm = reactive({ id: null, status: 'open', admin_note: '', reply_content: '' })
const pagination = reactive({
  page: 1,
  pageSize: 20,
  itemCount: 0,
  showSizePicker: true,
  pageSizes: [10, 20, 50],
  onChange: (page) => {
    pagination.page = page
    loadFeedback()
  },
  onUpdatePageSize: (size) => {
    pagination.pageSize = size
    pagination.page = 1
    loadFeedback()
  },
})
const statusOptions = [
  { label: '待处理', value: 'open' },
  { label: '处理中', value: 'processing' },
  { label: '已解决', value: 'resolved' },
  { label: '已关闭', value: 'closed' },
]
const columns = [
  { title: '反馈内容', key: 'content', ellipsis: { tooltip: true }, render: row => row.content || '-' },
  { title: '用户', key: 'username', render: row => row.username || row.email || row.user_id || '-' },
  { title: '状态', key: 'status', width: 100, render: row => h(NTag, { type: tagType(row.status) }, { default: () => statusLabel(row.status) }) },
  { title: '对话', key: 'reply_count', width: 80, render: row => `${row.reply_count || 0} 条` },
  { title: '提交时间', key: 'created_at', width: 180 },
  { title: '操作', key: 'actions', width: 100, render: row => h(NButton, { size: 'small', onClick: () => openDetail(row) }, { default: () => '处理' }) },
]

function statusLabel(value) {
  return statusOptions.find(item => item.value === value)?.label || value || '待处理'
}
function tagType(value) {
  if (value === 'resolved')
    return 'success'
  if (value === 'processing')
    return 'warning'
  if (value === 'closed')
    return 'default'
  return 'info'
}
async function openDetail(row) {
  selected.value = row
  Object.assign(editForm, { id: row.id, status: row.status || 'open', admin_note: row.admin_note || '', reply_content: '' })
  replies.value = []
  showDetail.value = true
  try {
    const result = await phpAdminFeedbackApi.thread(row.id)
    replies.value = result.data?.replies || []
  }
  catch (error) { message.error(error.message || '对话记录加载失败') }
}
async function loadFeedback() {
  loading.value = true
  try {
    const result = await phpAdminFeedbackApi.list({ ...filters, page: pagination.page, page_size: pagination.pageSize })
    const data = result.data || {}
    feedback.value = data.items || data.feedback || data.list || []
    pagination.itemCount = Number(data.pagination?.total ?? data.total ?? feedback.value.length) || 0
  }
  catch (error) { message.error(error.message || '反馈加载失败') }
  finally { loading.value = false }
}
async function saveFeedback() {
  if (!editForm.id)
    return
  saving.value = true
  try {
    await phpAdminFeedbackApi.update({ id: editForm.id, status: editForm.status, admin_note: editForm.admin_note })
    message.success('反馈已更新')
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '反馈更新失败') }
  finally { saving.value = false }
}
async function adoptFeedback() {
  if (!selected.value?.id)
    return
  saving.value = true
  try {
    await phpAdminFeedbackApi.adopt(selected.value.id, editForm.reply_content.trim())
    message.success('已采纳并回复用户')
    showDetail.value = false
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '采纳失败') }
  finally { saving.value = false }
}
async function sendReply() {
  if (!selected.value?.id)
    return
  const content = editForm.reply_content.trim()
  if (content.length < 2)
    return message.warning('回复内容至少 2 个字符')
  saving.value = true
  try {
    await phpAdminFeedbackApi.reply({ id: selected.value.id, content })
    message.success('回复已发送')
    editForm.reply_content = ''
    const result = await phpAdminFeedbackApi.thread(selected.value.id)
    replies.value = result.data?.replies || []
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '回复失败') }
  finally { saving.value = false }
}
async function quickAction(action) {
  if (!selected.value?.id)
    return
  saving.value = true
  try {
    await phpAdminFeedbackApi.update({ id: selected.value.id, feedback_action: action, admin_note: editForm.admin_note })
    message.success(action === 'ignore' ? '已忽略' : '已重新打开')
    showDetail.value = false
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '反馈操作失败') }
  finally { saving.value = false }
}

onMounted(loadFeedback)
</script>

<style scoped>
.content {
  white-space: pre-wrap;
  line-height: 1.7;
}
.thread {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 32vh;
  overflow-y: auto;
  padding-right: 4px;
}
.bubble {
  max-width: 92%;
  padding: 8px 12px;
  border-radius: 10px;
  background: var(--n-color-embedded);
}
.bubble.from-admin {
  align-self: flex-end;
  background: rgba(49, 108, 114, 0.12);
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
  margin-top: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}
:deep(.feedback-modal .n-modal-card) {
  width: min(680px, 94vw) !important;
  max-width: 94vw;
}
:deep(.feedback-modal .n-modal-card-body) {
  max-height: 70vh;
  overflow-y: auto;
}
@media (max-width: 768px) {
  .modal-actions {
    justify-content: stretch;
  }
  .modal-actions .n-button {
    flex: 1;
    min-width: calc(50% - 4px);
  }
  :deep(.feedback-modal .n-modal-card) {
    width: 100% !important;
    max-width: 100%;
    margin: 0;
    border-radius: 16px 16px 0 0;
  }
}
</style>
