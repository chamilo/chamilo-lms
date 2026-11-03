<template>
  <div>
    <div class="flex items-center gap-2 mb-4">
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        size="small"
        type="black"
        @click="goBack"
      />
      <BaseButton
        :label="t('Send email to all')"
        icon="email-unread"
        only-icon
        size="small"
        type="black"
        :disabled="loading || sending || !users.length"
        :is-loading="sending"
        @click="sendEmailToAll"
      />
      <BaseButton
        :label="t('Remind all selected users')"
        icon="email-unread"
        only-icon
        size="small"
        type="primary"
        :disabled="loading || sending || !selectedUsers.length"
        :is-loading="sending"
        @click="confirmSelectedReminder"
      />
      <span
        v-if="selectedUsers.length"
        class="text-sm text-gray-600"
      >
        {{ t("{0} users selected", [selectedUsers.length]) }}
      </span>
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
      v-model:selected-items="selectedUsers"
      :values="users"
      data-key="id"
    >
      <Column selection-mode="multiple" />

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
      {{ t("No missing submissions found for this assignment or data is not available") }}
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import cstudentpublication from "../../services/cstudentpublication"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import Column from "primevue/column"
import ProgressSpinner from "primevue/progressspinner"
import { getCourseContext } from "../../utils/courseContext"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"

const props = defineProps({
  id: [String, Number],
})

const users = ref([])
const assignment = ref(null)
const loading = ref(true)
const sending = ref(false)
const selectedUsers = ref([])

const router = useRouter()
const route = useRoute()
const { t } = useI18n()
const { cid, sid, gid } = getCourseContext()
const notification = useNotification()
const { requireConfirmation } = useConfirmation()

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

function confirmSelectedReminder() {
  if (!selectedUsers.value.length || sending.value) return

  const selectedIds = selectedUsers.value.map((user) => user.id)
  requireConfirmation({
    title: t("Remind all selected users"),
    message: `${t("Please confirm your choice")} (${t("{0} users selected", [selectedUsers.value.length])})`,
    accept: () => sendReminders(selectedIds),
  })
}

async function sendReminders(userIds = null) {
  if (sending.value || (userIds !== null && !userIds.length)) return

  sending.value = true
  try {
    await cstudentpublication.sendEmailToUnsubmitted(
      props.id,
      { cid, ...(sid && { sid }), ...(gid && { gid }) },
      userIds,
    )
    notification.showSuccessNotification(t(userIds === null ? "Email sent to all unsubmitted users" : "Email sent"))
  } catch {
    notification.showErrorNotification(t("Failed to send email"))
  } finally {
    selectedUsers.value = []
    try {
      users.value = await cstudentpublication.getUnsubmittedUsers(props.id)
    } catch (error) {
      console.error("Failed to refresh unsubmitted users", error)
    }
    sending.value = false
  }
}

function sendEmailToAll() {
  return sendReminders()
}
</script>
