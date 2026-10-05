import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_PHP_API_URL || '/api.php',
  timeout: 30000,
  withCredentials: true,
})

let csrfToken = ''

function appendValue(form, key, value) {
  if (value === undefined || value === null) return
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
  if (includeCsrf && csrfToken) form.append('csrf_token', csrfToken)
  Object.entries(payload).forEach(([key, value]) => appendValue(form, key, value))
  return form
}

api.interceptors.response.use((response) => {
  const body = response.data
  if (body && typeof body === 'object' && body.csrf_token) csrfToken = body.csrf_token
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
})

export async function phpAction(action, payload = {}, options = {}) {
  const method = (options.method || 'POST').toUpperCase()
  const bypassCsrf = options.bypassCsrf === true
  const timeout = options.timeout || 30000
  if (method === 'GET') {
    const response = await api.get('', {
      params: { action, ...payload },
      timeout,
    })
    return response
  }
  return api.post('', toFormData(action, payload, !bypassCsrf), { timeout })
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
