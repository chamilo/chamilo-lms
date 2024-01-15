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
import { computed } from "vue"
import { useStore } from "vuex"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router"

const route = useRoute();
const router = useRouter()
const store = useStore()
const isUserTeacher = computed(() => store.getters["security/isCurrentTeacher"])
const { t } = useI18n();

function goToNewAssigment() {
  router.push({
    name: "AssigmnentsCreate",
    query: route.query,
  })
}
</script>
