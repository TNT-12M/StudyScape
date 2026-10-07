<template>
  <AppPage show-footer>
    <n-card title="题库浏览" :bordered="false">
      <template #header-extra>
        <n-button secondary :loading="loading" @click="loadQuestions">
          刷新
        </n-button>
      </template>
      <n-form inline label-placement="left" class="filters">
        <n-form-item label="学段">
          <n-select v-model:value="filters.education_level" clearable :options="levelOptions" placeholder="全部学段" style="width: 140px" @update:value="changeLevel" />
        </n-form-item>
        <n-form-item label="科目">
          <n-select v-model:value="filters.subject" clearable :options="subjectOptions" placeholder="全部科目" style="width: 170px" @update:value="search" />
        </n-form-item>
        <n-form-item label="题型">
          <n-select v-model:value="filters.qtype" clearable :options="questionTypeOptions" placeholder="全部题型" style="width: 150px" @update:value="search" />
        </n-form-item>
        <n-form-item label="关键词">
          <n-input v-model:value="filters.keyword" clearable placeholder="搜索题干或解析" style="width: 240px" @keyup.enter="search" />
        </n-form-item>
        <n-form-item>
          <n-button type="primary" @click="search">
            搜索
          </n-button>
        </n-form-item>
      </n-form>

      <n-alert v-if="error" type="error" :title="error" closable class="alert" @close="error = ''" />
      <n-spin :show="loading">
        <n-data-table
          :columns="columns"
          :data="questions"
          :loading="loading"
          :pagination="pagination"
          :row-key="row => row.id"
          :scroll-x="1250"
          remote
          @update:page="handlePageChange"
          @update:page-size="handlePageSizeChange"
        />
        <n-empty v-if="!loading && !questions.length" description="暂无符合条件的题目" class="empty" />
      </n-spin>
    </n-card>
  </AppPage>
</template>

<script setup>
import { computed, h, onMounted, reactive, ref } from 'vue'
import { useSessionStore } from '@/store/modules/session'
import { phpQuestionsApi } from '@/api/php-modules'
import { sanitizeHtml, renderMathInHtml } from '@/views/user/components/question-utils'

const session = useSessionStore()
const questions = ref([])
const subjects = ref([])
const loading = ref(false)
const error = ref('')
const filters = reactive({ education_level: '', subject: '', qtype: '', keyword: '' })
const pagination = reactive({ page: 1, pageSize: 10, itemCount: 0, pageCount: 0, showSizePicker: true, pageSizes: [10, 20, 50] })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const questionTypeOptions = [
  { label: '单选题', value: 'single' },
  { label: '多选题', value: 'multiple' },
  { label: '判断题', value: 'judge' },
  { label: '填空题', value: 'fill' },
  { label: '多空填空题', value: 'multi_fill' },
  { label: '简答题', value: 'short' },
]
const subjectOptions = computed(() => subjects.value.map(subject => ({ label: subject, value: subject })))
const columns = [
  { title: 'ID', key: 'id', width: 80, fixed: 'left' },
  { title: '科目', key: 'subject', width: 120 },
  { title: '题型', key: 'question_type', width: 120 },
  { title: '分类', key: 'category', width: 120 },
  { title: '学段', key: 'education_level', width: 100 },
  { title: '题干', key: 'content', minWidth: 300, ellipsis: { tooltip: true }, render: row => renderContent(row) },
  { title: '选项', key: 'options', minWidth: 220, ellipsis: { tooltip: true }, render: row => formatOptions(row.options) },
  { title: '难度', key: 'difficulty', width: 80 },
  { title: '分值', key: 'points', width: 80 },
  { title: '创建时间', key: 'created_at', width: 180 },
]

function renderContent(row) {
  const content = row.content || ''
  return h('div', { class: 'question-content', innerHTML: renderMathInHtml(sanitizeHtml(content)) })
}

function formatOptions(options) {
  if (options === null || options === undefined || options === '')
    return '-'
  if (typeof options === 'string')
    return options
  try {
    return JSON.stringify(options)
  }
  catch {
    return String(options)
  }
}

async function loadSubjects() {
  try {
    const result = await phpQuestionsApi.subjects({ education_level: filters.education_level })
    subjects.value = result.data?.subjects || []
  }
  catch (err) { error.value = err.message || '科目加载失败' }
}

async function loadQuestions() {
  loading.value = true
  error.value = ''
  try {
    const result = await phpQuestionsApi.list({ ...filters, page: pagination.page, page_size: pagination.pageSize })
    const data = result.data || {}
    questions.value = data.items || []
    pagination.itemCount = Number(data.total) || 0
    pagination.pageCount = Number(data.total_pages) || 0
  }
  catch (err) { error.value = err.message || '题库加载失败' }
  finally { loading.value = false }
}

async function changeLevel() {
  filters.subject = ''
  pagination.page = 1
  await loadSubjects()
  await loadQuestions()
}

function search() {
  pagination.page = 1
  loadQuestions()
}

function handlePageChange(page) {
  pagination.page = page
  loadQuestions()
}

function handlePageSizeChange(pageSize) {
  pagination.pageSize = pageSize
  pagination.page = 1
  loadQuestions()
}

onMounted(async () => {
  if (session.defaultEducationLevel) {
    filters.education_level = session.defaultEducationLevel
  }
  await loadSubjects()
  await loadQuestions()
})
</script>

<style scoped>
.filters {
  margin-bottom: 18px;
}
.alert {
  margin-bottom: 18px;
}
.question-content {
  max-height: 88px;
  overflow: hidden;
  line-height: 1.6;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
.question-content :deep(img) {
  max-width: 100%;
  max-height: 80px;
  object-fit: contain;
}
.empty {
  padding: 70px 0;
}
</style>
