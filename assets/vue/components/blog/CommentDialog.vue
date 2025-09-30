<template>
  <BaseDialog v-model:isVisible="visible" :title="t('Add Comment')" header-icon="comment" :width="'560px'">
    <div class="space-y-3">
      <textarea v-model="text" rows="5" class="w-full border rounded p-2" :placeholder="t('Write a comment…')" @keydown.ctrl.enter.prevent="submit" />
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500">{{ t('Press Ctrl+Enter to send') }}</span>
        <div class="flex gap-2">
          <BaseButton type="black" icon="close" :label="t('Cancel')" @click="close" />
          <BaseButton type="primary" icon="send" :label="t('Send')" :disabled="!text.trim()" @click="submit" />
        </div>
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const emit = defineEmits(["close","submitted"])
const visible = ref(true)
const text = ref("")
function close(){ visible.value=false; emit("close") }
function submit(){ if (!text.value.trim()) return; emit("submitted", { text: text.value.trim() }); close() }
</script>
