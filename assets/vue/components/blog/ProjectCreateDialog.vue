<template>
  <BaseDialog
    v-model:isVisible="visibleProxy"
    :title="t('New project')"
    header-icon="plus"
    :width="'560px'"
  >
    <div class="grid gap-4">
      <BaseInputText id="p-title" :label="t('Title')" v-model="title"
                     :form-submitted="submitted" :is-invalid="!title" />
      <BaseInputText id="p-sub" :label="t('Subtitle')" v-model="subtitle" />
    </div>
    <template #footer>
      <BaseButton type="black" icon="close" :label="t('Cancel')" @click="close" />
      <BaseButton type="primary" icon="check" :label="t('Create')" @click="submit" />
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

function close() { visibleProxy.value = false }
function submit() {
  submitted.value = true
  if (!title.value.trim()) return
  emit("submitted", { title: title.value.trim(), subtitle: subtitle.value.trim() })
}
</script>
