<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Move the file') + ' - ' + props.submission?.title"
    :style="{ width: '450px' }"
    @hide="onHide"
  >
    <div class="flex flex-col space-y-4">
      <div>
        <label class="font-semibold">{{ t("Select") }}</label>
        <select
          v-model="selectedTargetId"
          class="border rounded p-2 w-full"
          :disabled="assignments.length === 0"
        >
          <option
            v-if="assignments.length === 0"
            disabled
            value=""
          >
            {{ t("No assignments available") }}
          </option>
          <option
            v-for="assignment in assignments"
            :key="assignment.iid"
            :value="assignment.iid"
          >
            {{ assignment.title }}
          </option>
        </select>

        <Button
          :label="t('Move the file')"
          icon="pi pi-send"
          @click="move"
          class="p-button-primary"
          :disabled="assignments.length === 0"
        />
      </div>
    </div>
  </Dialog>
</template>
<script setup>
import { computed, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import cStudentPublicationService from "../../services/cstudentpublication"
import { useNotification } from "../../composables/notification"
import { useCidReq } from "../../composables/cidReq"

const props = defineProps({
  modelValue: Boolean,
  submission: Object,
  currentAssignmentId: Number,
})

const emit = defineEmits(["update:modelValue", "moved"])
const { cid, sid, gid } = useCidReq()
const { t } = useI18n()
const notification = useNotification()

const visible = computed({
  get: () => props.modelValue,
  set: (val) => emit("update:modelValue", val),
})

const assignments = ref([])
const selectedTargetId = ref(null)

watch(
  () => visible.value,
  async (newVal) => {
    if (newVal) {
      resetForm()
      await loadAssignments()
    }
  },
)

function onHide() {
  emit("update:modelValue", false)
}

function resetForm() {
  assignments.value = []
  selectedTargetId.value = null
}

async function loadAssignments() {
  try {
    const response = await cStudentPublicationService.findAll({
      params: {
        cid,
        sid,
        gid,
        "publicationParent.iid": false,
      },
    })

    const json = await response.json()
    const member = json["hydra:member"] || []

    assignments.value = member.filter((a) => a.iid !== props.currentAssignmentId)
    selectedTargetId.value = assignments.value.length ? assignments.value[0].iid : null
  } catch (error) {
    notification.showErrorNotification(error)
  }
}

async function move() {
  if (!selectedTargetId.value) {
    notification.showErrorNotification(t("Please select a target assignment"))
    return
  }

  try {
    await cStudentPublicationService.moveSubmission(props.submission.iid, selectedTargetId.value)
    notification.showSuccessNotification(t("Submission moved successfully"))
    emit("moved")
    emit("update:modelValue", false)
  } catch (error) {
    notification.showErrorNotification(error)
  }
}
</script>
