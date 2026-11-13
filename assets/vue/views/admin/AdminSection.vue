<template>
  <div class="w-full max-w-3xl">
    <Skeleton
      v-if="loading"
      height="18rem"
    />
    <p v-else-if="error" role="alert">{{ t("An error occurred") }}</p>
    <p v-else-if="!block">{{ t("Not available") }}</p>
    <AdminBlock
      v-else
      :key="section"
      :id="block.id"
      v-model:extra-content="block.extraContent"
      :editable="block.editable"
      :description="config.description ? t(config.description) : null"
      :items="block.items"
      :search-url="block.searchUrl"
      :title="config.title === 'Chamilo.org' ? config.title : t(config.title)"
      :bg-index="config.bgIndex"
      :icon="config.icon"
      @item-action="onItemAction"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Skeleton from "primevue/skeleton"
import AdminBlock from "../../components/admin/AdminBlock.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import adminService from "../../services/adminService"
import baseService from "../../services/baseService"
import { adminSections } from "../../utils/adminSections"

const props = defineProps({
  section: { type: String, required: true },
})

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification, showSuccessNotification } = useNotification()
const config = computed(() => adminSections[props.section])
const block = ref(null)
const loading = ref(true)
const error = ref(false)
let requestId = 0

async function loadBlock() {
  const currentRequestId = ++requestId
  loading.value = true
  error.value = false

  try {
    const blocks = await adminService.findBlocks()
    if (currentRequestId === requestId) {
      // The server decides which blocks and actions this user may access.
      block.value = blocks[props.section] || null
    }
  } catch (exception) {
    console.error("[AdminSection] Failed to load block", exception)
    if (currentRequestId === requestId) {
      error.value = true
    }
  } finally {
    if (currentRequestId === requestId) {
      loading.value = false
    }
  }
}

function onItemAction(item) {
  if (props.section !== "health_check" || item.action !== "record-migration-history") {
    return
  }

  requireConfirmation({
    message: t(
      "This records the migrations your database already carries, and leaves the newer ones pending so the update can execute them. Your data is not modified.",
    ),
    accept: recordMigrationHistory,
  })
}

async function recordMigrationHistory() {
  try {
    const result = await baseService.post("/admin/system-status-migration-history", {})
    showSuccessNotification(result.message)
    await loadBlock()
  } catch (exception) {
    showErrorNotification(exception)
  }
}

watch(() => props.section, loadBlock, { immediate: true })
</script>
