import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_PHP_API_URL || '/api.php',
  timeout: 30000,
  withCredentials: true,
})

// 大文件入口：与普通接口分离，Nginx 单独放宽到 50MB
const bigApi = axios.create({
  baseURL: import.meta.env.VITE_UPLOAD_URL || '/upload.php',
  timeout: 180000,
  withCredentials: true,
})

// 只有这些 action 走大文件入口
const BIG_UPLOAD_ACTIONS = new Set([
  'ocr_batch_create',
  'ocr_batch_import_json',
  'ocr_batch_commit',
  'import_questions_json',
  'import_special_questions_json',
  'material_upload',
  'material_extract',
])

let csrfToken = ''

function appendValue(form, key, value) {
  if (value === undefined || value === null)
    return
  if (typeof Blob !== 'undefined' && value instanceof Blob) {
    form.append(key, value)
    return
  }
  if (typeof value === 'object') {
    form.append(key, JSON.stringify(value))
    return
  }
  form.append(key, String(value))
}

function toFormData(action, payload = {}, includeCsrf = true) {
  const form = new FormData()
  form.append('action', action)
  if (includeCsrf && csrfToken)
    form.append('csrf_token', csrfToken)
  Object.entries(payload).forEach(([key, value]) => appendValue(form, key, value))
  return form
}

function installResponseInterceptors(client) {
  client.interceptors.response.use(
    (response) => {
      const body = response.data
      if (body && typeof body === 'object' && body.csrf_token)
        csrfToken = body.csrf_token
      if (body && typeof body === 'object' && 'success' in body) {
        if (!body.success) {
          const error = new Error(body.message || '请求失败')
          error.response = response
          error.legacy = body
          throw error
        }
        return body
      }
      return body
    },
    (error) => {
      if (error.response?.status === 413) {
        const data = error.response.data
        error.message = (data && typeof data === 'object' && data.message)
          ? data.message
          : '文件超过服务器上传上限（普通接口 2MB，题库/资料/OCR 为 50MB），请压缩或分批提交'
      }
      return Promise.reject(error)
    },
  )
}
installResponseInterceptors(api)
installResponseInterceptors(bigApi)

export async function phpAction(action, payload = {}, options = {}) {
  const method = (options.method || 'POST').toUpperCase()
  const bypassCsrf = options.bypassCsrf === true
  const timeout = options.timeout || (BIG_UPLOAD_ACTIONS.has(action) ? 180000 : 30000)
  // 大文件类 action 走 /upload.php，其余仍走 /api.php
  const client = BIG_UPLOAD_ACTIONS.has(action) ? bigApi : api
  if (method === 'GET') {
    const response = await client.get('', {
      params: { action, ...payload },
      timeout,
    })
    return response
  }
  return client.post('', toFormData(action, payload, !bypassCsrf), { timeout })
}

export function getCaptchaUrl() {
  return `${import.meta.env.VITE_PHP_API_URL || '/api.php'}?action=captcha&t=${Date.now()}`
}

export function clearPhpSessionState() {
  csrfToken = ''
}

export function getCsrfToken() {
  return csrfToken
}

export default phpAction
