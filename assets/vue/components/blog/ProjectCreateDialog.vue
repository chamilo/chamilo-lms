<template>
  <BaseDialog
    v-model:isVisible="visibleProxy"
    :title="t('New project')"
    :width="'560px'"
    header-icon="plus"
  >
    <div class="grid gap-4">
      <BaseInputText
        id="p-title"
        v-model="title"
        :form-submitted="submitted"
        :is-invalid="!title"
        :label="t('Title')"
      />
      <BaseInputText
        id="p-sub"
        v-model="subtitle"
        :label="t('subtitle')"
      />
    </div>
    <template #footer>
      <BaseButton
        :label="t('Create')"
        icon="check"
        type="primary"
        @click="submit"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"

const { t } = useI18n()
const props = defineProps({ isVisible: { type: Boolean, default: false } })
const emit = defineEmits(["update:isVisible", "submitted"])

const visibleProxy = computed({
  get: () => props.isVisible,
  set: (v) => emit("update:isVisible", v),
})

const title = ref("")
const subtitle = ref("")
const submitted = ref(false)

function submit() {
  submitted.value = true

  if (!title.value.trim()) {
    return
  }

  emit("submitted", { title: title.value.trim(), subtitle: subtitle.value.trim() })
}
</script>
