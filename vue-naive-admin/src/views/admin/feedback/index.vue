<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="反馈筛选" segmented>
        <n-form inline label-placement="left">
          <n-form-item label="处理状态">
            <n-select v-model:value="filters.status" clearable :options="statusOptions" style="width: 150px" @update:value="loadFeedback" />
          </n-form-item>
          <n-form-item label="关键词">
            <n-input v-model:value="filters.keyword" clearable placeholder="内容、联系方式或用户" @keyup.enter="loadFeedback" />
          </n-form-item>
          <n-form-item><n-button type="primary" :loading="loading" @click="loadFeedback">查询</n-button></n-form-item>
        </n-form>
      </n-card>
      <n-card title="用户反馈" segmented>
        <n-data-table :columns="columns" :data="feedback" :loading="loading" :pagination="pagination" />
        <n-empty v-if="!loading && !feedback.length" description="暂无反馈" />
      </n-card>
    </n-space>
    <n-modal v-model:show="showDetail" preset="card" title="反馈详情" style="width: min(680px, 94vw)">
      <n-descriptions v-if="selected" bordered :column="1">
        <n-descriptions-item label="用户">{{ selected.username || selected.email || selected.user_id || '-' }}</n-descriptions-item>
        <n-descriptions-item label="联系方式">{{ selected.contact || '未提供' }}</n-descriptions-item>
        <n-descriptions-item label="提交时间">{{ selected.created_at || selected.createdAt || '-' }}</n-descriptions-item>
        <n-descriptions-item label="反馈内容"><div class="content">{{ selected.content || selected.message || '-' }}</div></n-descriptions-item>
        <n-descriptions-item label="已有回复">{{ selected.reply_content || '暂无' }}</n-descriptions-item>
        <n-descriptions-item label="处理备注"><n-input v-model:value="editForm.admin_note" type="textarea" :rows="4" placeholder="内部备注，可选" /></n-descriptions-item>
        <n-descriptions-item label="处理状态"><n-select v-model:value="editForm.status" :options="statusOptions" /></n-descriptions-item>
      </n-descriptions>
      <template #footer><n-space justify="end"><n-button @click="showDetail = false">取消</n-button><n-button v-if="selected?.status !== 'resolved'" type="success" :loading="saving" @click="quickAction('adopt')">采纳并回复</n-button><n-button v-if="selected?.status !== 'closed'" type="warning" :loading="saving" @click="quickAction('ignore')">忽略</n-button><n-button v-if="selected?.status === 'closed' || selected?.status === 'resolved'" :loading="saving" @click="quickAction('reopen')">重新处理</n-button><n-button type="primary" :loading="saving" @click="saveFeedback">保存</n-button></n-space></template>
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
const filters = reactive({ status: null, keyword: '' })
const editForm = reactive({ id: null, status: 'open', admin_note: '' })
const pagination = reactive({ page: 1, pageSize: 20, itemCount: 0, showSizePicker: true, pageSizes: [10, 20, 50], onChange: page => { pagination.page = page; loadFeedback() }, onUpdatePageSize: size => { pagination.pageSize = size; pagination.page = 1; loadFeedback() } })
const statusOptions = [
  { label: '待处理', value: 'open' },
  { label: '已关闭', value: 'closed' },
]
const columns = [
  { title: '反馈内容', key: 'content', ellipsis: { tooltip: true }, render: row => row.content || '-' },
  { title: '用户', key: 'username', render: row => row.username || row.email || row.user_id || '-' },
  { title: '状态', key: 'status', width: 100, render: row => h(NTag, { type: tagType(row.status) }, { default: () => statusLabel(row.status) }) },
  { title: '提交时间', key: 'created_at', width: 180 },
  { title: '操作', key: 'actions', width: 100, render: row => h(NButton, { size: 'small', onClick: () => openDetail(row) }, { default: () => '处理' }) },
]

function statusLabel(value) { return statusOptions.find(item => item.value === value)?.label || value || '待处理' }
function tagType(value) { return value === 'resolved' ? 'success' : value === 'closed' ? 'default' : 'info' }
function openDetail(row) {
  selected.value = row
  Object.assign(editForm, { id: row.id, status: row.status || 'open', admin_note: row.admin_note || '' })
  showDetail.value = true
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
  if (!editForm.id) return
  saving.value = true
  try {
    await phpAdminFeedbackApi.update({ ...editForm })
    message.success('反馈已更新')
    showDetail.value = false
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '反馈更新失败') }
  finally { saving.value = false }
}
async function quickAction(action) {
  if (!selected.value?.id) return
  saving.value = true
  try {
    await phpAdminFeedbackApi.update({ id: selected.value.id, feedback_action: action, admin_note: editForm.admin_note })
    message.success(action === 'adopt' ? '已采纳并回复' : action === 'ignore' ? '已忽略' : '已重新打开')
    showDetail.value = false
    await loadFeedback()
  }
  catch (error) { message.error(error.message || '反馈操作失败') }
  finally { saving.value = false }
}

onMounted(loadFeedback)
</script>

<style scoped>
.content { white-space: pre-wrap; line-height: 1.7; }
</style>
