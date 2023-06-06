<template>
  <div class="field">
    <div class="p-float-label">
      <AutoComplete
        v-model="baseModel"
        :input-id="id"
        :multiple="isMultiple"
        :suggestions="suggestions"
        force-selection
        option-label="name"
        @complete="onComplete"
      />
      <label :for="id" v-t="label" />
    </div>
    <small v-if="isInvalid" v-t="helpText" class="p-error" />
  </div>
</template>

<script setup>
import AutoComplete from "primevue/autocomplete";
import { ref, watch } from "vue";

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
  search: {
    type: Function,
    required: true,
    default: () => {},
  },
});

const emit = defineEmits(["update:modelValue"]);

const baseModel = ref([]);

watch(baseModel, (newValue) => emit("update:modelValue", newValue.map(item => item.value)));

const suggestions = ref([]);

const onComplete = (event) => {
  if (event.query.length >= 3) {
    props.search(event.query).then((members) => (suggestions.value = members));
  }
};
</script>
