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
import { useI18n } from "vue-i18n"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import gradebookService from "../../services/gradebookService"

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
 * Creates or updates the forum participation gradebook item via the API.
 */
async function submit() {
  if (!isValid.value) {
    return
  }

  // Weight is the highest award: pointsMany when set, otherwise pointsOne.
  const weight = Math.max(Number(form.pointsOne) || 0, hasMany.value ? Number(form.pointsMany) : 0)

  if (isEdit.value) {
    await gradebookService.updateForumParticipationLink(props.link.id, {
      pointsOne: String(form.pointsOne),
      pointsMany: hasMany.value ? String(form.pointsMany) : null,
      weight,
    })
  } else {
    await gradebookService.createForumParticipationLink({
      threadId: form.threadId,
      courseId: props.courseId,
      categoryId: props.categoryId,
      pointsOne: form.pointsOne,
      pointsMany: form.pointsMany,
    })
  }

  emit("saved")
}
</script>
