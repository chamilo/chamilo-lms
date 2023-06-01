<template>
  <div class="field">
    <div class="p-float-label">
      <InputText :id="id" v-model="baseModel" :class="{ 'p-invalid': isInvalid }" type="text" />
      <label v-t="label" :class="{ 'p-error': isInvalid }" :for="id" />
    </div>
    <small v-if="isInvalid" v-t="helpText" class="p-error" />
  </div>
</template>

<script setup>
import { ref, watch } from "vue";
import InputText from "primevue/inputtext";

defineProps({
  id: {
    type: String,
    require: true,
    default: "",
  },
  label: {
    type: String,
    required: true,
    default: "",
  },
  value: {
    type: Object,
    require: true,
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
});

const emit = defineEmits(["update:value"]);

const baseModel = ref(null);

watch(baseModel, (newValue) => emit("update:value", newValue));
</script>
