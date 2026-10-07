<template>
  <AppPage show-footer>
    <n-card title="资料中心" :bordered="false" class="page-card">
      <template #header-extra><n-button secondary :loading="loading" @click="loadMaterials">刷新</n-button></template>
      <n-space wrap align="center" class="filters">
        <n-select v-model:value="filters.education_level" clearable :options="levelOptions" placeholder="全部学段" style="width: 130px" @update:value="onLevelChange" />
        <n-select v-model:value="filters.category_id" clearable :options="categoryOptions" placeholder="全部分类" style="width: 140px" @update:value="loadMaterials" />
        <n-select v-model:value="filters.subject" clearable :options="subjectOptions" placeholder="全部科目" style="width: 140px" @update:value="loadMaterials" />
        <n-input v-model:value="filters.keyword" clearable placeholder="搜索文件名或简介" style="width: 240px" @keyup.enter="loadMaterials">
          <template #suffix><n-button text @click="loadMaterials">搜索</n-button></template>
        </n-input>
      </n-space>
      <n-alert v-if="error" type="error" :title="error" closable class="mt-16" @close="error = ''" />
      <n-spin :show="loading">
        <div class="summary muted">共 {{ materials.length }} 份资料</div>
        <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
          <n-gi v-for="material in materials" :key="material.id" span="3 s:1 m:1 l:1">
            <n-card size="small" class="material-card">
              <template #header>
                <div class="material-title"><n-tag type="info" size="small">{{ extension(material.filename) }}</n-tag><span>{{ material.filename }}</span></div>
              </template>
              <div class="material-description">{{ material.description || '暂无简介' }}</div>
              <n-space size="small" wrap class="material-meta">
                <n-tag v-if="material.education_level" size="small">{{ levelLabel(material.education_level) }}</n-tag>
                <n-tag v-if="material.category_name" size="small" type="info">{{ material.category_name }}</n-tag>
                <n-tag v-if="material.subject" size="small" type="success">{{ material.subject }}</n-tag>
                <span>{{ formatSize(material.file_size) }}</span>
              </n-space>
              <div class="material-footer">
                <span class="muted">更新于 {{ formatDate(material.updated_at || material.uploaded_at) }}</span>
                <n-button type="primary" size="small" :loading="downloading === material.id" @click="download(material)">下载</n-button>
              </div>
            </n-card>
          </n-gi>
        </n-grid>
        <n-empty v-if="!loading && !materials.length" description="暂无符合条件的资料" class="empty" />
      </n-spin>
    </n-card>
  </AppPage>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useSessionStore } from '@/store/modules/session'
import { phpMaterialsApi } from '@/api/php-modules'
import { formatDate } from '@/views/user/components/question-utils'

const session = useSessionStore()
const filters = reactive({ education_level: null, category_id: null, subject: null, keyword: '' })
const materials = ref([])
const subjects = ref([])
const categories = ref([])
const loading = ref(false)
const downloading = ref(null)
const error = ref('')
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const subjectOptions = computed(() => subjects.value.map(subject => ({ label: subject, value: subject })))
const categoryOptions = computed(() => categories.value.map(c => ({ label: c.name, value: c.id })))

function onLevelChange() {
  filters.category_id = null
  loadMaterials()
}

onMounted(() => {
  // 有默认学段时，自动过滤
  if (session.defaultEducationLevel) {
    filters.education_level = session.defaultEducationLevel
  }
  loadMaterials()
})

async function loadMaterials() {
  loading.value = true
  error.value = ''
  try {
    const result = await phpMaterialsApi.list(filters)
    const data = result.data || {}
    categories.value = data.categories || []
    subjects.value = data.subjects || []
    materials.value = (data.materials || []).map(m => {
      const cat = categories.value.find(c => c.id === m.category_id)
      return { ...m, category_name: cat?.name || '' }
    })
  }
  catch (err) { error.value = err.message || '资料加载失败' }
  finally { loading.value = false }
}

async function download(material) {
  downloading.value = material.id
  try {
    const result = await phpMaterialsApi.token(material.id)
    const token = result.data?.download_token
    if (!token) throw new Error('下载授权失败')
    const apiUrl = import.meta.env.VITE_PHP_API_URL || '/api.php'
    const link = document.createElement('a')
    link.href = `${apiUrl}?action=material_download&token=${encodeURIComponent(token)}`
    link.target = '_blank'
    link.rel = 'noopener'
    link.click()
  }
  catch (err) { window.$message?.error(err.message || '下载失败') }
  finally { downloading.value = null }
}

function levelLabel(level) { return level === 'senior' ? '高中' : '初中' }
function extension(filename) { return String(filename || '文件').split('.').pop()?.toUpperCase().slice(0, 5) || '文件' }
function formatSize(size) {
  let value = Number(size) || 0
  const units = ['B', 'KB', 'MB', 'GB']
  let index = 0
  while (value >= 1024 && index < units.length - 1) { value /= 1024; index += 1 }
  return `${value.toFixed(index ? 1 : 0)} ${units[index]}`
}

onMounted(loadMaterials)
</script>

<style scoped>
.page-card { min-height: 520px; }
.filters { padding-bottom: 4px; }
.summary { margin: 18px 0 14px; }
.mt-16 { margin-top: 16px; }
.material-card { height: 100%; }
.material-title { display: flex; align-items: center; gap: 8px; min-width: 0; }
.material-title > span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.material-description { min-height: 42px; color: var(--n-text-color-2); line-height: 1.6; overflow-wrap: anywhere; }
.material-meta { margin-top: 16px; }
.material-footer { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 18px; }
.muted { color: var(--n-text-color-3); font-size: 12px; }
.empty { padding: 70px 0; }
</style>
