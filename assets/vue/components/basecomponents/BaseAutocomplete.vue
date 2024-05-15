<template>
  <div class="field">
    <FloatLabel>
      <AutoComplete
        v-model="modelValue"
        :input-id="id"
        :multiple="isMultiple"
        :suggestions="suggestions"
        force-selection
        :option-label="optionLabel"
        :disabled="disabled"
        :min-length="3"
        @complete="onComplete"
        @item-select="$emit('item-select', $event)"
      />
      <label
        v-t="label"
        :for="id"
      />
    </FloatLabel>
    <small
      v-if="isInvalid"
      v-t="helpText"
      class="p-error"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import FloatLabel from "primevue/floatlabel"
import AutoComplete from "primevue/autocomplete"

const modelValue = defineModel({
  type: [Array, String],
  require: true,
})

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

defineEmits(["item-select"])

const suggestions = ref([])

const onComplete = async (event) => {
  try {
    const members = await props.search(event.query)
    suggestions.value = members && members.length ? members : []
  } catch (error) {
    console.error("Error during onComplete:", error)
    suggestions.value = []
  }
}
</script>
