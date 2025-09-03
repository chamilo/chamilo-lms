<template>
  <div>
    <div class="flex items-center gap-2 mb-4">
      <BaseIcon
        icon="back"
        size="big"
        @click="goBack"
        :title="t('Back')"
      />
      <BaseIcon
        icon="email-unread"
        size="big"
        :title="t('Send email to all')"
        @click="sendEmailToAll"
      />
    </div>
    <hr />
    <h1 class="text-xl font-bold">
      {{ t("Users without submission for assignment") }}: {{ assignment?.title || `#${id}` }}
    </h1>

    <div
      v-if="loading"
      class="flex justify-center my-10 m-4"
    >
      <ProgressSpinner />
    </div>

    <BaseTable
      v-else-if="users.length"
      :values="users"
      data-key="id"
    >
      <Column :header="t('Last name')">
        <template #body="slotProps">
          {{ slotProps.data.lastname }}
        </template>
      </Column>

      <Column :header="t('First name')">
        <template #body="slotProps">
          {{ slotProps.data.firstname }}
        </template>
      </Column>

      <Column :header="t('E-mail')">
        <template #body="slotProps">
          <a
            class="text-blue-600 hover:underline"
            :href="`mailto:${slotProps.data.email}`"
          >
            {{ slotProps.data.email }}
          </a>
        </template>
      </Column>
    </BaseTable>

    <div
      v-else
      class="text-gray-500"
    >
      {{ t("No missing submissions found for this assignment or data is not available.") }}
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import cstudentpublication from "../../services/cstudentpublication"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import Column from "primevue/column"
import ProgressSpinner from "primevue/progressspinner"
import { useCidReq } from "../../composables/cidReq"
import { useNotification } from "../../composables/notification"

const props = defineProps({
  id: [String, Number],
})

const users = ref([])
const assignment = ref(null)
const loading = ref(true)

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { cid, sid, gid } = useCidReq()
const notification = useNotification()

onMounted(async () => {
  loading.value = true
  try {
    users.value = await cstudentpublication.getUnsubmittedUsers(props.id)
    assignment.value = await cstudentpublication.getAssignmentMetadata(props.id, cid, sid, gid)
  } catch (error) {
    console.error("Error loading data", error)
  } finally {
    loading.value = false
  }
})

function goBack() {
  router.push({
    name: "AssignmentDetail",
    params: { id: props.id },
    query: route.query,
  })
}

async function sendEmailToAll() {
  try {
    await cstudentpublication.sendEmailToUnsubmitted(props.id, {
      cid,
      ...(sid && { sid }),
      ...(gid && { gid }),
    })
    notification.showSuccessNotification(t("Email sent to all unsubmitted users."))
  } catch (error) {
    notification.showErrorNotification(t("Failed to send email."))
  }
}
</script>
