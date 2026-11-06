<template>
  <AppPage>
    <n-card title="组卷管理" segmented>
      <template #header-extra><n-button type="primary" @click="openCreate">新建试卷</n-button></template>
      <n-data-table :columns="columns" :data="papers" :loading="loading" />
    </n-card>
    <n-modal v-model:show="showEditor" preset="card" :title="editingId ? '编辑试卷' : '新建试卷'" style="width:min(1200px, 96vw)">
      <n-form :model="form" label-placement="left" label-width="90">
        <n-grid :cols="3" :x-gap="16"><n-form-item-gi label="试卷名称"><n-input v-model:value="form.name" /></n-form-item-gi><n-form-item-gi label="考试时长"><n-input-number v-model:value="form.duration_minutes" :min="1" /></n-form-item-gi><n-form-item-gi label="说明"><n-input v-model:value="form.description" /></n-form-item-gi></n-grid>
      </n-form>
      <n-divider>选题</n-divider>
      <n-grid :cols="2" :x-gap="16">
        <!-- ========== 左侧: 题库池 ========== -->
        <n-gi>
          <n-card size="small" title="题库池">
            <template #header-extra>
              <n-space wrap>
                <n-select v-model:value="poolFilters.education_level" clearable :options="levelOptions" placeholder="学段" style="width:100px" @update:value="loadPool" />
                <n-select v-model:value="poolFilters.subject" clearable :options="subjectOptions" placeholder="科目" style="width:110px" @update:value="loadPool" />
                <n-input v-model:value="poolFilters.keyword" placeholder="题干关键词" clearable style="width:150px" @keyup.enter="loadPool" />
                <n-button size="small" type="primary" @click="loadPool">搜索</n-button>
              </n-space>
            </template>
            <n-spin :show="poolLoading">
              <n-space vertical size="small">
                <div v-for="q in pool" :key="q.id" :class="['pool-item', { selected: selectedMap.has(q.id) }]">
                  <div class="pool-item-top">
                    <n-checkbox :checked="selectedMap.has(q.id)" @update:checked="checked => toggleQuestion(q, checked)" />
                    <span class="pool-meta">
                      <n-tag size="tiny" round :bordered="false">{{ q.subject }}</n-tag>
                      <n-tag size="tiny" round :bordered="false" type="info">{{ levelLabel(q.education_level) }}</n-tag>
                      <n-tag size="tiny" round :bordered="false" type="warning">{{ q.question_type }}</n-tag>
                      <span class="pool-qid">Q{{ q.id }}</span>
                    </span>
                  </div>
                  <div class="pool-content" @click="previewQuestion(q)">
                    <span class="pool-preview">{{ shortText(q.content) }}</span>
                    <n-button v-if="isTruncated(q.content)" size="tiny" quaternary type="primary" @click.stop="previewQuestion(q)">查看完整 →</n-button>
                  </div>
                </div>
                <n-empty v-if="!pool.length" description="暂无题目" />
              </n-space>
            </n-spin>
          </n-card>
        </n-gi>

        <!-- ========== 右侧: 已选题目 ========== -->
        <n-gi>
          <n-card size="small" title="已选题目">
            <template #header-extra><n-text depth="3">共 {{ selectedQuestions.length }} 题</n-text></template>
            <n-list bordered>
              <n-list-item v-for="(item, index) in selectedQuestions" :key="item.id">
                <n-space vertical size="small" style="width:100%">
                  <n-space justify="space-between" align="center" style="width:100%">
                    <n-space>
                      <span class="sel-index">{{ index + 1 }}.</span>
                      <n-tag size="tiny" round :bordered="false">{{ item.subject }}</n-tag>
                      <span class="pool-qid">Q{{ item.id }}</span>
                    </n-space>
                    <n-space>
                      <n-input-number v-model:value="item.points" :min="0" :step="0.5" size="small" style="width:90px" />
                      <n-button size="small" quaternary :disabled="index === 0" @click="moveItem(index, -1)">↑</n-button>
                      <n-button size="small" quaternary :disabled="index === selectedQuestions.length - 1" @click="moveItem(index, 1)">↓</n-button>
                      <n-button size="small" quaternary @click="previewQuestion(item)">查看</n-button>
                      <n-button size="small" type="error" quaternary @click="toggleQuestion(item, false)">移除</n-button>
                    </n-space>
                  </n-space>
                  <div class="sel-content" @click="previewQuestion(item)">
                    <span>{{ shortText(item.content) }}</span>
                    <n-button v-if="isTruncated(item.content)" size="tiny" quaternary type="primary" @click.stop="previewQuestion(item)">完整题干 →</n-button>
                  </div>
                </n-space>
              </n-list-item>
              <n-empty v-if="!selectedQuestions.length" description="请从左侧选择题目" />
            </n-list>
          </n-card>
        </n-gi>
      </n-grid>
      <template #footer><n-space justify="end"><n-button @click="showEditor = false">取消</n-button><n-button type="primary" :loading="saving" @click="savePaper">保存试卷</n-button></n-space></template>
    </n-modal>

    <!-- ========== 完整题干预览弹窗 ========== -->
    <n-modal v-model:show="showPreview" preset="card" title="题目预览" style="width:min(800px, 96vw)">
      <div v-if="previewQ" class="preview-wrap">
        <n-space align="center" size="small" style="margin-bottom:12px">
          <n-tag round :bordered="false" size="small">{{ previewQ.subject }}</n-tag>
          <n-tag round :bordered="false" type="info" size="small">{{ levelLabel(previewQ.education_level) }}</n-tag>
          <n-tag round :bordered="false" type="warning" size="small">{{ previewQ.question_type }}</n-tag>
          <n-tag round :bordered="false" type="success" size="small">{{ previewQ.points || 1 }} 分</n-tag>
          <span class="pool-qid">Q{{ previewQ.id }}</span>
        </n-space>
        <div class="preview-body" v-html="previewQ.content || ''"></div>
        <div v-if="hasOptions" class="preview-options">
          <div v-for="(opt, oi) in previewOptionsList" :key="oi" class="preview-option"><b>{{ letter(oi) }}.</b> <span v-html="opt" /></div>
        </div>
        <n-divider v-if="previewQ.explanation">解析</n-divider>
        <div v-if="previewQ.explanation" class="preview-body" v-html="previewQ.explanation"></div>
      </div>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showPreview = false">关闭</n-button>
          <n-button v-if="previewIsSelected" type="warning" @click="removeFromPreview">从已选移除</n-button>
          <n-button v-else type="primary" @click="addFromPreview">加入已选</n-button>
        </n-space>
      </template>
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
const showPreview = ref(false)
const previewQ = ref(null)
const form = reactive({ name: '', description: '', duration_minutes: 60 })
const poolFilters = reactive({ education_level: '', subject: '', keyword: '' })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const subjectOptions = ref([])
const columns = [{ title: 'ID', key: 'id', width: 70 }, { title: '试卷名称', key: 'name' }, { title: '题数', key: 'question_count', width: 75 }, { title: '时长', key: 'duration_minutes', render: row => `${row.duration_minutes} 分钟`, width: 100 }, { title: '状态', key: 'is_published', render: row => h(NTag, { type: row.is_published ? 'success' : 'warning' }, { default: () => row.is_published ? '已发布' : '草稿' }), width: 90 }, { title: '更新时间', key: 'updated_at', width: 170 }, { title: '操作', key: 'actions', width: 320, render: row => h(NSpace, null, { default: () => [h(NButton, { size: 'small', onClick: () => openEdit(row.id) }, { default: () => '编辑' }), h(NButton, { size: 'small', type: row.is_published ? 'warning' : 'success', loading: publishingId.value === row.id, onClick: () => togglePublish(row) }, { default: () => row.is_published ? '下架' : '发布' }), h(NButton, { size: 'small', type: 'error', loading: deletingId.value === row.id, onClick: () => removePaper(row) }, { default: () => '删除' })] }) }]
function shortText(value) { return String(value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 80) }
function isTruncated(value) { return String(value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().length > 80 }
function letter(i) { return String.fromCharCode(65 + i) }
function levelLabel(v) { return v === 'junior' ? '初中' : v === 'senior' ? '高中' : v || '' }
async function loadSubjects() { try { const r = await phpQuestionsApi.subjects({}); subjectOptions.value = (r.data?.subjects || []).map(s => ({ label: s, value: s })) } catch (e) {} }
function previewQuestion(q) { previewQ.value = q; showPreview.value = true }
function removeFromPreview() { if (previewQ.value) { toggleQuestion(previewQ.value, false); showPreview.value = false } }
function addFromPreview() { if (previewQ.value) { toggleQuestion(previewQ.value, true); showPreview.value = false } }
const hasOptions = computed(() => previewQ.value && previewQ.value.options && Object.keys(previewQ.value.options).length > 0)
const previewOptionsList = computed(() => {
  if (!previewQ.value || !previewQ.value.options) return []
  const opts = previewQ.value.options
  // 兼容数组索引和对象键两种格式
  if (Array.isArray(opts)) return opts
  const keys = Object.keys(opts).sort((a, b) => {
    const na = parseInt(a, 10), nb = parseInt(b, 10)
    if (!isNaN(na) && !isNaN(nb)) return na - nb
    return a.localeCompare(b)
  })
  return keys.map(k => opts[k])
})
const previewIsSelected = computed(() => previewQ.value ? selectedMap.value.has(previewQ.value.id) : false)
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
        return false
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
onMounted(() => { loadPapers(); loadSubjects() })
</script>
<style scoped>
.pool-item { padding: 8px 10px; border: 1px solid var(--n-border-color); border-radius: 6px; transition: all 0.15s; }
.pool-item.selected { border-color: var(--n-color-primary); background: var(--n-color-primary-embedded); }
.pool-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.pool-meta { display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.pool-qid { font-size: 11px; opacity: 0.55; font-family: monospace; margin-left: auto; }
.pool-content { display: flex; align-items: center; gap: 6px; cursor: pointer; line-height: 1.5; }
.pool-content:hover { color: var(--n-color-primary); }
.pool-preview { flex: 1; }
.sel-index { font-weight: 600; font-size: 13px; }
.sel-content { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--n-text-color-2); cursor: pointer; }
.sel-content:hover { color: var(--n-color-primary); }
.preview-wrap { line-height: 1.8; font-size: 15px; }
.preview-body { padding: 12px 0; white-space: pre-wrap; word-break: break-word; }
.preview-body :deep(img) { max-width: 100%; height: auto; border-radius: 4px; margin: 6px 0; }
.preview-options { padding: 8px 0; }
.preview-option { padding: 4px 0; }
</style>
