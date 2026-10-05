import phpAction, { getCaptchaUrl } from './php'

export const phpAuthApi = {
  login: data => phpAction('login', data, { bypassCsrf: true }),
  register: data => phpAction('register', data, { bypassCsrf: true }),
  logout: () => phpAction('logout'),
  checkSession: () => phpAction('check_session', {}, { bypassCsrf: true }),
  captchaUrl: () => getCaptchaUrl(),
  sendEmailCode: data => phpAction('send_email_code', data, { bypassCsrf: true }),
  resetPassword: data => phpAction('reset_password', data, { bypassCsrf: true }),
}

export const phpVisitApi = {
  record: () => phpAction('record_visit', {}, { bypassCsrf: true }),
}

export const phpPublicApi = {
  overview: () => phpAction('public_overview', {}, { bypassCsrf: true }),
}

export const phpProfileApi = {
  update: data => phpAction('update_profile', data),
  changePassword: data => phpAction('change_password', data),
}

export const phpDashboardApi = {
  get: () => phpAction('get_dashboard'),
}

export const phpQuestionsApi = {
  stats: () => phpAction('question_stats'),
  subjects: data => phpAction('get_subjects', data, { bypassCsrf: true }),
  list: data => phpAction('list_questions', data, { bypassCsrf: true }),
  get: id => phpAction('get_question', { id }, { bypassCsrf: true }),
  add: data => phpAction('add_question', data),
  update: data => phpAction('update_question', data),
  remove: id => phpAction('delete_question', { id }),
  importJson: data => phpAction('import_questions_json', data),
  bulkMove: data => phpAction('bulk_move_questions', data),
  bulkCategory: data => phpAction('bulk_update_question_category', data),
  bulkRemove: data => phpAction('bulk_delete_questions', data),
  importOcr: data => phpAction('import_questions_json', data),
}

export const phpPapersApi = {
  list: data => phpAction('paper_list', data, { bypassCsrf: true }),
  get: id => phpAction('paper_get', { id }, { bypassCsrf: true }),
  save: data => phpAction('paper_save', data),
  publish: paper_id => phpAction('paper_publish', { paper_id }),
  unpublish: paper_id => phpAction('paper_unpublish', { paper_id }),
  remove: paper_id => phpAction('paper_delete', { paper_id }),
}

export const phpExamApi = {
  start: paper_id => phpAction('attempt_start_exam', { paper_id }),
  get: attempt_id => phpAction('attempt_get', { attempt_id }, { bypassCsrf: true }),
  submit: data => phpAction('attempt_submit', data),
  result: attempt_id => phpAction('attempt_result', { attempt_id }, { bypassCsrf: true }),
  selfGrade: data => phpAction('attempt_self_grade', data),
  mine: () => phpAction('attempt_list_mine', {}, { bypassCsrf: true }),
}

export const phpPracticeApi = {
  subjects: data => phpAction('practice_subjects', data, { bypassCsrf: true }),
  start: data => phpAction('practice_start', data),
  submit: data => phpAction('practice_submit', data),
  result: attempt_id => phpAction('practice_result', { attempt_id }, { bypassCsrf: true }),
  selfGrade: data => phpAction('practice_self_grade', data),
}

export const phpMaterialsApi = {
  list: data => phpAction('material_list', data, { bypassCsrf: true }),
  upload: data => phpAction('material_upload', data),
  update: data => phpAction('material_update', data),
  remove: id => phpAction('material_delete', { id }),
  token: id => phpAction('material_get_token', { id }),
  downloadUrl: token => `${import.meta.env.VITE_PHP_API_URL || '/api.php'}?action=material_download&token=${encodeURIComponent(token)}`,
}

export const phpOcrApi = {
  create: data => phpAction('ocr_batch_create', data),
  list: data => phpAction('ocr_batch_list', data),
  get: batch_id => phpAction('ocr_batch_get', { batch_id }),
  retry: batch_id => phpAction('ocr_batch_retry', { batch_id }),
  remove: batch_id => phpAction('ocr_batch_delete', { batch_id }),
  commit: data => phpAction('ocr_batch_commit', data),
}

export const phpFeedbackApi = {
  submit: data => phpAction('feedback_submit', data),
  mine: data => phpAction('feedback_list_mine', data, { bypassCsrf: true }),
}

export const phpNotificationApi = {
  list: data => phpAction('notification_list', data, { bypassCsrf: true }),
  unreadCount: () => phpAction('notification_unread_count', {}, { bypassCsrf: true }),
  markRead: (id, relatedId) => phpAction('notification_mark_read', id ? { id, related_id: relatedId || 0 } : {}),
}

export const phpAdminFeedbackApi = {
  list: data => phpAction('admin_feedback_list', data),
  update: data => phpAction('admin_feedback_update', data),
  adopt: id => phpAction('admin_feedback_update', { id, feedback_action: 'adopt' }),
  ignore: id => phpAction('admin_feedback_update', { id, feedback_action: 'ignore' }),
  reopen: id => phpAction('admin_feedback_update', { id, feedback_action: 'reopen' }),
}

export const phpAdminAnnouncementApi = {
  list: data => phpAction('admin_announcement_list', data),
  create: data => phpAction('admin_announcement_create', data),
  update: data => phpAction('admin_announcement_update', data),
  remove: id => phpAction('admin_announcement_delete', { id }),
  publish: id => phpAction('admin_announcement_publish', { id }),
}

export const phpAdminApi = {
  panel: () => phpAction('get_admin_panel'),
  accessStats: () => phpAction('admin_access_stats', {}, { bypassCsrf: true }),
  statistics: () => phpAction('admin_access_stats', {}, { bypassCsrf: true }),
  securityScanStatus: () => phpAction('security_scan_status', {}, { bypassCsrf: true }),
  securityScanForce: () => phpAction('security_scan_force', {}),
  securityIpList: data => phpAction('security_ip_list', data),
  feedbackList: data => phpAction('admin_feedback_list', data),
  feedbackUpdate: data => phpAction('admin_feedback_update', data),
  createAdmin: data => phpAction('create_root', data),
  toggleUser: user_id => phpAction('toggle_user', { user_id }),
  grantRoot: user_id => phpAction('grant_root', { user_id }),
  revokeRoot: user_id => phpAction('revoke_root', { user_id }),
  grantContentAdmin: user_id => phpAction('grant_content_admin', { user_id }),
  revokeContentAdmin: user_id => phpAction('revoke_content_admin', { user_id }),
  removeUser: user_id => phpAction('delete_user', { user_id }),
}
