<template>
  <AppPage>
    <n-card :title="title">
      <n-result status="info" :title="title" :description="description">
        <template #footer><n-button type="primary" @click="router.push(backPath)">返回学习中心</n-button></template>
      </n-result>
    </n-card>
  </AppPage>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '页面骨架已接入 PHP API，业务交互将在后续迁移阶段补齐。' },
  backPath: { type: String, default: '/app' },
})
const router = useRouter()
</script>
