<template>
  <AppPage>
    <n-card title="组卷管理" segmented>
      <template #header-extra><n-button type="primary" @click="openCreate">新建试卷</n-button></template>
      <n-data-table :columns="columns" :data="papers" :loading="loading" />
    </n-card>
    <n-modal v-model:show="showEditor" preset="card" :title="editingId ? '编辑试卷' : '新建试卷'" style="width:min(1100px, 96vw)">
      <n-form :model="form" label-placement="left" label-width="90">
        <n-grid :cols="3" :x-gap="16"><n-form-item-gi label="试卷名称"><n-input v-model:value="form.name" /></n-form-item-gi><n-form-item-gi label="考试时长"><n-input-number v-model:value="form.duration_minutes" :min="1" /></n-form-item-gi><n-form-item-gi label="说明"><n-input v-model:value="form.description" /></n-form-item-gi></n-grid>
      </n-form>
      <n-divider>选题</n-divider>
      <n-grid :cols="2" :x-gap="16">
        <n-gi><n-card size="small" title="题库池"><template #header-extra><n-space><n-select v-model:value="poolFilters.education_level" clearable :options="levelOptions" placeholder="学段" style="width:110px" @update:value="loadPool" /><n-input v-model:value="poolFilters.keyword" placeholder="题干关键词" clearable style="width:160px" @keyup.enter="loadPool" /><n-button size="small" @click="loadPool">搜索</n-button></n-space></template><n-spin :show="poolLoading"><n-space vertical><n-checkbox v-for="q in pool" :key="q.id" :checked="selectedMap.has(q.id)" @update:checked="checked => toggleQuestion(q, checked)"><span>#{{ q.id }} {{ q.subject }} · {{ shortText(q.content) }}</span></n-checkbox></n-space><n-empty v-if="!pool.length" description="暂无题目" /></n-spin></n-card></n-gi>
        <n-gi><n-card size="small" title="已选题目"><template #header-extra><n-text depth="3">共 {{ selectedQuestions.length }} 题</n-text></template><n-list bordered><n-list-item v-for="(item, index) in selectedQuestions" :key="item.id"><n-space justify="space-between" align="center" style="width:100%"><span>{{ index + 1 }}. {{ shortText(item.content) }}</span><n-space><n-input-number v-model:value="item.points" :min="0" :step="0.5" size="small" style="width:100px" /><n-button size="small" quaternary :disabled="index === 0" @click="moveItem(index, -1)">上移</n-button><n-button size="small" quaternary :disabled="index === selectedQuestions.length - 1" @click="moveItem(index, 1)">下移</n-button><n-button size="small" type="error" quaternary @click="toggleQuestion(item, false)">移除</n-button></n-space></n-space></n-list-item></n-list><n-empty v-if="!selectedQuestions.length" description="请从左侧选择题目" /></n-card></n-gi>
      </n-grid>
      <template #footer><n-space justify="end"><n-button @click="showEditor = false">取消</n-button><n-button type="primary" :loading="saving" @click="savePaper">保存试卷</n-button></n-space></template>
    </n-modal>
  </AppPage>
</template>
<script setup>
import { NButton, NSpace, NTag } from 'naive-ui'
import { phpPapersApi, phpQuestionsApi } from '@/api/php-modules'
const message = window.$message
const dialog = window.$dialog
const papers = ref([])
const pool = ref([])
const selectedQuestions = ref([])
const selectedMap = computed(() => new Set(selectedQuestions.value.map(item => item.id)))
const loading = ref(false)
const poolLoading = ref(false)
const saving = ref(false)
const publishingId = ref(null)
const deletingId = ref(null)
const showEditor = ref(false)
const editingId = ref(null)
const form = reactive({ name: '', description: '', duration_minutes: 60 })
const poolFilters = reactive({ education_level: '', keyword: '' })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const columns = [{ title: 'ID', key: 'id', width: 70 }, { title: '试卷名称', key: 'name' }, { title: '题数', key: 'question_count', width: 75 }, { title: '时长', key: 'duration_minutes', render: row => `${row.duration_minutes} 分钟`, width: 100 }, { title: '状态', key: 'is_published', render: row => h(NTag, { type: row.is_published ? 'success' : 'warning' }, { default: () => row.is_published ? '已发布' : '草稿' }), width: 90 }, { title: '更新时间', key: 'updated_at', width: 170 }, { title: '操作', key: 'actions', width: 320, render: row => h(NSpace, null, { default: () => [h(NButton, { size: 'small', onClick: () => openEdit(row.id) }, { default: () => '编辑' }), h(NButton, { size: 'small', type: row.is_published ? 'warning' : 'success', loading: publishingId.value === row.id, onClick: () => togglePublish(row) }, { default: () => row.is_published ? '下架' : '发布' }), h(NButton, { size: 'small', type: 'error', loading: deletingId.value === row.id, onClick: () => removePaper(row) }, { default: () => '删除' })] }) }]
function shortText(value) { return String(value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) }
async function loadPapers() { loading.value = true; try { papers.value = (await phpPapersApi.list({})).data?.papers || [] } catch (error) { message.error(error.message) } finally { loading.value = false } }
async function loadPool() { poolLoading.value = true; try { const result = await phpQuestionsApi.list({ ...poolFilters, page: 1, page_size: 100, scope: 'manage_all' }); pool.value = result.data?.items || [] } catch (error) { message.error(error.message) } finally { poolLoading.value = false } }
function openCreate() { editingId.value = null; Object.assign(form, { name: '', description: '', duration_minutes: 60 }); selectedQuestions.value = []; showEditor.value = true; loadPool() }
async function openEdit(id) { try { const result = await phpPapersApi.get(id); const data = result.data || {}; const paper = data.paper || {}; editingId.value = id; Object.assign(form, { name: paper.name || '', description: paper.description || '', duration_minutes: Number(paper.duration_minutes) || 60 }); selectedQuestions.value = (data.questions || []).map(item => ({ ...item, points: Number(item.paper_points || item.points || 1) })); showEditor.value = true; await loadPool() } catch (error) { message.error(error.message) } }
function toggleQuestion(question, checked) { const index = selectedQuestions.value.findIndex(item => item.id === question.id); if (checked && index < 0) selectedQuestions.value.push({ ...question, points: Number(question.points || 1) }); else if (!checked && index >= 0) selectedQuestions.value.splice(index, 1) }
function moveItem(index, delta) { const target = index + delta; if (target < 0 || target >= selectedQuestions.value.length) return; const [item] = selectedQuestions.value.splice(index, 1); selectedQuestions.value.splice(target, 0, item) }
async function savePaper() { if (!form.name.trim()) return message.warning('请输入试卷名称'); if (!selectedQuestions.value.length) return message.warning('请至少选择一道题目'); saving.value = true; try { await phpPapersApi.save({ paper_id: editingId.value || 0, name: form.name.trim(), description: form.description, duration_minutes: form.duration_minutes, question_ids: selectedQuestions.value.map(item => ({ id: item.id, points: item.points })) }); message.success('试卷已保存'); showEditor.value = false; await loadPapers() } catch (error) { message.error(error.message) } finally { saving.value = false } }
function togglePublish(row) {
  const isPublished = !!row.is_published
  const action = isPublished ? phpPapersApi.unpublish : phpPapersApi.publish
  dialog.warning({
    title: isPublished ? '下架试卷' : '发布试卷',
    content: isPublished ? '已有提交记录的试卷可能无法下架，确定继续吗？' : '发布后学生可以看到并开始考试，确定继续吗？',
    positiveText: '确定',
    negativeText: '取消',
    onPositiveClick: async () => {
      publishingId.value = row.id
      try {
        await action(row.id)
        message.success(isPublished ? '已下架' : '已发布')
        await loadPapers()
      } catch (error) {
        console.error('[试卷操作失败]', error)
        message.error(error.message || '操作失败，请检查控制台')
        return false // 阻止对话框关闭，让用户看到错误
      } finally {
        publishingId.value = null
      }
    }
  })
}
function removePaper(row) {
  dialog.warning({
    title: '删除试卷',
    content: '删除后试卷、关联题目及所有学生作答记录将一并清除，确定继续吗？',
    positiveText: '删除',
    negativeText: '取消',
    onPositiveClick: async () => {
      deletingId.value = row.id
      try {
        await phpPapersApi.remove(row.id)
        message.success('已删除')
        await loadPapers()
      } catch (error) {
        console.error('[删除试卷失败]', error)
        message.error(error.message || '删除失败，请检查控制台')
        return false
      } finally {
        deletingId.value = null
      }
    }
  })
}
onMounted(loadPapers)
</script>
