<template>
  <div>
    <div class="field">
      <div class="p-float-label">
        <InputText
          id="item_title"
          v-model="v$.item.title.$model"
          :class="{ 'p-invalid': v$.item.title.$invalid }"
        />
        <label
          v-t="'Title'"
          :class="{ 'p-error': v$.item.title.$invalid }"
          for="item_title"
        />
      </div>
      <small
        v-if="v$.item.title.$invalid || v$.item.title.$pending.$response"
        v-t="v$.item.title.required.$message"
        class="p-error"
      />
    </div>

    <slot />

    <div class="text-right">
      <Button
        :disabled="v$.item.$invalid"
        :label="t('Save')"
        class="p-button-secondary"
        icon="mdi mdi-content-save"
        type="button"
        @click="btnSaveOnClick"
      />
    </div>
  </div>
</template>

<script setup>
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => {},
  }
});

const emit = defineEmits([
  'update:modelValue',
  'submit',
]);

const v$ = useVuelidate(
  {
    item: {
      title: {
        required,
      },
      parentResourceNodeId: {},
    },
  },
  {
    item: computed(() => props.modelValue),
  }
);

function btnSaveOnClick () {
  const item = { ...props.modelValue, ...v$.value.item.$model };

  emit('update:modelValue', item);

  emit('submit', item);
}
</script>
