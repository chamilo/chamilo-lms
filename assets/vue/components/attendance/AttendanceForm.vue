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

    <BaseSelect
      id="attendance_room"
      v-model="formData.room"
      :label="t('Default room')"
      :options="roomOptions"
    />

    <!-- Advanced Settings (create + edit) -->
    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <ResourceLanguageSelector
        id="attendance-language"
        v-model="formData.language"
      />
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

      <!-- Gradebook options -->
      <div>
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
            v-if="gradebookOptions.length > 1"
            id="attendance_gradebook_category"
            v-model="formData.gradebookOption"
            :label="t('Select gradebook option')"
            :options="gradebookOptions"
            name="gradebook_category_id"
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
import ResourceLanguageSelector from "../resources/ResourceLanguageSelector.vue"
import { useRoute, useRouter } from "vue-router"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { getCourseContext } from "../../utils/courseContext"
import gradebookService from "../../services/gradebookService"
import roomService from "../../services/roomService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { sid, cid, gid } = getCourseContext()
const emit = defineEmits(["backPressed"])
const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({}),
  },
})

const parentResourceNodeId = ref(Number(route.params.node))
// Course context derived server-side from the gated session course.
const resourceLinkList = ref([{ visibility: RESOURCE_LINK_PUBLISHED }])

const formData = reactive({
  id: null,
  title: "",
  description: "",
  qualifyGradebook: false,
  gradebookOption: null,
  gradebookTitle: "",
  gradeWeight: 0.0,
  requireUnique: false,
  language: "",
  room: null,
})

const gradebookOptions = ref([])
const roomOptions = ref([])

const rules = {
  title: { required },
  description: {},
  qualifyGradebook: {},
  gradebookOption: {},
  gradebookTitle: {},
  gradeWeight: {},
  requireUnique: {},
  room: {},
}

const v$ = useVuelidate(rules, formData)
const showAdvancedSettings = ref(false)
const isEditMode = computed(() => !!props.initialData?.id)

onMounted(async () => {
  try {
    roomOptions.value = await roomService.getOptions({
      includeDefault: true,
      defaultLabel: t("No default room"),
      floorLabel: t("Floor"),
      capacityLabel: t("Capacity"),
    })
  } catch (error) {
    console.error("Error loading rooms:", error)
  }

  try {
    const response = await gradebookService.getLinkOptions({
      cid,
      ...(sid ? { sid } : {}),
      ...(gid ? { gid } : {}),
      node: Number(route.params.node),
      type: 7,
      refId: Number(props.initialData?.id || route.params.id || 0),
    })

    gradebookOptions.value = (response?.categories || []).map((category) => ({
      label: category.label,
      value: Number(category.value),
    }))

    const rootCategoryId = Number(gradebookOptions.value?.[0]?.value || 0)
    const link = response?.link || null

    if (link) {
      formData.qualifyGradebook = true
      formData.gradebookOption = Number(link.categoryId || rootCategoryId) || null
      formData.gradeWeight = Number(link.weight || 0)
      showAdvancedSettings.value = true
    } else {
      formData.qualifyGradebook = false
      formData.gradebookOption = rootCategoryId || null
    }
  } catch (error) {
    console.error("Error loading attendance Gradebook configuration:", error)
    gradebookOptions.value = []
    formData.gradebookOption = null
  }
})

const toggleGradebookOptions = () => {
  if (formData.qualifyGradebook) {
    if (!formData.gradebookOption) {
      formData.gradebookOption = Number(gradebookOptions.value?.[0]?.value || 0) || null
    }

    return
  }

  formData.gradebookOption = Number(gradebookOptions.value?.[0]?.value || 0) || null
  formData.gradebookTitle = ""
  formData.gradeWeight = 0.0
}

watch(
  () => props.initialData,
  (newData) => {
    Object.assign(formData, newData || {})

    if (newData?.room && typeof newData.room === "object") {
      formData.room = newData.room["@id"] || null
    }
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
    addToGradebook: !!formData.qualifyGradebook,
    gradebookCategoryId: formData.qualifyGradebook ? Number(formData.gradebookOption) : 0,
    attendanceQualifyTitle: formData.qualifyGradebook ? formData.gradebookTitle : "",
    attendanceWeight: formData.qualifyGradebook ? Number(formData.gradeWeight) : 0,
    requireUnique: !!formData.requireUnique,
    language: formData.language || "",
    room: formData.room || null,
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
