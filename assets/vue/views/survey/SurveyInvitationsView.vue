<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-90">{{ t("Publish survey") }}</h1>
        <p class="mt-1 text-sm text-gray-600">
          {{ displayText(survey.title, t("Survey")) }}
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <BaseButton
          :label="t('Back to surveys')"
          :route="buildListRoute()"
          icon="back"
          type="plain"
        />
        <BaseButton
          :label="t('Preview')"
          :route="buildPreviewRoute()"
          icon="play-box-outline"
          type="primary"
        />
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded border border-red-200 bg-red-50 p-3 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="successMessage"
      class="rounded border border-green-200 bg-green-50 p-3 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Invited") }}</p>
        <p class="mt-1 text-2xl font-semibold text-gray-90">{{ counts.invited || 0 }}</p>
      </div>
      <div
        v-if="canShowAnsweredDetails"
        class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm"
      >
        <p class="text-sm text-gray-500">{{ t("Answered") }}</p>
        <p class="mt-1 text-2xl font-semibold text-green-700">{{ counts.answered || 0 }}</p>
      </div>
      <div class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
        <p class="text-sm text-gray-500">{{ t("Pending") }}</p>
        <p class="mt-1 text-2xl font-semibold text-blue-700">{{ counts.unanswered || 0 }}</p>
      </div>
    </div>

    <section class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
      <div class="mb-4 flex items-center gap-2">
        <BaseIcon icon="send" />
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Publish survey") }}</h2>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-4">
          <div>
            <div class="mb-2 flex items-center justify-between gap-2">
              <label class="text-sm font-semibold text-gray-700">{{ t("Course users") }}</label>
              <div class="flex gap-2">
                <BaseButton
                  :label="t('Select all')"
                  size="small"
                  type="plain"
                  @click="selectAllUsers"
                />
                <BaseButton
                  :label="t('Clear')"
                  size="small"
                  type="plain"
                  @click="selectedUserIds = []"
                />
              </div>
            </div>
            <div class="max-h-72 overflow-auto rounded border border-gray-25">
              <label
                v-for="user in users"
                :key="user.id"
                class="flex cursor-pointer items-start gap-3 border-b border-gray-15 px-3 py-2 last:border-b-0 hover:bg-gray-10"
              >
                <input
                  v-model="selectedUserIds"
                  :name="`survey_user_${user.id}`"
                  :value="user.id"
                  class="mt-1"
                  type="checkbox"
                >
                <span class="min-w-0">
                  <span class="block font-medium text-gray-90">{{ user.name }}</span>
                  <span class="block text-xs text-gray-500">{{ user.email || '-' }}</span>
                  <span
                    v-if="user.invited"
                    class="mt-1 inline-block rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700"
                  >
                    {{ t("Already invited") }}
                  </span>
                </span>
              </label>
              <p
                v-if="!users.length && !isLoading"
                class="p-4 text-sm text-gray-500"
              >
                {{ t("No users found") }}
              </p>
            </div>
          </div>

          <div>
            <div class="mb-2 flex items-center justify-between gap-2">
              <label class="text-sm font-semibold text-gray-700">{{ t("Groups") }}</label>
              <BaseButton
                :label="t('Clear')"
                size="small"
                type="plain"
                @click="selectedGroupIds = []"
              />
            </div>
            <div class="max-h-48 overflow-auto rounded border border-gray-25">
              <label
                v-for="group in groups"
                :key="group.id"
                class="flex cursor-pointer items-center gap-3 border-b border-gray-15 px-3 py-2 last:border-b-0 hover:bg-gray-10"
              >
                <input
                  v-model="selectedGroupIds"
                  :name="`survey_group_${group.id}`"
                  :value="group.id"
                  type="checkbox"
                >
                <span class="min-w-0">
                  <span class="block font-medium text-gray-90">{{ group.title }}</span>
                  <span class="block text-xs text-gray-500">{{ t('Members') }}: {{ group.memberCount }}</span>
                </span>
              </label>
              <p
                v-if="!groups.length && !isLoading"
                class="p-4 text-sm text-gray-500"
              >
                {{ t("No groups found") }}
              </p>
            </div>
          </div>
        </div>

        <div class="space-y-4">
          <div>
            <label
              class="mb-1 block text-sm font-semibold text-gray-700"
              for="survey_mail_subject"
            >
              {{ t("Mail subject") }}
            </label>
            <input
              id="survey_mail_subject"
              v-model="mailSubject"
              class="w-full rounded border border-gray-300 px-3 py-2 text-sm"
              name="survey_mail_subject"
              type="text"
            >
          </div>

          <div>
            <label
              class="mb-1 block text-sm font-semibold text-gray-700"
              for="survey_mail_text"
            >
              {{ t("E-mail message") }}
            </label>
            <textarea
              id="survey_mail_text"
              v-model="mailText"
              class="min-h-40 w-full rounded border border-gray-300 px-3 py-2 text-sm"
              name="survey_mail_text"
            />
            <p class="mt-1 text-xs text-gray-500">
              {{ t("Use **link** where the survey link should appear.") }}
            </p>
          </div>

          <div class="space-y-2 rounded border border-gray-25 bg-gray-10 p-3 text-sm">
            <label class="flex items-center gap-2">
              <input
                v-model="sendMail"
                name="send_mail"
                type="checkbox"
              >
              <span>{{ t("Send mail") }}</span>
            </label>
            <label
              v-if="canRemindUnanswered"
              class="flex items-center gap-2"
            >
              <input
                v-model="remindUnanswered"
                name="remind_unanswered"
                type="checkbox"
              >
              <span>{{ t("Remind only users who didn't answer") }}</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                v-model="resendToAll"
                name="resend_to_all"
                type="checkbox"
              >
              <span>{{ t("Remind all selected users") }}</span>
            </label>
            <label class="flex items-center gap-2">
              <input
                v-model="hideLink"
                name="hide_link"
                type="checkbox"
              >
              <span>{{ t("Hide survey invitation link") }}</span>
            </label>
          </div>

          <div class="rounded border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
            <p class="font-semibold">{{ t("Anonymous access link") }}</p>
            <p class="mt-1 text-xs">
              {{ t("Users who are not invited can use this link to take the survey.") }}
            </p>
            <div class="mt-2 flex gap-2">
              <input
                :value="autoAnswerUrl"
                class="min-w-0 flex-1 rounded border border-blue-100 px-2 py-1 text-xs"
                name="anonymous_link"
                readonly
                type="text"
              >
              <BaseButton
                :label="t('Copy')"
                icon="copy"
                size="small"
                type="primary"
                @click="copyText(autoAnswerUrl)"
              />
            </div>
          </div>

          <div class="flex justify-end gap-2">
            <BaseButton
              :disabled="isSaving"
              :label="t('Publish survey')"
              icon="send"
              type="success"
              @click="publishSurvey"
            />
          </div>
        </div>
      </div>
    </section>

    <section class="rounded-lg border border-gray-25 bg-white p-4 shadow-sm">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex items-center gap-2">
          <BaseIcon icon="account-multiple" />
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Survey invitations") }}</h2>
        </div>
        <div class="flex flex-wrap gap-2">
          <BaseButton
            :label="t('Invited')"
            :type="activeTab === 'invited' ? 'primary' : 'plain'"
            size="small"
            @click="activeTab = 'invited'"
          />
          <BaseButton
            v-if="canShowAnsweredDetails"
            :label="t('Answered')"
            :type="activeTab === 'answered' ? 'primary' : 'plain'"
            size="small"
            @click="activeTab = 'answered'"
          />
          <BaseButton
            :label="t('Pending')"
            :type="activeTab === 'pending' ? 'primary' : 'plain'"
            size="small"
            @click="activeTab = 'pending'"
          />
        </div>
      </div>

      <BaseTable
        :is-loading="isLoading"
        :text-for-empty="t('No invitations found')"
        :total-items="filteredInvitations.length"
        :values="filteredInvitations"
        data-key="id"
      >
        <Column
          :header="t('User')"
          field="userName"
          sortable
        >
          <template #body="{ data }">
            <div>
              <div class="font-medium text-gray-90">{{ data.userName }}</div>
              <div class="text-xs text-gray-500">{{ data.email || '-' }}</div>
              <div
                v-if="data.groupTitle"
                class="mt-1 text-xs text-gray-500"
              >
                {{ t('Group') }}: {{ data.groupTitle }}
              </div>
            </div>
          </template>
        </Column>

        <Column :header="t('Invitation date')">
          <template #body="{ data }">
            {{ formatDate(data.invitationDate) }}
          </template>
        </Column>

        <Column :header="t('Status')">
          <template #body="{ data }">
            <span
              v-if="data.answered"
              class="rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700"
            >
              {{ t('Answered') }}
            </span>
            <span
              v-else-if="data.answeredHidden"
              class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700"
            >
              {{ t('Answered anonymously') }}
            </span>
            <span
              v-else
              class="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700"
            >
              {{ t('Pending') }}
            </span>
          </template>
        </Column>

        <Column :header="t('Survey invitation link')">
          <template #body="{ data }">
            <div
              v-if="!data.answered"
              class="flex gap-2"
            >
              <input
                :value="buildAnswerUrl(data)"
                class="min-w-0 flex-1 rounded border border-gray-25 px-2 py-1 text-xs"
                readonly
                type="text"
              >
              <BaseButton
                :label="t('Copy')"
                icon="copy"
                only-icon
                size="small"
                type="primary-text"
                @click="copyText(buildAnswerUrl(data))"
              />
            </div>
            <span v-else>-</span>
          </template>
        </Column>
      </BaseTable>
    </section>
  </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import surveyService from "../../services/surveyService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const survey = ref({})
const counts = ref({ invited: 0, answered: 0, unanswered: 0 })
const users = ref([])
const groups = ref([])
const invitations = ref([])
const settings = ref({})
const anonymousLink = ref("")
const selectedUserIds = ref([])
const selectedGroupIds = ref([])
const csrfToken = ref("")
const mailSubject = ref("")
const mailText = ref("")
const sendMail = ref(true)
const resendToAll = ref(false)
const remindUnanswered = ref(false)
const hideLink = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const activeTab = ref("invited")

const surveyId = computed(() => Number(route.params.surveyId || 0))
const isAnonymousSurvey = computed(() => isTruthy(survey.value?.anonymous) || isTruthy(settings.value?.anonymous))
const autoAnswerUrl = computed(() => {
  if (isAnonymousSurvey.value) {
    return buildAnswerUrl({ invitationCode: "auto" })
  }

  const responseLink = String(anonymousLink.value || "")
  if (responseLink && responseLink !== "auto") {
    return toAbsoluteUrl(responseLink)
  }

  return buildAnswerUrl({ invitationCode: "auto" })
})
const canShowAnsweredDetails = computed(() => {
  const setting = settings.value?.canShowAnsweredDetails
  if (isTruthy(setting)) {
    return true
  }

  if (isExplicitFalse(setting)) {
    return false
  }

  return !isAnonymousSurvey.value || isTruthy(settings.value?.anonymousShowAnswered)
})
const canRemindUnanswered = computed(() => {
  const setting = settings.value?.canRemindUnanswered
  if (isTruthy(setting)) {
    return true
  }

  if (isExplicitFalse(setting)) {
    return false
  }

  return !isAnonymousSurvey.value || isTruthy(settings.value?.anonymousShowAnswered)
})

const filteredInvitations = computed(() => {
  if (activeTab.value === "answered") {
    if (!canShowAnsweredDetails.value) {
      return []
    }

    return invitations.value.filter((item) => item.answered || item.answeredHidden)
  }

  if (activeTab.value === "pending") {
    return invitations.value.filter((item) => !item.answered && !item.answeredHidden)
  }

  return invitations.value
})

function getContextParams() {
  return cleanQuery({
    cid: route.query.cid,
    sid: route.query.sid,
    gid: route.query.gid,
  })
}

function getPublicContextParams() {
  return cleanQuery({
    publicCid: route.query.publicCid ?? route.query.cid,
    publicSid: route.query.publicSid ?? route.query.sid,
    publicGid: route.query.publicGid ?? route.query.gid ?? 0,
  })
}

function cleanQuery(query) {
  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => value !== undefined && value !== null && value !== ""),
  )
}

function isTruthy(value) {
  return value === true || value === 1 || String(value).toLowerCase() === "true" || String(value) === "1"
}

function isExplicitFalse(value) {
  return value === false || value === 0 || String(value).toLowerCase() === "false" || String(value) === "0"
}

function toAbsoluteUrl(value) {
  if (!value) {
    return ""
  }

  if (/^https?:\/\//i.test(value)) {
    return value
  }

  return `${window.location.origin}${String(value).startsWith("/") ? "" : "/"}${value}`
}

function buildListRoute() {
  return {
    name: "SurveyList",
    params: { node: route.params.node },
    query: getContextParams(),
  }
}

function buildPreviewRoute() {
  return {
    name: "SurveyPreview",
    params: {
      node: route.params.node,
      surveyId: surveyId.value,
    },
    query: {
      ...getContextParams(),
      preview: 1,
    },
  }
}

function buildAnswerUrl(invitation) {
  const resolved = router.resolve({
    name: survey.value.surveyType === 3 ? "SurveyMeeting" : "SurveyAnswer",
    params: {
      node: route.params.node,
      surveyId: surveyId.value,
    },
    query: {
      ...(isAnonymousSurvey.value ? getPublicContextParams() : getContextParams()),
      invitationCode: invitation?.invitationCode || "auto",
    },
  })

  return `${window.location.origin}${resolved.href}`
}

async function loadInvitationData() {
  if (!surveyId.value) {
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const response = await surveyService.getSurveyInvitations(getContextParams(), surveyId.value)
    survey.value = response.survey || {}
    counts.value = response.counts || { invited: 0, answered: 0, unanswered: 0 }
    users.value = Array.isArray(response.users) ? response.users : []
    groups.value = Array.isArray(response.groups) ? response.groups : []
    invitations.value = Array.isArray(response.invitations) ? response.invitations : []
    settings.value = response.settings || {}
    anonymousLink.value = response.anonymousLink || ""
    if (!canShowAnsweredDetails.value && activeTab.value === "answered") {
      activeTab.value = "invited"
    }
    if (!canRemindUnanswered.value) {
      remindUnanswered.value = false
    }
    selectedUserIds.value = Array.isArray(response.selectedUserIds) ? response.selectedUserIds : []
    selectedGroupIds.value = Array.isArray(response.selectedGroupIds) ? response.selectedGroupIds : []
    csrfToken.value = response.csrfToken || ""
    mailSubject.value = response.mailSubject || defaultSubject()
    mailText.value = response.mailText || defaultMailText()

    if (response.message) {
      successMessage.value = t(response.message)
    }
  } catch (error) {
    console.error("Error loading survey invitations", error)
    const detail = error?.response?.data?.detail || error?.response?.data?.message || ""
    errorMessage.value = detail ? `${t("Could not load survey invitations")}: ${detail}` : t("Could not load survey invitations")
  } finally {
    isLoading.value = false
  }
}

async function publishSurvey() {
  errorMessage.value = ""
  successMessage.value = ""

  if (sendMail.value && (!mailSubject.value.trim() || !mailText.value.trim())) {
    errorMessage.value = t("Mail subject and message are required when sending mail.")
    return
  }

  isSaving.value = true

  try {
    const response = await surveyService.publishSurveyInvitations(
      {
        csrfToken: csrfToken.value,
        selectedUserIds: selectedUserIds.value,
        selectedGroupIds: selectedGroupIds.value,
        mailSubject: mailSubject.value,
        mailText: mailText.value,
        sendMail: sendMail.value,
        resendToAll: resendToAll.value,
        remindUnanswered: canRemindUnanswered.value && remindUnanswered.value,
        hideLink: hideLink.value,
        additionalEmails: [],
      },
      getContextParams(),
      surveyId.value,
    )

    survey.value = response.survey || survey.value
    counts.value = response.counts || counts.value
    users.value = Array.isArray(response.users) ? response.users : users.value
    groups.value = Array.isArray(response.groups) ? response.groups : groups.value
    invitations.value = Array.isArray(response.invitations) ? response.invitations : invitations.value
    selectedUserIds.value = Array.isArray(response.selectedUserIds) ? response.selectedUserIds : selectedUserIds.value
    selectedGroupIds.value = Array.isArray(response.selectedGroupIds) ? response.selectedGroupIds : selectedGroupIds.value
    csrfToken.value = response.csrfToken || csrfToken.value
    settings.value = response.settings || settings.value
    if (settings.value?.canShowAnsweredDetails === false && activeTab.value === "answered") {
      activeTab.value = "invited"
    }
    if (!canRemindUnanswered.value) {
      remindUnanswered.value = false
    }
    successMessage.value = t(response.message || "Survey published")
  } catch (error) {
    console.error("Error publishing survey", error)
    errorMessage.value = error?.response?.data?.detail || t("Could not publish survey")
  } finally {
    isSaving.value = false
  }
}

function selectAllUsers() {
  selectedUserIds.value = users.value.map((user) => user.id)
}

async function copyText(text) {
  try {
    await navigator.clipboard.writeText(text)
    successMessage.value = t("Copied")
  } catch (error) {
    console.error("Error copying survey link", error)
    errorMessage.value = t("Could not copy the link")
  }
}

function formatDate(value) {
  if (!value) {
    return t("No date")
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return t("No date")
  }

  return date.toLocaleString()
}

function decodeHtml(value) {
  if (!value) {
    return ""
  }

  const textarea = document.createElement("textarea")
  textarea.innerHTML = String(value)

  return textarea.value
}

function displayText(value, fallback = "") {
  const decodedValue = decodeHtml(value)
  const plainValue = decodeHtml(decodedValue.replace(/<[^>]*>/g, " "))
    .replace(/\s+/g, " ")
    .trim()

  return plainValue || fallback
}

function defaultSubject() {
  return `${t("Survey")}: ${displayText(survey.value.title, "")}`.trim()
}

function defaultMailText() {
  return `${t("Please answer this survey using the following link")}: **link**`
}

onMounted(loadInvitationData)

watch(
  () => [route.params.surveyId, route.query.cid, route.query.sid, route.query.gid],
  () => loadInvitationData(),
)

watch(canShowAnsweredDetails, (canShow) => {
  if (!canShow && activeTab.value === "answered") {
    activeTab.value = "invited"
  }
})

watch(canRemindUnanswered, (canRemind) => {
  if (!canRemind) {
    remindUnanswered.value = false
  }
})
</script>
