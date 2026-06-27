<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Import')" />

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
      </div>

      <BaseCheckbox
        id="scorm-use-maximum-score"
        v-model="useMaxScore"
        name="useMaxScore"
        :label="t('Use default maximum score of 100')"
      />

      <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
        <div class="flex w-full flex-col gap-5">
          <BaseSelect
            id="scorm-content-proximity"
            v-model="contentProximity"
            name="contentProximity"
            :label="t('Content')"
            :options="proximityOptions"
            option-label="label"
            option-value="value"
          />

          <BaseInputText
            id="scorm-content-maker"
            v-model="contentMaker"
            name="contentMaker"
            :label="t('Authoring')"
          />

          <BaseCheckbox
            v-if="canAllowHtaccess"
            id="scorm-allow-htaccess"
            v-model="allowHtaccess"
            name="allowHtaccess"
            :label="t('Allow .htaccess from SCORM packages')"
          />
        </div>
      </BaseAdvancedSettingsButton>

      <div class="flex items-center gap-4">
        <BaseButton
          :disabled="saving"
          :label="t('Import')"
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
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"
import { useCidReqStore } from "../../store/cidReq"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const cidReqStore = useCidReqStore()
const platformConfig = usePlatformConfig()
const { course, session } = storeToRefs(cidReqStore)
const { showErrorNotification, showSuccessNotification } = useNotification()

const packageFile = ref(null)
const csrfToken = ref("")
const saving = ref(false)
const showAdvancedSettings = ref(false)
const useMaxScore = ref(true)
const contentProximity = ref("local")
const contentMaker = ref("Scorm")
const allowHtaccess = ref(false)

const proximityOptions = computed(() => [
  { label: t("Local"), value: "local" },
  { label: t("Remote"), value: "remote" },
])

const canAllowHtaccess = computed(
  () => String(platformConfig.getSetting("lp.allow_htaccess_import_from_scorm")).toLowerCase() === "true",
)

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
    formData.append("useMaxScore", useMaxScore.value ? "1" : "0")
    formData.append("contentProximity", contentProximity.value)
    formData.append("contentMaker", contentMaker.value)
    formData.append("allowHtaccess", allowHtaccess.value ? "1" : "0")

    await lpService.importScormPackage(contextParams.value, formData)
    showSuccessNotification(t("File upload succeeded!"))
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
