<template>
  <BaseToolbar>
    <BaseButton
      v-if="isAllowedToEdit"
      :label="t('Create assignment')"
      icon="file-cloud-add"
      type="black"
      @click="goToNewAssignment"
    />
  </BaseToolbar>
  <TeacherAssignmentList :isAllowedToEdit="isAllowedToEdit" />
</template>
<script setup>
import TeacherAssignmentList from "../../components/assignments/TeacherAssignmentList.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { ref, onMounted } from "vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"

const route = useRoute();
const router = useRouter()
const securityStore = useSecurityStore()
const { t } = useI18n();

const isAllowedToEdit = ref(false)

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
})

function goToNewAssignment() {
  router.push({
    name: "AssignmentsCreate",
    query: route.query,
  })
}
</script>
