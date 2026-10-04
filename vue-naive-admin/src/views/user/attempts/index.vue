<template>
  <AppPage show-footer>
    <n-card title="学习记录" :bordered="false">
      <template #header-extra><n-button secondary :loading="loading" @click="loadAttempts">刷新</n-button></template>
      <n-tabs v-model:value="tab" type="line">
        <n-tab-pane name="all" tab="全部记录"><AttemptTable :attempts="attempts" @resume="resume" @result="showResult" /></n-tab-pane>
        <n-tab-pane name="exam" tab="组卷考试"><AttemptTable :attempts="examAttempts" @resume="resume" @result="showResult" /></n-tab-pane>
        <n-tab-pane name="practice" tab="自由刷题"><AttemptTable :attempts="practiceAttempts" @resume="resume" @result="showResult" /></n-tab-pane>
      </n-tabs>
    </n-card>
    <n-modal v-model:show="resultVisible" preset="card" style="width: min(960px, calc(100vw - 32px))" :title="resultTitle">
      <ResultPanel :title="resultTitle" :attempt="resultData.attempt" :questions="resultData.questions" :summary="resultData.summary" :saving="savingGrade" @back="resultVisible = false" @save="saveGrades" />
    </n-modal>
  </AppPage>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { phpExamApi, phpPracticeApi } from '@/api/php-modules'
import AttemptTable from '@/views/user/components/AttemptTable.vue'
import ResultPanel from '@/views/user/components/ResultPanel.vue'

const attempts = ref([])
const loading = ref(false)
const savingGrade = ref(false)
const tab = ref('all')
const resultVisible = ref(false)
const resultTitle = ref('答题结果')
const resultData = ref({ attempt: null, questions: [], summary: {} })
const examAttempts = computed(() => attempts.value.filter(attempt => attempt.paper_id))
const practiceAttempts = computed(() => attempts.value.filter(attempt => !attempt.paper_id))

async function loadAttempts() {
  loading.value = true
  try { attempts.value = (await phpExamApi.mine()).data?.attempts || [] }
  catch (error) { window.$message?.error(error.message || '记录加载失败') }
  finally { loading.value = false }
}

async function resume(attempt) {
  try {
    const result = await phpExamApi.get(attempt.id)
    if (result.data?.attempt?.status === 'submitted') return showResult(attempt)
    window.$message?.info('请在对应的考试或刷题页面继续作答')
  }
  catch (error) { window.$message?.error(error.message || '作答加载失败') }
}

async function showResult(attempt) {
  try {
    const result = attempt.paper_id ? await phpExamApi.result(attempt.id) : await phpPracticeApi.result(attempt.id)
    resultData.value = result.data || { attempt: null, questions: [], summary: {} }
    resultTitle.value = result.data?.paper?.name ? `${result.data.paper.name} · 结果` : `${attempt.subject || '自由刷题'} · 结果`
    resultVisible.value = true
  }
  catch (error) { window.$message?.error(error.message || '结果加载失败') }
}

async function saveGrades(grades) {
  if (!resultData.value.attempt?.id) return
  savingGrade.value = true
  const id = resultData.value.attempt.id
  try {
    if (resultData.value.attempt.paper_id) await phpExamApi.selfGrade({ attempt_id: id, grades })
    else await phpPracticeApi.selfGrade({ attempt_id: id, grades })
    window.$message?.success('自评已保存')
    await showResult({ id, paper_id: resultData.value.attempt.paper_id, subject: resultTitle.value })
  }
  catch (error) { window.$message?.error(error.message || '自评保存失败') }
  finally { savingGrade.value = false }
}

onMounted(loadAttempts)
</script>
