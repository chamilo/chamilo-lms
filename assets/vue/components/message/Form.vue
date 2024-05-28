<template>
  <div class="grid grid-cols-3 gap-4">
    <div class="col-span-2">
      <BaseInputText
        id="item_title"
        v-model="item.title"
        :label="t('Title')"
      />

      <slot></slot>
    </div>

    <div>
      <div v-if="attachments && attachments.length > 0">
        <div
          v-t="'Attachments'"
          class="text-h6"
        />

        <ul>
          <li
            v-for="(attachment, index) in attachments"
            :key="index"
            class="my-2"
          >
            <audio
              v-if="attachment.type.indexOf('audio') === 0"
              class="max-w-full"
              controls
            >
              <source :src="window.URL.createObjectURL(attachment)" />
            </audio>
          </li>
        </ul>

        <hr />
      </div>

      <AudioRecorder @attach-audio="attachAudios" />
    </div>
  </div>
</template>

<script setup>
import AudioRecorder from "../AudioRecorder"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import { computed } from "vue"

const attachments = defineModel("attachments", {
  type: Array,
})

const props = defineProps({
  values: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => {},
  },
  initialValues: {
    type: Object,
    default: () => {},
  },
})

const { t } = useI18n()

const item = computed(() => props.initialValues || props.values)

const attachAudios = (audio) => attachments.value.push(audio)
</script>
