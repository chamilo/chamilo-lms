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

    <BaseAdvancedSettingsButton
      v-if="showResourceLanguageAdvancedSettings"
      v-model="showAdvancedSettings"
    >
      <ResourceLanguageSelector v-model="languageModel" />
    </BaseAdvancedSettingsButton>

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
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import ResourceLanguageSelector from "../resources/ResourceLanguageSelector.vue"

const { t } = useI18n()
const showAdvancedSettings = ref(false)

function isResourceLanguageActive(language) {
  if (!language || "object" !== typeof language) {
    return false
  }

  if ("available" in language) {
    return true === language.available || 1 === language.available || "1" === language.available
  }

  if ("isAvailable" in language) {
    return true === language.isAvailable || 1 === language.isAvailable || "1" === language.isAvailable
  }

  if ("enabled" in language) {
    return true === language.enabled || 1 === language.enabled || "1" === language.enabled
  }

  return true
}

const showResourceLanguageAdvancedSettings = computed(() => {
  const languages = Array.isArray(window.languages) ? window.languages : []

  return languages.filter(isResourceLanguageActive).length > 1
})

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
      language: {},
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

function extractResourceLanguageIso(language) {
  if (!language) {
    return ""
  }

  if ("string" === typeof language) {
    const iriMatch = language.match(/\/api\/languages\/(\d+)/)
    if (!iriMatch) {
      return language
    }

    const languages = Array.isArray(window.languages) ? window.languages : []
    const found = languages.find((item) => String(item?.id || "") === iriMatch[1])

    return String(found?.isocode || "")
  }

  return String(language.isocode || language.isoCode || "")
}

const languageModel = computed({
  get() {
    return (
      v$.value.item.language.$model ??
      extractResourceLanguageIso(
        props.modelValue?.resourceNode?.language ||
          props.modelValue?.resourceNode?.firstResourceFile?.language ||
          props.modelValue?.firstResourceFile?.language,
      )
    )
  },
  set(value) {
    v$.value.item.language.$model = value ?? ""
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
