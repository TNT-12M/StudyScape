<template>
  <div class="attempt-page">
    <n-card class="attempt-toolbar" :bordered="true">
      <div class="toolbar-content">
        <div>
          <div class="attempt-title">{{ title }}</div>
          <div class="muted">共 {{ questions.length }} 题<span v-if="duration"> · {{ duration }} 分钟</span><span v-else> · 不限时</span></div>
        </div>
        <n-space align="center">
          <n-tag :type="remaining <= 60 && duration ? 'error' : remaining <= 300 && duration ? 'warning' : 'info'" size="large">
            {{ timerText }}
          </n-tag>
          <n-button quaternary @click="saveDraft">暂存草稿</n-button>
          <n-button type="primary" :loading="submitting" @click="submit">提交</n-button>
        </n-space>
      </div>
    </n-card>

    <n-grid :cols="5" :x-gap="18" :y-gap="18" responsive="screen" item-responsive>
      <n-gi span="5 m:1">
        <n-card title="题号导航" size="small" class="question-nav-card">
          <div class="question-nav">
            <n-button v-for="(question, index) in questions" :key="question.id" :type="currentIndex === index ? 'primary' : answerIsFilled(question, answers) ? 'success' : 'default'" size="small" @click="scrollToQuestion(index)">{{ index + 1 }}</n-button>
          </div>
          <n-divider />
          <n-space vertical size="small" class="muted">
            <span>已作答 {{ answeredCount }} / {{ questions.length }}</span>
            <span>提交后可查看参考答案并自评</span>
          </n-space>
        </n-card>
      </n-gi>
      <n-gi span="5 m:4">
        <QuestionCard
          v-for="(question, index) in questions"
          :key="question.id"
          :ref="element => setQuestionRef(element, index)"
          :question="question"
          :index="index"
          :answer="answers[question.id]"
          :points="question.paper_points || question.points"
          @update:answer="value => updateAnswer(question, value)"
        />
        <n-empty v-if="!questions.length" description="暂无题目" />
      </n-gi>
    </n-grid>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import QuestionCard from './QuestionCard.vue'
import { answerIsFilled, normalizeAnswer, parseDbDate } from './question-utils'

const props = defineProps({
  title: { type: String, default: '答题' },
  questions: { type: Array, default: () => [] },
  duration: { type: Number, default: 0 },
  startedAt: { type: [String, Date], default: '' },
  submitting: { type: Boolean, default: false },
  storageKey: { type: String, default: '' },
})
const emit = defineEmits(['submit', 'saved'])

const answers = ref({})
const currentIndex = ref(0)
const remaining = ref(props.duration > 0 ? props.duration * 60 : 0)
const questionRefs = ref([])
let timer
let autoSubmitted = false

const answeredCount = computed(() => props.questions.filter(question => answerIsFilled(question, answers.value)).length)
const timerText = computed(() => {
  if (!props.duration) return '不限时'
  const seconds = Math.max(remaining.value, 0)
  return `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`
})

watch(() => props.questions, initialiseAnswers, { immediate: true })
watch(() => [props.duration, props.startedAt], startTimer, { immediate: true })
onMounted(() => nextTick(() => loadDraft()))
onUnmounted(() => clearInterval(timer))

function initialiseAnswers(questions) {
  const initial = {}
  questions.forEach(question => { initial[question.id] = normalizeAnswer(question, question.student_answer) })
  answers.value = initial
}

function startTimer() {
  clearInterval(timer)
  if (!props.duration) { remaining.value = 0; return }
  const started = parseDbDate(props.startedAt)
  const tick = () => {
    remaining.value = Math.max(props.duration * 60 - Math.floor((Date.now() - started.getTime()) / 1000), 0)
    if (remaining.value === 0 && !autoSubmitted) {
      autoSubmitted = true
      window.$notification?.warning({ title: '时间到', content: '系统将自动提交当前答卷' })
      submit(true)
    }
  }
  tick()
  timer = window.setInterval(tick, 1000)
}

function loadDraft() {
  if (!props.storageKey) return
  try {
    const saved = JSON.parse(localStorage.getItem(props.storageKey) || 'null')
    if (saved && typeof saved === 'object') answers.value = { ...answers.value, ...saved }
  }
  catch {}
}

function saveDraft() {
  if (props.storageKey) localStorage.setItem(props.storageKey, JSON.stringify(answers.value))
  window.$message?.success('草稿已暂存')
  emit('saved', answers.value)
}

function updateAnswer(question, value) { answers.value = { ...answers.value, [question.id]: value } }
function setQuestionRef(element, index) { if (element) questionRefs.value[index] = element }
function scrollToQuestion(index) {
  currentIndex.value = index
  const target = questionRefs.value[index]?.$el || questionRefs.value[index]
  target?.scrollIntoView?.({ behavior: 'smooth', block: 'start' })
}
function submit(forced = false) {
  if (!forced && answeredCount.value < props.questions.length) {
    const confirmed = window.confirm(`共 ${props.questions.length} 题，已作答 ${answeredCount.value} 题，确认提交？`)
    if (!confirmed) return
  }
  clearInterval(timer)
  emit('submit', { ...answers.value }, forced)
}
</script>

<style scoped>
.attempt-page { display: flex; flex-direction: column; gap: 18px; }
.attempt-toolbar { position: sticky; top: 12px; z-index: 5; }
.toolbar-content { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.attempt-title { font-size: 18px; font-weight: 650; }
.muted { color: var(--n-text-color-3); font-size: 13px; }
.question-nav-card { position: sticky; top: 106px; }
.question-nav { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; }
@media (max-width: 800px) { .attempt-toolbar { position: static; } .question-nav-card { position: static; } }
</style>
