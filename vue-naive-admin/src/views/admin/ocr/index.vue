<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="智能识别与审核" segmented>
        <n-alert type="info">上传 PDF/图片由服务端 OCR 处理后进入审核，或直接导入已有的 JSON 跳过 OCR。两种方式均支持审核编辑后再统一入库。</n-alert>
        <n-tabs v-model:value="activeTab" animated>
          <n-tab-pane name="upload" tab="上传 PDF / 图片">
            <n-form inline class="upload-form">
              <n-form-item label="文件"><n-upload :show-file-list="false" accept=".pdf,.jpg,.jpeg,.png,.webp,.bmp,.tiff" :custom-request="upload"><n-button type="primary">选择 PDF / 图片</n-button></n-upload></n-form-item>
              <n-form-item label="学段"><n-select v-model:value="form.education_level" :options="levels" style="width:130px" /></n-form-item>
              <n-form-item label="科目"><n-select v-model:value="form.subject" :options="ocrSubjectOptions" clearable filterable allow-create style="width:150px" placeholder="必填, 或输入新科目" /></n-form-item>
            </n-form>
          </n-tab-pane>
          <n-tab-pane name="import" tab="导入已有 JSON">
            <n-form inline class="upload-form">
              <n-form-item label="JSON 文件"><n-upload :show-file-list="false" accept=".json" :custom-request="importJson"><n-button type="success">选择 JSON 文件</n-button></n-upload></n-form-item>
              <n-form-item label="试卷名"><n-input v-model:value="importForm.paper_name" placeholder="可选，默认用文件名" /></n-form-item>
              <n-form-item label="学段"><n-select v-model:value="importForm.education_level" :options="levels" style="width:130px" /></n-form-item>
              <n-form-item label="科目"><n-select v-model:value="importForm.subject" :options="ocrSubjectOptions" clearable filterable allow-create style="width:150px" placeholder="可选, 默认综合" /></n-form-item>
            </n-form>
            <n-alert type="default" :show-icon="false" closable class="import-hint">
              支持 Contract B 格式（MinerU pipeline 输出）或审核页格式，系统会自动识别 schema 并转换。
            </n-alert>
          </n-tab-pane>
        </n-tabs>
      </n-card>
      <n-card title="识别批次" segmented>
        <n-data-table :columns="batchColumns" :data="batches" :loading="loading" :pagination="paginationProps" @update:page="onPageChange" @update:page-size="onPageSizeChange" />
      </n-card>
      <n-card v-if="current" ref="reviewCard" :title="`审核：${current.file_name}`" segmented class="review-card">
          <template #header-extra><n-space><n-tooltip trigger="hover"><template #trigger><n-button :disabled="current.status!=='review' && current.status!=='committed'" @click="downloadAIPrompt">📄 下载 AI 提示词 PDF</n-button></template>OCR 完成后可用<template v-if="current.status==='queued' || current.status==='processing'">（当前: {{ current.stage || '处理中' }}）</template></n-tooltip><n-tooltip trigger="hover"><template #trigger><n-button :disabled="current.status!=='review' && current.status!=='committed'" @click="importAIModal = true">📋 批量粘贴导入答案</n-button></template>OCR 完成后可用，将 AI 返回的 JSON 粘贴进来自动填充<template v-if="current.status==='queued' || current.status==='processing'">（当前: {{ current.stage || '处理中' }}）</template></n-tooltip><n-button @click="selectAll(true)">全部保留</n-button><n-button @click="selectAll(false)">全部剔除</n-button><n-button type="primary" :loading="committing" @click="commit">统一入库</n-button></n-space></template>
          <!-- 批次标签编辑: 学段 + 学科 -->
          <n-alert v-if="current" class="batch-meta-alert" type="info" closable :show-icon="true" title="🏷️ 批次标签">
            <n-space align="center">
              <span>学段：</span>
              <n-select v-if="current.status!=='queued' && current.status!=='processing'" v-model:value="currentMeta.education_level" :options="levels" style="width:130px" />
              <n-tag v-else size="medium" bordered>{{ levels.find(l => l.value === currentMeta.education_level)?.label || '-' }}</n-tag>
              <span style="margin-left:12px">学科：</span>
              <n-select v-if="current.status!=='queued' && current.status!=='processing'" v-model:value="currentMeta.subject" :options="fullSubjectOptions" clearable filterable allow-create style="width:160px" placeholder="选或输入" />
              <n-tag v-else size="medium" bordered>{{ currentMeta.subject || '-' }}</n-tag>
              <n-button v-if="current.status!=='queued' && current.status!=='processing'" size="small" :loading="savingMeta" @click="saveMeta">💾 保存标签</n-button>
              <n-tag v-if="savedMetaHint" size="small" type="success" :bordered="false">{{ savedMetaHint }}</n-tag>
            </n-space>
          </n-alert>
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
    <!-- 批量粘贴导入答案模态框 -->
    <n-modal v-model:show="importAIModal" preset="card" title="📋 批量粘贴 AI 答案" style="width: 640px" :mask-closable="false">
      <n-alert type="info" :show-icon="false" class="import-hint">
        把网页版 AI 返回的 <b>JSON</b> 整段复制过来（Ctrl+C），粘贴到下面的文本框（Ctrl+V），点击确定后会自动填充每道题的答案、解析和难度。
      </n-alert>
      <n-input v-model:value="aiJsonText" type="textarea" :rows="14" placeholder='粘贴 AI 返回的 JSON, 格式示例:&#10;{&quot;answers&quot;: [&#10;  {&quot;qid&quot;: 1, &quot;answer&quot;: &quot;C&quot;, &quot;explanation&quot;: &quot;...&quot;, &quot;difficulty&quot;: 3},&#10;  {&quot;qid&quot;: 2, &quot;answer&quot;: &quot;B&quot;, &quot;explanation&quot;: &quot;...&quot;, &quot;difficulty&quot;: 2}&#10;]}' />
      <template #action>
        <n-space justify="end">
          <n-button @click="importAIModal = false">取消</n-button>
          <n-button type="primary" :loading="importingAI" @click="confirmImportAI">✅ 确定导入</n-button>
        </n-space>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { h, nextTick, onMounted, onUnmounted } from 'vue'
import { phpOcrApi, phpQuestionsApi } from '@/api/php-modules'
import { renderMathInHtml, renderOption, sanitizeHtml } from '@/views/user/components/question-utils'

const message = window.$message
const loading = ref(false)
const committing = ref(false)
const batches = ref([])
const current = ref(null)
const reviewCard = ref(null)
const questions = ref([])
const activeTab = ref('upload')
const form = reactive({ education_level: 'junior', subject: '' })
const importForm = reactive({ paper_name: '', education_level: 'senior', subject: '' })
const levels = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]

// OCR 页面专用科目下拉 (从 questions 表全量枚举 + 常用预设)
const subjects = ref([])
const ocrSubjectOptions = computed(() => {
  const fromDB = subjects.value.map(s => ({ label: s, value: s }))
  const preset = ['语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '道德与法治', '道法', '综合']
  const all = [...preset, ...subjects.value]
  const seen = new Set()
  return all.filter(s => {
    if (seen.has(s)) return false
    seen.add(s); return true
  }).map(s => ({ label: s, value: s }))
})
// 页面加载时拉一次全量科目
phpQuestionsApi.subjects({}).then(r => { subjects.value = r.data?.subjects || [] }).catch(() => {})

// 审核卡片批次标签编辑
const currentMeta = reactive({ education_level: 'junior', subject: '' })
const savingMeta = ref(false)
const savedMetaHint = ref('')
// K12 完整学科下拉 (前端硬编码全部 + DB 动态合并, 比上传页的多一个信息技术)
const fullSubjectOptions = computed(() => {
  const preset = ['语文', '数学', '英语', '物理', '化学', '生物', '历史', '地理', '政治', '道德与法治', '道法', '信息技术', '综合']
  const all = [...preset, ...subjects.value]
  const seen = new Set()
  return all.filter(s => { if (seen.has(s)) return false; seen.add(s); return true })
    .map(s => ({ label: s, value: s }))
})
async function saveMeta() {
  if (!current.value?.id) return
  savingMeta.value = true; savedMetaHint.value = ''
  try {
    await phpOcrApi.setMeta({ batch_id: current.value.id, education_level: currentMeta.education_level, subject: currentMeta.subject })
    savedMetaHint.value = '✓ 已保存'
    setTimeout(() => { savedMetaHint.value = '' }, 2000)
  } catch (error) { message.error(error.message || '保存失败') }
  finally { savingMeta.value = false }
}
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
// ★ 细粒度 OCR 阶段 (对应 bridge_client 发的 PROGRESS 消息)
const stageMap = {
  mineru_starting: '⚡ MinerU 冷启动...',
  mineru_uploading: '📤 MinerU 上传 PDF...',
  mineru_parsing: '🔍 MinerU 解析中',      // 带 progress: "3/10 页"
  mineru_downloading: '📥 下载解析结果...',
  flatten_blocks: '🔧 整理文本块...',
  group_questions: '✂️ 切分题目...',
  validate_questions: '✅ 校验有效题...',  // 带 progress: "识别出 21 道"
  pipeline_running: '🔧 Pipeline 处理中...',  // 兜底 (兼容旧 bridge_client)
  done: '✅ 识别完成',
}
function renderBatchStatus(row) {
  const status = row.status || ''
  const stage = row.stage || ''
  const progress = row.progress || ''
  const qpos = row.queue_position || 0

  // 已完成/终态: 简单显示
  if (['review', 'committed', 'failed'].includes(status)) {
    return h('span', { class: `status-${status}` }, statusMap[status] || status)
  }

  // queued: 显示队列位置
  if (status === 'queued') {
    let text = '排队中'
    if (qpos > 0) text += ` (队列第 ${qpos} 位)`
    return h('span', { class: 'status-queued' }, text)
  }

  // processing/running: 主阶段 + 子进度
  if (status === 'processing' || status === 'running') {
    const stageText = stageMap[stage] || '处理中...'
    if (progress) {
      // 双行显示: 主阶段 + 子进度
      return h('div', { class: 'status-processing' }, [
        h('div', {}, stageText),
        h('div', { class: 'status-progress' }, progress),
      ])
    }
    return h('span', { class: 'status-processing' }, stageText)
  }

  return h('span', statusMap[status] || status)
}
const batchColumns = [
  { title: '文件名', key: 'file_name', ellipsis: { tooltip: true } },
  { title: '状态', key: 'status', width: 180, render: renderBatchStatus },
  { title: '题目数', key: 'question_count', width: 90 },
  { title: '错误', key: 'error_message', ellipsis: { tooltip: true }, render: row => row.error_message ? h('span', { style: 'color: var(--n-error-color)' }, prettyOcrError(row.error_message)) : '-' },
  { title: '操作', key: 'actions', width: 280, render: row => h('div', { class: 'actions' }, [
    h('button', { class: 'action-button primary', onClick: () => loadBatch(row.id) }, '审核'),
    (row.status === 'review' || row.status === 'committed')
      ? h('button', { class: 'action-button', onClick: () => { loadBatch(row.id).then(() => importAIModal.value = true) } }, '📋 导入答案')
      : null,
    h('button', { class: 'action-button danger', onClick: () => removeBatch(row.id) }, '删除')
  ].filter(Boolean)) },
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
  // ★ Pipeline → 前端字段名映射 (Contract B format → UI format)
  if (question.question_content !== undefined && question.content === undefined) {
    question.content = question.question_content
  }
  if (Array.isArray(question.question_options) && question.options === undefined) {
    question.options = question.question_options
  }
  if (question.answer !== undefined && question.correct_answer === undefined) {
    question.correct_answer = question.answer
  }
  if (question.resolve !== undefined && question.explanation === undefined) {
    question.explanation = question.resolve
  }
  // ★ 图片映射: 后端 question_images (base64 字符串数组) → 前端 images ({base64} 对象数组)
  // 后端格式: ["base64str1", "base64str2"]
  // 前端期望: [{base64: "base64str1"}, {base64: "base64str2"}]
  if (Array.isArray(question.question_images) && question.images === undefined) {
    question.images = question.question_images
      .filter(img => img && typeof img === 'string')
      .map(b64 => ({ base64: b64 }))
  }
  // 兜底: 兼容某些接口直接返回 images (对象数组)
  if (!Array.isArray(question.images)) {
    question.images = []
  }

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
async function importJson({ file }) {
  try {
    const result = await phpOcrApi.importJson({ file: file.file, education_level: importForm.education_level, subject: importForm.subject, paper_name: importForm.paper_name })
    message.success(result.message || 'JSON 导入成功')
    await loadBatches()
    // 自动跳转到新 batch 的审核页
    if (result.data?.batch_id) {
      await loadBatch(result.data.batch_id)
    }
  } catch (error) { message.error(error.message || 'JSON 导入失败') }
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
  try {
    const result = await phpOcrApi.get(id)
    current.value = result.data?.batch || null
    // 同步批次标签到可编辑的 currentMeta
    if (current.value) {
      currentMeta.education_level = current.value.education_level || 'junior'
      currentMeta.subject = current.value.subject || ''
      savedMetaHint.value = ''
    }
    questions.value = (current.value?.result?.questions || []).map(normalizeQuestion)
    await nextTick()
    reviewCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  } catch (error) { message.error(error.message) }
}
function selectAll(value) { questions.value.forEach(question => { question.included = value }) }
function downloadAIPrompt() {
  if (!current.value?.id) return
  phpOcrApi.export(current.value.id)
  message.info('PDF 生成中, 浏览器将自动下载...')
}
const importAIModal = ref(false)
const aiJsonText = ref('')
const importingAI = ref(false)
function confirmImportAI() {
  if (!aiJsonText.value.trim()) { message.warning('请先粘贴 JSON'); return }
  importingAI.value = true
  try {
    // 容错: 去掉 JSON 前后的 markdown ```json ... ``` 包裹
    let text = aiJsonText.value.trim()
    const mdMatch = text.match(/```(?:json)?\s*([\s\S]*?)```/)
    if (mdMatch) text = mdMatch[1].trim()

    let parsed
    try { parsed = JSON.parse(text) }
    catch (e) { message.error('JSON 格式错误: ' + e.message); return }

    // 兼容两种格式: {"answers":[...]} 或 直接数组 [{qid:1,...}]
    let answers = parsed?.answers
    if (!Array.isArray(answers) && Array.isArray(parsed)) answers = parsed
    if (!Array.isArray(answers) || !answers.length) { message.error('没找到 answers 数组'); return }

    let matched = 0
    let qidFieldOk = false
    for (const ans of answers) {
      const qid = ans.qid ?? ans.question_id ?? ans.index ?? ans.num
      let target = null
      // 1. 按 source_qid 匹配 (pipeline 输出里的 qid)
      if (qid != null) {
        qidFieldOk = true
        target = questions.value.find(q => String(q.source_qid) === String(qid))
        // 2. 按 index 匹配 (如果上面找不到)
        if (!target && typeof qid === 'number') {
          target = questions.value[qid - 1]
        }
      }
      // 3. 如果 ans 没有 qid, 按顺序依次填充
      if (!target) {
        const idx = answers.indexOf(ans)
        target = questions.value[idx]
      }

      if (target) {
        if (ans.answer != null) target.correct_answer = String(ans.answer).trim()
        if (ans.explanation != null) target.explanation = String(ans.explanation).trim()
        if (ans.difficulty != null) target.difficulty = Math.max(1, Math.min(5, parseInt(ans.difficulty) || 3))
        matched++
      }
    }

    if (matched === 0) {
      message.error('AI 返回的题号和当前试卷不匹配')
    } else {
      message.success(`✅ 已导入 ${matched}/${questions.value.length} 道题的答案`)
      importAIModal.value = false
      aiJsonText.value = ''
    }
  } catch (e) {
    message.error('导入失败: ' + e.message)
  } finally {
    importingAI.value = false
  }
}
async function commit() {
  if (!current.value?.id) return
  committing.value = true
  try { const result = await phpOcrApi.commit({ batch_id: current.value.id, result_json: JSON.stringify({ ...current.value.result, questions: questions.value }) }); message.success(result.message || '已入库'); await loadBatches(); await loadBatch(current.value.id) } catch (error) { message.error(error.message) } finally { committing.value = false }
}
async function removeBatch(id) { try { await phpOcrApi.remove(id); message.success('批次已删除'); await loadBatches() } catch (error) { message.error(error.message) } }

// 自动轮询: 有 queued/processing 状态的批次时, 每 3s 刷新列表
let pollTimer = null
onMounted(async () => {
  await loadBatches()
  pollTimer = setInterval(async () => {
    const needRefresh = batches.value.some(b => b.status === 'queued' || b.status === 'processing' || b.status === 'running')
    if (needRefresh) {
      await loadBatches()
      // 如果正在查看的 current 也在处理, 顺便刷新详情卡片
      if (current.value && (current.value.status === 'queued' || current.value.status === 'processing')) {
        try { await loadBatch(current.value.id) } catch {}
      }
    }
  }, 3000)
})
onUnmounted(() => { if (pollTimer) clearInterval(pollTimer) })
</script>

<style scoped>
.upload-form { margin-top: 16px; }
.ocr-error-detail .ocr-error-title { font-weight: 600; margin-bottom: 4px; }
.ocr-error-detail .ocr-error-msg { line-height: 1.6; }
.ocr-error-raw { margin-top: 8px; }
.ocr-error-raw summary { cursor: pointer; font-size: 12px; opacity: 0.7; }
.ocr-error-raw pre { margin: 8px 0 0; padding: 10px; background: rgba(0,0,0,0.06); border-radius: 4px; font-size: 12px; white-space: pre-wrap; word-break: break-all; max-height: 200px; overflow-y: auto; }.review-card { scroll-margin-top: 20px; }.excluded { opacity: .45; }.preview { padding: 10px 0; line-height: 1.7; white-space: pre-wrap; }.preview :deep(img) { max-width: 100%; max-height: 360px; }.option-edit-list { display: flex; flex-direction: column; gap: 8px; width: 100%; }.option-edit-row { display: flex; align-items: center; gap: 8px; }.option-letter { font-weight: 600; flex-shrink: 0; min-width: 22px; }.option-input { flex: 1; min-width: 0; }.option-rendered { flex: 1; min-height: 22px; line-height: 1.6; padding: 4px 8px; cursor: text; border: 1px dashed transparent; border-radius: 4px; word-break: break-word; }.option-rendered:hover { border-color: var(--n-border-color); background: var(--n-color-hover); }.stem-rendered { min-height: 60px; line-height: 1.7; padding: 8px 10px; cursor: text; border: 1px dashed transparent; border-radius: 4px; white-space: pre-wrap; word-break: break-word; }.stem-rendered:hover { border-color: var(--n-border-color); background: var(--n-color-hover); }.opt-btn { width: 30px; height: 30px; border: 1px solid var(--n-border-color); background: var(--n-color); color: var(--n-text-color); border-radius: 4px; cursor: pointer; font-size: 16px; line-height: 1; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }.opt-btn.del:hover { color: #fff; background: var(--n-error-color); border-color: var(--n-error-color); }.opt-btn.add:hover { color: #fff; background: var(--n-primary-color); border-color: var(--n-primary-color); }.question-image { display: block; max-width: 100%; max-height: 360px; margin: 8px 0; }.edit-form { margin-top: 12px; }.actions { display: flex; gap: 8px; }.action-button { border: 1px solid var(--n-border-color); background: var(--n-color); color: var(--n-text-color); padding: 5px 10px; border-radius: 4px; cursor: pointer; }.action-button.primary { color: #fff; background: var(--n-primary-color); border-color: var(--n-primary-color); }.action-button.danger { color: #fff; background: var(--n-error-color); border-color: var(--n-error-color); }
.status-progress { font-size: 12px; color: var(--n-text-color-3); line-height: 1.4; margin-top: 2px; }
</style>
