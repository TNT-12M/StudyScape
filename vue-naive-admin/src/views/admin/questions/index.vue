<template>
  <AppPage>
    <n-space vertical size="large">
      <n-grid :cols="4" :x-gap="12" responsive="screen" item-responsive>
        <n-gi v-for="item in statCards" :key="item.label" span="4 s:2 m:1"><n-card size="small"><n-statistic :label="item.label" :value="item.value" /></n-card></n-gi>
      </n-grid>
      <n-card title="题库筛选" segmented>
        <template #header-extra><n-space><n-button @click="loadQuestions">刷新</n-button><n-button type="primary" @click="openCreate">新增题目</n-button><n-button type="info" @click="showImport = true">JSON / OCR 导入</n-button></n-space></template>
        <n-form inline label-placement="left">
          <n-form-item label="学段"><n-select v-model:value="filters.education_level" clearable :options="levelOptions" style="width:130px" @update:value="reloadSubjects" /></n-form-item>
          <n-form-item label="科目"><n-select v-model:value="filters.subject" clearable :options="subjectOptions" style="width:150px" /></n-form-item>
          <n-form-item label="题型"><n-select v-model:value="filters.qtype" clearable :options="typeOptions" style="width:140px" /></n-form-item>
          <n-form-item label="关键词"><n-input v-model:value="filters.keyword" clearable placeholder="题干或解析" style="width:220px" @keyup.enter="loadQuestions" /></n-form-item>
          <n-form-item><n-button type="primary" @click="loadQuestions">搜索</n-button></n-form-item>
        </n-form>
      </n-card>
      <n-card title="题目列表" segmented>
        <template #header-extra><n-space><n-button :disabled="!selectedIds.length" @click="bulkCategory">批量改分类</n-button><n-button :disabled="!selectedIds.length" @click="bulkMove">批量移动学段</n-button><n-button type="error" :disabled="!selectedIds.length" @click="bulkDelete">批量删除</n-button></n-space></template>
        <n-data-table v-model:checked-row-keys="selectedIds" :columns="columns" :data="questions" :row-key="row => row.id" :loading="loading" :pagination="pagination" :scroll-x="1200" remote :expanded-row-keys="expandedRowKeys" @update:expanded-row-keys="keys => expandedRowKeys = keys" @update:page="handlePageChange" @update:page-size="handlePageSizeChange">
          <template #expanded-row="{ row }">
            <div class="expanded-content">
              <div class="expanded-section">
                <div class="expanded-label">题干</div>
                <div class="question-preview question-preview--inline" v-html="renderFullContent(row.content)" />
              </div>
              <div v-if="row.explanation" class="expanded-section">
                <div class="expanded-label">解析</div>
                <div class="question-preview question-preview--inline" v-html="renderFullContent(row.explanation)" />
              </div>
            </div>
          </template>
        </n-data-table>
        <n-empty v-if="!loading && !questions.length" description="当前筛选条件下没有题目" />
      </n-card>
    </n-space>

    <n-modal v-model:show="showEditor" preset="card" :title="editingId ? '编辑题目' : '新增题目'" style="width:min(760px, 94vw)">
      <n-form ref="formRef" :model="form" :rules="rules" label-placement="left" label-width="100">
        <n-grid :cols="2" :x-gap="16"><n-form-item-gi label="科目" path="subject"><n-input v-model:value="form.subject" /></n-form-item-gi><n-form-item-gi label="学段" path="education_level"><n-select v-model:value="form.education_level" :options="levelOptions" /></n-form-item-gi><n-form-item-gi label="分类"><n-select v-model:value="form.category" :options="categoryOptions" /></n-form-item-gi><n-form-item-gi label="题型" path="question_type"><n-select v-model:value="form.question_type" :options="typeOptions" @update:value="syncOptions" /></n-form-item-gi><n-form-item-gi label="难度"><n-rate v-model:value="form.difficulty" :count="5" /></n-form-item-gi><n-form-item-gi label="分值"><n-input-number v-model:value="form.points" :min="0" :step="0.5" /></n-form-item-gi></n-grid>
        <n-form-item label="题干" path="content">
          <n-tabs v-model:value="contentMode" type="segment" size="small">
            <n-tab-pane name="preview" tab="预览">
              <div class="question-preview" v-html="previewContent" />
            </n-tab-pane>
            <n-tab-pane name="source" tab="编辑源码">
              <n-input v-model:value="form.content" type="textarea" :rows="8" placeholder="请输入题干文本或 HTML 内容" />
            </n-tab-pane>
          </n-tabs>
        </n-form-item>
        <n-form-item v-if="form.question_type !== 'fill'" label="选项"><n-dynamic-input v-model:value="form.options" :on-create="() => ''"><template #create-button-default>增加选项</template></n-dynamic-input></n-form-item>
        <n-form-item label="正确答案" path="correct_answer"><n-input v-model:value="form.correct_answer" placeholder="A / AB / 答案" /></n-form-item>
        <n-form-item label="解析"><n-input v-model:value="form.explanation" type="textarea" :rows="3" /></n-form-item>
      </n-form>
      <template #footer><n-space justify="end"><n-button @click="showEditor = false">取消</n-button><n-button type="primary" :loading="saving" @click="saveQuestion">保存</n-button></n-space></template>
    </n-modal>

    <n-modal v-model:show="showImport" preset="card" title="JSON / OCR 导入" style="width:min(760px, 94vw)">
      <n-alert type="info" class="mb-16">支持题库 JSON、PaperCutter-VL 结构和答案 JSON。答案文件可在下方单独选择，导入时按题号匹配。</n-alert>
      <n-form label-placement="top"><n-form-item label="题库 JSON"><n-upload :show-file-list="false" accept=".json" @before-upload="readQuestionFile"><n-button>选择题库文件</n-button></n-upload><n-input v-model:value="importForm.json_data" type="textarea" :rows="12" placeholder="粘贴 JSON 数据" class="mt-8" /></n-form-item><n-form-item label="答案 JSON（可选）"><n-upload :show-file-list="false" accept=".json" @before-upload="readAnswerFile"><n-button>选择答案文件</n-button></n-upload></n-form-item><n-grid :cols="3" :x-gap="12"><n-form-item-gi label="学段"><n-select v-model:value="importForm.education_level" clearable :options="levelOptions" /></n-form-item-gi><n-form-item-gi label="科目覆盖"><n-input v-model:value="importForm.subject" /></n-form-item-gi><n-form-item-gi label="分类覆盖"><n-select v-model:value="importForm.category" clearable :options="categoryOptions" /></n-form-item-gi></n-grid></n-form>
      <template #footer><n-space justify="end"><n-button @click="showImport = false">取消</n-button><n-button type="primary" :loading="importing" @click="doImport">开始导入</n-button></n-space></template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { NButton, NSelect, NSpace } from 'naive-ui'
import { phpQuestionsApi } from '@/api/php-modules'
import { sanitizeHtml, renderMathInHtml } from '@/views/user/components/question-utils'

const message = window.$message
const dialog = window.$dialog
const questions = ref([])
const selectedIds = ref([])
const expandedRowKeys = ref([])
const loading = ref(false)
const saving = ref(false)
const importing = ref(false)
const showEditor = ref(false)
const showImport = ref(false)
const editingId = ref(null)
const contentMode = ref('preview')
const stats = ref({ total: 0, by_subject: [], by_type: [] })
const filters = reactive({ education_level: '', subject: '', qtype: '', keyword: '' })
const pagination = reactive({ page: 1, pageSize: 10, itemCount: 0, showSizePicker: true, pageSizes: [10, 20, 50] })
const subjects = ref([])
const form = reactive(defaultForm())
const importForm = reactive({ json_data: '', education_level: '', category: '', subject: '', answerData: null })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const typeOptions = [{ label: '单选题', value: 'single' }, { label: '多选题', value: 'multiple' }, { label: '判断题', value: 'judge' }, { label: '填空题', value: 'fill' }, { label: '多空填空题', value: 'multi_fill' }, { label: '简答题', value: 'short' }]
const categoryOptions = typeOptions
const subjectOptions = computed(() => subjects.value.map(item => ({ label: item, value: item })))
const statCards = computed(() => [{ label: '题目总数', value: stats.value.total || 0 }, { label: '科目数', value: stats.value.by_subject?.length || 0 }, { label: '题型数', value: stats.value.by_type?.length || 0 }, { label: '当前页', value: questions.value.length }])
const previewContent = computed(() => {
  const content = form.content || ''
  if (!content.trim()) return '<span class="question-placeholder">暂无题干内容</span>'
  return renderMathInHtml(sanitizeHtml(content))
})
const rules = { subject: { required: true, message: '请输入科目' }, education_level: { required: true, message: '请选择学段' }, content: { required: true, message: '请输入题干' }, correct_answer: { required: true, message: '请输入答案' } }
const columns = [{ type: 'selection' }, { title: '展开', key: 'expand', width: 60, render: row => h(NButton, { size: 'small', quaternary: true, onClick: () => toggleExpand(row.id) }, { default: () => expandedRowKeys.value.includes(row.id) ? '收起' : '展开' }) }, { title: 'ID', key: 'id', width: 70 }, { title: '科目', key: 'subject', width: 100 }, { title: '学段', key: 'education_level', render: row => row.education_level === 'senior' ? '高中' : '初中', width: 80 }, { title: '题型', key: 'question_type', render: row => labelOf(row.question_type), width: 100 }, { title: '题干摘要', key: 'content', width: 560, render: row => h('span', { class: 'question-summary', title: plainSummary(row.content, 240) }, plainSummary(row.content, 120)) }, { title: '难度', key: 'difficulty', width: 75 }, { title: '分值', key: 'points', width: 70 }, { title: '操作', key: 'actions', width: 150, render: row => h(NSpace, null, { default: () => [h(NButton, { size: 'small', onClick: () => openEdit(row.id) }, { default: () => '查看/编辑' }), h(NButton, { size: 'small', type: 'error', onClick: () => removeQuestion(row.id) }, { default: () => '删除' })] }) }]

function defaultForm() { return { subject: '', education_level: 'junior', category: 'single', question_type: 'single', content: '', options: ['', '', '', ''], correct_answer: '', explanation: '', difficulty: 3, points: 1 } }
function labelOf(type) { return typeOptions.find(item => item.value === type)?.label || type || '-' }
function toggleExpand(id) {
  const idx = expandedRowKeys.value.indexOf(id)
  if (idx >= 0) expandedRowKeys.value.splice(idx, 1)
  else expandedRowKeys.value.push(id)
}
function renderFullContent(value) {
  const content = String(value || '')
  if (!content.trim()) return '<span style="color:var(--n-text-color-3)">暂无内容</span>'
  return renderMathInHtml(sanitizeHtml(content))
}
function plainSummary(value, limit = 120) {
  const source = String(value || '')
  const wrapper = document.createElement('div')
  if (/<[a-z][\s\S]*>/i.test(source)) wrapper.innerHTML = source
  else wrapper.textContent = source
  wrapper.querySelectorAll('img, script, style, svg').forEach(node => node.remove())
  const text = (wrapper.textContent || wrapper.innerText || '').replace(/\s+/g, ' ').trim()
  return [...text].slice(0, limit).join('') + ([...text].length > limit ? '…' : '')
}
function syncOptions(type) { if (type === 'fill' || type === 'short') form.options = [] ; else if (!form.options?.length) form.options = ['', '', '', ''] }
async function loadStats() { try { stats.value = (await phpQuestionsApi.stats()).data || stats.value } catch (error) { message.error(error.message) } }
async function reloadSubjects() { try { subjects.value = (await phpQuestionsApi.subjects({ education_level: filters.education_level })).data?.subjects || [] } catch (error) { message.error(error.message) }; await loadQuestions() }
async function loadQuestions() { loading.value = true; try { const result = await phpQuestionsApi.list({ ...filters, page: pagination.page, page_size: pagination.pageSize, scope: 'manage_all' }); const data = result.data || {}; questions.value = data.items || []; pagination.itemCount = Number(data.total) || 0; subjects.value = data.subjects || subjects.value } catch (error) { message.error(error.message) } finally { loading.value = false } }
function handlePageChange(page) { pagination.page = page; loadQuestions() }
function handlePageSizeChange(size) { pagination.pageSize = size; pagination.page = 1; loadQuestions() }
function openCreate() { Object.assign(form, defaultForm()); editingId.value = null; contentMode.value = 'source'; showEditor.value = true }
async function openEdit(id) { try { const result = await phpQuestionsApi.get(id); const q = result.data.question; Object.assign(form, { ...defaultForm(), ...q, options: Array.isArray(q.options) ? q.options.map(item => String(item).replace(/^[A-Z]\.\s*/, '')) : [] }); editingId.value = id; contentMode.value = 'preview'; showEditor.value = true } catch (error) { message.error(error.message) } }
async function saveQuestion() { saving.value = true; try { const payload = { ...form, id: editingId.value || undefined, options: JSON.stringify((form.question_type === 'fill' || form.question_type === 'short') ? [] : form.options.filter(Boolean)) }; await (editingId.value ? phpQuestionsApi.update(payload) : phpQuestionsApi.add(payload)); message.success('题目已保存'); showEditor.value = false; await Promise.all([loadQuestions(), loadStats()]) } catch (error) { message.error(error.message) } finally { saving.value = false } }
function removeQuestion(id) { dialog.warning({ title: '删除题目', content: '删除后会同步清理试卷关联，确定继续吗？', positiveText: '删除', negativeText: '取消', onPositiveClick: async () => { try { await phpQuestionsApi.remove(id); message.success('已删除'); await Promise.all([loadQuestions(), loadStats()]) } catch (error) { message.error(error.message) } } }) }
async function bulkCategory() { const category = await chooseValue('修改分类', categoryOptions); if (!category) return; try { await phpQuestionsApi.bulkCategory({ ids: selectedIds.value, category }); selectedIds.value = []; message.success('批量修改成功'); await loadQuestions() } catch (error) { message.error(error.message) } }
async function bulkMove() { const education_level = await chooseValue('移动到学段', levelOptions); if (!education_level) return; try { await phpQuestionsApi.bulkMove({ ids: selectedIds.value, education_level }); selectedIds.value = []; message.success('批量移动成功'); await loadQuestions() } catch (error) { message.error(error.message) } }
function bulkDelete() { dialog.warning({ title: '批量删除', content: `将删除 ${selectedIds.value.length} 道题目及关联记录，确定继续吗？`, positiveText: '删除', negativeText: '取消', onPositiveClick: async () => { try { await phpQuestionsApi.bulkRemove({ ids: selectedIds.value }); selectedIds.value = []; message.success('批量删除成功'); await Promise.all([loadQuestions(), loadStats()]) } catch (error) { message.error(error.message) } } }) }
async function chooseValue(title, options) { let selected = ''; const confirmed = await new Promise(resolve => dialog.create({ title, content: () => h(NSelect, { value: selected, options, placeholder: '请选择', 'onUpdate:value': value => { selected = value } }), positiveText: '确定', negativeText: '取消', onPositiveClick: () => resolve(true), onNegativeClick: () => resolve(false) })); return confirmed ? selected : '' }
async function readQuestionFile({ file }) { importForm.json_data = await file.file.text(); return false }
async function readAnswerFile({ file }) { try { importForm.answerData = JSON.parse(await file.file.text()); message.success('答案文件已读取') } catch { message.error('答案 JSON 格式错误') }; return false }
function mergeAnswers(data) { if (!importForm.answerData) return data; const answers = Array.isArray(importForm.answerData) ? importForm.answerData : importForm.answerData.questions || []; const map = Object.fromEntries(answers.map(item => [item.question_id ?? item.questionId ?? item.id, item.answer || item.correct_answer || ''])); const list = Array.isArray(data) ? data : data.questions || []; list.forEach(item => { const id = item.question_id ?? item.questionId ?? item.id; if (id != null && !item.answer && map[id]) item.answer = map[id] }); return Array.isArray(data) ? list : { ...data, questions: list } }
async function doImport() { if (!importForm.json_data.trim()) return message.warning('请先提供题库 JSON'); importing.value = true; try { let data = JSON.parse(importForm.json_data); data = mergeAnswers(data); const result = await phpQuestionsApi.importOcr({ json_data: JSON.stringify(data), education_level: importForm.education_level, category: importForm.category, subject: importForm.subject }); message.success(result.message || '导入完成'); showImport.value = false; Object.assign(importForm, { json_data: '', education_level: '', category: '', subject: '', answerData: null }); await Promise.all([loadQuestions(), loadStats()]) } catch (error) { message.error(error.message || 'JSON 格式错误') } finally { importing.value = false } }
onMounted(async () => { await Promise.all([loadStats(), reloadSubjects()]); await loadQuestions() })
</script>
<style scoped>
.mb-16 { margin-bottom: 16px; }
.mt-8 { margin-top: 8px; }
.question-summary { display: block; white-space: normal; line-height: 1.55; overflow-wrap: anywhere; word-break: break-word; }
.question-preview { min-height: 150px; max-height: 360px; overflow: auto; padding: 12px; border: 1px solid var(--n-border-color); border-radius: 4px; line-height: 1.7; overflow-wrap: anywhere; word-break: break-word; }
.question-preview :deep(img) { display: block; max-width: 100%; height: auto; max-height: 320px; object-fit: contain; margin: 8px 0; }
.question-preview :deep(table) { max-width: 100%; overflow: auto; border-collapse: collapse; }
.question-preview :deep(td), .question-preview :deep(th) { border: 1px solid var(--n-border-color); padding: 4px 8px; }
.question-placeholder { color: var(--n-text-color-3); }
.expanded-content { padding: 12px 20px; background: var(--n-color-fill-soft); border-radius: 4px; }
.expanded-section { margin-bottom: 12px; }
.expanded-section:last-child { margin-bottom: 0; }
.expanded-label { font-size: 12px; color: var(--n-text-color-3); margin-bottom: 6px; font-weight: 500; }
.question-preview--inline { min-height: auto; max-height: none; padding: 10px 12px; background: var(--n-color); }
</style>
