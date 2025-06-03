<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Required courses')"
    class="w-[30rem]"
  >
    <div v-if="sequenceList.length">
      <div
        v-for="item in sequenceList"
        :key="item.name"
        class="mb-4"
      >
        <h4 class="font-semibold text-gray-700 mb-2">{{ item.name }}</h4>
        <ul>
          <li
            v-for="(dep, id) in item.dependents"
            :key="id"
            class="flex items-center gap-2"
          >
            <i :class="dep.status ? 'mdi mdi-check-circle text-green-500' : 'mdi mdi-alert-circle text-red-500'" />
            <span>{{ dep.name }}</span>
          </li>
        </ul>
      </div>
    </div>
    <div
      v-else
      class="text-sm text-gray-500"
    >
      {{ t("No dependencies") }}
    </div>
  </Dialog>
</template>

<script setup>
import { ref, watch, computed } from "vue"
import { useI18n } from "vue-i18n"
import courseService from "../../services/courseService"

const { t } = useI18n()
const props = defineProps({
  visible: Boolean,
  modelValue: Boolean,
  courseId: Number,
  sessionId: Number,
})
const emit = defineEmits(["update:modelValue"])
const sequenceList = ref([])

watch(
  () => props.modelValue,
  async (newVal) => {
    if (newVal && props.courseId) {
      try {
        const { sequenceList: list } = await courseService.getNextCourse(props.courseId, props.sessionId || 0)
        sequenceList.value = list || []
      } catch (e) {
        console.warn("Failed to load sequence info", e)
      }
    }
  },
)

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
})
</script>
