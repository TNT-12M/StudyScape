<template>
  <AppPage show-footer>
    <div v-if="view === 'list'" class="practice-page">
      <n-card title="自由刷题" :bordered="false">
        <template #header-extra><n-button secondary :loading="loading" @click="loadPage">刷新</n-button></template>
        <n-tabs v-model:value="tab" type="line">
          <n-tab-pane name="config" tab="开始刷题">
            <n-form ref="formRef" :model="form" :rules="rules" label-placement="left" label-width="120" class="config-form">
              <n-grid :cols="2" :x-gap="24" :y-gap="8" responsive="screen" item-responsive>
                <n-gi span="2 m:1"><n-form-item label="选择学段" path="education_level"><n-select v-model:value="form.education_level" :options="levelOptions" placeholder="请选择学段" @update:value="loadSubjects" /></n-form-item></n-gi>
                <n-gi span="2 m:1"><n-form-item label="选择科目" path="subject"><n-select v-model:value="form.subject" :options="subjectOptions" :disabled="!form.education_level" placeholder="请先选择学段" /></n-form-item>
                </n-gi>
                <n-gi span="2 m:1"><n-form-item label="题目数量" path="count"><n-input-number v-model:value="form.count" :min="1" :max="50" /></n-form-item>
                </n-gi>
                <n-gi span="2 m:1"><n-form-item label="时长（分钟）"><n-input-number v-model:value="form.duration_minutes" :min="0" :max="240" :step="5" /><span class="form-hint">0 表示不限时</span></n-form-item>
                </n-gi>
              </n-grid>
              <n-space justify="end"><n-button type="primary" :loading="starting" :disabled="!subjectOptions.length" @click="startPractice">开始随机抽题</n-button></n-space>
            </n-form>
          </n-tab-pane>
          <n-tab-pane name="history" tab="我的刷题历史">
            <AttemptTable :attempts="practiceAttempts" @resume="resumeAttempt" @result="showResult" />
          </n-tab-pane>
        </n-tabs>
      </n-card>
    </div>

    <AttemptRunner v-else-if="view === 'doing'" :title="`自由刷题 · ${currentSubject || '综合'}`" :questions="questions" :duration="attemptDuration" :started-at="startedAt" :submitting="submitting" :storage-key="draftKey" @submit="submitPractice" />
    <ResultPanel v-else :title="`自由刷题 · ${currentSubject || '结果'}`" :attempt="resultData.attempt" :questions="resultData.questions" :summary="resultData.summary" :saving="savingGrade" @back="backToList" @save="saveGrades" />
  </AppPage>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { phpExamApi, phpPracticeApi } from '@/api/php-modules'
import AttemptRunner from '@/views/user/components/AttemptRunner.vue'
import ResultPanel from '@/views/user/components/ResultPanel.vue'
import AttemptTable from '@/views/user/components/AttemptTable.vue'

const view = ref('list')
const tab = ref('config')
const loading = ref(false)
const starting = ref(false)
const submitting = ref(false)
const savingGrade = ref(false)
const subjects = ref([])
const attempts = ref([])
const questions = ref([])
const resultData = ref({ attempt: null, questions: [], summary: {} })
const currentAttemptId = ref(null)
const currentSubject = ref('')
const attemptDuration = ref(0)
const startedAt = ref('')
const formRef = ref(null)
const form = reactive({ education_level: null, subject: null, count: 10, duration_minutes: 0 })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const rules = { education_level: { required: true, message: '请选择学段', trigger: 'change' }, subject: { required: true, message: '请选择科目', trigger: 'change' } }
const subjectOptions = computed(() => subjects.value.map(subject => ({ label: subject, value: subject })))
const practiceAttempts = computed(() => attempts.value.filter(attempt => !attempt.paper_id))
const draftKey = computed(() => currentAttemptId.value ? `practice-draft-${currentAttemptId.value}` : '')

async function loadPage() {
  loading.value = true
  try {
    const result = await phpExamApi.mine()
    attempts.value = result.data?.attempts || []
  }
  catch (error) { window.$message?.error(error.message || '历史记录加载失败') }
  finally { loading.value = false }
}

async function loadSubjects(level = form.education_level) {
  form.subject = null
  subjects.value = []
  if (!level) return
  try {
    const result = await phpPracticeApi.subjects({ education_level: level })
    subjects.value = result.data?.subjects || []
  }
  catch (error) { window.$message?.error(error.message || '科目加载失败') }
}

async function startPractice() {
  await formRef.value?.validate()
  starting.value = true
  try {
    const result = await phpPracticeApi.start({ ...form })
    enterAttempt(result.data, form.subject)
  }
  catch (error) { window.$message?.error(error.message || '开始刷题失败') }
  finally { starting.value = false }
}

async function resumeAttempt(attempt) {
  try {
    const result = await phpExamApi.get(attempt.id)
    if (result.data?.attempt?.status === 'submitted') return showResult(attempt)
    enterAttempt(result.data, result.data?.subject || attempt.subject)
  }
  catch (error) { window.$message?.error(error.message || '恢复作答失败') }
}

function enterAttempt(data, subject = '') {
  currentAttemptId.value = data.attempt_id || data.attempt?.id
  currentSubject.value = subject || data.subject || ''
  questions.value = data.questions || []
  attemptDuration.value = Number(data.attempt?.duration_minutes || 0)
  startedAt.value = data.attempt?.started_at || ''
  view.value = 'doing'
}

async function submitPractice(answers, forced = false) {
  if (!currentAttemptId.value) return
  submitting.value = true
  const payload = questions.value.map(question => {
    let value = answers[question.id]
    if ((question.question_type === 'multi' || question.question_type === 'multiple' || question.question_type === 'multi_fill') && !Array.isArray(value)) value = []
    if (!Array.isArray(value) && value == null) value = ''
    return { question_id: question.id, student_answer: value }
  })
  try {
    const result = await phpPracticeApi.submit({ attempt_id: currentAttemptId.value, answers: payload })
    if (result.data?.overdue || forced) window.$message?.warning('已提交，部分作答可能超时')
    await showResult({ id: currentAttemptId.value })
  }
  catch (error) { window.$message?.error(error.message || '提交失败') }
  finally { submitting.value = false }
}

async function showResult(attempt) {
  const id = attempt.id || currentAttemptId.value
  if (!id) return
  try {
    const result = await phpPracticeApi.result(id)
    resultData.value = result.data || { attempt: null, questions: [], summary: {} }
    currentAttemptId.value = id
    currentSubject.value = attempt.subject || result.data?.questions?.[0]?.subject || currentSubject.value
    view.value = 'result'
    await loadPage()
  }
  catch (error) { window.$message?.error(error.message || '结果加载失败') }
}

async function saveGrades(grades) {
  if (!currentAttemptId.value) return
  savingGrade.value = true
  try {
    await phpPracticeApi.selfGrade({ attempt_id: currentAttemptId.value, grades })
    window.$message?.success('自评已保存')
    await showResult({ id: currentAttemptId.value, subject: currentSubject.value })
  }
  catch (error) { window.$message?.error(error.message || '保存自评失败') }
  finally { savingGrade.value = false }
}

function backToList() { view.value = 'list'; tab.value = 'history'; loadPage() }

onMounted(loadPage)
</script>

<style scoped>
.practice-page { display: flex; flex-direction: column; gap: 16px; }
.config-form { max-width: 820px; padding: 24px 0 8px; }
.form-hint { margin-left: 10px; color: var(--n-text-color-3); font-size: 12px; }
</style>
