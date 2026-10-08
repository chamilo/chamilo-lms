<template>
  <div class="flex w-full flex-col gap-6">
    <SectionHeader
      :show-student-view-button="false"
      :title="isEdit ? t('Edit Microsoft Teams meeting') : formTitle"
    >
      <BaseButton
        :label="t('Back')"
        icon="back"
        only-icon
        type="black"
        @click="goBack"
      />
    </SectionHeader>

    <div
      v-if="loading"
      class="animate-pulse rounded-2xl border border-gray-25 bg-white p-8"
    >
      <div class="mb-4 h-8 w-1/3 rounded bg-gray-15" />
      <div class="h-48 rounded bg-gray-15" />
    </div>

    <div
      v-else-if="loadError"
      class="rounded-lg border border-danger/30 bg-danger/10 p-4 text-danger"
    >
      {{ t("An error occurred") }}
    </div>

    <div
      v-else-if="!configured"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("Microsoft Teams integration is not configured.") }}
    </div>

    <div
      v-else-if="!canSubmit"
      class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-warning-dark"
    >
      {{ t("You are not allowed to manage Microsoft Teams meetings in this context.") }}
    </div>

    <form
      v-else
      class="mx-auto flex w-full max-w-4xl flex-col gap-5"
      @submit.prevent="save"
    >
      <section class="rounded-2xl border border-gray-25 bg-white p-5">
        <div class="mb-5">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ isEdit ? t("Meeting details") : formTitle }}
          </h2>
          <p
            v-if="!isEdit && mode === 'instant'"
            class="mt-1 text-sm text-gray-50"
          >
            {{ t("The meeting starts immediately and is created with a one-hour duration.") }}
          </p>
        </div>

        <div class="flex flex-col gap-5">
          <BaseInputText
            id="teams-meeting-title"
            v-model="form.title"
            name="teams_meeting_title"
            :error-text="t('Required field')"
            :form-submitted="formSubmitted"
            :is-invalid="titleInvalid"
            :label="t('Title')"
            maxlength="255"
            required
          />

          <div
            v-if="showSchedule"
            class="grid grid-cols-1 gap-5 md:grid-cols-2"
          >
            <BaseCalendar
              id="teams-meeting-start"
              v-model="form.startAt"
              :error-text="scheduleInvalid ? t('A valid start and end date are required.') : null"
              :is-invalid="scheduleInvalid"
              :label="t('Start')"
              show-time
            />
            <BaseCalendar
              id="teams-meeting-end"
              v-model="form.endAt"
              :error-text="scheduleInvalid ? t('The end date must be after the start date.') : null"
              :is-invalid="scheduleInvalid"
              :label="t('End')"
              show-time
            />
          </div>

          <BaseCheckbox
            v-if="showAnnouncementOption"
            id="teams-create-announcement"
            v-model="form.createAnnouncement"
            :label="t('Create a course announcement for this meeting')"
            name="teams_create_announcement"
          />

          <BaseCheckbox
            v-if="showCalendarOption"
            id="teams-add-to-calendar"
            v-model="form.addToCalendar"
            :label="t('Add to calendar')"
            name="teams_add_to_calendar"
          />
        </div>
      </section>

      <div class="flex flex-wrap justify-end gap-3">
        <BaseButton
          :disabled="saving"
          :label="t('Back')"
          icon="back"
          type="black"
          @click="goBack"
        />
        <BaseButton
          id="save-teams-meeting"
          name="save_teams_meeting"
          :disabled="saving"
          :icon="isEdit ? 'save' : mode === 'instant' ? 'camera' : 'calendar-plus'"
          :is-loading="saving"
          is-submit
          :label="isEdit ? t('Save') : mode === 'instant' ? t('Start meeting') : t('Schedule meeting')"
          type="primary"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from "vue"
import { storeToRefs } from "pinia"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import announcementService from "../../services/announcementService"
import teamsService from "../../services/teamsService"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { course, session } = storeToRefs(useCidReqStore())
const { abbreviatedDatetime } = useFormatDate()
const { showErrorNotification, showSuccessNotification, showWarningNotification } = useNotification()

const loading = ref(true)
const loadError = ref(false)
const saving = ref(false)
const formSubmitted = ref(false)
const configured = ref(false)
const canSubmit = ref(false)

const form = reactive({
  title: "",
  startAt: null,
  endAt: null,
  createAnnouncement: false,
  addToCalendar: false,
})

const isCourseScope = computed(() => route.meta.teamsCourseScope === true)
const isEdit = computed(() => Number(route.params.meetingId ?? 0) > 0)
const mode = computed(() => (route.query.mode === "instant" ? "instant" : "scheduled"))
const scope = computed(() => {
  if (isCourseScope.value) {
    return "course"
  }

  return route.query.scope === "global" ? "global" : "personal"
})
const showSchedule = computed(() => isEdit.value || mode.value === "scheduled")
const showAnnouncementOption = computed(() => isCourseScope.value && !isEdit.value)
const showCalendarOption = computed(() => isCourseScope.value && !isEdit.value)
const formTitle = computed(() =>
  mode.value === "instant" ? t("Start Microsoft Teams meeting") : t("Schedule Microsoft Teams meeting"),
)

const courseContext = computed(() => ({
  cid: course.value?.id ?? Number(route.query.cid ?? 0),
  sid: session.value?.id ?? Number(route.query.sid ?? 0),
  gid: Number(route.query.gid ?? 0),
}))

const collectionParams = computed(() => {
  if (isCourseScope.value) {
    return { scope: "course", ...courseContext.value }
  }

  return { scope: scope.value }
})

const itemParams = computed(() => (isCourseScope.value ? courseContext.value : {}))
const titleInvalid = computed(() => formSubmitted.value && form.title.trim() === "")
const scheduleInvalid = computed(() => {
  if (!formSubmitted.value || !showSchedule.value) {
    return false
  }

  return !(form.startAt instanceof Date) || !(form.endAt instanceof Date) || form.endAt <= form.startAt
})

watch(
  () => `${route.name}:${route.params.meetingId ?? ""}:${scope.value}:${JSON.stringify(collectionParams.value)}`,
  () => load(),
  { immediate: true },
)

async function load() {
  loading.value = true
  loadError.value = false
  formSubmitted.value = false

  try {
    if (isEdit.value) {
      const item = await teamsService.getMeeting(Number(route.params.meetingId), itemParams.value)
      configured.value = true
      canSubmit.value = item?.canManage === true
      form.title = item?.title || ""
      form.startAt = item?.startAt ? new Date(item.startAt) : null
      form.endAt = item?.endAt ? new Date(item.endAt) : null
      form.createAnnouncement = false
      form.addToCalendar = false

      if (!(form.startAt instanceof Date) || Number.isNaN(form.startAt.getTime())) {
        form.startAt = null
      }
      if (!(form.endAt instanceof Date) || Number.isNaN(form.endAt.getTime())) {
        form.endAt = null
      }

      return
    }

    const data = await teamsService.getMeetings(collectionParams.value)
    configured.value = data?.configured === true
    canSubmit.value = configured.value && data?.canCreate === true
    form.title = ""
    form.createAnnouncement = false
    form.addToCalendar = false

    if (mode.value === "scheduled") {
      const start = new Date(Date.now() + 30 * 60 * 1000)
      start.setSeconds(0, 0)
      const end = new Date(start.getTime() + 60 * 60 * 1000)
      form.startAt = start
      form.endAt = end
    } else {
      form.startAt = null
      form.endAt = null
    }
  } catch (error) {
    loadError.value = true
    configured.value = false
    canSubmit.value = false
    showErrorNotification(error)
  } finally {
    loading.value = false
  }
}

async function save() {
  formSubmitted.value = true

  if (titleInvalid.value || scheduleInvalid.value || !canSubmit.value) {
    return
  }

  saving.value = true

  try {
    if (isEdit.value) {
      await teamsService.updateMeeting(Number(route.params.meetingId), itemParams.value, {
        title: form.title.trim(),
        startAt: form.startAt?.toISOString() ?? null,
        endAt: form.endAt?.toISOString() ?? null,
      })

      showSuccessNotification(t("The meeting was updated."))
      await router.push(listRoute())
      return
    }

    const payload = {
      scope: scope.value,
      title: form.title.trim(),
      startAt: mode.value === "scheduled" ? form.startAt.toISOString() : null,
      endAt: mode.value === "scheduled" ? form.endAt.toISOString() : null,
      addToCalendar: isCourseScope.value && form.addToCalendar,
    }

    const meeting = await teamsService.createMeeting(isCourseScope.value ? courseContext.value : {}, payload)

    if (form.createAnnouncement && isCourseScope.value) {
      await createCourseAnnouncement(meeting)
    }

    showSuccessNotification(mode.value === "instant" ? t("The meeting was created.") : t("The meeting was scheduled."))
    await router.push(listRoute())
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

async function createCourseAnnouncement(meeting) {
  const meetingId = Number(meeting?.id ?? 0)
  if (!Number.isInteger(meetingId) || meetingId <= 0) {
    showWarningNotification(
      t("The meeting was created, but its announcement could not be created because the join link is missing."),
    )
    return
  }

  const authenticatedJoinUrl = teamsService.getProtectedJoinUrl(meetingId)
  const escapedTitle = escapeHtml(meeting?.title || form.title.trim())
  const escapedJoinUrl = escapeHtml(authenticatedJoinUrl)
  const escapedStart = escapeHtml(meeting?.startAt ? abbreviatedDatetime(meeting.startAt) || meeting.startAt : "")

  const content = [
    `<p><strong>${escapeHtml(t("Microsoft Teams meeting"))}:</strong> ${escapedTitle}</p>`,
    escapedStart ? `<p><strong>${escapeHtml(t("Start"))}:</strong> ${escapedStart}</p>` : "",
    `<p><a href="${escapedJoinUrl}" target="_blank" rel="noopener noreferrer">${escapeHtml(t("Join Microsoft Teams meeting"))}</a></p>`,
  ]
    .filter(Boolean)
    .join("")

  try {
    await announcementService.create(
      {
        title: `${t("Microsoft Teams")}: ${meeting?.title || form.title.trim()}`,
        content,
        language: "",
        recipients: ["everyone"],
        sendByEmail: false,
        sendToUsersInSessions: false,
        sendToHrmUsers: false,
        sendCopyToSelf: true,
        scheduleByDate: false,
        scheduleDate: "",
        addToCalendar: false,
        reminders: [],
      },
      courseContext.value,
    )
  } catch {
    showWarningNotification(t("The meeting was created, but the course announcement could not be created."))
  }
}

function listRoute() {
  const query = { ...route.query }
  delete query.mode

  if (isCourseScope.value) {
    return {
      name: "TeamsCourseList",
      query,
    }
  }

  return {
    name: "TeamsHubList",
    query: { ...query, scope: scope.value },
  }
}

function goBack() {
  router.push(listRoute())
}

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;")
}
</script>
