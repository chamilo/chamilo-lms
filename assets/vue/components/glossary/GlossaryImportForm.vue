<template>
  <form>
    <BaseFileUpload
      id="terms-file"
      :label="t('File')"
      class="mb-6"
      @file-selected="selectedFile = $event"
    />

    <p>{{ t("File type") }}</p>
    <BaseRadioButtons
      id="file-type"
      v-model="fileType"
      :options="formats"
      class="mb-6"
      name="file-type"
      initial-value="csv"
    />

    <BaseCheckbox
      id="terms-delete-all"
      v-model="replace"
      :label="t('Delete all terms before import')"
      name="terms-delete-all"
    />

    <BaseCheckbox
      id="terms-update"
      v-model="update"
      :label="t('Update existing terms')"
      name="terms-delete-all"
    />

    <LayoutFormButtons class="mt-8">
      <BaseButton
        :label="t('Back')"
        type="black"
        icon="back"
        @click="emit('backPressed')"
      />
      <BaseButton
        :label="t('Import')"
        type="secondary"
        icon="import"
        @click="submitForm"
      />
    </LayoutFormButtons>
  </form>
</template>

<script setup>
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { ref } from "vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import LayoutFormButtons from "../layout/LayoutFormButtons.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useNotification } from "../../composables/notification"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import glossaryService from "../../services/glossaryService"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import {useCidReq} from "../../composables/cidReq";

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notification = useNotification()
const { sid, cid } = useCidReq()

const emit = defineEmits(["backPressed"])

const formats = [
  { label: "CSV", value: "csv" },
  { label: "Excel", value: "xls" },
]
const fileType = ref("csv")

const selectedFile = ref(null)
const replace = ref(false)
const update = ref(false)
const parentResourceNodeId = ref(Number(route.params.node))

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ])
)

const submitForm = async () => {
  console.log(selectedFile.value)
  const formData = new FormData()
  formData.append("file", selectedFile.value)
  formData.append("file_type", fileType.value)
  formData.append("replace", replace.value)
  formData.append("update", update.value)
  formData.append("sid", route.query.sid)
  formData.append("cid", route.query.cid)
  formData.append("parentResourceNodeId", parentResourceNodeId.value)
  formData.append("resourceLinkList", resourceLinkList.value)

  try {
    await glossaryService.import(formData)
    notification.showSuccessNotification(t("Terms imported succesfully"))
    await router.push({
      name: "GlossaryList",
      query: route.query,
    })
  } catch (error) {
    notification.showErrorNotification(t("Could not import terms"))
  }
}
</script>
