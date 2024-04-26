<template>
  <div class="flex items-center gap-0">
    <BaseUserAvatar
      :image-url="profileImageUrl"
      :alt="t('Picture')"
      class="mr-2 mb-2"
      size="small"
    />
    <p>{{ fullName }} ({{ username }})</p>
  </div>
</template>

<script setup>
import BaseUserAvatar from "../../components/basecomponents/BaseUserAvatar.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()

defineProps({
  profileImageUrl: {
    type: String,
    required: true,
  },
  fullName: {
    type: String,
    required: true,
  },
  username: {
    type: String,
    required: true,
  },
})
</script>

<style scoped></style>
