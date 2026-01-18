<template>
  <form
    @submit.prevent="submitForm"
    class="flex flex-col gap-6 mt-6"
  >
    <!-- Title -->
    <BaseInputTextWithVuelidate
      v-model="formData.title"
      :label="t('Title')"
      :vuelidate-property="v$.title"
      required
    />

    <!-- Description -->
    <BaseTinyEditor
      v-model="formData.description"
      :title="t('Description')"
      editor-id="attendance_description"
    />

    <!-- Advanced Settings (create + edit) -->
    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <!-- Require unique presence -->
      <div class="flex flex-col gap-2 mb-4">
        <BaseCheckbox
          id="attendance_require_unique"
          v-model="formData.requireUnique"
          :label="t('Require unique presence')"
          name="attendance_require_unique"
        />
        <p class="text-xs text-gray-500">
          {{ t("If enabled, the gradebook will give a learner 100% when present at least once.") }}
        </p>
      </div>

      <!-- Gradebook options (only in creation mode) -->
      <div v-if="!isEditMode">
        <div class="flex flex-row mb-4">
          <label class="font-semibold w-28">{{ t("Gradebook options") }}:</label>
          <BaseCheckbox
            id="attendance_qualify_gradebook"
            v-model="formData.qualifyGradebook"
            :label="t('Qualify attendance gradebook')"
            name="attendance_qualify_gradebook"
            @change="toggleGradebookOptions"
          />
        </div>

        <div
          v-if="formData.qualifyGradebook"
          class="ml-6"
        >
          <BaseSelect
            v-model="formData.gradebookOption"
            :label="t('Select gradebook option')"
            :options="gradebookOptions"
          />

          <BaseInputText
            v-model="formData.gradebookTitle"
            :label="t('Gradebook column title')"
          />

          <BaseInputNumber
            v-model="formData.gradeWeight"
            :label="t('Grade weight')"
            :min="0"
            :step="0.01"
          />
        </div>
      </div>
    </BaseAdvancedSettingsButton>

    <!-- Buttons -->
    <LayoutFormButtons>
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="black"
        @click="emit('backPressed', route.query)"
      />
      <BaseButton
        :label="t('Save')"
        icon="save"
        type="success"
        @click="submitForm"
      />
    </LayoutFormButtons>
  </form>
</template>
<script setup>
import { computed, onMounted, ref, reactive, watch } from "vue"
import { useI18n } from "vue-i18n"
import { required } from "@vuelidate/validators"
import useVuelidate from "@vuelidate/core"
import attendanceService from "../../services/attendanceService"
import BaseInputTextWithVuelidate from "../../components/basecomponents/BaseInputTextWithVuelidate.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseInputNumber from "../../components/basecomponents/BaseInputNumber.vue"
import LayoutFormButtons from "../../components/layout/LayoutFormButtons.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useRoute, useRouter } from "vue-router"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useCidReq } from "../../composables/cidReq"
import gradebookService from "../../services/gradebookService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { sid, cid } = useCidReq()
const emit = defineEmits(["backPressed"])
const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({}),
  },
})

const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref([
  {
    sid,
    cid,
    visibility: RESOURCE_LINK_PUBLISHED,
  },
])

const formData = reactive({
  id: null,
  title: "",
  description: "",
  qualifyGradebook: false,
  gradebookOption: null,
  gradebookTitle: "",
  gradeWeight: 0.0,
  requireUnique: false,
})

const gradebookOptions = ref([])

const rules = {
  title: { required },
  description: {},
  qualifyGradebook: {},
  gradebookOption: {},
  gradebookTitle: {},
  gradeWeight: {},
  requireUnique: {},
}

const v$ = useVuelidate(rules, formData)
const showAdvancedSettings = ref(false)
const isEditMode = computed(() => !!props.initialData?.id)

onMounted(async () => {
  if (!isEditMode.value) {
    try {
      const categories = await gradebookService.getCategories(cid, sid)
      gradebookOptions.value = categories.map((cat) => ({
        label: cat.title,
        value: cat.id,
      }))
    } catch (error) {
      console.error("Error loading gradebook categories:", error)
    }
  }
})

const toggleGradebookOptions = () => {
  if (!formData.qualifyGradebook) {
    formData.gradebookOption = null
    formData.gradebookTitle = ""
    formData.gradeWeight = 0.0
  }
}

watch(
  () => props.initialData,
  (newData) => {
    Object.assign(formData, newData || {})
  },
  { immediate: true },
)

const submitForm = async () => {
  v$.value.$touch()
  if (v$.value.$invalid) {
    return
  }

  const postData = {
    title: formData.title,
    description: formData.description,
    sid: route.query.sid || null,
    cid: route.query.cid || null,
    attendanceQualifyTitle: formData.gradebookTitle,
    attendanceWeight: formData.gradeWeight,
    requireUnique: !!formData.requireUnique,
  }

  // Only send these on create (safer)
  if (!props.initialData?.id) {
    postData.parentResourceNodeId = parentResourceNodeId.value
    postData.resourceLinkList = resourceLinkList.value
  }

  try {
    if (props.initialData?.id) {
      await attendanceService.updateAttendance(props.initialData.id, postData)
      emit("backPressed", route.query)
      return
    }

    const created = await attendanceService.createAttendance(postData)

    router.push({
      name: "AttendanceAddCalendarEvent",
      params: {
        node: String(route.params.node),
        id: created.iid,
      },
      query: {
        cid: route.query.cid,
        sid: route.query.sid,
        gid: route.query.gid,
      },
    })
  } catch (error) {
    console.error("Error submitting attendance:", error)
  }
}
</script>
