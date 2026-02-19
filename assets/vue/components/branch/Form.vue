<template>
  <div>
    <BaseInputText
      id="item_title"
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
      :maxlength="250"
    />

    <BaseInputText
      id="item_description"
      v-model="v$.item.description.$model"
      :error-text="v$.item.description.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.description.$error"
      :label="t('Description')"
      :maxlength="2000"
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
        <BaseSelect
          id="item_parent"
          v-model="v$.item.parent.$model"
          :label="t('Parent branch')"
          :options="parentOptions"
          option-label="name"
          option-value="id"
          :show-clear="true"
        />

        <BaseInputText
          v-if="props.modelValue['@id']"
          id="item_unique_id"
          :model-value="props.modelValue.uniqueId"
          :label="t('Unique identifier')"
          :disabled="true"
        />

        <BaseInputText
          id="item_branch_ip"
          v-model="v$.item.branchIp.$model"
          :error-text="v$.item.branchIp.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.branchIp.$error"
          :label="t('IP address')"
          :maxlength="40"
          :placeholder="t('IPv4 or IPv6')"
        />

        <BaseInputText
          id="item_latitude"
          v-model="v$.item.latitude.$model"
          :error-text="v$.item.latitude.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.latitude.$error"
          :label="t('Latitude')"
          :placeholder="t('e.g. 48.8566')"
        />

        <BaseInputText
          id="item_longitude"
          v-model="v$.item.longitude.$model"
          :error-text="v$.item.longitude.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.longitude.$error"
          :label="t('Longitude')"
          :placeholder="t('e.g. 2.3522')"
        />

        <BaseInputNumber
          id="item_dwn_speed"
          v-model="v$.item.dwnSpeed.$model"
          :label="t('Download speed (KB/s)')"
          :min="0"
        />

        <BaseInputNumber
          id="item_up_speed"
          v-model="v$.item.upSpeed.$model"
          :label="t('Upload speed (KB/s)')"
          :min="0"
        />

        <BaseInputNumber
          id="item_delay"
          v-model="v$.item.delay.$model"
          :label="t('Delay (ms)')"
          :min="0"
        />

        <BaseInputText
          id="item_admin_mail"
          v-model="v$.item.adminMail.$model"
          :error-text="v$.item.adminMail.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.adminMail.$error"
          :label="t('Administrator e-mail')"
          :maxlength="250"
          :placeholder="t('admin@example.com')"
        />

        <BaseInputText
          id="item_admin_name"
          v-model="v$.item.adminName.$model"
          :error-text="v$.item.adminName.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.adminName.$error"
          :label="t('Administrator name')"
          :maxlength="250"
        />

        <BaseInputText
          id="item_admin_phone"
          v-model="v$.item.adminPhone.$model"
          :error-text="v$.item.adminPhone.$errors.map((error) => error.$message).join('<br>')"
          :is-invalid="v$.item.adminPhone.$error"
          :label="t('Administrator phone number')"
          :maxlength="40"
        />

        <template v-if="props.modelValue['@id']">
          <BaseInputText
            id="item_last_sync_trans_id"
            :model-value="props.modelValue.lastSyncTransId != null ? String(props.modelValue.lastSyncTransId) : ''"
            :label="t('ID of last synch transaction')"
            :disabled="true"
          />

          <BaseInputText
            id="item_last_sync_trans_date"
            :model-value="props.modelValue.lastSyncTransDate ?? ''"
            :label="t('Time of last synchronization')"
            :disabled="true"
          />

          <BaseInputText
            id="item_last_sync_type"
            :model-value="props.modelValue.lastSyncType ?? ''"
            :label="t('Last synchronization type')"
            :disabled="true"
          />

          <BaseInputText
            id="item_ssl_pub_key"
            :model-value="props.modelValue.sslPubKey ?? ''"
            :label="t('SSL public key')"
            :disabled="true"
          />

          <BaseInputText
            id="item_branch_type"
            :model-value="props.modelValue.branchType ?? ''"
            :label="t('Branch type')"
            :disabled="true"
          />
        </template>
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
import BaseInputNumber from "../basecomponents/BaseInputNumber.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import useVuelidate from "@vuelidate/core"
import { required, maxLength, email, decimal, integer, between } from "@vuelidate/validators"
import { useI18n } from "vue-i18n"
import baseService from "../../services/baseService"

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
})

const emit = defineEmits(["update:modelValue", "submit"])

const { t } = useI18n()

const parentOptions = ref([])
const showAdvanced = ref(false)

onMounted(async () => {
  try {
    const { items } = await baseService.getCollection("/api/branches")
    parentOptions.value = items
      .filter((b) => !props.modelValue["@id"] || b["@id"] !== props.modelValue["@id"])
      .map((b) => ({ name: b.title, id: b["@id"] }))
  } catch (e) {
    console.error("Failed to load branches", e)
  }
})

const advancedFields = ["branchIp", "latitude", "longitude", "dwnSpeed", "upSpeed", "delay", "adminMail", "adminName", "adminPhone", "parent"]

const phoneRegex = /^[\d\s+\-().]*$/

const validations = {
  item: {
    title: { required, maxLength: maxLength(250) },
    description: { maxLength: maxLength(2000) },
    parent: {},
    branchIp: { maxLength: maxLength(40) },
    latitude: { decimal, between: between(-90, 90) },
    longitude: { decimal, between: between(-180, 180) },
    dwnSpeed: { integer },
    upSpeed: { integer },
    delay: { integer },
    adminMail: { email, maxLength: maxLength(250) },
    adminName: { maxLength: maxLength(250) },
    adminPhone: {
      maxLength: maxLength(40),
      phoneFormat: (value) => !value || phoneRegex.test(value),
    },
  },
}

const v$ = useVuelidate(validations, { item: computed(() => props.modelValue) })

watch(
  () => props.modelValue,
  async (newValue) => {
    if (!newValue) return
    await nextTick()

    if (newValue.parent && typeof newValue.parent === "object" && newValue.parent["@id"]) {
      emit("update:modelValue", { ...newValue, parent: newValue.parent["@id"] })
    }

    if (advancedFields.some((f) => newValue[f] != null && newValue[f] !== "")) {
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
