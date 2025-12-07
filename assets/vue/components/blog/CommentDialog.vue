<template>
  <BaseDialog
    v-model:isVisible="visible"
    :title="dialogTitleComputed"
    :header-icon="headerIconComputed"
    :width="'560px'"
  >
    <div class="space-y-3">
      <textarea
        v-model="text"
        rows="5"
        class="w-full border rounded p-2"
        :placeholder="placeholderComputed"
        @keydown.ctrl.enter.prevent="submit"
      />
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500">{{ t('Press Ctrl+Enter to send') }}</span>
        <div class="flex gap-2">
          <BaseButton type="black" icon="close" :label="t('Cancel')" @click="close" />
          <BaseButton
            type="primary"
            :icon="confirmIconComputed"
            :label="confirmLabelComputed"
            :disabled="!text.trim()"
            @click="submit"
          />
        </div>
      </div>
    </div>
  </BaseDialog>
</template>

<script setup>
import { ref, watch, computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const emit = defineEmits(["close","submitted"])

const props = defineProps({
  initialText: { type: String, default: "" },
  dialogTitle: { type: String, default: "" },
  confirmLabel: { type: String, default: "" },
  headerIcon: { type: String, default: "" },
  placeholder: { type: String, default: "" },
})

const visible = ref(true)
const text = ref(props.initialText)

// Sync when parent changes initialText between openings
watch(() => props.initialText, (v) => { text.value = v || "" })

// Compute fallbacks to keep previous UX intact
const dialogTitleComputed = computed(() => props.dialogTitle || t("Add comment"))
const confirmLabelComputed = computed(() => props.confirmLabel || t("Send"))
const headerIconComputed = computed(() => props.headerIcon || "comment")
const placeholderComputed = computed(() => props.placeholder || t("Write your comment here..."))
const confirmIconComputed = computed(() => (props.confirmLabel ? "check" : "send"))

function close(){ visible.value=false; emit("close") }
function submit(){
  if (!text.value.trim()) return
  emit("submitted", { text: text.value.trim() })
  close()
}
</script>
