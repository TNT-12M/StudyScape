<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="公告管理" segmented>
        <template #header-extra>
          <n-space>
            <n-select v-model:value="filters.status" clearable placeholder="状态筛选" style="width: 140px" :options="statusOptions" @update:value="loadList" />
            <n-button type="primary" @click="openCreate">新建公告</n-button>
          </n-space>
        </template>
        <n-data-table :columns="columns" :data="items" :loading="loading" :pagination="pagination" />
        <n-empty v-if="!loading && !items.length" description="暂无公告" />
      </n-card>
    </n-space>

    <!-- 新建/编辑弹窗 -->
    <n-modal v-model:show="showEditor" preset="card" :title="editForm.id ? '编辑公告' : '新建公告'" style="width: min(720px, 94vw)">
      <n-form :model="editForm" label-placement="top">
        <n-form-item label="标题" required>
          <n-input v-model:value="editForm.title" placeholder="请输入公告标题" maxlength="100" show-count />
        </n-form-item>
        <n-form-item label="内容" required>
          <n-input v-model:value="editForm.content" type="textarea" :rows="10" placeholder="请输入公告内容，支持换行" maxlength="10000" show-count />
        </n-form-item>
        <n-alert v-if="editForm.id" type="warning" :show-icon="false">已发布的公告不能修改内容，如需调整请删除后重新发布。</n-alert>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showEditor = false">取消</n-button>
          <n-button type="primary" :loading="saving" @click="saveAnnouncement">{{ editForm.id ? '保存' : '创建草稿' }}</n-button>
        </n-space>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { NButton, NTag, NSpace } from 'naive-ui'
import { phpAdminAnnouncementApi } from '@/api/php-modules'

const message = window.$message
const dialog = window.$dialog

const items = ref([])
const loading = ref(false)
const saving = ref(false)
const showEditor = ref(false)
const filters = reactive({ status: null })
const editForm = reactive({ id: null, title: '', content: '' })
const pagination = reactive({
  page: 1,
  pageSize: 20,
  itemCount: 0,
  showSizePicker: true,
  pageSizes: [10, 20, 50],
  onChange: page => { pagination.page = page; loadList() },
  onUpdatePageSize: size => { pagination.pageSize = size; pagination.page = 1; loadList() },
})

const statusOptions = [
  { label: '草稿', value: 'draft' },
  { label: '已发布', value: 'published' },
]

const columns = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '标题', key: 'title', ellipsis: { tooltip: true } },
  {
    title: '状态', key: 'is_published', width: 100,
    render: row => h(NTag, { type: row.is_published ? 'success' : 'default', size: 'small' }, { default: () => row.is_published ? '已发布' : '草稿' }),
  },
  { title: '创建人', key: 'creator_name', width: 120, render: row => row.creator_name || '-' },
  { title: '发布时间', key: 'published_at', width: 180, render: row => row.published_at || '-' },
  { title: '创建时间', key: 'created_at', width: 180 },
  {
    title: '操作', key: 'actions', width: 200,
    render: row => h(NSpace, { size: 'small' }, {
      default: () => [
        !row.is_published && h(NButton, { size: 'small', onClick: () => openEdit(row) }, { default: () => '编辑' }),
        !row.is_published && h(NButton, { size: 'small', type: 'success', onClick: () => handlePublish(row) }, { default: () => '发布' }),
        h(NButton, { size: 'small', type: 'error', onClick: () => handleDelete(row) }, { default: () => '删除' }),
      ].filter(Boolean),
    }),
  },
]

function openCreate() {
  Object.assign(editForm, { id: null, title: '', content: '' })
  showEditor.value = true
}

function openEdit(row) {
  Object.assign(editForm, { id: row.id, title: row.title, content: row.content })
  showEditor.value = true
}

async function loadList() {
  loading.value = true
  try {
    const result = await phpAdminAnnouncementApi.list({ ...filters, page: pagination.page, page_size: pagination.pageSize })
    const data = result.data || {}
    items.value = data.items || []
    pagination.itemCount = Number(data.pagination?.total ?? items.value.length) || 0
  }
  catch (error) { message.error(error.message || '加载失败') }
  finally { loading.value = false }
}

async function saveAnnouncement() {
  if (!editForm.title.trim()) { message.error('请输入标题'); return }
  if (!editForm.content.trim()) { message.error('请输入内容'); return }
  saving.value = true
  try {
    if (editForm.id) {
      await phpAdminAnnouncementApi.update({ ...editForm })
      message.success('已保存')
    } else {
      await phpAdminAnnouncementApi.create({ ...editForm })
      message.success('草稿已创建')
    }
    showEditor.value = false
    loadList()
  }
  catch (error) { message.error(error.message || '保存失败') }
  finally { saving.value = false }
}

function handlePublish(row) {
  dialog.warning({
    title: '确认发布',
    content: `发布后，所有活跃用户都会收到这条"${row.title}"的通知。发布后无法修改，确定继续？`,
    positiveText: '确认发布',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        const result = await phpAdminAnnouncementApi.publish(row.id)
        message.success(result.message || '发布成功')
        loadList()
      } catch (error) { message.error(error.message || '发布失败') }
    },
  })
}

function handleDelete(row) {
  dialog.warning({
    title: '确认删除',
    content: `确定要删除公告"${row.title}"吗？此操作不可撤销。`,
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await phpAdminAnnouncementApi.remove(row.id)
        message.success('已删除')
        loadList()
      } catch (error) { message.error(error.message || '删除失败') }
    },
  })
}

loadList()
</script>
