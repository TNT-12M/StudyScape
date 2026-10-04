<template>
  <n-data-table :columns="columns" :data="attempts" :bordered="false" :single-line="false" :scroll-x="760" />
</template>

<script setup>
import { h } from 'vue'
import { NButton, NSpace, NTag } from 'naive-ui'
import { formatDate } from './question-utils'

const props = defineProps({ attempts: { type: Array, default: () => [] } })
const emit = defineEmits(['resume', 'result'])
const columns = [
  { title: '科目 / 试卷', key: 'name', minWidth: 170, render: row => row.paper_name || row.subject || '综合' },
  { title: '题数', key: 'question_count', width: 70 },
  { title: '时长', key: 'duration_minutes', width: 90, render: row => row.duration_minutes ? `${row.duration_minutes} 分钟` : '不限时' },
  { title: '开始时间', key: 'started_at', minWidth: 155, render: row => formatDate(row.started_at) },
  { title: '状态', key: 'status', width: 90, render: row => h(NTag, { type: row.status === 'submitted' ? 'success' : 'warning', size: 'small' }, { default: () => row.status === 'submitted' ? '已提交' : '进行中' }) },
  { title: '自评得分', key: 'self_score_total', width: 90, render: row => row.self_score_total ?? '—' },
  { title: '操作', key: 'actions', width: 150, render: row => h(NSpace, { size: 'small' }, { default: () => [h(NButton, { size: 'small', type: row.status === 'submitted' ? 'default' : 'primary', onClick: () => row.status === 'submitted' ? emit('result', row) : emit('resume', row) }, { default: () => row.status === 'submitted' ? '查看结果' : '继续作答' })] }) },
]
</script>
