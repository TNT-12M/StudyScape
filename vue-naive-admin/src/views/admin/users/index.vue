<template>
  <AppPage>
    <n-space vertical size="large">
      <n-grid :cols="3" :x-gap="12"><n-gi v-for="item in summary" :key="item.label"><n-card size="small"><n-statistic :label="item.label" :value="item.value" /></n-card></n-gi></n-grid>
      <n-card v-if="session.isRoot && session.user?.is_initial_root" title="创建 root" segmented><n-form inline :model="createForm"><n-form-item label="用户名"><n-input v-model:value="createForm.username" /></n-form-item><n-form-item label="邮箱"><n-input v-model:value="createForm.email" /></n-form-item><n-form-item label="初始密码"><n-input v-model:value="createForm.password" type="password" show-password-on="click" /></n-form-item><n-button type="primary" @click="createAdmin">创建 root</n-button></n-form></n-card>
      <n-card title="用户列表" segmented><template #header-extra><n-space><n-input v-model:value="keyword" clearable placeholder="用户名/邮箱" @keyup.enter="filterUsers" /><n-select v-model:value="role" :options="roleOptions" style="width:120px" @update:value="filterUsers" /><n-select v-model:value="status" :options="statusOptions" style="width:120px" @update:value="filterUsers" /><n-button @click="loadPanel">刷新</n-button></n-space></template><n-alert type="info" class="mb-12">出于安全原因，密码不会在管理端展示、复制或导出。</n-alert><n-data-table :columns="columns" :data="filteredUsers" :loading="loading" :pagination="pagination" :scroll-x="1250" /></n-card>
    </n-space>
  </AppPage>
</template>
<script setup>
import { NButton, NSpace, NTag } from 'naive-ui'
import { phpAdminApi } from '@/api/php-modules'
import { useSessionStore } from '@/store'
const message = window.$message
const dialog = window.$dialog
const session = useSessionStore()
const users = ref([])
const loading = ref(false)
const keyword = ref('')
const role = ref('all')
const status = ref('all')
const pagination = reactive({ page: 1, pageSize: 10, showSizePicker: true, pageSizes: [10, 20, 50] })
const createForm = reactive({ username: '', email: '', password: '' })
const roleOptions = [{ label: '全部角色', value: 'all' }, { label: 'root', value: 'root' }, { label: '内容管理员', value: 'content_admin' }, { label: '普通用户', value: 'user' }]
const statusOptions = [{ label: '全部状态', value: 'all' }, { label: '已启用', value: 'active' }, { label: '已禁用', value: 'inactive' }]
const filteredUsers = computed(() => users.value.filter(item => (!keyword.value || `${item.username} ${item.email}`.toLowerCase().includes(keyword.value.toLowerCase())) && (role.value === 'all' || (item.role || 'user') === role.value) && (status.value === 'all' || (status.value === 'active' ? !!item.is_active : !item.is_active))))
const summary = computed(() => [{ label: '用户总数', value: users.value.length }, { label: '启用账号', value: users.value.filter(item => item.is_active).length }, { label: 'root', value: users.value.filter(item => item.role === 'root').length }, { label: '内容管理员', value: users.value.filter(item => item.role === 'content_admin').length }])
function roleLabel(row) { return row.role === 'root' ? 'root' : row.role === 'content_admin' ? '内容管理员' : '普通用户' }
const columns = [{ title: 'ID', key: 'id', width: 70 }, { title: '用户名', key: 'username' }, { title: '邮箱', key: 'email' }, { title: '角色', key: 'role', render: row => h(NTag, { type: row.role === 'root' ? 'warning' : row.role === 'content_admin' ? 'success' : 'info' }, { default: () => roleLabel(row) }) }, { title: '状态', key: 'is_active', render: row => h(NTag, { type: row.is_active ? 'success' : 'error' }, { default: () => row.is_active ? '启用' : '禁用' }) }, { title: '注册时间', key: 'created_at' }, { title: '最后登录', key: 'last_login', render: row => row.last_login || '从未登录' }, { title: '操作', key: 'actions', width: 430, render: row => h(NSpace, null, { default: () => [!row.is_initial_root && h(NButton, { size: 'small', onClick: () => toggleUser(row) }, { default: () => row.is_active ? '禁用' : '启用' }), session.isRoot && row.role === 'user' && h(NButton, { size: 'small', type: 'success', onClick: () => grantContent(row) }, { default: () => '授予内容管理员' }), session.isRoot && row.role === 'content_admin' && h(NButton, { size: 'small', type: 'warning', onClick: () => revokeContent(row) }, { default: () => '撤销内容权限' }), session.user?.is_initial_root && row.role !== 'root' && h(NButton, { size: 'small', type: 'warning', onClick: () => grantRoot(row) }, { default: () => '授予 root' }), session.user?.is_initial_root && row.role === 'root' && !row.is_initial_root && h(NButton, { size: 'small', type: 'warning', onClick: () => revokeRoot(row) }, { default: () => '撤销 root' }), !row.is_initial_root && h(NButton, { size: 'small', type: 'error', onClick: () => deleteUser(row) }, { default: () => '删除' })] }) }]
async function loadPanel() { loading.value = true; try { const data = (await phpAdminApi.panel()).data || {}; users.value = (data.users || []).map(({ password, ...safe }) => safe) } catch (error) { message.error(error.message) } finally { loading.value = false } }
function filterUsers() { pagination.page = 1 }
async function createAdmin() { if (!createForm.username || !createForm.email || !createForm.password) return message.warning('请完整填写创建信息'); try { await phpAdminApi.createAdmin(createForm); message.success('管理员已创建'); Object.assign(createForm, { username: '', email: '', password: '' }); await loadPanel() } catch (error) { message.error(error.message) } }
async function toggleUser(row) { try { await phpAdminApi.toggleUser(row.id); message.success('账号状态已更新'); await loadPanel() } catch (error) { message.error(error.message) } }
function grantContent(row) { dialog.warning({ title: '授予内容管理员', content: `确定授予 ${row.username} 题库和资料管理权限吗？`, positiveText: '确定', negativeText: '取消', onPositiveClick: async () => { try { await phpAdminApi.grantContentAdmin(row.id); message.success('已授予内容管理员'); await loadPanel() } catch (error) { message.error(error.message) } } }) }
function revokeContent(row) { dialog.warning({ title: '撤销内容权限', content: `确定撤销 ${row.username} 的内容管理权限吗？`, positiveText: '确定', negativeText: '取消', onPositiveClick: async () => { try { await phpAdminApi.revokeContentAdmin(row.id); message.success('已撤销'); await loadPanel() } catch (error) { message.error(error.message) } } }) }
function grantRoot(row) { dialog.warning({ title: '授予 root', content: `确定将 ${row.username} 授予 root 权限吗？`, positiveText: '确定', negativeText: '取消', onPositiveClick: async () => { try { await phpAdminApi.grantRoot(row.id); message.success('已授予 root'); await loadPanel() } catch (error) { message.error(error.message) } } }) }
function revokeRoot(row) { dialog.warning({ title: '撤销 root', content: `确定撤销 ${row.username} 的 root 权限吗？`, positiveText: '确定', negativeText: '取消', onPositiveClick: async () => { try { await phpAdminApi.revokeRoot(row.id); message.success('已撤销 root'); await loadPanel() } catch (error) { message.error(error.message) } } }) }
function deleteUser(row) { dialog.warning({ title: '删除用户', content: `确定删除 ${row.username} 吗？此操作不可撤销。`, positiveText: '删除', negativeText: '取消', onPositiveClick: async () => { try { await phpAdminApi.removeUser(row.id); message.success('用户已删除'); await loadPanel() } catch (error) { message.error(error.message) } } }) }
onMounted(loadPanel)
</script>
<style scoped>.mb-12{margin-bottom:12px}.challenge{display:block;margin:16px 0;padding:12px;text-align:center;letter-spacing:3px}</style>
