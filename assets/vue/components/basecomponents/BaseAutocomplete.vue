<template>
  <div class="field">
    <FloatLabel variant="on">
      <AutoComplete
        v-model="modelValue"
        :disabled="disabled"
        :input-id="id"
        :min-length="3"
        :multiple="isMultiple"
        :option-label="optionLabel"
        :suggestions="suggestions"
        fluid
        force-selection
        @complete="onComplete"
        @item-select="$emit('item-select', $event)"
      >
        <template
          v-if="hasChipSlot"
          #chip="{ value }"
        >
          <slot
            :value="value"
            name="chip"
          ></slot>
        </template>
        <template #removetokenicon="slopProps">
          <span class="p-autocomplete-token-icon">
            <BaseIcon
              icon="close"
              @click="slopProps.removeCallback"
            />
          </span>
        </template>
      </AutoComplete>
      <label
        v-t="Label"
        :for="id"
      />
    </FloatLabel>
    <small
      v-if="isInvalid"
      v-t="Error message"
      class="p-error"
    />
  </div>
</template>

<script setup>
import { onMounted, ref, useSlots } from "vue"
import FloatLabel from "primevue/floatlabel"
import AutoComplete from "primevue/autocomplete"
import BaseIcon from "./BaseIcon.vue"

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

const slots = useSlots()
const hasChipSlot = ref(false)

onMounted(() => {
  hasChipSlot.value = !!slots.chip
})
</script>
