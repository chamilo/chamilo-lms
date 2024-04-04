<template>
  <BaseToolbar>
    <BaseButton
      :label="t('Create assignment')"
      icon="file-cloud-add"
      type="black"
      @click="goToNewAssigment"
    />
  </BaseToolbar>
  <TeacherAssignmentList v-if="isUserTeacher" />
</template>
<script setup>
import TeacherAssignmentList from "../../components/assignments/TeacherAssignmentList.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"

const route = useRoute();
const router = useRouter()
const securityStore = useSecurityStore()
const isUserTeacher = securityStore.isCurrentTeacher
const { t } = useI18n();

function goToNewAssigment() {
  router.push({
    name: "AssigmnentsCreate",
    query: route.query,
  })
}
</script>
