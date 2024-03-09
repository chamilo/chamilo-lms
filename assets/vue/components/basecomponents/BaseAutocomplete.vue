<template>
  <div class="field">
    <div class="p-float-label">
      <AutoComplete
        v-model="baseModel"
        :input-id="id"
        :multiple="isMultiple"
        :suggestions="suggestions"
        force-selection
        :option-label="optionLabel"
        :disabled="disabled"
        :min-length="3"
        @complete="onComplete"
        @item-select="$emit('item-select', $event)"
        @update:model-value="$emit('update:modelValue', $event)"
      />
      <label
        v-t="label"
        :for="id"
      />
    </div>
    <small
      v-if="isInvalid"
      v-t="helpText"
      class="p-error"
    />
  </div>
</template>

<script setup>
import AutoComplete from "primevue/autocomplete"
import { ref } from "vue"

const props = defineProps({
  id: {
    type: String,
    required: true,
    default: null,
  },
  label: {
    type: String,
    required: true,
    default: null,
  },
  modelValue: {
    type: Array,
    require: true,
    default: () => [],
  },
  helpText: {
    type: String,
    required: false,
    default: null,
  },
  isInvalid: {
    type: Boolean,
    required: false,
    default: false,
  },
  isMultiple: {
    type: Boolean,
    require: false,
    default: false,
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false,
  },
  search: {
    type: Function,
    required: true,
    default: () => {},
  },
  optionLabel: {
    type: String,
    required: false,
    default: () => "name",
  },
})

defineEmits(["update:modelValue", "item-select"])

const baseModel = ref([])

const suggestions = ref([])

const onComplete = async (event) => {
  try {
    const members = await props.search(event.query)
    suggestions.value = members && members.length ? members : []
  } catch (error) {
    console.error('Error during onComplete:', error)
    suggestions.value = []
  }
}
</script>
