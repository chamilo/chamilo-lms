<template>
  <div class="flex justify-between items-center">
    <h2 v-t="'My courses'" />
    <Button
      v-if="isTeacher"
      :label="t('Course')"
      class="p-button-secondary hidden md:inline-flex"
      icon="pi pi-plus"
      @click="redirectToCreateCourse"
    />
  </div>
  <hr>
  <router-view />
</template>

<script setup>
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';
import { storeToRefs } from "pinia"
import { useSecurityStore } from "../store/securityStore"
import { useRouter } from "vue-router"

const { t } = useI18n();
const { isTeacher } = storeToRefs(useSecurityStore())
const router = useRouter()

const redirectToCreateCourse = () => {
  router.push({ name: 'CourseCreate' })
}
</script>
