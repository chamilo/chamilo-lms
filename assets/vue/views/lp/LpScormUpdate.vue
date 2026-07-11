<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Update SCORM')" />

    <form
      class="flex max-w-3xl flex-col gap-6"
      @submit.prevent="submit"
    >
      <div class="flex flex-col gap-2">
        <span class="text-sm font-medium text-gray-90">{{ t("File") }}</span>
        <BaseFileUpload
          accept=".zip,application/zip,application/x-zip-compressed"
          :label="t('Choose file')"
          @file-selected="packageFile = $event"
        />
        <small class="text-gray-50">{{ t("Upload") }} SCORM (.zip)</small>
        <small class="text-gray-50">{{ t("You must upload a zip file with the same name as the original SCORM file.") }}</small>
      </div>

      <div class="flex items-center gap-4">
        <BaseButton
          :disabled="saving"
          :label="t('Update SCORM')"
          icon="upload"
          type="success"
          is-submit
        />
        <BaseButton
          :disabled="saving"
          :label="t('Cancel')"
          icon="close"
          type="plain"
          @click="cancel"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReqStore = useCidReqStore()
const { course, session } = storeToRefs(cidReqStore)
const { showErrorNotification, showSuccessNotification } = useNotification()

const packageFile = ref(null)
const csrfToken = ref("")
const saving = ref(false)

const learningPathId = computed(() => Number(route.params.lpId ?? 0))


const contextParams = computed(() => ({
  cid: Number(course.value?.id ?? route.query?.cid ?? 0),
  sid: Number(session.value?.id ?? route.query?.sid ?? 0),
  gid: Number(route.query?.gid ?? 0),
  node: Number(route.params?.node ?? 0),
  isStudentView: "false",
}))

onMounted(async () => {
  try {
    const result = await lpService.getActionToken(contextParams.value)
    csrfToken.value = result?.token ?? ""
  } catch (error) {
    showErrorNotification(error)
  }
})

async function submit() {
  if (!(packageFile.value instanceof File)) {
    showErrorNotification(t("No file selected."))
    return
  }

  saving.value = true
  try {
    const formData = new FormData()
    formData.append("package", packageFile.value)
    formData.append("csrfToken", csrfToken.value)

    await lpService.updateScormPackage(learningPathId.value, contextParams.value, formData)
    showSuccessNotification(t("Update successful"))
    await router.push({ name: "LpList", query: route.query })
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function cancel() {
  router.push({ name: "LpList", query: route.query })
}
</script>
