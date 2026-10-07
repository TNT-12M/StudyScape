<template>
  <AppPage show-footer>
    <n-card :bordered="false" class="page-card">
      <template #header>
        <n-space align="center" justify="space-between" style="width: 100%">
          <span class="page-title">资料需求</span>
          <n-button type="primary" @click="showSubmit = true">我想要的资料</n-button>
        </n-space>
      </template>

      <n-alert v-if="error" type="error" :title="error" closable class="mb-16" @close="error = ''" />

      <n-spin :show="loading">
        <div v-if="!items.length && !loading" class="empty-state">
          <n-empty description="还没有提交过资料需求">
            <n-button type="primary" @click="showSubmit = true">提交第一个需求</n-button>
          </n-empty>
        </div>
        <div v-else class="request-list">
          <n-card v-for="item in items" :key="item.id" hoverable class="request-item">
            <n-space vertical size="small" style="width: 100%">
              <n-space align="center" justify="space-between" wrap>
                <n-text strong>{{ item.title || '未填写标题' }}</n-text>
                <n-tag :type="tagType(item.status)" size="small">{{ statusLabel(item.status) }}</n-tag>
              </n-space>
              <n-space wrap size="small" class="meta">
                <n-tag v-if="item.education_level" size="small">{{ levelLabel(item.education_level) }}</n-tag>
                <n-tag v-if="item.subject" size="small" type="success">{{ item.subject }}</n-tag>
                <span class="muted">{{ item.created_at }}</span>
              </n-space>
              <div class="content">{{ item.content }}</div>
              <n-alert v-if="item.admin_note" type="info" :title="'管理员回复：' + item.admin_note" size="small" />
            </n-space>
          </n-card>
        </div>
      </n-spin>

      <div class="pagination-wrap" v-if="items.length">
        <n-pagination
          v-model:page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :item-count="pagination.itemCount"
          :page-sizes="[10, 20, 50]"
          show-size-picker
          @update:page="loadList"
          @update:page-size="onPageSizeChange"
        />
      </div>
    </n-card>

    <!-- 提交需求弹窗 -->
    <n-modal v-model:show="showSubmit" preset="card" title="我想要的资料" style="width: 520px">
      <n-form :model="form" label-placement="left" label-width="80">
        <n-form-item label="标题">
          <n-input v-model:value="form.title" placeholder="简短描述，比如「高一数学必修一讲义」" maxlength="50" show-count />
        </n-form-item>
        <n-form-item label="学段">
          <n-select v-model:value="form.education_level" :options="levelOptions" clearable placeholder="请选择学段" />
        </n-form-item>
        <n-form-item label="科目">
          <n-input v-model:value="form.subject" placeholder="如：数学、英语" maxlength="30" />
        </n-form-item>
        <n-form-item label="详细描述">
          <n-input v-model:value="form.content" type="textarea" :rows="5" placeholder="请详细描述你需要什么样的资料，包括版本、年级、内容等" maxlength="500" show-count />
        </n-form-item>
        <n-form-item label="联系方式">
          <n-input v-model:value="form.contact" placeholder="选填，方便我们联系你" maxlength="50" />
        </n-form-item>
      </n-form>
      <template #footer>
        <n-space justify="end">
          <n-button @click="showSubmit = false">取消</n-button>
          <n-button type="primary" :loading="submitting" @click="submitRequest">提交需求</n-button>
        </n-space>
      </template>
    </n-modal>
  </AppPage>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useSessionStore } from '@/store/modules/session'
import { phpMaterialRequestApi } from '@/api/php-modules'

const session = useSessionStore()
const message = window.$message
const items = ref([])
const loading = ref(false)
const error = ref('')
const showSubmit = ref(false)
const submitting = ref(false)

const form = reactive({ title: '', education_level: null, subject: '', content: '', contact: '' })
const pagination = reactive({ page: 1, pageSize: 10, itemCount: 0 })

const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const statusOptions = [
  { label: '待处理', value: 'pending' },
  { label: '处理中', value: 'processing' },
  { label: '已满足', value: 'fulfilled' },
  { label: '已拒绝', value: 'rejected' },
]

function levelLabel(v) { return v === 'senior' ? '高中' : v === 'junior' ? '初中' : '' }
function statusLabel(v) { return statusOptions.find(o => o.value === v)?.label || v || '待处理' }
function tagType(v) {
  if (v === 'fulfilled') return 'success'
  if (v === 'processing') return 'warning'
  if (v === 'rejected') return 'error'
  return 'info'
}

async function loadList() {
  loading.value = true
  error.value = ''
  try {
    const result = await phpMaterialRequestApi.mine({ page: pagination.page, page_size: pagination.pageSize })
    const data = result.data || {}
    items.value = data.items || []
    pagination.itemCount = Number(data.pagination?.total ?? 0) || 0
  } catch (err) { error.value = err.message || '加载失败' }
  finally { loading.value = false }
}

async function submitRequest() {
  if (!form.content.trim()) return message.warning('请填写需求描述')
  if (form.content.trim().length < 2) return message.warning('需求描述至少 2 个字符')
  submitting.value = true
  try {
    await phpMaterialRequestApi.submit({
      title: form.title.trim(),
      content: form.content.trim(),
      education_level: form.education_level || '',
      subject: form.subject.trim(),
      contact: form.contact.trim(),
    })
    message.success('需求已提交，我们会尽快处理')
    showSubmit.value = false
    form.title = ''
    form.education_level = session.defaultEducationLevel || null
    form.subject = ''
    form.content = ''
    form.contact = ''
    pagination.page = 1
    await loadList()
  } catch (err) { message.error(err.message || '提交失败') }
  finally { submitting.value = false }
}

function onPageSizeChange(size) {
  pagination.pageSize = size
  pagination.page = 1
  loadList()
}

onMounted(() => {
  if (session.defaultEducationLevel) {
    form.education_level = session.defaultEducationLevel
  }
  loadList()
})
</script>

<style scoped>
.page-card { max-width: 900px; margin: 0 auto; }
.page-title { font-size: 18px; font-weight: 600; }
.mb-16 { margin-bottom: 16px; }
.empty-state { padding: 40px 0; text-align: center; }
.request-list { display: flex; flex-direction: column; gap: 12px; }
.request-item { transition: all 0.2s; }
.meta { color: var(--n-text-color-3); font-size: 13px; }
.content { white-space: pre-wrap; line-height: 1.7; color: var(--n-text-color-2); }
.muted { color: var(--n-text-color-3); font-size: 13px; }
.pagination-wrap { display: flex; justify-content: center; margin-top: 20px; }
</style>
