<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="资料筛选" segmented>
        <template #header-extra>
          <n-space>
            <n-button v-if="session.can('material_manage')" @click="showCatModal = true">分类管理</n-button>
            <n-button v-if="session.can('material_manage')" type="primary" @click="showUpload = !showUpload">{{ showUpload ? '收起上传' : '上传资料' }}</n-button>
          </n-space>
        </template>
        <n-form inline>
          <n-form-item label="学段">
            <n-select v-model:value="filters.education_level" clearable :options="levelOptions" style="width:130px" @update:value="onLevelChange" />
          </n-form-item>
          <n-form-item label="分类">
            <n-select v-model:value="filters.category_id" clearable :options="categoryOptions" placeholder="全部分类" style="width:140px" />
          </n-form-item>
          <n-form-item label="科目">
            <n-select v-model:value="filters.subject" clearable :options="subjectOptions" placeholder="全部科目" style="width:140px" />
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
              <n-space wrap>
                <n-form-item label="统一学段" label-placement="left" style="margin-bottom: 0">
                  <n-select v-model:value="batchForm.education_level" :options="levelOptions" style="width: 120px" @update:value="applyBatchEducation" />
                </n-form-item>
                <n-form-item label="统一分类" label-placement="left" style="margin-bottom: 0">
                  <n-select v-model:value="batchForm.category_id" :options="batchCategoryOptions" clearable placeholder="可选" style="width: 130px" @update:value="applyBatchCategory" />
                </n-form-item>
                <n-form-item label="统一科目" label-placement="left" style="margin-bottom: 0">
                  <n-input v-model:value="batchForm.subject" placeholder="可选" style="width: 130px" @update:value="applyBatchSubject" />
                </n-form-item>
              </n-space>
            </div>

            <n-alert v-if="!fileItems.length" type="info" :show-icon="false">尚未选择文件（支持 PDF/Word/Excel/PPT/TXT/图片，也支持 ZIP/RAR/7z 压缩包自动解压）</n-alert>

            <n-alert v-if="hasArchiveItems" type="warning" :show-icon="true" style="margin-bottom: 8px">
              这些文件来自压缩包解压，请确认学段/分类/科目后点击"批量入库"（后端统一 move + INSERT）
            </n-alert>

            <div v-else class="file-list">
              <div v-for="(item, index) in fileItems" :key="item.uid" class="file-item">
                <div class="file-item-main">
                  <div class="file-icon">📄</div>
                  <div class="file-info">
                    <div style="display:flex;align-items:center;gap:6px">
                      <n-input v-model:value="item.filename" size="small" placeholder="文件名" class="file-name-input" />
                      <n-tag v-if="item._isArchiveItem" size="small" round bordered>压缩包解压</n-tag>
                    </div>
                    <div class="file-meta">{{ formatSize(item.size ?? item.file?.size) }} · {{ item.file?.type || guessType(item.filename) }}</div>
                  </div>
                </div>
                <div class="file-item-fields">
                  <n-select v-model:value="item.education_level" :options="levelOptions" size="small" placeholder="学段" style="width: 100px" @update:value="() => refreshItemCategories(item)" />
                  <n-select v-model:value="item.category_id" :options="getItemCategoryOptions(item)" clearable size="small" placeholder="分类" style="width: 110px" />
                  <n-input v-model:value="item.subject" size="small" placeholder="科目" style="width: 110px" />
                  <n-input v-model:value="item.description" size="small" placeholder="说明（可选）" style="flex: 1; min-width: 120px" />
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
                {{ hasArchiveItems ? '批量入库' : '批量上传' }}（{{ fileItems.length }} 个）
              </n-button>
            </n-space>
          </n-space>
        </n-card>
      </n-card>

      <n-card title="资料列表" segmented>
        <n-data-table
          :columns="columns"
          :data="materials"
          :loading="loading"
          :scroll-x="1100"
          :pagination="pagination"
          @update:page="onPageChange"
          @update:page-size="onPageSizeChange"
        />
      </n-card>
    </n-space>

    <!-- 编辑资料 -->
    <n-modal v-model:show="showEditor" preset="card" title="编辑资料信息" style="width:560px">
      <n-form :model="editForm" label-placement="left" label-width="90">
        <n-form-item label="文件名"><n-input v-model:value="editForm.filename" /></n-form-item>
        <n-form-item label="学段"><n-select v-model:value="editForm.education_level" :options="levelOptions" @update:value="loadCategories" /></n-form-item>
        <n-form-item label="分类"><n-select v-model:value="editForm.category_id" :options="editCategoryOptions" clearable placeholder="请选择分类" /></n-form-item>
        <n-form-item label="科目"><n-input v-model:value="editForm.subject" /></n-form-item>
        <n-form-item label="说明"><n-input v-model:value="editForm.description" type="textarea" :rows="3" /></n-form-item>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showEditor = false">取消</n-button>
          <n-button type="primary" @click="saveEdit">保存</n-button>
        </n-space>
      </template>
    </n-modal>

    <!-- 分类管理 -->
    <n-modal v-model:show="showCatModal" preset="card" title="资料分类管理" style="width:600px">
      <n-space vertical style="width:100%">
        <n-space>
          <n-select v-model:value="catFilterLevel" :options="levelOptions" style="width:120px" @update:value="loadCategories" />
          <n-input v-model:value="newCatName" placeholder="新分类名称" style="width:160px" @keyup.enter="addCategory" />
          <n-input-number v-model:value="newCatSort" :min="0" placeholder="排序" style="width:100px" />
          <n-button type="primary" :loading="catAdding" @click="addCategory">添加分类</n-button>
        </n-space>
        <n-data-table
          :columns="catColumns"
          :data="filteredCatList"
          :loading="catLoading"
          :bordered="false"
          size="small"
        />
      </n-space>
    </n-modal>

    <!-- 编辑分类 -->
    <n-modal v-model:show="showCatEditModal" preset="card" title="编辑分类" style="width:420px">
      <n-form :model="catEditForm" label-placement="left" label-width="80">
        <n-form-item label="分类名称"><n-input v-model:value="catEditForm.name" /></n-form-item>
        <n-form-item label="排序"><n-input-number v-model:value="catEditForm.sort_order" :min="0" style="width:100%" /></n-form-item>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showCatEditModal = false">取消</n-button>
          <n-button type="primary" :loading="catSaving" @click="saveCategoryEdit">保存</n-button>
        </n-space>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { NButton, NSpace, NTag, NInputNumber } from 'naive-ui'
import { phpMaterialsApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'
import { formatDate } from '@/views/user/components/question-utils'

const session = useSessionStore()
const message = window.$message
const dialog = window.$dialog

const materials = ref([])
const subjects = ref([])
const categories = ref([])
const pagination = reactive({ page: 1, pageSize: 10, itemCount: 0, showSizePicker: true, pageSizes: [10, 20, 50] })
const loading = ref(false)
const uploading = ref(false)
const showUpload = ref(false)
const showEditor = ref(false)
const showCatModal = ref(false)
const showCatEditModal = ref(false)
const catLoading = ref(false)
const catAdding = ref(false)
const catSaving = ref(false)
const catEditForm = reactive({ id: null, name: '', sort_order: 0 })

const filters = reactive({ education_level: '', category_id: null, subject: '', keyword: '' })
const batchForm = reactive({ education_level: 'junior', category_id: null, subject: '' })
const editForm = reactive({ id: null, filename: '', education_level: 'junior', category_id: null, subject: '', description: '' })
const catFilterLevel = ref('junior')
const newCatName = ref('')
const newCatSort = ref(0)

const levelOptions = [
  { label: '初中', value: 'junior' },
  { label: '高中', value: 'senior' },
]

// 待上传文件列表
const fileItems = ref([])
let uidCounter = 0

const hasArchiveItems = computed(() => fileItems.value.some(it => it._isArchiveItem))
const canUpload = computed(() => fileItems.value.length > 0 && !uploading.value && fileItems.value.every(it => it.education_level))

const subjectOptions = computed(() => subjects.value.map(item => ({ label: item, value: item })))
const categoryOptions = computed(() => {
  const level = filters.education_level
  const list = categories.value.filter(c => !level || c.education_level === level)
  return [
    { label: '全部分类', value: null },
    ...list.map(c => ({ label: c.name, value: c.id })),
  ]
})
// 分类管理弹窗按学段过滤后的列表
const filteredCatList = computed(() => {
  if (!catFilterLevel.value) return categories.value
  return categories.value.filter(c => c.education_level === catFilterLevel.value)
})
const editCategoryOptions = computed(() => {
  const level = editForm.education_level
  return categories.value
    .filter(c => !level || c.education_level === level)
    .map(c => ({ label: c.name, value: c.id }))
})

const batchCategoryOptions = computed(() => {
  const level = batchForm.education_level
  return categories.value
    .filter(c => !level || c.education_level === level)
    .map(c => ({ label: c.name, value: c.id }))
})

function getItemCategoryOptions(item) {
  const level = item.education_level
  return categories.value
    .filter(c => c.education_level === level)
    .map(c => ({ label: c.name, value: c.id }))
}

function refreshItemCategories(item) {
  // 学段变化后，如果当前分类不属于新学段，清空
  if (item.category_id) {
    const exists = categories.value.find(c => c.id === item.category_id && c.education_level === item.education_level)
    if (!exists) item.category_id = null
  }
}

const columns = [
  { title: '文件名', key: 'filename', ellipsis: { tooltip: true } },
  { title: '学段', key: 'education_level', render: row => row.education_level === 'senior' ? '高中' : '初中', width: 80 },
  { title: '分类', key: 'category_name', width: 100, render: row => row.category_name ? h('n-tag', { size: 'small' }, { default: () => row.category_name }) : '-' },
  { title: '科目', key: 'subject', width: 100 },
  { title: '大小', key: 'file_size', render: row => formatSize(row.file_size), width: 100 },
  { title: '下载', key: 'downloads', width: 70 },
  { title: '上传时间', key: 'uploaded_at', width: 170, render: row => formatDate(row.uploaded_at) },
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

// 分类管理表格列
const catColumns = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '分类名称', key: 'name' },
  { title: '排序', key: 'sort_order', width: 80 },
  { title: '资料数', key: 'material_count', width: 80 },
  {
    title: '操作', key: 'actions', width: 180,
    render: row => h(NSpace, null, {
      default: () => [
        h(NButton, { size: 'small', onClick: () => editCategory(row) }, { default: () => '编辑' }),
        h(NButton, { size: 'small', type: 'error', onClick: () => deleteCategory(row) }, { default: () => '删除' }),
      ],
    }),
  },
]

function onLevelChange() {
  loadCategories()
  filters.category_id = null
  pagination.page = 1
  loadMaterials()
}

function onPageChange(page) {
  pagination.page = page
  loadMaterials()
}

function onPageSizeChange(pageSize) {
  pagination.pageSize = pageSize
  pagination.page = 1
  loadMaterials()
}

const ALLOWED_EXTS = ['pdf','doc','docx','txt','md','xls','xlsx','ppt','pptx','zip','rar','7z','jpg','jpeg','png','gif','webp','bmp','mp4','mp3']
const ARCHIVE_EXTS = ['zip','rar','7z']

async function handleBeforeUpload({ file }) {
  const f = file.file
  if (!f) return false
  // 跳过重复（含已展开的 archive item）
  if (fileItems.value.some(it => it.filename === f.name && !it._isArchiveItem)) return false
  // 大小检查（压缩包 200MB, 普通 50MB）
  const ext = f.name.split('.').pop().toLowerCase()
  const isArchive = ARCHIVE_EXTS.includes(ext)
  const maxSize = isArchive ? 200 * 1024 * 1024 : 50 * 1024 * 1024
  if (f.size > maxSize) {
    message.warning(`文件 ${f.name} 超过 ${Math.round(maxSize/1024/1024)}MB，已跳过`)
    return false
  }
  if (!ALLOWED_EXTS.includes(ext)) {
    message.warning(`不支持的格式: .${ext}`)
    return false
  }

  // ===== 压缩包走解压流程 =====
  if (isArchive) {
    message.info(`正在解析压缩包 ${f.name} ...`)
    try {
      const res = await phpMaterialsApi.extract({
        file: f,
        education_level: batchForm.education_level,
        subject: batchForm.subject || '',
      })
      const data = res.data || res
      if (!data || !Array.isArray(data.files) || !data.files.length) {
        message.error(res.message || '压缩包里没有可导入的文件')
        return false
      }
      // 展开成多个 item
      for (const finfo of data.files) {
        fileItems.value.push({
          uid: ++uidCounter,
          _isArchiveItem: true,              // 标记: 不是直接上传的 file
          _archiveToken: data.token,         // 后端临时目录 token
          filename: finfo.filename,
          file: null,                        // 没有原生 File 对象
          education_level: data.education_level_default || batchForm.education_level,
          category_id: batchForm.category_id || null,
          subject: data.subject_default || batchForm.subject || '',
          description: '',
          size: finfo.size,
          status: '',
          errorMsg: '',
        })
      }
      message.success(`✓ ${f.name} 解压成功，${data.files.length} 个文件待入库`)
    } catch (e) {
      message.error(`解压失败: ${e.message || e}`)
    }
    return false
  }

  // ===== 普通文件直接入队 =====
  fileItems.value.push({
    uid: ++uidCounter,
    file: f,
    filename: f.name,
    education_level: batchForm.education_level,
    category_id: batchForm.category_id || null,
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
  fileItems.value.forEach(item => {
    item.education_level = value
    refreshItemCategories(item)
  })
}

function applyBatchCategory(value) {
  fileItems.value.forEach(item => { item.category_id = value })
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

  // 分开两类: 压缩包展开 vs 普通文件
  const archiveItems = fileItems.value.filter(it => it._isArchiveItem && it.status !== 'success')
  const normalItems = fileItems.value.filter(it => !it._isArchiveItem && it.status !== 'success')

  // ===== 1. 压缩包展开的 → 调 batchConfirm (一次性批量入库) =====
  if (archiveItems.length) {
    // 按 token 分组（可能多个压缩包）
    const byToken = {}
    for (const ai of archiveItems) {
      if (!byToken[ai._archiveToken]) byToken[ai._archiveToken] = []
      byToken[ai._archiveToken].push(ai)
    }
    for (const [token, items] of Object.entries(byToken)) {
      for (const ai of items) ai.status = 'uploading'
      try {
        const res = await phpMaterialsApi.batchConfirm({
          token,
          items: items.map(it => ({
            filename: it.filename,
            education_level: it.education_level,
            category_id: it.category_id || 0,
            subject: it.subject,
            description: it.description,
          })),
        })
        const data = res.data || res
        const n = data?.success_count ?? items.length
        successCount += n
        // 标记为 success
        for (const ai of items) ai.status = 'success'
      } catch (e) {
        failCount += items.length
        for (const ai of items) { ai.status = 'error'; ai.errorMsg = e.message || '批量入库失败' }
      }
    }
  }

  // ===== 2. 普通文件 → 逐个 upload =====
  for (const item of normalItems) {
    item.status = 'uploading'
    item.errorMsg = ''
    try {
      await phpMaterialsApi.upload({
        file: item.file,
        filename: item.filename || item.file.name,
        education_level: item.education_level,
        category_id: item.category_id || 0,
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
    message.success(`完成：成功 ${successCount} 个${failCount ? `，失败 ${failCount} 个` : ''}`)
    fileItems.value = fileItems.value.filter(it => it.status !== 'success')
    loadMaterials()
  } else {
    message.error(`全部失败，请检查`)
  }
}

async function loadMaterials() {
  loading.value = true
  try {
    const result = await phpMaterialsApi.list({
      ...filters,
      page: pagination.page,
      page_size: pagination.pageSize,
    })
    const data = result.data || {}
    materials.value = (data.materials || []).map(m => {
      const cat = categories.value.find(c => c.id === m.category_id)
      return { ...m, category_name: cat?.name || '' }
    })
    subjects.value = data.subjects || []
    if (!categories.value.length) categories.value = dedupeCategories(data.categories || [])
    if (typeof data.total === 'number') pagination.itemCount = data.total
  } catch (error) { message.error(error.message) }
  finally { loading.value = false }
}

function dedupeCategories(list) {
  // 按 id 去重，同时 trim name（兜底：历史数据可能有空格或重复）
  const seen = new Set()
  const out = []
  for (const c of list) {
    if (!c || seen.has(c.id)) continue
    seen.add(c.id)
    out.push({ ...c, name: (c.name || '').trim() })
  }
  return out
}

async function loadCategories() {
  try {
    // 始终加载全部分类，确保资料列表中所有学段的分类名都能正确显示
    // 各下拉选项和分类管理表格按各自需求在前端过滤
    const result = await phpMaterialsApi.categoryList({ education_level: '' })
    categories.value = dedupeCategories(result.data?.categories || [])
    // 更新资料列表的分类名
    materials.value = materials.value.map(m => {
      const cat = categories.value.find(c => c.id === m.category_id)
      return { ...m, category_name: cat?.name || '' }
    })
  } catch (error) { message.error(error.message) }
}

function formatSize(value) {
  let size = Number(value) || 0
  const units = ['B', 'KB', 'MB', 'GB']
  let index = 0
  while (size >= 1024 && index < units.length - 1) { size /= 1024; index++ }
  return `${size.toFixed(index ? 1 : 0)} ${units[index]}`
}

function guessType(filename) {
  const map = { pdf: 'PDF 文档', doc: 'Word 文档', docx: 'Word 文档', xls: 'Excel 表格', xlsx: 'Excel 表格', ppt: 'PPT 演示', pptx: 'PPT 演示', txt: '文本', md: 'Markdown', zip: '压缩包', rar: '压缩包', '7z': '压缩包', jpg: '图片', jpeg: '图片', png: '图片', gif: '图片', webp: '图片' }
  const ext = (filename || '').split('.').pop().toLowerCase()
  return map[ext] || `${ext.toUpperCase()} 文件`
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
    category_id: row.category_id || null,
    subject: row.subject || '',
    description: row.description || '',
  })
  showEditor.value = true
}

async function saveEdit() {
  try {
    await phpMaterialsApi.update({
      id: editForm.id,
      filename: editForm.filename,
      education_level: editForm.education_level,
      category_id: editForm.category_id || 0,
      subject: editForm.subject,
      description: editForm.description,
    })
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

// ===== 分类管理 =====
async function addCategory() {
  const name = newCatName.value.trim()
  if (!name) return message.warning('请输入分类名称')
  catAdding.value = true
  try {
    await phpMaterialsApi.categoryAdd({
      name,
      education_level: catFilterLevel.value,
      sort_order: newCatSort.value || 0,
    })
    message.success('分类已添加')
    newCatName.value = ''
    newCatSort.value = 0
    await loadCategories()
  } catch (error) { message.error(error.message) }
  finally { catAdding.value = false }
}

function editCategory(row) {
  Object.assign(catEditForm, {
    id: row.id,
    name: row.name,
    sort_order: row.sort_order,
  })
  showCatEditModal.value = true
}

async function saveCategoryEdit() {
  if (!catEditForm.name.trim()) return message.warning('分类名称不能为空')
  catSaving.value = true
  try {
    await phpMaterialsApi.categoryUpdate({
      id: catEditForm.id,
      name: catEditForm.name.trim(),
      sort_order: catEditForm.sort_order || 0,
    })
    message.success('分类已更新')
    showCatEditModal.value = false
    await loadCategories()
  } catch (error) { message.error(error.message) }
  finally { catSaving.value = false }
}

function deleteCategory(row) {
  dialog.warning({
    title: '删除分类',
    content: `确认删除分类「${row.name}」？`,
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      try {
        await phpMaterialsApi.categoryRemove(row.id)
        message.success('分类已删除')
        await loadCategories()
      } catch (error) { message.error(error.message) }
    },
  })
}

onMounted(async () => {
  await loadCategories()
  await loadMaterials()
})
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
  flex: 2 1 450px;
}

.file-item-status { flex: 0 0 auto; }
</style>
