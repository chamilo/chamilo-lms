<template>
  <div class="w-full">
    <FloatLabel variant="on">
      <Dropdown
        v-model="innerValue"
        :options="localOptions"
        :loading="loading"
        :showClear="clearable"
        :filter="true"
        :filterFields="filterFields"
        :virtualScrollerOptions="virtual ? { itemSize: 38, showLoader: true } : null"
        :emptyMessage="emptyMessage"
        :placeholder="placeholder"
        optionLabel="label"
        optionValue="id"
        class="w-full"
        panelClass="cm-searchselect-panel"
        @change="emitChange"
      >
        <!-- Item template: label on one line, optional sublabel on second line -->
        <template #option="slotProps">
          <div class="flex flex-col leading-tight">
            <span class="text-sm text-gray-90">{{ slotProps.option.label }}</span>
            <span
              v-if="slotProps.option.sublabel"
              class="text-xs text-gray-50"
            >
              {{ slotProps.option.sublabel }}
            </span>
          </div>
        </template>
      </Dropdown>
      <label :for="inputId">{{ label }}</label>
    </FloatLabel>

    <small
      v-if="hint"
      class="mt-1 block text-xs text-gray-50"
      >{{ hint }}</small
    >
    <small
      v-if="errorText"
      class="mt-1 block text-xs text-danger"
      >{{ errorText }}</small
    >
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import FloatLabel from "primevue/floatlabel"
import Dropdown from "primevue/dropdown"

const props = defineProps({
  modelValue: { type: [String, Number], default: "" },
  options: { type: Array, default: () => [] }, // [{ id, label, sublabel?, payload? }]
  label: { type: String, default: "Select an option" },
  placeholder: { type: String, default: "Search…" },
  inputId: { type: String, default: "searchable-select" },
  loading: { type: Boolean, default: false },
  clearable: { type: Boolean, default: true },
  virtual: { type: Boolean, default: true },
  filterFields: { type: Array, default: () => ["label", "sublabel"] },
  emptyMessage: { type: String, default: "No matches found." },
  hint: { type: String, default: "" },
  errorText: { type: String, default: "" },
})

const emit = defineEmits(["update:modelValue", "change"])

const innerValue = ref(props.modelValue)
const localOptions = ref([...props.options])

watch(
  () => props.modelValue,
  (v) => (innerValue.value = v),
)
watch(
  () => props.options,
  (v) => (localOptions.value = [...v]),
)

function emitChange() {
  emit("update:modelValue", innerValue.value)
  emit("change", innerValue.value)
}
</script>

<style scoped>
:deep(.p-dropdown) {
  @apply w-full rounded border border-gray-25 text-sm;
}
:deep(.p-dropdown .p-inputtext) {
  @apply text-sm;
}
:deep(.cm-searchselect-panel) {
  @apply max-h-80;
}
</style>
