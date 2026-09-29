<template>
  <form
    class="flex flex-col gap-4"
    @submit.prevent="submit"
  >
    <BaseSelect
      id="forum-participation-thread"
      v-model="form.threadId"
      name="threadId"
      :label="t('Forum thread')"
      :options="threadOptions"
      option-label="title"
      option-value="id"
      :disabled="isEdit"
    />

    <div class="flex gap-4 items-end">
      <BaseInputNumber
        id="forum-participation-points-one"
        v-model="form.pointsOne"
        name="pointsOne"
        :label="t('Points for one message')"
        :min="0"
      />
      <BaseInputNumber
        id="forum-participation-points-many"
        v-model="form.pointsMany"
        name="pointsMany"
        :label="t('Points for two or more messages')"
        :min="0"
      />
    </div>

    <div class="flex gap-2 justify-end">
      <BaseButton
        type="plain"
        :label="t('Cancel')"
        @click="$emit('cancel')"
      />
      <BaseButton
        type="success"
        icon="save"
        :label="t('Save')"
        :disabled="!isValid"
        @click="submit"
      />
    </div>
  </form>
</template>

<script setup>
import { computed, reactive } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import gradebookService from "../../services/gradebookService"
import { getCourseContext } from "../../utils/courseContext"

const props = defineProps({
  courseId: {
    type: Number,
    required: true,
  },
  categoryId: {
    type: [Number, String],
    required: true,
  },
  threads: {
    type: Array,
    required: true,
  },
  link: {
    type: Object,
    required: false,
    default: null,
  },
})

const emit = defineEmits(["saved", "cancel"])

const { t } = useI18n()
const route = useRoute()
const { cid, sid, gid } = getCourseContext()

const isEdit = computed(() => null !== props.link)

const form = reactive({
  threadId: props.link?.refId ?? null,
  pointsOne: Number(props.link?.pointsOne ?? 0),
  // pointsMany is optional (the 2+ messages bonus); null means "one or more = pointsOne".
  pointsMany: props.link?.pointsMany != null ? Number(props.link.pointsMany) : null,
})

const threadOptions = computed(() => props.threads)

const hasMany = computed(() => form.pointsMany !== null && form.pointsMany !== "" && form.pointsMany >= 0)

const isValid = computed(() => null !== form.threadId && form.pointsOne >= 0)

/**
 * Creates or updates the forum participation item through the Gradebook action processor.
 */
async function submit() {
  if (!isValid.value) {
    return
  }

  const contextParams = {
    cid: Number(props.courseId || cid),
    sid: Number(sid || 0),
    gid: Number(gid || 0),
    node: Number(route.params.node || 0),
  }
  const options = await gradebookService.getLinkOptions({
    ...contextParams,
    categoryId: Number(props.categoryId),
    ...(isEdit.value
      ? { linkId: Number(props.link.id) }
      : { type: 11, refId: Number(form.threadId) }),
  })

  await gradebookService.runLinkAction(
    {
      action: isEdit.value ? "update" : "create",
      linkId: isEdit.value ? Number(props.link.id) : null,
      categoryId: Number(props.categoryId),
      type: isEdit.value ? null : 11,
      refId: isEdit.value ? null : Number(form.threadId),
      weight: null,
      minScore: Number(props.link?.minScore || 0),
      pointsOne: Number(form.pointsOne || 0),
      pointsMany: hasMany.value ? Number(form.pointsMany) : null,
      submittedCsrfToken: options?.csrfToken || "",
    },
    contextParams,
  )

  emit("saved")
}
</script>
