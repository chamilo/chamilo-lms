<template>
  <div class="basic-user-info">
    <BaseUserAvatar
      :image-url="profileImageUrl"
      :alt="t('Picture')"
    />
    <div class="basic-user-info__caption">
      <div v-text="fullName" />
      <div v-text="username" />
    </div>
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
