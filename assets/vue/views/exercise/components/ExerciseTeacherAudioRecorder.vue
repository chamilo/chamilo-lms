<template>
  <div
    class="flex flex-col gap-2"
    data-testid="teacher-audio-recorder"
  >
    <span class="text-sm font-semibold text-gray-700">{{ t("Audio comment") }}</span>
    <AudioRecorder
      v-if="!modelValue"
      :multiple="false"
      :show-recorded-audios="false"
      @recorded-audio="onRecorded"
    />
    <div
      v-else
      class="flex flex-wrap items-center gap-2"
    >
      <audio
        class="max-w-full"
        controls
        :src="previewUrl"
      />
      <BaseButton
        :disabled="disabled"
        :label="t('Delete')"
        icon="delete"
        only-icon
        size="small"
        type="danger-text"
        @click="discard"
      />
    </div>
    <span
      v-if="modelValue"
      class="text-xs text-gray-600"
    >
      {{ t("The recording will be saved with the correction.") }}
    </span>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref } from "vue"
import { useI18n } from "vue-i18n"
import AudioRecorder from "../../../components/AudioRecorder.vue"
import BaseButton from "../../../components/basecomponents/BaseButton.vue"

defineProps({
  modelValue: {
    type: File,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(["update:modelValue"])

const { t } = useI18n()

const previewUrl = ref("")

function onRecorded(audioBlob) {
  revokePreview()
  previewUrl.value = window.URL.createObjectURL(audioBlob)
  emit("update:modelValue", new File([audioBlob], "teacher-comment.wav", { type: "audio/wav" }))
}

function discard() {
  revokePreview()
  emit("update:modelValue", null)
}

function revokePreview() {
  if (previewUrl.value) {
    window.URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = ""
  }
}

onBeforeUnmount(revokePreview)
</script>
