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
    if (isMultipleQuestion(question)) return []
    if (type === 'multi_fill') return Array.from({ length: blankCount(question) }, () => '')
    return ''
  }
  if (typeof value === 'string' && (isMultipleQuestion(question) || type === 'multi_fill' || type === 'fill')) {
    try {
      const parsed = JSON.parse(value)
      if (Array.isArray(parsed)) return parsed
    }
    catch {}
  }
  if (isMultipleQuestion(question) && !Array.isArray(value)) return [value]
  if (type === 'multi_fill' && !Array.isArray(value)) return Array.from({ length: blankCount(question) }, () => '')
  return value
}

export function answerIsFilled(question, answers) {
  const value = answers?.[question?.id]
  if (Array.isArray(value)) return value.some(item => String(item ?? '').trim() !== '')
  return String(value ?? '').trim() !== ''
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;')
}

function wrapBareMath(text) {
  // 已有分隔符则不处理
  if (/\$\$|\\\(|\\\[/.test(text)) return text
  // 安全版：以数学特征开头，中间填充字符不含 \ ^ _（避免与特征起始符重叠导致回溯）
  // 结构：MIDDLE (FILLER MIDDLE)* FILLER*  ——  O(n)，无灾难性回溯
  const MIDDLE = '(?:\\\\[a-zA-Z]+(?:\\{[^{}]*\\})*|\\^[\\w{]|_\\w)'
  const FILLER = '[A-Za-z0-9+\\-*/=()\\[\\]{}<>.|~\\s]'
  const re = new RegExp(`${MIDDLE}(?:${FILLER}*${MIDDLE})*${FILLER}*`, 'g')
  return text.replace(re, (match) => {
    const trimmed = match.trim()
    if (!trimmed || trimmed.length < 2) return match
    return `\\(${trimmed}\\)`
  })
}

export function renderMathInHtml(value) {
  let source = String(value ?? '')
  if (!/\$\$|\\\(|\\\[/.test(source)) source = wrapBareMath(source)
  if (!/\$\$|\\\(|\\\[/.test(source)) return source
  const wrapper = document.createElement('div')
  wrapper.innerHTML = source
  try {
    renderMathInElement(wrapper, {
      delimiters: [
        { left: '$$', right: '$$', display: true },
        { left: '\\[', right: '\\]', display: true },
        { left: '\\(', right: '\\)', display: false },
      ],
      throwOnError: false,
      strict: 'ignore',
      macros: {
        '\\longequal': '\\stackrel{\\text{#1}}{=}',
        '\\Longrightarrow': '\\Rightarrow',
        '\\longrightarrow': '\\rightarrow',
      },
    })
  } catch {
    return source
  }
  return wrapper.innerHTML
}

export function sanitizeHtml(value) {
  const source = String(value ?? '')
  if (!/<[a-z][\s\S]*>/i.test(source)) return escapeHtml(source)
  const wrapper = document.createElement('div')
  wrapper.innerHTML = source
  wrapper.querySelectorAll('script,style,iframe,form,input,button,object,embed,link,meta,base').forEach(node => node.remove())
  wrapper.querySelectorAll('*').forEach(node => {
    Array.from(node.attributes).forEach(attribute => {
      const name = attribute.name.toLowerCase()
      const attrValue = attribute.value.trim().toLowerCase()
      if (name.startsWith('on') || ((name === 'src' || name === 'href') && (attrValue.startsWith('javascript:') || attrValue.startsWith('data:text/html')))) {
        node.removeAttribute(attribute.name)
      }
    })
  })
  return wrapper.innerHTML
}

export function renderQuestionContent(question) {
  const content = String(question?.content ?? '')
  const isHtml = question?.is_html || /<[a-z][\s\S]*>/i.test(content)
  const html = isHtml ? sanitizeHtml(content) : escapeHtml(content).replace(/\n/g, '<br>')
  return renderMathInHtml(html).replace(/\{\{\s*(\d+)\s*\}\}/g, (_, number) => `<span class="question-blank">第${number}空</span>`)
}

// 提取纯文本（去掉所有 HTML 标签和多余空白），用于列表摘要显示
export function plainTextSummary(value, limit = 80) {
  const source = String(value || '')
  const wrapper = document.createElement('div')
  wrapper.innerHTML = /<[a-z][\s\S]*>/i.test(source) ? sanitizeHtml(source) : escapeHtml(source)
  const text = wrapper.textContent || wrapper.innerText || ''
  const trimmed = text.replace(/\s+/g, ' ').trim()
  if (trimmed.length <= limit) return trimmed
  return trimmed.slice(0, limit) + '…'
}

function looksLikeMath(value) {
  return /\\(?:times|cdot|left|right|frac|sqrt|sum|pi)|\^|_\s*[\{\\\w]|\b[xyz]\b\s*[=+\-]/i.test(String(value || ''))
}

export function renderOption(value) {
  let source = String(value ?? '').trim()
  // 去掉外层 $$（兼容旧数据）
  const blockMatch = source.match(/^\$\$([\s\S]*?)\$\$$/)
  if (blockMatch) source = blockMatch[1].trim()
  // 选项中 $$ 仅为分隔符，全部删除
  source = source.replace(/\$\$/g, '')
  // 去掉开头的选项标签（兼容旧数据），标签由调用方按索引提供
  source = source.replace(/^\s*[A-Za-zＡ-Ｄ]\s*[.．、)）:：]\s*/, '')
  // 没有分隔符但长得像公式，自动包成行内
  if (!/\\\(|\\\[/.test(source) && looksLikeMath(source)) {
    source = `\\(${source}\\)`
  }
  return renderMathInHtml(sanitizeHtml(source))
}

export function formatAnswer(question, value) {
  if (value === null || value === undefined || value === '') return '未作答'
  const parsed = normalizeAnswer(question, value)
  if (Array.isArray(parsed)) return parsed.length ? parsed.join(' / ') : '未作答'
  if (question?.question_type === 'judge') return String(parsed) === 'T' ? '对（T）' : String(parsed) === 'F' ? '错（F）' : String(parsed)
  return String(parsed)
}

export function parseDbDate(value) {
  if (!value) return new Date()
  const text = String(value)
  return new Date(/[zZ]|[+-]\d\d:?\d\d$/.test(text) ? text : `${text.replace(' ', 'T')}Z`)
}

export function formatDate(value) {
  if (!value) return '—'
  const date = parseDbDate(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('zh-CN', { hour12: false })
}
