<template>
  <section class="space-y-6">
    <BaseToolbar v-if="backRoute">
      <template #end>
        <BaseButton
          :label="t('Back')"
          :route="backRoute"
          icon="back"
          only-icon
          size="normal"
          :tooltip="t('Back')"
          type="plain"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="isSessionContext"
      class="rounded-xl border border-blue-200 bg-blue-100 p-4 text-sm text-blue-700"
      role="status"
    >
      {{
        t(
          "This course is opened in a session. Sending an invitation here will subscribe the recipient to the entire session {0}, not just this course.",
          [contextTitle],
        )
      }}
    </div>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
      aria-live="polite"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="actionErrorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
      aria-live="assertive"
    >
      {{ actionErrorMessage }}
    </div>

    <BaseCard>
      <template #title>
        {{ t("Invite by email") }}
      </template>

      <form
        class="invitation-send-form flex items-end gap-4"
        @submit.prevent="sendInvitation"
      >
        <BaseInputText
          id="invitation-email"
          v-model="email"
          :label="t('E-mail')"
          name="email"
          type="email"
          required
          class="max-w-sm grow"
        />
        <BaseButton
          :label="t('Send invitation')"
          icon="email-outline"
          type="success"
          is-submit
          :is-loading="isSending"
        />
      </form>
    </BaseCard>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      v-else
      :values="invitations"
      :pagination="false"
      data-key="id"
    >
      <Column
        field="email"
        :header="t('E-mail')"
      />
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span
            v-if="data.acceptedAt"
            class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-700"
          >
            {{ t("Accepted") }}
          </span>
          <span
            v-else-if="data.revokedAt"
            class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700"
          >
            {{ t("Revoked") }}
          </span>
          <span
            v-else
            class="rounded-full bg-blue-100 px-3 py-1 text-xs text-blue-700"
          >
            {{ t("Pending") }}
          </span>
        </template>
      </Column>
      <Column :header="t('Sent on')">
        <template #body="{ data }">
          {{ abbreviatedDatetime(data.createdAt) }}
        </template>
      </Column>
      <Column :header="t('Accepted on')">
        <template #body="{ data }">
          {{ data.acceptedAt ? abbreviatedDatetime(data.acceptedAt) : "-" }}
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <BaseButton
            v-if="data.invitationUrl"
            icon="copy"
            :label="t('Copy')"
            only-icon
            size="small"
            type="primary-text"
            :tooltip="t('Copy')"
            @click="copyInvitationLink(data)"
          />
          <BaseButton
            v-if="!data.acceptedAt && !data.revokedAt"
            icon="account-cancel"
            :is-loading="revokingId === data.id"
            :label="t('Revoke')"
            only-icon
            size="small"
            type="danger-text"
            :tooltip="t('Revoke')"
            @click="confirmRevoke(data)"
          />
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import courseInvitationService from "../../services/courseInvitationService"

const { t } = useI18n()
const route = useRoute()
const { abbreviatedDatetime } = useFormatDate()
const { requireConfirmation } = useConfirmation()

const invitations = ref([])
const isLoading = ref(false)
const isSending = ref(false)
const revokingId = ref(null)
const errorMessage = ref("")
const actionErrorMessage = ref("")
const successMessage = ref("")
const isSessionContext = ref(false)
const contextTitle = ref("")
const email = ref("")

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value
}

function getContextParams() {
  const params = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)

  if (sid > 0) {
    params.sid = sid
  }

  return params
}

const backRoute = computed(() => {
  const fromNode = getQueryValue(route.query.fromNode)
  if (!fromNode) {
    return null
  }

  const query = {
    cid: getQueryValue(route.query.cid),
  }
  const sid = Number(getQueryValue(route.query.sid) || 0)
  if (sid > 0) {
    query.sid = sid
  }
  const type = getQueryValue(route.query.type)
  if (type !== undefined && type !== null && type !== "") {
    query.type = type
  }

  return {
    name: "CourseUserList",
    params: { node: String(fromNode) },
    query,
  }
})

async function loadForm() {
  const form = await courseInvitationService.getForm(getContextParams())
  isSessionContext.value = Boolean(form.isSessionContext)
  contextTitle.value = form.contextTitle || ""
}

async function loadInvitations() {
  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await courseInvitationService.getList(getContextParams())
    invitations.value = Array.isArray(response.items) ? response.items : []
  } catch (error) {
    console.error("Error loading course invitations", error)
    errorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function loadAll() {
  actionErrorMessage.value = ""
  successMessage.value = ""
  await Promise.all([loadForm(), loadInvitations()])
}

async function sendInvitation() {
  if (isSending.value || !email.value) {
    return
  }

  isSending.value = true
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseInvitationService.create({ email: email.value }, getContextParams())
    email.value = ""
    successMessage.value = t("Invitation has been sent")
    await loadAll()
  } catch (error) {
    console.error("Error sending course invitation", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    isSending.value = false
  }
}

async function copyInvitationLink(invitation) {
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    // navigator.clipboard requires a secure context (HTTPS, or the literal
    // "localhost" host) - plain-HTTP installs (e.g. a custom dev hostname
    // that isn't literally "localhost") don't have it, so this throws there.
    await navigator.clipboard.writeText(invitation.invitationUrl)
    successMessage.value = t("Copied to clipboard")
  } catch (error) {
    console.error("Error copying invitation link, falling back to prompt", error)
    window.prompt(t("Copy link"), invitation.invitationUrl)
  }
}

function confirmRevoke(invitation) {
  requireConfirmation({
    message: t("Are you sure you want to revoke the invitation sent to {0}?", [invitation.email]),
    accept: () => revokeInvitation(invitation),
  })
}

async function revokeInvitation(invitation) {
  if (revokingId.value !== null) {
    return
  }

  revokingId.value = invitation.id
  actionErrorMessage.value = ""
  successMessage.value = ""

  try {
    await courseInvitationService.revoke(invitation.id, getContextParams())
    successMessage.value = t("Invitation has been revoked")
    await loadInvitations()
  } catch (error) {
    console.error("Error revoking course invitation", error)
    actionErrorMessage.value =
      error?.response?.data?.detail || error?.response?.data?.["hydra:description"] || t("An error occurred")
  } finally {
    revokingId.value = null
  }
}

onMounted(loadAll)

watch(() => [route.query.cid, route.query.sid], loadAll)
</script>

<style scoped>
/* BaseInputText's ".field" wrapper carries a global mb-5 for stacked forms;
   it has no counterpart on BaseButton, which throws off items-end alignment
   in this single-row layout. */
.invitation-send-form :deep(.field) {
  margin-bottom: 0;
}
</style>
