import renderMathInElement from 'katex/dist/contrib/auto-render.mjs'
import 'katex/dist/katex.min.css'

export const questionTypeLabels = {
  single: '单选题',
  multiple: '多选题',
  multi: '多选题',
  judge: '判断题',
  fill: '填空题',
  multi_fill: '多空填空题',
  short: '简答题',
}

export function questionTypeLabel(type) {
  return questionTypeLabels[type] || type || '题目'
}

export function isMultipleQuestion(question) {
  return question?.question_type === 'multi' || question?.question_type === 'multiple'
}

export function blankCount(question) {
  const matches = String(question?.content || '').match(/\{\{\s*\d+\s*\}\}/g)
  return Math.max(matches?.length || 0, 1)
}

export function normalizeAnswer(question, value) {
  const type = question?.question_type
  if (value === null || value === undefined || value === '') {
    if (isMultipleQuestion(question))
      return []
    if (type === 'multi_fill')
      return Array.from({ length: blankCount(question) }).fill('')
    return ''
  }
  if (typeof value === 'string' && (isMultipleQuestion(question) || type === 'multi_fill' || type === 'fill')) {
    try {
      const parsed = JSON.parse(value)
      if (Array.isArray(parsed))
        return parsed
    }
    catch {}
  }
  if (isMultipleQuestion(question) && !Array.isArray(value))
    return [value]
  if (type === 'multi_fill' && !Array.isArray(value))
    return Array.from({ length: blankCount(question) }).fill('')
  return value
}

export function answerIsFilled(question, answers) {
  const value = answers?.[question?.id]
  if (Array.isArray(value))
    return value.some(item => String(item ?? '').trim() !== '')
  return String(value ?? '').trim() !== ''
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll('\'', '&#039;')
}

// 公式定界符：$$ / 单 $ / \( \) / \[ \]（$$ 必须排在单 $ 之前，优先匹配）
const MATH_DELIMITER_RE = /\$\$|\$|\\\(|\\\[/

// 数学文本归一化：
// 1) \xlongequal 转为 KaTeX 支持的原语；
// 2) KaTeX 的 $ 定界默认不接受内侧空白（如 "$ x^{2} $" 不会渲染），将这类成对的"带空格 $" 改写为 \( \)
function normalizeMathText(text) {
  let source = normalizeLatex(String(text ?? ''))
  // 先保护 $$...$$ 块（其内部不参与单 $ 配对）
  const blocks = []
  source = source.replace(/\$\$([^$]*)\$\$/g, (_, inner) => `\uE000${blocks.push(inner) - 1}\uE001`)
  // 成对单 $ 统一改写为 \( \)，并去掉内侧空白：
  // 既兼容普通 $x$，也修复 KaTeX 默认不渲染的 "$ x^{2} $"（定界符内侧紧邻空格）
  source = source.replace(/\$([^$]+)\$/g, (_, inner) => `\\(${inner.trim()}\\)`)
  // 还原 $$ 块
  source = source.replace(/\uE000(\d+)\uE001/g, (_, i) => `$$${blocks[Number(i)]}$$`)
  return source
}

// KaTeX 不支持 \xlongequal（来自 chemfig/extarrows 语料），在渲染前归一化为等价原语：
// 单参数 \xlongequal{A} → 等号上方写 A；双参数 \xlongequal{A}{B} → 上 A 下 B；空参数即普通等号
function normalizeLatex(text) {
  let source = String(text ?? '')
  if (!source.includes('\\xlongequal'))
    return source
  const wrapText = value => (/[\u4E00-\u9FA5]/.test(value) ? `\\text{${value}}` : value)
  // 双参数（注意先于单参数替换）
  source = source.replace(/\\xlongequal\s*\{([^{}]*)\}\s*\{([^{}]*)\}/g, (_, top, bottom) => `\\mathrel{\\overset{${wrapText(top.trim())}}{\\underset{${wrapText(bottom.trim())}}{=}}}`)
  // 单参数
  source = source.replace(/\\xlongequal\s*\{([^{}]*)\}/g, (_, top) => `\\mathrel{\\overset{${wrapText(top.trim())}}{=}}`)
  return source
}

function wrapBareMath(text) {
  // 已有分隔符则不处理
  if (MATH_DELIMITER_RE.test(text))
    return text
  // 安全版：以数学特征开头，中间填充字符不含 \ ^ _（避免与特征起始符重叠导致回溯）
  // 结构：MIDDLE (FILLER MIDDLE)* FILLER*  ——  O(n)，无灾难性回溯
  // 注意：^ _ 后必须紧跟字母/数字/括号才算上下标（排除填空题"______"这类纯下划线）
  const MIDDLE = '(?:\\\\[a-z]+(?:\\{[^{}]*\\})*|\\^[a-z0-9(]|_[a-z0-9(])'
  const FILLER = '[a-z0-9+\\-*/=()[\\]{}<>.|~\\s]'
  // MIDDLE 必须以反斜杠 / ^ / _ 起始，FILLER 字符类不含这三个起始符，交换字符不会产生新的 MIDDLE 起点
  // eslint-disable-next-line regexp/no-super-linear-backtracking
  const re = new RegExp(`${MIDDLE}(?:${FILLER}*${MIDDLE})*${FILLER}*`, 'gi')
  return text.replace(re, (match) => {
    const trimmed = match.trim()
    if (!trimmed || trimmed.length < 2)
      return match
    return `\\(${trimmed}\\)`
  })
}

export function renderMathInHtml(value) {
  let source = normalizeMathText(String(value ?? ''))
  if (!MATH_DELIMITER_RE.test(source))
    source = wrapBareMath(source)
  if (!MATH_DELIMITER_RE.test(source))
    return source
  const wrapper = document.createElement('div')
  wrapper.innerHTML = source
  try {
    renderMathInElement(wrapper, {
      delimiters: [
        { left: '$$', right: '$$', display: true },
        { left: '\\[', right: '\\]', display: true },
        { left: '\\(', right: '\\)', display: false },
        { left: '$', right: '$', display: false },
      ],
      throwOnError: false,
      strict: 'ignore',
      macros: {
        '\\longequal': '\\stackrel{\\text{#1}}{=}',
        '\\Longrightarrow': '\\Rightarrow',
        '\\longrightarrow': '\\rightarrow',
      },
    })
  }
  catch {
    return source
  }
  return wrapper.innerHTML
}

export function sanitizeHtml(value) {
  const source = String(value ?? '')
  if (!/<[a-z][\s\S]*>/i.test(source))
    return escapeHtml(source)
  const wrapper = document.createElement('div')
  wrapper.innerHTML = source
  wrapper.querySelectorAll('script,style,iframe,form,input,button,object,embed,link,meta,base').forEach(node => node.remove())
  wrapper.querySelectorAll('*').forEach((node) => {
    Array.from(node.attributes).forEach((attribute) => {
      const name = attribute.name.toLowerCase()
      const attrValue = attribute.value.trim().toLowerCase()
      if (name.startsWith('on') || ((name === 'src' || name === 'href') && (attrValue.startsWith('javascript:') || attrValue.startsWith('data:text/html')))) {
        node.removeAttribute(attribute.name)
      }
    })
  })
  return wrapper.innerHTML
}

// 统一的富文本渲染：HTML 题先消毒，纯文本题转义并保留换行，最后渲染公式
export function renderRichText(value) {
  const source = String(value ?? '')
  const isHtml = /<[a-z][\s\S]*>/i.test(source)
  const html = isHtml ? sanitizeHtml(source) : escapeHtml(source).replace(/\n/g, '<br>')
  return renderMathInHtml(html)
}

export function renderQuestionContent(question) {
  return renderRichText(question?.content ?? '')
    .replace(/\{\{\s*(\d+)\s*\}\}/g, (_, number) => `<span class="question-blank">第${number}空</span>`)
}

// 提取纯文本（去掉所有 HTML 标签和多余空白），用于列表摘要显示
export function plainTextSummary(value, limit = 80) {
  const source = String(value || '')
  const wrapper = document.createElement('div')
  wrapper.innerHTML = /<[a-z][\s\S]*>/i.test(source) ? sanitizeHtml(source) : escapeHtml(source)
  const text = wrapper.textContent || ''
  const trimmed = text.replace(/\s+/g, ' ').trim()
  if (trimmed.length <= limit)
    return trimmed
  return `${trimmed.slice(0, limit)}…`
}

function looksLikeMath(value) {
  return /\\(?:times|cdot|left|right|frac|sqrt|sum|pi)|\^[a-z0-9(]|_[a-z0-9(]|(?<![a-z])[xyz]\s*[=+\-]/i.test(String(value || ''))
}

export function renderOption(value) {
  let source = String(value ?? '').trim()
  // 去掉外层 $$（OCR 审核常见形态，内部按行内公式渲染）
  const blockMatch = source.match(/^\$\$([\s\S]*?)\$\$$/)
  if (blockMatch)
    source = blockMatch[1].trim()
  // 归一化公式定界符：成对的 $...$（含内侧带空格的 "$ x $"）转为 \(...\)
  source = normalizeMathText(source)
  // 归一化后若仍残留奇数个 $，说明是孤立美元符号，作为普通字符移除
  if ((source.match(/\$/g) || []).length % 2 !== 0)
    source = source.replace(/\$/g, '')
  // 去掉选项标签：纯文本在开头，HTML 选项常位于元素文本开头（如 <div>A. 文字</div>）；仅剥字母保留文字（含全角 A-Ｄ）
  // eslint-disable-next-line regexp/no-obscure-range
  source = source.replace(/(^|>)\s*[A-Za-zＡ-Ｄ]\s*[.．、)）:：]\s*/g, '$1').trim()
  // 没有分隔符但长得像公式，自动包成行内（已有 \( \) / $ 定界符时跳过，避免嵌套）
  if (!MATH_DELIMITER_RE.test(source) && looksLikeMath(source))
    source = `\\(${source}\\)`
  return renderMathInHtml(sanitizeHtml(source))
}

export function formatAnswer(question, value) {
  if (value === null || value === undefined || value === '')
    return '未作答'
  const parsed = normalizeAnswer(question, value)
  if (Array.isArray(parsed))
    return parsed.length ? parsed.join(' / ') : '未作答'
  if (question?.question_type === 'judge')
    return String(parsed) === 'T' ? '对（T）' : String(parsed) === 'F' ? '错（F）' : String(parsed)
  return String(parsed)
}

// 答案的 HTML 渲染：复用 formatAnswer 的归一化/判断题转换，再做消毒与公式渲染，供 v-html 使用
export function renderAnswer(question, value) {
  const parsed = normalizeAnswer(question, value)
  if (Array.isArray(parsed)) {
    const items = parsed.map(item => String(item ?? '').trim()).filter(Boolean)
    return items.length ? items.map(item => renderMathInHtml(sanitizeHtml(item))).join(' / ') : '未作答'
  }
  const text = formatAnswer(question, value)
  if (text === '未作答')
    return text
  return renderMathInHtml(sanitizeHtml(text))
}

export function parseDbDate(value) {
  if (!value)
    return new Date()
  const text = String(value)
  return new Date(/z|[+-]\d\d:?\d\d$/i.test(text) ? text : `${text.replace(' ', 'T')}Z`)
}

export function formatDate(value) {
  if (!value)
    return '—'
  const date = parseDbDate(value)
  if (Number.isNaN(date.getTime()))
    return String(value)
  return date.toLocaleString('zh-CN', { hour12: false })
}
