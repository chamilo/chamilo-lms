<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Session requirements and dependencies')"
    class="w-[30rem] z-[99999] !block !opacity-100"
  >
    <div v-if="hasData">
      <div
        v-for="section in requirements"
        :key="section.name"
        class="mb-4"
      >
        <h4 class="font-semibold text-gray-700 mb-2">
          {{ section.name }}
        </h4>
        <ul class="list-disc pl-5 text-sm text-gray-700">
          <li
            v-for="req in section.requirements"
            :key="req.name"
            class="flex items-center gap-2"
          >
            <i
              v-if="req.status !== null"
              :class="req.status
            ? 'mdi mdi-check-circle text-green-500'
            : 'mdi mdi-alert-circle text-red-500'"
            ></i>
            <span v-html="req.adminLink || req.name"></span>
          </li>
        </ul>
      </div>

      <div
        v-if="graphImage"
        class="mt-4 text-center"
      >
        <img
          :src="graphImage"
          alt="Graph"
          class="max-w-full max-h-96 mx-auto border rounded"
        />
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
import Dialog from "primevue/dialog"
import { computed } from "vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

const props = defineProps({
  modelValue: Boolean,
  courseId: Number,
  sessionId: Number,
  requirements: Array,
  dependencies: Array,
  graphImage: String,
})

const emit = defineEmits(["update:modelValue"])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
})

const requirements = computed(() => props.requirements || [])
const dependencies = computed(() => props.dependencies || [])

const hasData = computed(() => {
  return requirements.value.length > 0 || dependencies.value.length > 0
})
</script>
