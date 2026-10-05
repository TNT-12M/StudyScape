<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="资料筛选" segmented>
        <template #header-extra><n-button type="primary" @click="showUpload = !showUpload">{{ showUpload ? '收起上传' : '上传资料' }}</n-button></template>
        <n-form inline>
          <n-form-item label="学段">
            <n-select v-model:value="filters.education_level" clearable :options="levelOptions" style="width:130px" />
          </n-form-item>
          <n-form-item label="分类">
            <n-select v-model:value="filters.subject" clearable :options="subjectOptions" style="width:160px" />
          </n-form-item>
          <n-form-item label="关键词">
            <n-input v-model:value="filters.keyword" clearable style="width:220px" @keyup.enter="loadMaterials" />
          </n-form-item>
          <n-button type="primary" @click="loadMaterials">搜索</n-button>
        </n-form>

        <!-- 批量上传面板 -->
        <n-card v-if="showUpload" size="small" class="upload-panel">
          <n-space vertical size="medium" style="width: 100%">
            <div class="upload-top-row">
              <n-upload
                multiple
                :show-file-list="false"
                :max="20"
                :custom-request="() => {}"
                @before-upload="handleBeforeUpload"
              >
                <n-button type="primary">选择文件（可多选）</n-button>
                <template #tip>支持 PDF / Word / Excel / PPT / TXT / MD，单文件最大 50MB，一次最多 20 个</template>
              </n-upload>
              <n-space>
                <n-form-item label="统一学段" label-placement="left" style="margin-bottom: 0">
                  <n-select v-model:value="batchForm.education_level" :options="levelOptions" style="width: 130px" @update:value="applyBatchEducation" />
                </n-form-item>
                <n-form-item label="统一分类" label-placement="left" style="margin-bottom: 0">
                  <n-input v-model:value="batchForm.subject" placeholder="可选" style="width: 160px" @update:value="applyBatchSubject" />
                </n-form-item>
              </n-space>
            </div>

            <n-alert v-if="!fileItems.length" type="info" :show-icon="false">尚未选择文件</n-alert>

            <div v-else class="file-list">
              <div v-for="(item, index) in fileItems" :key="item.uid" class="file-item">
                <div class="file-item-main">
                  <div class="file-icon">📄</div>
                  <div class="file-info">
                    <n-input v-model:value="item.filename" size="small" placeholder="文件名" class="file-name-input" />
                    <div class="file-meta">{{ formatSize(item.file.size) }} · {{ item.file.type || '未知类型' }}</div>
                  </div>
                </div>
                <div class="file-item-fields">
                  <n-select v-model:value="item.education_level" :options="levelOptions" size="small" placeholder="学段" style="width: 110px" />
                  <n-input v-model:value="item.subject" size="small" placeholder="分类" style="width: 140px" />
                  <n-input v-model:value="item.description" size="small" placeholder="说明（可选）" style="flex: 1" />
                  <n-button size="small" text type="error" @click="removeFile(index)">移除</n-button>
                </div>
                <div v-if="item.status" class="file-item-status">
                  <n-tag :type="item.status === 'success' ? 'success' : item.status === 'uploading' ? 'info' : 'error'" size="small">
                    {{ statusText(item) }}
                  </n-tag>
                </div>
              </div>
            </div>

            <n-space justify="end" v-if="fileItems.length">
              <n-button @click="clearAll">清空</n-button>
              <n-button type="primary" :loading="uploading" :disabled="!canUpload" @click="startBatchUpload">
                批量上传（{{ fileItems.length }} 个）
              </n-button>
            </n-space>
          </n-space>
        </n-card>
      </n-card>

      <n-card title="资料列表" segmented>
        <n-data-table :columns="columns" :data="materials" :loading="loading" :scroll-x="1100" :pagination="pagination" />
      </n-card>
    </n-space>

    <n-modal v-model:show="showEditor" preset="card" title="编辑资料信息" style="width:560px">
      <n-form :model="editForm" label-placement="left" label-width="90">
        <n-form-item label="文件名"><n-input v-model:value="editForm.filename" /></n-form-item>
        <n-form-item label="学段"><n-select v-model:value="editForm.education_level" :options="levelOptions" /></n-form-item>
        <n-form-item label="分类"><n-input v-model:value="editForm.subject" /></n-form-item>
        <n-form-item label="说明"><n-input v-model:value="editForm.description" /></n-form-item>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showEditor = false">取消</n-button>
          <n-button type="primary" @click="saveEdit">保存</n-button>
        </n-space>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { NButton, NSpace, NTag } from 'naive-ui'
import { phpMaterialsApi } from '@/api/php-modules'

const message = window.$message
const dialog = window.$dialog

const materials = ref([])
const subjects = ref([])
const pagination = reactive({ page: 1, pageSize: 10, showSizePicker: true, pageSizes: [10, 20, 50] })
const loading = ref(false)
const uploading = ref(false)
const showUpload = ref(false)
const showEditor = ref(false)

const filters = reactive({ education_level: '', subject: '', keyword: '' })
const batchForm = reactive({ education_level: 'junior', subject: '' })
const editForm = reactive({ id: null, filename: '', education_level: 'junior', subject: '', description: '' })

const levelOptions = [
  { label: '初中', value: 'junior' },
  { label: '高中', value: 'senior' },
]

// 待上传文件列表
const fileItems = ref([])
let uidCounter = 0

const canUpload = computed(() => fileItems.value.length > 0 && !uploading.value && fileItems.value.every(it => it.education_level))

const subjectOptions = computed(() => subjects.value.map(item => ({ label: item, value: item })))

const columns = [
  { title: '文件名', key: 'filename', ellipsis: { tooltip: true } },
  { title: '学段', key: 'education_level', render: row => row.education_level === 'senior' ? '高中' : '初中', width: 80 },
  { title: '分类', key: 'subject', width: 120 },
  { title: '大小', key: 'file_size', render: row => formatSize(row.file_size), width: 100 },
  { title: '下载', key: 'downloads', width: 70 },
  { title: '上传时间', key: 'uploaded_at', width: 170 },
  {
    title: '操作', key: 'actions', width: 260,
    render: row => h(NSpace, null, {
      default: () => [
        h(NButton, { size: 'small', type: 'primary', onClick: () => download(row) }, { default: () => '下载' }),
        h(NButton, { size: 'small', onClick: () => openEdit(row) }, { default: () => '编辑' }),
        h(NButton, { size: 'small', type: 'error', onClick: () => remove(row) }, { default: () => '删除' }),
      ],
    }),
  },
]

function handleBeforeUpload({ file }) {
  const f = file.file
  if (!f) return false
  // 跳过重复
  if (fileItems.value.some(it => it.file.name === f.name && it.file.size === f.size)) return false
  // 大小检查（50MB）
  if (f.size > 50 * 1024 * 1024) {
    message.warning(`文件 ${f.name} 超过 50MB，已跳过`)
    return false
  }
  fileItems.value.push({
    uid: ++uidCounter,
    file: f,
    filename: f.name,
    education_level: batchForm.education_level,
    subject: batchForm.subject || '',
    description: '',
    status: '',
    errorMsg: '',
  })
  return false // 阻止 Naive UI 默认上传
}

function removeFile(index) {
  fileItems.value.splice(index, 1)
}

function clearAll() {
  fileItems.value = []
}

function applyBatchEducation(value) {
  fileItems.value.forEach(item => { item.education_level = value })
}

function applyBatchSubject(value) {
  fileItems.value.forEach(item => { item.subject = value })
}

function statusText(item) {
  if (item.status === 'uploading') return '上传中...'
  if (item.status === 'success') return '成功'
  if (item.status === 'error') return item.errorMsg || '失败'
  return ''
}

async function startBatchUpload() {
  if (!fileItems.value.length) return
  const invalid = fileItems.value.find(it => !it.education_level)
  if (invalid) { message.warning('请为所有文件选择学段'); return }

  uploading.value = true
  let successCount = 0
  let failCount = 0

  for (const item of fileItems.value) {
    if (item.status === 'success') continue
    item.status = 'uploading'
    item.errorMsg = ''
    try {
      await phpMaterialsApi.upload({
        file: item.file,
        filename: item.filename || item.file.name,
        education_level: item.education_level,
        subject: item.subject,
        description: item.description,
      })
      item.status = 'success'
      successCount++
    } catch (error) {
      item.status = 'error'
      item.errorMsg = error.message || '上传失败'
      failCount++
    }
  }

  uploading.value = false
  if (successCount > 0) {
    message.success(`上传完成：成功 ${successCount} 个${failCount ? `，失败 ${failCount} 个` : ''}`)
    // 移除成功的，保留失败的方便重试
    fileItems.value = fileItems.value.filter(it => it.status !== 'success')
    loadMaterials()
  } else {
    message.error(`全部上传失败，请检查文件格式和大小`)
  }
}

async function loadMaterials() {
  loading.value = true
  try {
    const result = await phpMaterialsApi.list(filters)
    const data = result.data || {}
    materials.value = data.materials || []
    subjects.value = data.subjects || []
  } catch (error) { message.error(error.message) }
  finally { loading.value = false }
}

function formatSize(value) {
  let size = Number(value) || 0
  const units = ['B', 'KB', 'MB', 'GB']
  let index = 0
  while (size >= 1024 && index < units.length - 1) { size /= 1024; index++ }
  return `${size.toFixed(index ? 1 : 0)} ${units[index]}`
}

async function download(row) {
  try {
    const result = await phpMaterialsApi.token(row.id)
    const url = phpMaterialsApi.downloadUrl(result.data.download_token)
    window.open(url, '_blank', 'noopener')
  } catch (error) { message.error(error.message) }
}

function openEdit(row) {
  Object.assign(editForm, {
    id: row.id,
    filename: row.filename,
    education_level: row.education_level,
    subject: row.subject || '',
    description: row.description || '',
  })
  showEditor.value = true
}

async function saveEdit() {
  try {
    await phpMaterialsApi.update(editForm)
    message.success('资料信息已更新')
    showEditor.value = false
    await loadMaterials()
  } catch (error) { message.error(error.message) }
}

function remove(row) {
  dialog.warning({
    title: '删除资料',
    content: `确认删除「${row.filename}」？文件也会被删除。`,
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await phpMaterialsApi.remove(row.id)
        message.success('已删除')
        await loadMaterials()
      } catch (error) { message.error(error.message) }
    },
  })
}

onMounted(loadMaterials)
</script>

<style scoped>
.upload-panel { margin-top: 16px; }
.upload-panel :deep(.n-card__content) { padding-bottom: 4px; }

.upload-top-row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid var(--n-border-color);
  border-radius: 8px;
  background: var(--n-color);
  flex-wrap: wrap;
}

.file-item-main {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 280px;
  flex: 1 1 280px;
}

.file-icon { font-size: 22px; }

.file-info { flex: 1; min-width: 0; }

.file-name-input { width: 100%; }

.file-meta {
  font-size: 12px;
  color: var(--n-text-color-3);
  margin-top: 4px;
}

.file-item-fields {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 2 1 400px;
}

.file-item-status { flex: 0 0 auto; }
</style>
