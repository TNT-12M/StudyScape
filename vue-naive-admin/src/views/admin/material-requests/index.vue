<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="筛选" segmented>
        <n-form inline label-placement="left">
          <n-form-item label="状态">
            <n-select v-model:value="filters.status" clearable :options="statusOptions" style="width: 140px" @update:value="loadList" />
          </n-form-item>
          <n-form-item label="学段">
            <n-select v-model:value="filters.education_level" clearable :options="levelOptions" style="width: 120px" @update:value="loadList" />
          </n-form-item>
          <n-form-item label="关键词">
            <n-input v-model:value="filters.keyword" clearable placeholder="标题/内容/科目/用户" style="width: 220px" @keyup.enter="loadList" />
          </n-form-item>
          <n-form-item><n-button type="primary" :loading="loading" @click="loadList">查询</n-button></n-form-item>
        </n-form>
      </n-card>

      <n-card title="资料需求" segmented>
        <n-data-table :columns="columns" :data="items" :loading="loading" :pagination="pagination" @update:page="onPageChange" @update:page-size="onPageSizeChange" />
        <n-empty v-if="!loading && !items.length" description="暂无需求" />
      </n-card>
    </n-space>

    <n-modal v-model:show="showDetail" preset="card" title="需求详情" class="req-modal">
      <n-descriptions v-if="selected" bordered :column="1" label-placement="top">
        <n-descriptions-item label="用户">{{ selected.username || selected.user_id || '-' }}</n-descriptions-item>
        <n-descriptions-item label="标题">{{ selected.title || '未填写' }}</n-descriptions-item>
        <n-descriptions-item label="学段 / 科目">
          {{ levelLabel(selected.education_level) || '未指定' }} / {{ selected.subject || '未指定' }}
        </n-descriptions-item>
        <n-descriptions-item label="联系方式">{{ selected.contact || '未提供' }}</n-descriptions-item>
        <n-descriptions-item label="提交时间">{{ selected.created_at || '-' }}</n-descriptions-item>
        <n-descriptions-item label="需求描述"><div class="content">{{ selected.content || '-' }}</div></n-descriptions-item>
        <n-descriptions-item label="当前状态">
          <n-tag :type="tagType(selected.status)">{{ statusLabel(selected.status) }}</n-tag>
        </n-descriptions-item>
        <n-descriptions-item v-if="selected.admin_note" label="历史备注">{{ selected.admin_note }}</n-descriptions-item>
        <n-descriptions-item label="处理备注">
          <n-input v-model:value="editForm.admin_note" type="textarea" :rows="4" placeholder="处理备注，用户可见" />
        </n-descriptions-item>
      </n-descriptions>
      <template #footer>
        <div class="modal-actions">
          <n-button @click="showDetail = false">关闭</n-button>
          <n-button v-if="selected?.status === 'fulfilled' || selected?.status === 'rejected'" :loading="saving" @click="quickAction('reopen')">重新处理</n-button>
          <n-button v-if="selected?.status !== 'processing' && selected?.status !== 'fulfilled'" type="warning" :loading="saving" @click="quickAction('processing')">标记处理中</n-button>
          <n-button v-if="selected?.status !== 'rejected'" type="error" :loading="saving" @click="quickAction('reject')">拒绝</n-button>
          <n-button type="success" :loading="saving" @click="quickAction('fulfill')">标记已满足</n-button>
        </div>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { h, reactive, ref, onMounted } from 'vue'
import { NButton, NTag } from 'naive-ui'
import { phpMaterialRequestApi } from '@/api/php-modules'

const message = window.$message
const items = ref([])
const loading = ref(false)
const saving = ref(false)
const showDetail = ref(false)
const selected = ref(null)

const filters = reactive({ status: null, education_level: null, keyword: '' })
const editForm = reactive({ id: null, admin_note: '' })
const pagination = reactive({ page: 1, pageSize: 20, itemCount: 0, showSizePicker: true, pageSizes: [10, 20, 50] })

const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const statusOptions = [
  { label: '待处理', value: 'pending' },
  { label: '处理中', value: 'processing' },
  { label: '已满足', value: 'fulfilled' },
  { label: '已拒绝', value: 'rejected' },
]

const columns = [
  { title: '标题', key: 'title', ellipsis: { tooltip: true }, render: row => row.title || '-' },
  { title: '学段', key: 'education_level', width: 80, render: row => levelLabel(row.education_level) },
  { title: '科目', key: 'subject', width: 100, render: row => row.subject || '-' },
  { title: '用户', key: 'username', width: 120, render: row => row.username || row.user_id || '-' },
  { title: '状态', key: 'status', width: 100, render: row => h(NTag, { type: tagType(row.status), size: 'small' }, { default: () => statusLabel(row.status) }) },
  { title: '提交时间', key: 'created_at', width: 170 },
  {
    title: '操作', key: 'actions', width: 100,
    render: row => h(NButton, { size: 'small', onClick: () => openDetail(row) }, { default: () => '查看' }),
  },
]

function levelLabel(v) { return v === 'senior' ? '高中' : v === 'junior' ? '初中' : '-' }
function statusLabel(v) { return statusOptions.find(o => o.value === v)?.label || v || '待处理' }
function tagType(v) {
  if (v === 'fulfilled') return 'success'
  if (v === 'processing') return 'warning'
  if (v === 'rejected') return 'error'
  return 'info'
}

function openDetail(row) {
  selected.value = row
  Object.assign(editForm, { id: row.id, admin_note: row.admin_note || '' })
  showDetail.value = true
}

async function loadList() {
  loading.value = true
  try {
    const result = await phpMaterialRequestApi.list({
      ...filters,
      page: pagination.page,
      page_size: pagination.pageSize,
    })
    const data = result.data || {}
    items.value = data.items || []
    pagination.itemCount = Number(data.pagination?.total ?? 0) || 0
  } catch (error) { message.error(error.message || '加载失败') }
  finally { loading.value = false }
}

async function quickAction(action) {
  if (!selected.value?.id) return
  saving.value = true
  try {
    await phpMaterialRequestApi.update({
      id: selected.value.id,
      request_action: action,
      admin_note: editForm.admin_note,
    })
    const map = { fulfill: '已标记为已满足', reject: '已拒绝', processing: '已标记为处理中', reopen: '已重新打开' }
    message.success(map[action] || '操作成功')
    showDetail.value = false
    await loadList()
  } catch (error) { message.error(error.message || '操作失败') }
  finally { saving.value = false }
}

function onPageChange(page) { pagination.page = page; loadList() }
function onPageSizeChange(size) { pagination.pageSize = size; pagination.page = 1; loadList() }

onMounted(loadList)
</script>

<style scoped>
.content { white-space: pre-wrap; line-height: 1.7; }
.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  flex-wrap: wrap;
}
:deep(.req-modal .n-modal-card) {
  width: min(680px, 94vw) !important;
  max-width: 94vw;
}
:deep(.req-modal .n-modal-card-body) {
  max-height: 70vh;
  overflow-y: auto;
}
</style>
