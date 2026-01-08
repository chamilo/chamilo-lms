<template>
  <form @submit.prevent="onSubmit">
    <BaseInputText
      v-model="v$.title.$model"
      :error-text="v$.title.$errors.map((e) => e.$message).join('<br>')"
      :is-invalid="v$.title.$error"
      :label="t('Assignment name')"
    />

    <BaseTinyEditor
      v-model="assignment.description"
      :label="t('Description')"
      editor-id=""
    />

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <BaseInputNumber
        id="qualification"
        v-model="assignment.qualification"
        :label="t('Maximum score')"
        :min="0"
        :step="0.01"
      />

      <BaseCheckbox
        id="make_calification_id"
        v-model="chkAddToGradebook"
        :label="t('Add to gradebook')"
        name="make_calification"
      />

      <div v-if="chkAddToGradebook">
        <BaseSelect
          v-model="assignment.gradebookId"
          :error-text="v$.gradebookId.$errors.map((e) => e.$message).join('<br>')"
          :is-invalid="v$.gradebookId.$error"
          :label="t('Select assessment')"
          :options="gradebookCategories"
          id="gradebook-gradebook-id"
          name="gradebook_category_id"
          option-label="name"
          option-value="id"
        />

        <BaseInputNumber
          id="weight"
          v-model="assignment.weight"
          :error-text="v$.weight.$errors.map((e) => e.$message).join('<br>')"
          :is-invalid="v$.weight.$error"
          :label="t('Weight inside assessment')"
          :min="0"
          :step="0.01"
        />
      </div>

      <BaseCheckbox
        id="expiry_date"
        v-model="chkExpiresOn"
        :label="t('Enable handing over deadline (visible to learners)')"
        name="enableExpiryDate"
      />

      <BaseCalendar
        v-if="chkExpiresOn"
        id="expires_on"
        v-model="assignment.expiresOn"
        :error-text="v$.expiresOn.$errors.map((e) => e.$message).join('<br>')"
        :is-invalid="v$.expiresOn.$error"
        :label="t('Posted sending deadline')"
        show-time
      />

      <BaseCheckbox
        id="end_date"
        v-model="chkEndsOn"
        :label="t('Enable final acceptance date (invisible to learners)')"
        name="enableEndDate"
      />

      <BaseCalendar
        v-if="chkEndsOn"
        id="ends_on"
        v-model="assignment.endsOn"
        :error-text="v$.endsOn.$errors.map((e) => e.$message).join('<br>')"
        :is-invalid="v$.endsOn.$error"
        :label="t('Ends at (completely closed)')"
        show-time
      />

      <BaseCheckbox
        id="add-to-calendar"
        v-model="assignment.addToCalendar"
        :label="t('Add to calendar')"
        name="add_to_calendar"
      />

      <BaseSelect
        v-model="assignment.allowTextAssignment"
        :options="documentTypes"
        option-label="label"
        option-value="value"
        id="allow-text-assignment"
        name="allow_text_assignment"
        label=""
      />

      <BaseCheckbox
        id="require_extension"
        v-model="chkRequireExtension"
        :label="t('Require specific file format')"
        name="require_extension"
      />

      <div v-if="chkRequireExtension">
        <BaseMultiSelect
          v-model="assignment.allowedExtensions"
          :options="predefinedExtensions"
          :label="t('Select allowed file formats')"
          input-id="allowed-file-extensions"
        />

        <BaseInputText
          v-if="assignment.allowedExtensions.includes('other')"
          id="custom-extensions"
          v-model="assignment.customExtensions"
          :label="t('Custom extensions (separated by space)')"
        />
      </div>
    </BaseAdvancedSettingsButton>

    <div class="flex justify-end space-x-2 mt-4">
      <BaseButton
        :disabled="isFormLoading"
        :label="t('Save')"
        icon="save"
        is-submit
        type="secondary"
      />
    </div>
  </form>
</template>

<script setup>
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseMultiSelect from "../basecomponents/BaseMultiSelect.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"
import useVuelidate from "@vuelidate/core"
import { computed, reactive, ref, watchEffect } from "vue"
import { maxValue, minValue, required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import { useCidReq } from "../../composables/cidReq"
import { useRoute } from "vue-router"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const props = defineProps({
  defaultAssignment: {
    type: Object,
    default: null,
  },
  isFormLoading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(["submit"])

const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const route = useRoute()

const showAdvancedSettings = ref(false)
const chkAddToGradebook = ref(false)
const chkExpiresOn = ref(false)
const chkEndsOn = ref(false)

const gradebookCategories = ref([{ name: "Default", id: 1 }])
const documentTypes = ref([
  { label: t("Allow files or online text"), value: 0 },
  { label: t("Allow only text"), value: 1 },
  { label: t("Allow only files"), value: 2 },
])

const chkRequireExtension = ref(false)
const predefinedExtensions = ref([
  { name: "PDF", id: "pdf" },
  { name: "DOCX", id: "docx" },
  { name: "XLSX", id: "xlsx" },
  { name: "ZIP", id: "zip" },
  { name: "MP3", id: "mp3" },
  { name: "MP4", id: "mp4" },
  { name: t("Other extensions"), id: "other" },
])

const assignment = reactive({
  title: "",
  description: "",
  qualification: 0,
  gradebookId: Number(gradebookCategories.value?.[0]?.id ?? 1),
  weight: 0,
  expiresOn: new Date(),
  endsOn: new Date(),
  addToCalendar: false,
  allowTextAssignment: 2,
  allowedExtensions: [],
  customExtensions: "",
})

function extractGradebookCategoryId(def) {
  // Support multiple possible backend shapes.
  // We return a number or null.
  const direct = def?.gradebookCategoryId
  if (direct !== undefined && direct !== null && direct !== "") return Number(direct)

  const nested = def?.gradebookCategory?.id
  if (nested !== undefined && nested !== null && nested !== "") return Number(nested)

  const legacy = def?.gradebookId?.id
  if (legacy !== undefined && legacy !== null && legacy !== "") return Number(legacy)

  return null
}

watchEffect(() => {
  const def = props.defaultAssignment
  if (!def) return

  assignment.title = def.title
  assignment.description = def.description
  assignment.qualification = def.qualification
  assignment.addToCalendar = def.assignment.eventCalendarId > 0

  if (def.weight > 0) {
    chkAddToGradebook.value = true
    assignment.weight = def.weight
    const gbId = extractGradebookCategoryId(def)
    assignment.gradebookId = gbId ?? Number(gradebookCategories.value?.[0]?.id ?? 1)
  } else {
    assignment.gradebookId = Number(gradebookCategories.value?.[0]?.id ?? 1)
  }
  if (def.assignment.expiresOn) {
    chkExpiresOn.value = true
    assignment.expiresOn = new Date(def.assignment.expiresOn)
  }
  if (def.assignment.endsOn) {
    chkEndsOn.value = true
    assignment.endsOn = new Date(def.assignment.endsOn)
  }

  assignment.allowTextAssignment = def.allowTextAssignment

  if (def.extensions) {
    const extensionsArray = def.extensions
      .split(" ")
      .map((ext) => ext.trim())
      .filter((ext) => ext.length > 0)

    if (extensionsArray.length > 0) {
      chkRequireExtension.value = true

      const predefinedIds = predefinedExtensions.value.map((e) => e.id).filter((id) => id !== "other")

      const predefined = extensionsArray.filter((ext) => predefinedIds.includes(ext))
      const custom = extensionsArray.filter((ext) => !predefinedIds.includes(ext))
      if (assignment.allowedExtensions.length === 0) {
        assignment.allowedExtensions = predefined

        if (custom.length > 0) {
          assignment.allowedExtensions.push("other")
          assignment.customExtensions = custom.join(" ")
        }
      }
    }
  }

  if (
    def.qualification ||
    def.assignment.eventCalendarId ||
    def.weight ||
    def.assignment.expiresOn ||
    def.assignment.endsOn ||
    def.allowTextAssignment !== undefined ||
    def.allowedExtensions
  ) {
    showAdvancedSettings.value = true
  }
})

const rules = computed(() => {
  const r = { title: { required, $autoDirty: true } }
  if (showAdvancedSettings.value) {
    if (chkAddToGradebook.value) {
      r.gradebookId = { required }
      r.weight = { required }
    }
    if (chkExpiresOn.value) {
      r.expiresOn = { required, $autoDirty: true }
      if (chkEndsOn.value) r.expiresOn.maxValue = maxValue(assignment.endsOn)
    }
    if (chkEndsOn.value) {
      r.endsOn = { required, $autoDirty: true }
      if (chkExpiresOn.value) r.endsOn.minValue = minValue(assignment.expiresOn)
    }
    r.allowTextAssignment = { required }
  }
  return r
})

const v$ = useVuelidate(rules, assignment)

async function onSubmit() {
  const valid = await v$.value.$validate()
  if (!valid) return

  const payload = {
    title: assignment.title,
    description: assignment.description,
    parentResourceNode: Number(route.params.node),
    resourceLinkList: [{ cid, sid, gid, visibility: RESOURCE_LINK_PUBLISHED }],
    qualification: assignment.qualification,
    addToCalendar: assignment.addToCalendar,
    allowTextAssignment: assignment.allowTextAssignment,
  }

  if (chkAddToGradebook.value) {
    payload.gradebookCategoryId = Number(assignment.gradebookId)
    payload.weight = assignment.weight
  }
  if (chkExpiresOn.value) {
    payload.expiresOn = assignment.expiresOn.toISOString()
  }
  if (chkEndsOn.value) {
    payload.endsOn = assignment.endsOn.toISOString()
  }
  if (chkRequireExtension.value && assignment.allowedExtensions.length > 0) {
    let extensions = []

    assignment.allowedExtensions.forEach((ext) => {
      if (ext !== "other") {
        extensions.push(ext)
      }
    })
    if (assignment.allowedExtensions.includes("other") && assignment.customExtensions) {
      const customExts = assignment.customExtensions
        .split(" ")
        .map((ext) => ext.trim().toLowerCase().replace(".", ""))
        .filter((ext) => ext.length > 0)
      extensions.push(...customExts)
    }

    if (extensions.length > 0) {
      payload.extensions = extensions.join(" ") // Example: "pdf docx rar ai"
    }
  }
  if (props.defaultAssignment?.["@id"]) {
    payload["@id"] = props.defaultAssignment["@id"]
  }

  emit("submit", payload)
}
</script>
