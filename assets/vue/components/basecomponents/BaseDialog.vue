<script setup>
import { computed } from "vue"
import Dialog from "primevue/dialog"
import { useI18n } from "vue-i18n"
import { buttonTypeValidator, iconValidator } from "./validators"
import BaseIcon from "./BaseIcon.vue"
import BaseButton from "./BaseButton.vue"

const { t } = useI18n()

const isVisible = defineModel("isVisible", {
  required: true,
  type: Boolean,
})

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  headerIcon: {
    type: String,
    default: "",
    validator: (value) => {
      if (value === "") {
        return true
      }
      return iconValidator(value)
    },
  },
  showCloseButton: {
    type: Boolean,
    default: true,
  },
  closeLabel: {
    type: String,
    default: "",
  },
  closeIcon: {
    type: String,
    default: "close",
    validator: iconValidator,
  },
  closeType: {
    type: String,
    default: "black",
    validator: buttonTypeValidator,
  },
})

const innerCloseLabel = computed(() => (props.closeLabel === "" ? t("Cancel") : props.closeLabel))
</script>

<template>
  <Dialog
    v-model:visible="isVisible"
    :modal="true"
    class="p-fluid"
  >
    <template #header>
      <div class="text-left">
        <BaseIcon
          v-if="headerIcon"
          :icon="headerIcon"
          class="mr-2"
        />
        <span class="font-semibold">{{ title }}</span>
      </div>
    </template>
    <slot></slot>
    <template #footer>
      <BaseButton
        v-if="showCloseButton"
        :icon="closeIcon"
        :label="innerCloseLabel"
        :type="closeType"
        @click="isVisible = false"
      />
      <slot name="footer"></slot>
    </template>
  </Dialog>
</template>
