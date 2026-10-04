<template>
  <AppPage show-footer>
    <div v-if="view === 'list'" class="papers-page">
      <n-card title="组卷考试" :bordered="false">
        <template #header-extra><n-button secondary :loading="loading" @click="loadList">刷新</n-button></template>
        <n-tabs v-model:value="tab" type="line">
          <n-tab-pane name="available" tab="可参加的试卷">
            <n-spin :show="loading">
              <n-grid :cols="3" :x-gap="16" :y-gap="16" responsive="screen" item-responsive>
                <n-gi v-for="paper in papers" :key="paper.id" span="3 s:1 m:1 l:1">
                  <n-card size="small" class="paper-card">
                    <template #header><div class="paper-title">{{ paper.name }} <n-tag v-if="paper.is_published" size="small" type="success">已发布</n-tag></div></template>
                    <p class="paper-description">{{ paper.description || '暂无介绍' }}</p>
                    <n-space size="small"><n-tag size="small">{{ paper.question_count || 0 }} 题</n-tag><n-tag size="small" type="info">{{ paper.duration_minutes }} 分钟</n-tag></n-space>
                    <div class="paper-actions"><n-button type="primary" size="small" @click="showPaper(paper.id)">查看试卷</n-button></div>
                  </n-card>
                </n-gi>
              </n-grid>
              <n-empty v-if="!loading && !papers.length" description="暂无已发布试卷" class="empty" />
            </n-spin>
          </n-tab-pane>
          <n-tab-pane name="history" tab="我的考试历史">
            <AttemptTable :attempts="examAttempts" @resume="resumeAttempt" @result="showResult" />
          </n-tab-pane>
        </n-tabs>
      </n-card>
    </div>

    <n-card v-else-if="view === 'paper'" :title="selectedPaper?.name || '试卷详情'" :bordered="false">
      <template #header-extra><n-button quaternary @click="view = 'list'">返回列表</n-button></template>
      <n-space vertical size="large" class="paper-info">
        <div class="muted">{{ selectedPaper?.description || '暂无介绍' }}</div>
        <n-space><n-tag>{{ previewQuestions.length }} 题</n-tag><n-tag type="info">{{ selectedPaper?.duration_minutes || 0 }} 分钟</n-tag></n-space>
        <n-divider />
        <div class="muted">题型预览</div>
        <n-list bordered>
          <n-list-item v-for="(question, index) in previewQuestions" :key="question.id">
            <div class="preview-row"><span><b>第 {{ index + 1 }} 题</b> · {{ questionTypeLabel(question.question_type) }}</span><span class="muted">{{ question.paper_points || question.points || 1 }} 分</span></div>
            <div class="preview-content" v-html="renderQuestionContent(question)" />
          </n-list-item>
        </n-list>
        <n-alert type="info" :show-icon="false">开始考试后将立即计时，请确认有完整时间完成答卷。</n-alert>
        <n-space justify="end"><n-button type="primary" :loading="starting" @click="startExam">开始考试</n-button></n-space>
      </n-space>
    </n-card>

    <AttemptRunner v-else-if="view === 'doing'" :title="selectedPaper?.name || '组卷考试'" :questions="questions" :duration="attemptDuration" :started-at="startedAt" :submitting="submitting" :storage-key="draftKey" @submit="submitExam" />
    <ResultPanel v-else :title="`${selectedPaper?.name || '组卷考试'} · 结果`" :attempt="resultData.attempt" :questions="resultData.questions" :summary="resultData.summary" :saving="savingGrade" @back="backToList" @save="saveGrades" />
  </AppPage>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { phpExamApi, phpPapersApi } from '@/api/php-modules'
import AttemptRunner from '@/views/user/components/AttemptRunner.vue'
import AttemptTable from '@/views/user/components/AttemptTable.vue'
import ResultPanel from '@/views/user/components/ResultPanel.vue'
import { questionTypeLabel, renderQuestionContent } from '@/views/user/components/question-utils'

const view = ref('list')
const tab = ref('available')
const loading = ref(false)
const starting = ref(false)
const submitting = ref(false)
const savingGrade = ref(false)
const papers = ref([])
const attempts = ref([])
const selectedPaper = ref(null)
const previewQuestions = ref([])
const questions = ref([])
const currentAttemptId = ref(null)
const attemptDuration = ref(0)
const startedAt = ref('')
const resultData = ref({ attempt: null, questions: [], summary: {} })
const draftKey = computed(() => currentAttemptId.value ? `exam-draft-${currentAttemptId.value}` : '')
const examAttempts = computed(() => attempts.value.filter(attempt => attempt.paper_id))

async function loadList() {
  loading.value = true
  try {
    const [paperResult, attemptResult] = await Promise.all([phpPapersApi.list(), phpExamApi.mine()])
    papers.value = paperResult.data?.papers || []
    attempts.value = attemptResult.data?.attempts || []
  }
  catch (error) { window.$message?.error(error.message || '考试列表加载失败') }
  finally { loading.value = false }
}

async function showPaper(id) {
  try {
    const result = await phpPapersApi.get(id)
    selectedPaper.value = result.data?.paper || null
    previewQuestions.value = result.data?.questions || []
    view.value = 'paper'
  }
  catch (error) { window.$message?.error(error.message || '试卷加载失败') }
}

async function startExam() {
  if (!selectedPaper.value?.id) return
  if (!window.confirm('开始考试后计时将启动，确认开始？')) return
  starting.value = true
  try {
    const result = await phpExamApi.start(selectedPaper.value.id)
    enterAttempt(result.data)
  }
  catch (error) { window.$message?.error(error.message || '开始考试失败') }
  finally { starting.value = false }
}

async function resumeAttempt(attempt) {
  try {
    const result = await phpExamApi.get(attempt.id)
    if (result.data?.attempt?.status === 'submitted') return showResult(attempt)
    selectedPaper.value = result.data?.paper || { name: attempt.paper_name }
    enterAttempt(result.data)
  }
  catch (error) { window.$message?.error(error.message || '恢复考试失败') }
}

function enterAttempt(data) {
  currentAttemptId.value = data.attempt_id || data.attempt?.id
  selectedPaper.value = data.paper || selectedPaper.value
  questions.value = data.questions || []
  attemptDuration.value = Number(data.attempt?.duration_minutes || selectedPaper.value?.duration_minutes || 0)
  startedAt.value = data.attempt?.started_at || ''
  view.value = 'doing'
}

async function submitExam(answers, forced = false) {
  if (!currentAttemptId.value) return
  submitting.value = true
  const payload = questions.value.map(question => {
    let value = answers[question.id]
    if ((question.question_type === 'multi' || question.question_type === 'multiple' || question.question_type === 'multi_fill') && !Array.isArray(value)) value = []
    if (!Array.isArray(value) && value == null) value = ''
    return { question_id: question.id, student_answer: value }
  })
  try {
    const result = await phpExamApi.submit({ attempt_id: currentAttemptId.value, answers: payload })
    if (result.data?.overdue || forced) window.$message?.warning('已提交，部分作答可能超时')
    await showResult({ id: currentAttemptId.value })
  }
  catch (error) { window.$message?.error(error.message || '提交答卷失败') }
  finally { submitting.value = false }
}

async function showResult(attempt) {
  const id = attempt.id || currentAttemptId.value
  if (!id) return
  try {
    const result = await phpExamApi.result(id)
    resultData.value = result.data || { attempt: null, questions: [], summary: {} }
    currentAttemptId.value = id
    selectedPaper.value = result.data?.paper || selectedPaper.value
    view.value = 'result'
    await loadList()
  }
  catch (error) { window.$message?.error(error.message || '结果加载失败') }
}

async function saveGrades(grades) {
  if (!currentAttemptId.value) return
  savingGrade.value = true
  try {
    await phpExamApi.selfGrade({ attempt_id: currentAttemptId.value, grades })
    window.$message?.success('自评已保存')
    await showResult({ id: currentAttemptId.value })
  }
  catch (error) { window.$message?.error(error.message || '保存自评失败') }
  finally { savingGrade.value = false }
}

function backToList() { view.value = 'list'; tab.value = 'history'; loadList() }
onMounted(loadList)
</script>

<style scoped>
.papers-page { display: flex; flex-direction: column; gap: 16px; }
.paper-card { height: 100%; }
.paper-title { display: flex; align-items: center; gap: 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.paper-description { min-height: 44px; color: var(--n-text-color-2); line-height: 1.6; overflow-wrap: anywhere; }
.paper-actions { display: flex; justify-content: flex-end; margin-top: 18px; }
.paper-info { max-width: 960px; }
.preview-row { display: flex; justify-content: space-between; gap: 16px; }
.preview-content { margin-top: 8px; color: var(--n-text-color-2); line-height: 1.6; max-height: 100px; overflow: hidden; }
.preview-content :deep(img) { max-width: 100%; max-height: 100px; object-fit: contain; }
.muted { color: var(--n-text-color-3); font-size: 13px; }
.empty { padding: 70px 0; }
</style>
