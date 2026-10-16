<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="t('Microsoft Teams')"
    >
      <BaseButton
        v-if="isCourseScope && collection.personalEnabled"
        :label="t('Personal conferences')"
        icon="account"
        only-icon
        :route="{ name: 'TeamsHubList', query: { scope: 'personal' } }"
        type="black"
      />
      <BaseButton
        v-if="isCourseScope && collection.globalEnabled"
        :label="t('Global conferences')"
        icon="globe"
        only-icon
        :route="{ name: 'TeamsHubList', query: { scope: 'global' } }"
        type="black"
      />
      <BaseButton
        v-if="canCreate"
        :label="t('Start meeting')"
        icon="camera"
        only-icon
        :route="createRoute('instant')"
        type="primary"
      />
      <BaseButton
        v-if="canCreate"
        :label="t('Schedule meeting')"
        icon="calendar-plus"
        only-icon
        :route="createRoute('scheduled')"
        type="primary"
      />
      <BaseButton
        :disabled="loading"
        :label="t('Refresh')"
        icon="refresh"
        only-icon
        type="black"
        @click="loadMeetings"
      />
    </SectionHeader>

    <div
      v-if="!isCourseScope"
      class="flex flex-wrap gap-2 rounded-xl border border-gray-25 bg-white p-3"
    >
      <BaseButton
        v-if="collection.personalEnabled"
        :label="t('Personal conferences')"
        icon="account"
        :type="scope === 'personal' ? 'primary' : 'black'"
        @click="switchScope('personal')"
      />
      <BaseButton
        v-if="collection.globalEnabled"
        :label="t('Global conferences')"
        icon="globe"
        :type="scope === 'global' ? 'primary' : 'black'"
        @click="switchScope('global')"
      />
    </div>

    <div
      v-if="loaded && !collection.configured"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("Microsoft Teams integration is not configured.") }}
    </div>

    <div class="flex flex-col gap-3">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold text-gray-90">{{ t("Upcoming conferences") }}</h2>
          <p class="text-sm text-gray-50">
            {{ t("{0} conference(s)", [collection.totalUpcoming]) }}
          </p>
        </div>
      </div>

      <BaseTable
        :is-loading="loading"
        :text-for-empty="t('No upcoming conferences.')"
        :total-items="collection.totalUpcoming"
        :values="collection.upcoming"
        data-key="id"
      >
        <Column
          field="title"
          :header="t('Title')"
        >
          <template #body="{ data }">
            <RouterLink
              class="font-medium text-primary hover:underline"
              :to="detailRoute(data)"
            >
              {{ data.title }}
            </RouterLink>
          </template>
        </Column>
        <Column
          field="startAt"
          :header="t('Start')"
        >
          <template #body="{ data }">
            {{ formatMeetingDate(data.startAt) }}
          </template>
        </Column>
        <Column
          field="endAt"
          :header="t('End')"
        >
          <template #body="{ data }">
            {{ formatMeetingDate(data.endAt) }}
          </template>
        </Column>
        <Column
          field="organizerName"
          :header="t('Organizer')"
        >
          <template #body="{ data }">
            {{ data.organizerName || "—" }}
          </template>
        </Column>
        <Column
          field="status"
          :header="t('Status')"
        >
          <template #body="{ data }">
            <span :class="statusClass(data.status)">
              {{ statusLabel(data.status) }}
            </span>
          </template>
        </Column>
        <Column
          :header="t('Actions')"
          header-class="w-44"
        >
          <template #body="{ data }">
            <div class="flex items-center justify-end gap-1">
              <BaseButton
                v-if="data.canJoin"
                :label="t('Join meeting')"
                icon="link-external"
                only-icon
                type="primary-text"
                @click="joinMeeting(data)"
              />
              <BaseButton
                v-if="data.canJoin"
                :label="t('Copy meeting link')"
                icon="copy"
                only-icon
                type="primary-text"
                @click="copyMeetingLink(data)"
              />
              <BaseButton
                :label="t('Add to calendar')"
                icon="calendar-plus"
                only-icon
                type="primary-text"
                @click="downloadCalendar(data)"
              />
              <BaseButton
                v-if="data.canManage"
                :label="t('Edit')"
                icon="pencil"
                only-icon
                :route="editRoute(data)"
                type="secondary-text"
              />
              <BaseButton
                v-if="data.canManage"
                :disabled="cancellingId === data.id"
                :label="t('Cancel meeting')"
                icon="stop"
                only-icon
                type="danger-text"
                @click="confirmCancel(data)"
              />
            </div>
          </template>
        </Column>
      </BaseTable>
    </div>

    <div class="flex flex-col gap-3">
      <div>
        <h2 class="text-lg font-semibold text-gray-90">{{ t("Past conferences") }}</h2>
        <p class="text-sm text-gray-50">
          {{ t("{0} conference(s)", [collection.totalPast]) }}
        </p>
      </div>

      <BaseTable
        :is-loading="loading"
        :text-for-empty="t('No past conferences.')"
        :total-items="collection.totalPast"
        :values="collection.past"
        data-key="id"
      >
        <Column
          field="title"
          :header="t('Title')"
        >
          <template #body="{ data }">
            <RouterLink
              class="font-medium text-primary hover:underline"
              :to="detailRoute(data)"
            >
              {{ data.title }}
            </RouterLink>
          </template>
        </Column>
        <Column
          field="startAt"
          :header="t('Start')"
        >
          <template #body="{ data }">
            {{ formatMeetingDate(data.startAt) }}
          </template>
        </Column>
        <Column
          field="endAt"
          :header="t('End')"
        >
          <template #body="{ data }">
            {{ formatMeetingDate(data.endAt) }}
          </template>
        </Column>
        <Column
          field="organizerName"
          :header="t('Organizer')"
        >
          <template #body="{ data }">
            {{ data.organizerName || "—" }}
          </template>
        </Column>
        <Column
          field="status"
          :header="t('Status')"
        >
          <template #body="{ data }">
            <span :class="statusClass(data.status)">
              {{ statusLabel(data.status) }}
            </span>
          </template>
        </Column>
      </BaseTable>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { RouterLink, useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import teamsService from "../../services/teamsService"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { course, session } = storeToRefs(useCidReqStore())
const { abbreviatedDatetime } = useFormatDate()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification, showSuccessNotification, showWarningNotification } = useNotification()

const loading = ref(false)
const loaded = ref(false)
const cancellingId = ref(null)
const collection = reactive({
  configured: false,
  scope: "",
  canCreate: false,
  personalEnabled: false,
  globalEnabled: false,
  totalUpcoming: 0,
  totalPast: 0,
  upcoming: [],
  past: [],
})

const isCourseScope = computed(() => route.meta.teamsCourseScope === true)
const scope = computed(() => {
  if (isCourseScope.value) {
    return "course"
  }

  return route.query.scope === "global" ? "global" : "personal"
})

const courseContext = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

const requestParams = computed(() => {
  if (isCourseScope.value) {
    return { scope: "course", ...courseContext.value }
  }

  return { scope: scope.value }
})

const canCreate = computed(() => collection.configured && collection.canCreate)

watch(
  () => JSON.stringify(requestParams.value),
  () => loadMeetings(),
  { immediate: true },
)

watch(
  () => route.query.teamsAuth,
  (status) => {
    if (!status) {
      return
    }

    if (status === "account_mismatch") {
      showWarningNotification(
        t("Sign in to Microsoft with the same account used by this meeting organizer, then try again."),
      )
    } else {
      showWarningNotification(t("Microsoft sign-in could not be completed. Please try joining the meeting again."))
    }

    const query = { ...route.query }
    delete query.teamsAuth
    router.replace({ name: route.name, params: route.params, query })
  },
  { immediate: true },
)

async function loadMeetings() {
  loading.value = true

  try {
    const data = await teamsService.getMeetings(requestParams.value)
    collection.configured = data?.configured === true
    collection.scope = typeof data?.scope === "string" ? data.scope : scope.value
    collection.canCreate = data?.canCreate === true
    collection.personalEnabled = data?.personalEnabled === true
    collection.globalEnabled = data?.globalEnabled === true
    collection.totalUpcoming = Number(data?.totalUpcoming ?? 0)
    collection.totalPast = Number(data?.totalPast ?? 0)
    collection.upcoming = Array.isArray(data?.upcoming) ? data.upcoming : []
    collection.past = Array.isArray(data?.past) ? data.past : []
    loaded.value = true
  } catch (error) {
    collection.upcoming = []
    collection.past = []
    collection.totalUpcoming = 0
    collection.totalPast = 0
    loaded.value = true
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

function createRoute(mode) {
  if (isCourseScope.value) {
    return {
      name: "TeamsCourseCreate",
      query: { ...route.query, mode },
    }
  }

  return {
    name: "TeamsHubCreate",
    query: { ...route.query, scope: scope.value, mode },
  }
}

function editRoute(meeting) {
  if (isCourseScope.value) {
    return {
      name: "TeamsCourseEdit",
      params: { meetingId: meeting.id },
      query: route.query,
    }
  }

  return {
    name: "TeamsHubEdit",
    params: { meetingId: meeting.id },
    query: { ...route.query, scope: scope.value },
  }
}

function detailRoute(meeting) {
  if (isCourseScope.value) {
    return {
      name: "TeamsCourseDetail",
      params: { meetingId: meeting.id },
      query: route.query,
    }
  }

  return {
    name: "TeamsHubDetail",
    params: { meetingId: meeting.id },
    query: { ...route.query, scope: scope.value },
  }
}

function switchScope(nextScope) {
  const enabled =
    (nextScope === "personal" && collection.personalEnabled) || (nextScope === "global" && collection.globalEnabled)

  if (!enabled || nextScope === scope.value) {
    return
  }

  router.replace({
    name: "TeamsHubList",
    query: { ...route.query, scope: nextScope },
  })
}

function formatMeetingDate(value) {
  if (!value) {
    return "—"
  }

  try {
    return abbreviatedDatetime(value)
  } catch {
    return value
  }
}

function statusLabel(status) {
  const labels = {
    upcoming: t("Upcoming"),
    in_progress: t("In progress"),
    past: t("Past"),
    cancelled: t("Cancelled"),
  }

  return labels[status] || status || "—"
}

function statusClass(status) {
  const base = "inline-flex rounded-full px-2 py-1 text-xs font-medium"
  const classes = {
    upcoming: "bg-blue-100 text-blue-700",
    in_progress: "bg-green-100 text-green-700",
    past: "bg-gray-100 text-gray-700",
    cancelled: "bg-red-100 text-red-700",
  }

  return `${base} ${classes[status] || "bg-gray-100 text-gray-700"}`
}

function joinMeeting(meeting) {
  const meetingId = Number(meeting?.id ?? 0)
  if (!Number.isInteger(meetingId) || meetingId <= 0) {
    showWarningNotification(t("The meeting join link is not available."))
    return
  }

  window.open(`/conference/teams-auth/join/${meetingId}`, "_blank", "noopener,noreferrer")
}

async function copyMeetingLink(meeting) {
  const url = teamsService.getProtectedJoinUrl(meeting?.id)
  if (!url) {
    showWarningNotification(t("The meeting join link is not available."))
    return
  }

  try {
    if (!navigator.clipboard || !window.isSecureContext) {
      throw new Error("Clipboard API unavailable")
    }

    await navigator.clipboard.writeText(url)
    showSuccessNotification(t("Meeting link copied."))
  } catch (error) {
    console.error("Error copying Microsoft Teams meeting link", error)
    window.prompt(t("Copy link"), url)
  }
}

function downloadCalendar(meeting) {
  if (!teamsService.downloadCalendarFile(meeting)) {
    showWarningNotification(t("The meeting join link is not available."))
  }
}

function confirmCancel(meeting) {
  requireConfirmation({
    title: t("Cancel meeting"),
    message: t('Are you sure you want to cancel "{0}"?', [meeting.title]),
    accept: () => cancelMeeting(meeting),
  })
}

async function cancelMeeting(meeting) {
  cancellingId.value = meeting.id

  try {
    const params = isCourseScope.value ? courseContext.value : {}
    await teamsService.cancelMeeting(meeting.id, params)
    showSuccessNotification(t("The meeting was cancelled."))
    await loadMeetings()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    cancellingId.value = null
  }
}
</script>
