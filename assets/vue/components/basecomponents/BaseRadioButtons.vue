<template>
  <div class="flex flex-col">
    <div v-for="(option, index) in options" :key="option.value" class="flex items-center mr-2">
      <RadioButton
        :input-id="name + index"
        :model-value="modelValue"
        :name="name"
        :value="option.value"
        @change="handleOptionChange(option.value)"
      />
      <label :for="name + index" class="ml-2 cursor-pointer">{{ option.label }}</label>
    </div>
  </div>
</template>

<script>
import RadioButton from 'primevue/radiobutton';
import { ref, watch } from "vue";

export default {
  name: 'BaseRadioButtons',
  components: {
    RadioButton
  },
  props: {
    modelValue: {
      type: String,
      required: true
    },
    name: {
      type: String,
      required: true,
    },
    // Array with {label: x, value: y} for every option you want to support
    options: {
      type: Array,
      required: true,
    },
  },
  setup(props, { emit }) {
    const value = ref(props.modelValue);

    watch(() => props.modelValue, (newValue) => {
      value.value = newValue;
    });

    const handleOptionChange = (newValue) => {
      value.value = newValue;
      emit('update:modelValue', newValue);
    };

    return {
      value,
      handleOptionChange
    };
  },
};
</script>
