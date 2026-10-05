<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="PDF / 图片智能识别" segmented>
        <n-alert type="info">上传后由服务端 OCR 处理，管理员审核、编辑或剔除题目后再统一入库。</n-alert>
        <n-form inline class="upload-form">
          <n-form-item label="文件"><n-upload :show-file-list="false" accept=".pdf,.jpg,.jpeg,.png,.webp,.bmp,.tiff" :custom-request="upload"><n-button type="primary">选择 PDF / 图片</n-button></n-upload></n-form-item>
          <n-form-item label="学段"><n-select v-model:value="form.education_level" :options="levels" style="width:130px" /></n-form-item>
          <n-form-item label="科目"><n-input v-model:value="form.subject" placeholder="可选" /></n-form-item>
        </n-form>
      </n-card>
      <n-card title="识别批次" segmented>
        <n-data-table :columns="batchColumns" :data="batches" :loading="loading" :pagination="paginationProps" @update:page="onPageChange" @update:page-size="onPageSizeChange" />
      </n-card>
      <n-card v-if="current" ref="reviewCard" :title="`审核：${current.file_name}`" segmented class="review-card">
        <template #header-extra><n-space><n-button @click="selectAll(true)">全部保留</n-button><n-button @click="selectAll(false)">全部剔除</n-button><n-button type="primary" :loading="committing" @click="commit">统一入库</n-button></n-space></template>
        <n-alert v-if="current.error_message" type="error">
          <template #icon>⚠️</template>
          <div class="ocr-error-detail">
            <div class="ocr-error-title">OCR 识别失败</div>
            <div class="ocr-error-msg">{{ prettyOcrError(current.error_message) }}</div>
            <details class="ocr-error-raw"><summary>查看原始错误</summary><pre>{{ current.error_message }}</pre></details>
          </div>
        </n-alert>
        <n-empty v-if="!questions.length" description="暂无识别题目，请等待 OCR 完成或检查错误信息" />
        <n-list v-else bordered>
          <n-list-item v-for="(question, index) in questions" :key="question.source_qid || index">
            <n-card size="small" :class="{ excluded: !question.included }">
              <template #header><n-space align="center"><n-checkbox v-model:checked="question.included">保留第 {{ index + 1 }} 题</n-checkbox><n-tag>{{ question.question_type || '题目' }}</n-tag></n-space></template>
              <div v-for="(image, imageIndex) in question.images || []" :key="imageIndex"><img v-if="image.base64" class="question-image" :src="image.base64.startsWith('data:') ? image.base64 : `data:image/jpeg;base64,${image.base64}`" alt="题目图片"></div>
              <n-form label-placement="top" class="edit-form">
                <n-form-item label="题干">
                  <n-input
                    v-if="editingStem === index"
                    :ref="el => stemRefs[index] = el"
                    v-model:value="question.content"
                    type="textarea"
                    :rows="3"
                    @blur="editingStem = null"
                  />
                  <div v-else class="stem-rendered" v-html="safeHtml(question.content)" @click="editStem(index)" />
                </n-form-item>
                <n-form-item label="选项">
                  <div class="option-edit-list">
                    <div v-for="(option, optionIndex) in question.options" :key="optionIndex" class="option-edit-row">
                      <span class="option-letter">{{ letter(optionIndex) }}.</span>
                      <n-input
                        v-if="editingOption === `${index}-${optionIndex}`"
                        :ref="el => optionRefs[`${index}-${optionIndex}`] = el"
                        class="option-input"
                        :value="option"
                        @update:value="v => { question.options[optionIndex] = v }"
                        @blur="editingOption = null"
                        @keyup.enter="editingOption = null"
                      />
                      <span v-else class="option-rendered" v-html="safeOption(option)" @click="editOption(index, optionIndex)" />
                      <button type="button" class="opt-btn del" @click="question.options.splice(optionIndex, 1)" title="删除">−</button>
                      <button type="button" class="opt-btn add" @click="question.options.splice(optionIndex + 1, 0, '')" title="新增">+</button>
                    </div>
                    <n-button size="small" @click="question.options.push('')">添加选项</n-button>
                  </div>
                </n-form-item>
                <n-grid :cols="3" :x-gap="12"><n-form-item-gi label="答案"><n-input v-model:value="question.correct_answer" /></n-form-item-gi><n-form-item-gi label="分值"><n-input-number v-model:value="question.points" :min="0" /></n-form-item-gi><n-form-item-gi label="难度"><n-input-number v-model:value="question.difficulty" :min="1" :max="5" /></n-form-item-gi></n-grid>
                <n-form-item label="解析"><n-input v-model:value="question.explanation" type="textarea" :rows="2" /></n-form-item>
              </n-form>
            </n-card>
          </n-list-item>
        </n-list>
      </n-card>
    </n-space>
  </AppPage>
</template>

<script setup>
import { h, nextTick } from 'vue'
import { phpOcrApi } from '@/api/php-modules'
import { renderMathInHtml, renderOption, sanitizeHtml } from '@/views/user/components/question-utils'

const message = window.$message
const loading = ref(false)
const committing = ref(false)
const batches = ref([])
const current = ref(null)
const reviewCard = ref(null)
const questions = ref([])
const form = reactive({ education_level: 'junior', subject: '' })
const levels = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const page = ref(1)
const pageSize = ref(20)
const paginationProps = computed(() => ({
  page: page.value,
  pageSize: pageSize.value,
  itemCount: totalCount.value,
  showSizePicker: true,
  pageSizes: [10, 20, 50],
}))
const totalCount = ref(0)
const statusMap = { running: '处理中', queued: '排队中', review: '待审核', failed: '失败', committed: '已入库' }
const batchColumns = [
  { title: '文件名', key: 'file_name', ellipsis: { tooltip: true } },
  { title: '状态', key: 'status', width: 100, render: row => h('span', { class: `status-${row.status}` }, statusMap[row.status] || row.status) },
  { title: '题目数', key: 'question_count', width: 90 },
  { title: '错误', key: 'error_message', ellipsis: { tooltip: true }, render: row => row.error_message ? h('span', { style: 'color: var(--n-error-color)' }, prettyOcrError(row.error_message)) : '-' },
  { title: '操作', key: 'actions', width: 220, render: row => h('div', { class: 'actions' }, [h('button', { class: 'action-button primary', onClick: () => loadBatch(row.id) }, '审核'), h('button', { class: 'action-button danger', onClick: () => removeBatch(row.id) }, '删除')]) },
]
function letter(index) { return String.fromCharCode(65 + (index || 0)) }
function safeHtml(value) { return renderMathInHtml(sanitizeHtml(value || '')) }
function safeOption(value) { return renderOption(value) }

// 美化 OCR 错误信息，从原始 JSON/长文本中提取关键消息
function prettyOcrError(raw) {
  if (!raw) return ''
  const text = String(raw)
  // 提取 Code: 'xxx' 中的错误码
  const codeMatch = text.match(/['"]?Code['"]?\s*[:=]\s*['"]([^'"]+)['"]/i)
  // 提取 Message: 'xxx' 中的消息
  const msgMatch = text.match(/['"]?Message['"]?\s*[:=]\s*['"]([^'"]+)['"]/i)
  // 提取 OcrServiceExpired 等常见错误码
  const shortCode = codeMatch?.[1] || text.match(/OcrService\w+/)?.[0] || ''
  const message = msgMatch?.[1] || ''

  if (shortCode === 'OcrServiceExpired' || message.includes('expired')) {
    return 'OCR 服务已过期，请检查阿里云 OCR 服务状态并续费。'
  }
  if (shortCode === 'InvalidAccessKeyId' || message.includes('AccessKey')) {
    return 'OCR 密钥无效，请检查 AccessKey 配置。'
  }
  if (shortCode === 'InvalidImageFormat' || text.includes('InvalidImageFormat')) {
    return '图片格式不支持，请使用 JPG/PNG/PDF 等常见格式。'
  }
  if (message) return message
  // 兜底：截断长文本
  return text.length > 120 ? text.slice(0, 120) + '…' : text
}
const editingStem = ref(null)
const editingOption = ref(null)
const stemRefs = ref({})
const optionRefs = ref({})
function editStem(qIndex) { editingStem.value = qIndex; nextTick(() => stemRefs.value[qIndex]?.focus?.()) }
function editOption(qIndex, oIndex) { const key = `${qIndex}-${oIndex}`; editingOption.value = key; nextTick(() => optionRefs.value[key]?.focus?.()) }
function cleanOptionText(value) {
  let text = String(value ?? '').trim()
  // 选项中 $$ 仅为分隔符，全部删除
  text = text.replace(/\$\$/g, '')
  text = text.replace(/^\s*[A-Za-zＡ-Ｄ]\s*[.．、)）:：]\s*/, '')
  return text.replace(/\s+/g, ' ').trim()
}
function normalizeQuestion(question) {
  const source = String(question.content || '').replace(/\r/g, '')
  const hasOptions = Array.isArray(question.options) && question.options.length > 0

  if (!hasOptions) {
    // 只有当 options 为空时，才尝试从题干里剥离选项
    const marker = source.search(/(?<![A-Za-zＡ-Ｚ])[A-DＡ-Ｄ]\s*[.．、)）:：]\s*/)
    if (marker >= 0) {
      let prefix = source.slice(0, marker).trim()
      prefix = prefix.replace(/\$\$\s*$/, '').trim()
      const tail = source.slice(marker).trim()
      const parts = tail.split(/(?=\s*[A-DＡ-Ｄ]\s*[.．、)）:：]\s*)/i).map(item => item.trim()).filter(Boolean)
      if (parts.length >= 2) {
        question.content = prefix
        question.options = parts
      }
    }
  }
  // 如果 options 已经有值了，绝不从题干里再剥（避免张冠李戴）

  question.options = Array.isArray(question.options)
    ? question.options.map(item => cleanOptionText(item)).filter(Boolean)
    : []
  question.included = question.included !== false
  return question
}
async function upload({ file }) {
  try {
    const result = await phpOcrApi.create({ file: file.file, education_level: form.education_level, subject: form.subject })
    message.success(result.message || '已上传，等待 OCR')
    await loadBatches()
  } catch (error) { message.error(error.message || '上传失败') }
}
async function loadBatches() {
  loading.value = true
  try {
    const result = await phpOcrApi.list({ page: page.value, page_size: pageSize.value })
    batches.value = result.data?.items || []
    totalCount.value = result.data?.pagination?.total || 0
  } catch (error) { message.error(error.message) }
  finally { loading.value = false }
}
function onPageChange(p) { page.value = p; loadBatches() }
function onPageSizeChange(s) { pageSize.value = s; page.value = 1; loadBatches() }
async function loadBatch(id) {
  try { const result = await phpOcrApi.get(id); current.value = result.data?.batch || null; questions.value = (current.value?.result?.questions || []).map(normalizeQuestion); await nextTick(); reviewCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' }) } catch (error) { message.error(error.message) }
}
function selectAll(value) { questions.value.forEach(question => { question.included = value }) }
async function commit() {
  if (!current.value?.id) return
  committing.value = true
  try { const result = await phpOcrApi.commit({ batch_id: current.value.id, result_json: JSON.stringify({ ...current.value.result, questions: questions.value }) }); message.success(result.message || '已入库'); await loadBatches(); await loadBatch(current.value.id) } catch (error) { message.error(error.message) } finally { committing.value = false }
}
async function removeBatch(id) { try { await phpOcrApi.remove(id); message.success('批次已删除'); await loadBatches() } catch (error) { message.error(error.message) } }
onMounted(loadBatches)
</script>

<style scoped>
.upload-form { margin-top: 16px; }
.ocr-error-detail .ocr-error-title { font-weight: 600; margin-bottom: 4px; }
.ocr-error-detail .ocr-error-msg { line-height: 1.6; }
.ocr-error-raw { margin-top: 8px; }
.ocr-error-raw summary { cursor: pointer; font-size: 12px; opacity: 0.7; }
.ocr-error-raw pre { margin: 8px 0 0; padding: 10px; background: rgba(0,0,0,0.06); border-radius: 4px; font-size: 12px; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto; }.review-card { scroll-margin-top: 20px; }.excluded { opacity: .45; }.preview { padding: 10px 0; line-height: 1.7; white-space: pre-wrap; }.preview :deep(img) { max-width: 100%; max-height: 360px; }.option-edit-list { display: flex; flex-direction: column; gap: 8px; width: 100%; }.option-edit-row { display: flex; align-items: center; gap: 8px; }.option-letter { font-weight: 600; flex-shrink: 0; min-width: 22px; }.option-input { flex: 1; min-width: 0; }.option-rendered { flex: 1; min-height: 22px; line-height: 1.6; padding: 4px 8px; cursor: text; border: 1px dashed transparent; border-radius: 4px; word-break: break-word; }.option-rendered:hover { border-color: var(--n-border-color); background: var(--n-color-hover); }.stem-rendered { min-height: 60px; line-height: 1.7; padding: 8px 10px; cursor: text; border: 1px dashed transparent; border-radius: 4px; white-space: pre-wrap; word-break: break-word; }.stem-rendered:hover { border-color: var(--n-border-color); background: var(--n-color-hover); }.opt-btn { width: 30px; height: 30px; border: 1px solid var(--n-border-color); background: var(--n-color); color: var(--n-text-color); border-radius: 4px; cursor: pointer; font-size: 16px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }.opt-btn.del:hover { color: #fff; background: var(--n-error-color); border-color: var(--n-error-color); }.opt-btn.add:hover { color: #fff; background: var(--n-primary-color); border-color: var(--n-primary-color); }.question-image { display: block; max-width: 100%; max-height: 360px; margin: 8px 0; }.edit-form { margin-top: 12px; }.actions { display: flex; gap: 8px; }.action-button { border: 1px solid var(--n-border-color); background: var(--n-color); color: var(--n-text-color); padding: 5px 10px; border-radius: 4px; cursor: pointer; }.action-button.primary { color: #fff; background: var(--n-primary-color); border-color: var(--n-primary-color); }.action-button.danger { color: #fff; background: var(--n-error-color); border-color: var(--n-error-color); }
</style>
