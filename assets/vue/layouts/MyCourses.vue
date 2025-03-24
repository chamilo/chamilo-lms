<template>
  <SectionHeader :title="t('My courses')">
    <BaseAppLink :to="{ name: 'CourseCreate' }">
      <BaseButton
        v-if="isTeacher"
        type="secondary"
        icon="plus"
        :label="t('Course')"
      />
    </BaseAppLink>
  </SectionHeader>
  <router-view />
</template>

<script setup>
import { useI18n } from "vue-i18n"
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../store/securityStore"
import SectionHeader from "../components/layout/SectionHeader.vue"
import BaseButton from "../components/basecomponents/BaseButton.vue"
import BaseAppLink from "../components/basecomponents/BaseAppLink.vue"

const { t } = useI18n()
const { isTeacher } = storeToRefs(useSecurityStore())
</script>
