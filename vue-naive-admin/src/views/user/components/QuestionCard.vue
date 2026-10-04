<template>
  <n-card class="question-card" :bordered="true">
    <template #header>
      <div class="question-header">
        <span>第 {{ index + 1 }} 题</span>
        <n-space size="small">
          <n-tag size="small" type="info">{{ questionTypeLabel(question.question_type) }}</n-tag>
          <n-tag v-if="points" size="small" type="warning">{{ points }} 分</n-tag>
        </n-space>
      </div>
    </template>
    <div class="question-content" v-html="renderQuestionContent(question)" />
    <div v-if="mode === 'answer'" class="answer-area">
      <n-radio-group v-if="question.question_type === 'single'" :value="answer" @update:value="value => update(value)">
        <n-space vertical>
          <n-radio v-for="(option, optionIndex) in question.options || []" :key="optionIndex" :value="letter(optionIndex)">
            <span class="option-letter">{{ letter(optionIndex) }}.</span>
            <span v-html="renderOption(option)" />
          </n-radio>
        </n-space>
      </n-radio-group>
      <n-checkbox-group v-else-if="isMultipleQuestion(question)" :value="Array.isArray(answer) ? answer : []" @update:value="value => update([...value].sort())">
        <n-space vertical>
          <n-checkbox v-for="(option, optionIndex) in question.options || []" :key="optionIndex" :value="letter(optionIndex)">
            <span class="option-letter">{{ letter(optionIndex) }}.</span>
            <span v-html="renderOption(option)" />
          </n-checkbox>
        </n-space>
      </n-checkbox-group>
      <n-radio-group v-else-if="question.question_type === 'judge'" :value="answer" @update:value="value => update(value)">
        <n-space>
          <n-radio value="T">对（T）</n-radio>
          <n-radio value="F">错（F）</n-radio>
        </n-space>
      </n-radio-group>
      <n-space v-else-if="question.question_type === 'multi_fill'" vertical>
        <n-input v-for="(_, blankIndex) in blankCount(question)" :key="blankIndex" :value="Array.isArray(answer) ? answer[blankIndex] : ''" :placeholder="`第 ${blankIndex + 1} 空`" @update:value="value => updateBlank(blankIndex, value)" />
      </n-space>
      <n-input v-else-if="question.question_type === 'fill'" :value="typeof answer === 'string' ? answer : ''" placeholder="请输入答案" @update:value="update" />
      <n-input v-else type="textarea" :value="typeof answer === 'string' ? answer : ''" :rows="4" placeholder="请输入答案" @update:value="update" />
    </div>
    <template v-else>
      <n-alert v-if="!answerIsFilled(question, { [question.id]: question.student_answer })" type="warning" :show-icon="false" class="review-row">未作答</n-alert>
      <div v-else class="review-row"><b>你的答案：</b>{{ formatAnswer(question, question.student_answer) }}</div>
      <div class="review-row"><b>参考答案：</b>{{ formatAnswer(question, question.correct_answer) }}</div>
      <n-space align="center" class="grade-row">
        <span>自评：</span>
        <n-select v-model:value="grade.correct" clearable placeholder="未评" :options="gradeOptions" />
        <n-input-number v-model:value="grade.score" :min="0" :max="maxPoints" :step="0.5" placeholder="得分" />
        <span class="muted">/ {{ maxPoints }} 分</span>
      </n-space>
    </template>
  </n-card>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { answerIsFilled, blankCount, formatAnswer, isMultipleQuestion, normalizeAnswer, questionTypeLabel, renderOption, renderQuestionContent } from './question-utils'

const props = defineProps({
  question: { type: Object, required: true },
  index: { type: Number, default: 0 },
  answer: { type: [String, Array], default: '' },
  mode: { type: String, default: 'answer' },
  points: { type: [Number, String], default: 0 },
})
const emit = defineEmits(['update:answer', 'update:grade'])

const grade = reactive({ correct: props.question.self_correct, score: props.question.self_score })
const gradeOptions = [{ label: '正确', value: 1 }, { label: '错误', value: 0 }]
const maxPoints = computed(() => Number(props.question.max_points || props.points || props.question.points || 1))

watch(() => [grade.correct, grade.score], () => emit('update:grade', { question_id: props.question.id, self_correct: grade.correct ?? null, self_score: grade.score ?? null }), { deep: true })
watch(() => [props.question.self_correct, props.question.self_score], ([correct, score]) => {
  grade.correct = correct
  grade.score = score
})

function letter(index) { return String.fromCharCode(65 + index) }
function update(value) { emit('update:answer', value) }
function updateBlank(index, value) {
  const values = normalizeAnswer(props.question, props.answer)
  values[index] = value
  update(values)
}
</script>

<style scoped>
.question-card { margin-bottom: 16px; }
.question-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.question-content { line-height: 1.8; margin-bottom: 18px; overflow-wrap: anywhere; }
.question-content :deep(img), .answer-area :deep(img) { max-width: 100%; max-height: 360px; object-fit: contain; }
.question-content :deep(table), .answer-area :deep(table) { max-width: 100%; overflow-x: auto; display: block; }
.question-blank { display: inline-block; padding: 1px 8px; margin: 0 3px; color: var(--n-primary-color); background: var(--n-primary-color-suppl); border-radius: 4px; font-size: 12px; }
.option-letter { margin-right: 6px; font-weight: 600; }
.review-row { padding: 10px 12px; margin-top: 10px; border: 1px solid var(--n-border-color); border-radius: 6px; white-space: pre-wrap; overflow-wrap: anywhere; }
.grade-row { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--n-divider-color); }
.muted { color: var(--n-text-color-3); }
</style>
