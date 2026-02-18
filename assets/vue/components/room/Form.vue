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

    <BaseSelect
      id="item_branch"
      v-model="v$.item.branch.$model"
      :error-text="v$.item.branch.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.branch.$error"
      :label="t('Branch')"
      :options="branchOptions"
      option-label="name"
      option-value="id"
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
import { computed, onMounted, ref, watch, nextTick } from "vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import baseService from "../../services/baseService"

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

const branchOptions = ref([])

onMounted(async () => {
  try {
    const { items } = await baseService.getCollection("/api/branches")
    branchOptions.value = items.map((b) => ({
      name: b.title,
      id: b["@id"],
    }))
  } catch (e) {
    console.error("Failed to load branches", e)
  }
})

const validations = {
  item: {
    title: { required },
    description: {},
    branch: { required },
  },
}

const v$ = useVuelidate(validations, { item: computed(() => props.modelValue) })

watch(
  () => props.modelValue,
  async (newValue) => {
    if (!newValue) return
    await nextTick()

    if (newValue.branch && typeof newValue.branch === "object" && newValue.branch["@id"]) {
      emit("update:modelValue", { ...newValue, branch: newValue.branch["@id"] })
    }
  },
  { immediate: true },
)

function btnSaveOnClick() {
  const item = { ...props.modelValue, ...v$.value.item.$model }
  emit("update:modelValue", item)
  emit("submit", item)
}
</script>
