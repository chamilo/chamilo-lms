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

    <div class="space-y-4">
      <p class="text-h6">
        <BaseIcon icon="attachment" />
        {{ t("Attachments") }}
      </p>

      <ul
        v-if="attachments && attachments.length > 0"
        class="space-y-2"
      >
        <li
          v-for="(attachment, index) in attachments"
          :key="index"
          class="text-body-2"
          v-text="attachment.originalName"
        />
      </ul>

      <BaseUploader
        field-name="file"
        :endpoint="resourceFileService.endpoint"
        @upload-success="onUploadSuccess"
      />
    </div>
  </div>
</template>

<script setup>
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import { computed } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseUploader from "../basecomponents/BaseUploader.vue"
import resourceFileService from "../../services/resourceFileService"

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

const onUploadSuccess = ({ response }) => attachments.value.push(response)
</script>
