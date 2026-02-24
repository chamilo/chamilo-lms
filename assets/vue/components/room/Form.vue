<template>
  <div>
    <BaseInputText
      id="item_title"
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
      :maxlength="255"
    />

    <BaseInputText
      id="item_description"
      v-model="v$.item.description.$model"
      :error-text="v$.item.description.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.description.$error"
      :label="t('Description')"
      :maxlength="2000"
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

    <div class="mt-4">
      <button
        type="button"
        class="flex items-center gap-2 text-sm font-semibold text-primary hover:underline"
        @click="showAdvanced = !showAdvanced"
      >
        <i :class="showAdvanced ? 'mdi mdi-chevron-up' : 'mdi mdi-chevron-down'" />
        {{ t("Advanced settings") }}
      </button>

      <div
        v-if="showAdvanced"
        class="mt-4 flex flex-col gap-2 border-l-2 border-gray-200 pl-4"
      >
        <BaseInputText
          id="item_geolocation"
          v-model="v$.item.geolocation.$model"
          :error-text="v$.item.geolocation.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.geolocation.$error"
          :label="t('Geolocation')"
          :maxlength="255"
          :placeholder="t('Latitude, Longitude (e.g. 48.8566, 2.3522)')"
        />

        <BaseInputText
          id="item_ip"
          v-model="v$.item.ip.$model"
          :error-text="v$.item.ip.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.ip.$error"
          :label="t('IP address')"
          :maxlength="45"
          :placeholder="t('IPv4 or IPv6')"
        />

        <BaseInputText
          id="item_ip_mask"
          v-model="v$.item.ipMask.$model"
          :error-text="v$.item.ipMask.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.ipMask.$error"
          :label="t('IP mask')"
          :maxlength="6"
          :placeholder="t('e.g. /24')"
        />
      </div>
    </div>

    <div class="text-right mt-4">
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
import { required, maxLength, helpers } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import baseService from "../../services/baseService"

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

const branchOptions = ref([])
const showAdvanced = ref(false)

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

const ipMaskFormat = helpers.withMessage(
  "Must be in CIDR format (e.g. /24)",
  (value) => !value || /^\/\d{1,3}$/.test(value),
)

const validations = {
  item: {
    title: { required, maxLength: maxLength(255) },
    description: { maxLength: maxLength(2000) },
    branch: { required },
    geolocation: { maxLength: maxLength(255) },
    ip: { maxLength: maxLength(45) },
    ipMask: { maxLength: maxLength(6), ipMaskFormat },
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

    if (newValue.geolocation || newValue.ip || newValue.ipMask) {
      showAdvanced.value = true
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
