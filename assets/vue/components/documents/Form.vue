<template>
  <div>
    <div class="field">
      <FloatLabel variant="on">
        <InputText
          id="item_title"
          v-model="v$.item.title.$model"
          :class="{ 'p-invalid': v$.item.title.$invalid }"
        />
        <label
          for="item_title"
          v-text="t('Title')"
        />
      </FloatLabel>
      <small
        v-if="v$.item.title.$invalid || v$.item.title.$pending.$response"
        class="p-error"
        v-text="v$.item.title.required.$message"
      />
    </div>

    <BaseTextArea
      id="item_comment"
      v-model="commentModel"
      label="Description"
      rows="4"
      auto-resize
    />

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
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import Button from "primevue/button"
import FloatLabel from "primevue/floatlabel"
import InputText from "primevue/inputtext"
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"

const { t } = useI18n()

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(["update:modelValue", "submit"])

const v$ = useVuelidate(
  {
    item: {
      title: {
        required,
      },
      comment: {},
      parentResourceNodeId: {},
    },
  },
  {
    item: computed(() => props.modelValue),
  },
)

const commentModel = computed({
  get() {
    return v$.value.item.comment.$model ?? ""
  },
  set(value) {
    v$.value.item.comment.$model = value ?? ""
  },
})

function btnSaveOnClick() {
  const item = {
    ...props.modelValue,
    ...v$.value.item.$model,
    comment: v$.value.item.comment.$model ?? "",
  }

  emit("update:modelValue", item)
  emit("submit", item)
}
</script>
