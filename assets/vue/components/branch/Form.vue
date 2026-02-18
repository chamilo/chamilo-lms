<template>
  <div>
    <BaseInputText
      id="item_title"
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
    />

    <BaseInputText
      id="item_description"
      v-model="v$.item.description.$model"
      :label="t('Description')"
    />

    <div class="text-right">
      <Button
        :disabled="v$.item.$invalid"
        :label="t('Save')"
        icon="mdi mdi-content-save"
        type="button"
        @click="btnSaveOnClick"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, watch, nextTick } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

const validations = {
  item: {
    title: { required },
    description: {},
  },
}

const v$ = useVuelidate(validations, { item: computed(() => props.modelValue) })

watch(
  () => props.modelValue,
  async (newValue) => {
    if (!newValue) return
    await nextTick()
  },
  { immediate: true },
)

function btnSaveOnClick() {
  const item = { ...props.modelValue, ...v$.value.item.$model }
  emit("update:modelValue", item)
  emit("submit", item)
}
</script>
