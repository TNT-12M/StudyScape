<template>
  <AppPage>
    <n-space vertical size="large">
      <n-card title="资料筛选" segmented>
        <template #header-extra><n-button type="primary" @click="showUpload = !showUpload">{{ showUpload ? '收起上传' : '上传资料' }}</n-button></template>
        <n-form inline><n-form-item label="学段"><n-select v-model:value="filters.education_level" clearable :options="levelOptions" style="width:130px" /></n-form-item><n-form-item label="分类"><n-select v-model:value="filters.subject" clearable :options="subjectOptions" style="width:160px" /></n-form-item><n-form-item label="关键词"><n-input v-model:value="filters.keyword" clearable style="width:220px" @keyup.enter="loadMaterials" /></n-form-item><n-button type="primary" @click="loadMaterials">搜索</n-button></n-form>
        <n-card v-if="showUpload" size="small" class="upload-panel"><n-form :model="uploadForm" label-placement="left" label-width="90"><n-form-item label="文件"><n-upload multiple :show-file-list="true" :file-list="uploadFiles" :max="10" @update:file-list="uploadFiles = $event"><n-button>选择文件</n-button></n-upload></n-form-item><n-grid :cols="3" :x-gap="12"><n-form-item-gi label="学段"><n-select v-model:value="uploadForm.education_level" :options="levelOptions" /></n-form-item-gi><n-form-item-gi label="科目/分类"><n-input v-model:value="uploadForm.subject" /></n-form-item-gi><n-form-item-gi label="说明"><n-input v-model:value="uploadForm.description" /></n-form-item-gi></n-grid><n-button type="primary" :loading="uploading" @click="uploadMaterials">开始上传</n-button></n-form></n-card>
      </n-card>
      <n-card title="资料列表" segmented><n-data-table :columns="columns" :data="materials" :loading="loading" :scroll-x="1100" :pagination="pagination" /></n-card>
    </n-space>
    <n-modal v-model:show="showEditor" preset="card" title="编辑资料信息" style="width:560px"><n-form :model="editForm" label-placement="left" label-width="90"><n-form-item label="文件名"><n-input v-model:value="editForm.filename" /></n-form-item><n-form-item label="学段"><n-select v-model:value="editForm.education_level" :options="levelOptions" /></n-form-item><n-form-item label="分类"><n-input v-model:value="editForm.subject" /></n-form-item><n-form-item label="说明"><n-input v-model:value="editForm.description" /></n-form-item></n-form><template #footer><n-space justify="end"><n-button @click="showEditor = false">取消</n-button><n-button type="primary" @click="saveEdit">保存</n-button></n-space></template></n-modal>
  </AppPage>
</template>
<script setup>
import { NButton, NSpace, NTag } from 'naive-ui'
import { phpMaterialsApi } from '@/api/php-modules'
const message = window.$message
const dialog = window.$dialog
const materials = ref([])
const subjects = ref([])
const pagination = reactive({ page: 1, pageSize: 10, showSizePicker: true, pageSizes: [10, 20, 50] })
const loading = ref(false)
const uploading = ref(false)
const showUpload = ref(false)
const showEditor = ref(false)
const uploadFiles = ref([])
const filters = reactive({ education_level: '', subject: '', keyword: '' })
const uploadForm = reactive({ education_level: 'junior', subject: '', description: '' })
const editForm = reactive({ id: null, filename: '', education_level: 'junior', subject: '', description: '' })
const levelOptions = [{ label: '初中', value: 'junior' }, { label: '高中', value: 'senior' }]
const subjectOptions = computed(() => subjects.value.map(item => ({ label: item, value: item })))
const columns = [{ title: '文件名', key: 'filename', ellipsis: { tooltip: true } }, { title: '学段', key: 'education_level', render: row => row.education_level === 'senior' ? '高中' : '初中', width: 80 }, { title: '分类', key: 'subject', width: 120 }, { title: '大小', key: 'file_size', render: row => formatSize(row.file_size), width: 100 }, { title: '下载', key: 'downloads', width: 70 }, { title: '上传时间', key: 'uploaded_at', width: 170 }, { title: '操作', key: 'actions', width: 260, render: row => h(NSpace, null, { default: () => [h(NButton, { size: 'small', type: 'primary', onClick: () => download(row) }, { default: () => '下载' }), h(NButton, { size: 'small', onClick: () => openEdit(row) }, { default: () => '编辑' }), h(NButton, { size: 'small', type: 'error', onClick: () => remove(row) }, { default: () => '删除' })] }) }]
async function loadMaterials() { loading.value = true; try { const result = await phpMaterialsApi.list(filters); const data = result.data || {}; materials.value = data.materials || []; subjects.value = data.subjects || [] } catch (error) { message.error(error.message) } finally { loading.value = false } }
function formatSize(value) { let size = Number(value) || 0; const units = ['B', 'KB', 'MB', 'GB']; let index = 0; while (size >= 1024 && index < units.length - 1) { size /= 1024; index++ }; return `${size.toFixed(index ? 1 : 0)} ${units[index]}` }
async function uploadMaterials() { const validFiles = uploadFiles.value.filter(item => item.status !== 'error' && item.file).map(item => item.file); if (!uploadForm.education_level || !validFiles.length) return message.warning('请选择学段和至少一个文件'); uploading.value = true; let success = 0; try { for (const file of validFiles) { await phpMaterialsApi.upload({ file, education_level: uploadForm.education_level, subject: uploadForm.subject, description: uploadForm.description }); success++ }; message.success(`成功上传 ${success} 个文件`); uploadFiles.value = []; showUpload.value = false; await loadMaterials() } catch (error) { message.error(error.message) } finally { uploading.value = false } }
async function download(row) { try { const result = await phpMaterialsApi.token(row.id); const url = phpMaterialsApi.downloadUrl(result.data.download_token); window.open(url, '_blank', 'noopener') } catch (error) { message.error(error.message) } }
function openEdit(row) { Object.assign(editForm, { id: row.id, filename: row.filename, education_level: row.education_level, subject: row.subject || '', description: row.description || '' }); showEditor.value = true }
async function saveEdit() { try { await phpMaterialsApi.update(editForm); message.success('资料信息已更新'); showEditor.value = false; await loadMaterials() } catch (error) { message.error(error.message) } }
function remove(row) { dialog.warning({ title: '删除资料', content: `确认删除「${row.filename}」？文件也会被删除。`, positiveText: '删除', negativeText: '取消', onPositiveClick: async () => { try { await phpMaterialsApi.remove(row.id); message.success('已删除'); await loadMaterials() } catch (error) { message.error(error.message) } } }) }
onMounted(loadMaterials)
</script>
<style scoped>.upload-panel{margin-top:16px}.upload-panel :deep(.n-card__content){padding-bottom:4px}</style>
