<template>
  <div class="result-page">
    <n-card>
      <div class="result-heading">
        <div>
          <div class="result-title">{{ title }}</div>
          <div class="muted">提交时间：{{ formatDate(attempt?.submitted_at) }}</div>
        </div>
        <n-space>
          <n-button @click="$emit('back')">返回列表</n-button>
          <n-button type="primary" :loading="saving" @click="saveGrades">保存自评</n-button>
        </n-space>
      </div>
      <n-grid :cols="5" :x-gap="12" :y-gap="12" responsive="screen" item-responsive class="summary-grid">
        <n-gi v-for="item in summaryItems" :key="item.label" span="5 s:1">
          <div class="summary-item"><div class="muted">{{ item.label }}</div><strong>{{ item.value }}</strong></div>
        </n-gi>
      </n-grid>
    </n-card>
    <QuestionCard
      v-for="(question, index) in questions"
      :key="question.id"
      :question="question"
      :index="index"
      mode="review"
      :points="question.max_points || question.points"
      @update:grade="updateGrade"
    />
    <n-empty v-if="!questions.length" description="暂无结果" />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import QuestionCard from './QuestionCard.vue'
import { formatDate } from './question-utils'

const props = defineProps({
  title: { type: String, default: '答题结果' },
  attempt: { type: Object, default: null },
  questions: { type: Array, default: () => [] },
  summary: { type: Object, default: () => ({}) },
  saving: { type: Boolean, default: false },
})
const emit = defineEmits(['back', 'save'])
const grades = ref({})
const summaryItems = computed(() => [
  { label: '总题数', value: props.summary.total_count || 0 },
  { label: '已作答', value: props.summary.answered_count || 0 },
  { label: '已自评', value: props.summary.graded_count || 0 },
  { label: '自评正确', value: props.summary.correct_count || 0 },
  { label: '自评总分', value: props.summary.self_score_total ?? '—' },
])
function updateGrade(grade) { grades.value = { ...grades.value, [grade.question_id]: grade } }
function saveGrades() {
  const values = props.questions.map(question => grades.value[question.id] || {
    question_id: question.id,
    self_correct: question.self_correct ?? null,
    self_score: question.self_score ?? null,
  })
  emit('save', values)
}
</script>

<style scoped>
.result-page { display: flex; flex-direction: column; gap: 16px; }
.result-heading { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
.result-title { font-size: 20px; font-weight: 700; }
.muted { color: var(--n-text-color-3); font-size: 13px; }
.summary-grid { margin-top: 20px; }
.summary-item { padding: 12px; background: var(--n-color-modal); border: 1px solid var(--n-border-color); border-radius: 6px; }
.summary-item strong { display: block; margin-top: 6px; font-size: 22px; color: var(--n-primary-color); }
</style>
