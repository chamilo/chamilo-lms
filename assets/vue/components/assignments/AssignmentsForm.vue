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

const assignment = reactive({
  title: "",
  description: "",
  qualification: 0,
  gradebookId: gradebookCategories.value[0],
  weight: 0,
  expiresOn: new Date(),
  endsOn: new Date(),
  addToCalendar: false,
  allowTextAssignment: 2,
})

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

  if (
    def.qualification ||
    def.assignment.eventCalendarId ||
    def.weight ||
    def.assignment.expiresOn ||
    def.assignment.endsOn ||
    def.allowTextAssignment !== undefined
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
    payload.gradebookCategoryId = assignment.gradebookId.id
    payload.weight = assignment.weight
  }
  if (chkExpiresOn.value) {
    payload.expiresOn = assignment.expiresOn.toISOString()
  }
  if (chkEndsOn.value) {
    payload.endsOn = assignment.endsOn.toISOString()
  }
  if (props.defaultAssignment?.["@id"]) {
    payload["@id"] = props.defaultAssignment["@id"]
  }

  emit("submit", payload)
}
</script>
