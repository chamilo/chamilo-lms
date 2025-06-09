<template>
  <Dialog
    v-model:visible="visible"
    modal
    :header="t('Required courses')"
    class="w-[30rem] z-[99999] !block !opacity-100"
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
            v-for="(req, id) in item.requirements"
            :key="id"
            class="flex items-center gap-2"
          >
            <i :class="req.status ? 'mdi mdi-check-circle text-green-500' : 'mdi mdi-alert-circle text-red-500'" />
            <span>{{ req.name }}</span>
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
    <div
      v-if="graphImage"
      class="mb-4 text-center"
    >
      <img
        :src="graphImage"
        alt="Graph"
        class="max-w-full max-h-96 mx-auto border rounded"
      />
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
  graphImage: String,
})

const emit = defineEmits(["update:modelValue"])

const visible = computed({
  get: () => props.modelValue,
  set: (value) => emit("update:modelValue", value),
})

const sequenceList = computed(() => props.requirements || [])
</script>
