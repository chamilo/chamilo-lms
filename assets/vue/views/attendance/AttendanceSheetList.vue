<template>
  <section class="space-y-4">
    <SectionHeader
      :title="t('Attendance')"
      icon="attendance"
    >
      <BaseButton
        :label="t('Go back')"
        icon="back"
        only-icon
        type="tertiary-alternative-text"
        @click="redirectToAttendanceList"
      />
      <template v-if="isTeacherUI">
        <BaseButton
          :label="t('Go to calendar')"
          icon="calendar-plus"
          type="secondary"
          @click="redirectToCalendarList"
        />
        <BaseButton
          :label="t('Export to PDF')"
          icon="file-pdf"
          only-icon
          type="primary"
          @click="exportToPdf"
        />
        <BaseButton
          :label="t('Export to Excel')"
          icon="file-excel"
          only-icon
          type="primary"
          @click="exportToXls"
        />
        <BaseButton
          :label="t('Generate QR code')"
          icon="qrcode"
          only-icon
          type="primary"
          @click="generateQrCode"
        />
      </template>
    </SectionHeader>

    <div class="flex flex-wrap items-start justify-between gap-4">
      <Skeleton
        v-if="isLoading"
        height="24px"
        width="16rem"
      />
      <h3
        v-else
        class="text-body-1 font-semibold text-gray-90"
      >
        {{ attendanceTitle }}
      </h3>
      <BaseSelect
        v-if="isTeacherUI"
        id="attendance-filter"
        v-model="selectedFilter"
        :disabled="isLoading"
        :label="t('Filter')"
        :options="availableFilters"
        class="w-64"
        name="attendance-filter"
        @change="filterAttendanceSheets"
      />
    </div>

    <!-- Loading: student UI -->
    <div
      v-if="isLoading && isStudentUI"
      aria-busy="true"
      class="space-y-4"
    >
      <Skeleton
        height="16px"
        width="10rem"
      />
      <div class="flex flex-col gap-2">
        <Skeleton
          v-for="index in STUDENT_SKELETON.items"
          :key="index"
          :height="`${STUDENT_SKELETON.itemHeight}px`"
        />
      </div>
      <Skeleton
        v-if="studentCanValidateOwnAttendance"
        height="40px"
        width="100px"
      />
    </div>

    <!-- Loading: teacher UI -->
    <div
      v-else-if="isLoading"
      aria-busy="true"
      class="space-y-4"
    >
      <Skeleton
        height="16px"
        width="40rem"
      />
      <Skeleton :height="`${TEACHER_SKELETON.rowHeight * (TEACHER_SKELETON.rows + 1) + TEACHER_SKELETON.borders}px`" />
    </div>

    <!-- Student UI -->
    <div
      v-else-if="isStudentUI"
      class="space-y-4"
    >
      <p
        v-if="filteredDates.length > 0"
        class="text-body-2 text-gray-90"
      >
        {{ t("To attend") }}
        <span class="ms-2 rounded-lg bg-support-1 px-2 py-1 font-semibold text-primary">
          {{ signedCount }}/{{ totalCount }} ({{ Math.round((signedCount / totalCount) * 100) }}%)
        </span>
      </p>

      <p
        v-else
        class="text-body-2 text-gray-50"
      >
        {{ t("No attendance assigned yet") }}
      </p>

      <ul
        v-if="filteredDates.length > 0"
        class="flex flex-col gap-2"
      >
        <li
          v-for="date in filteredDates"
          :key="date.id"
          class="flex items-center justify-between gap-4 rounded-lg border border-gray-25 bg-white p-4 shadow-sm"
        >
          <div class="flex items-center gap-4">
            <div
              v-if="allowMultilevelGrading"
              :class="getStateClass(attendanceData[`${currentUserId}-${date.id}`])"
              :title="getStateLabel(parseInt(attendanceData[`${currentUserId}-${date.id}`]))"
              class="h-10 w-10 shrink-0 rounded-full"
            ></div>
            <Checkbox
              v-else
              :aria-label="formatAttendanceDate(date.dateTime)"
              :disabled="!studentCanValidateOwnAttendance"
              :model-value="attendanceData[`${currentUserId}-${date.id}`] === 1"
              binary
              @update:model-value="(checked) => onStudentAttendanceChange(date.id, checked)"
            />
            <div class="flex flex-col gap-1">
              <span class="text-body-1 text-gray-90">{{ formatAttendanceDate(date.dateTime) }}</span>
              <span
                v-if="date.effectiveRoom"
                class="text-body-2 text-gray-50"
              >
                {{ t("Room") }}: {{ formatRoom(date.effectiveRoom) }}
              </span>
              <RoomFloorPlanViewer
                v-if="date.effectiveRoom"
                :calendar-id="date.id"
              />
            </div>
          </div>
          <div class="flex shrink-0 gap-2">
            <BaseButton
              v-if="allowComments"
              :label="t('View comment')"
              icon="comment"
              only-icon
              size="small"
              type="primary-text"
              @click="openCommentDialog(currentUserId, date.id)"
            />
            <BaseButton
              v-if="enableSignature"
              :label="t('Sign')"
              icon="drawing"
              only-icon
              size="small"
              type="primary-text"
              @click="openSignatureDialog(currentUserId, date.id)"
            />
          </div>
        </li>
      </ul>

      <BaseButton
        v-if="studentCanValidateOwnAttendance && filteredDates.length > 0"
        :disabled="isSavingStudentAttendance"
        :is-loading="isSavingStudentAttendance"
        :label="t('Save')"
        icon="save"
        type="success"
        @click="saveStudentOwnAttendance"
      />
    </div>

    <!-- Teacher UI -->
    <div
      v-else
      class="space-y-4"
    >
      <Message
        v-if="!isTodayScheduled"
        :closable="false"
        icon="mdi mdi-calendar-alert"
        severity="warn"
      >
        {{
          t(
            "There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons",
          )
        }}
      </Message>

      <p class="text-body-2 text-gray-50">
        {{
          t(
            "The attendance calendar allows you to register attendance lists (one per real session the students need to attend)",
          )
        }}
      </p>

      <!-- Attendance Sheet Table -->
      <div
        class="relative flex overflow-hidden rounded-lg border border-gray-25 bg-white text-body-2 text-gray-90 shadow-sm"
      >
        <!-- Fixed user column -->
        <div
          class="flex-shrink-0 overflow-hidden border-e border-gray-25"
          style="width: 520px"
        >
          <table class="w-full border-collapse">
            <thead>
              <tr class="h-28 border-b border-gray-25 bg-gray-15 font-semibold">
                <th class="p-3 text-start">#</th>
                <th class="p-3 text-start">{{ t("Photo") }}</th>
                <th
                  v-if="showOfficialCode"
                  class="p-3 text-start"
                >
                  {{ t("Official code") }}
                </th>
                <th class="p-3 text-start">{{ t("Last name") }}</th>
                <th class="w-32 p-3 text-start">{{ t("First name") }}</th>
                <th class="p-3 text-start">{{ t("Not attended") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(user, index) in filteredAttendanceSheets"
                :key="user.id"
                class="h-28 border-b border-gray-25 last:border-b-0 hover:bg-gray-15"
              >
                <td class="p-3">{{ index + 1 }}</td>
                <td class="p-3">
                  <img
                    :src="user.photo"
                    alt=""
                    class="h-10 w-10 rounded-full"
                  />
                </td>
                <td
                  v-if="showOfficialCode"
                  :title="user.officialCode"
                  class="truncate p-3"
                >
                  {{ user.officialCode }}
                </td>
                <td
                  :title="user.lastName"
                  class="truncate p-3"
                >
                  {{ user.lastName }}
                </td>
                <td
                  :title="user.firstName"
                  class="truncate p-3"
                >
                  {{ user.firstName }}
                </td>
                <td class="p-3 text-center">{{ user.notAttended }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Scrollable dates -->
        <div class="flex-1 overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="h-28 border-b border-gray-25 bg-gray-15">
                <template v-if="filteredDates.length === 0 && selectedFilter === 'today'">
                  <th
                    class="bg-warning/10 py-6 text-center font-normal"
                    colspan="100"
                  >
                    <div class="flex items-center justify-center gap-4">
                      <BaseIcon
                        class="text-warning"
                        icon="calendar-clock"
                      />
                      <span>{{
                        t(
                          "There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons",
                        )
                      }}</span>
                      <BaseButton
                        :label="t('Go to calendar')"
                        icon="calendar-plus"
                        only-icon
                        size="small"
                        type="secondary"
                        @click="redirectToCalendarList"
                      />
                    </div>
                  </th>
                </template>
                <template v-else>
                  <th
                    v-for="date in filteredDates"
                    :key="date.id"
                    :class="{ 'cursor-not-allowed bg-gray-20': isColumnLocked(date.id) }"
                    class="border-e border-gray-25 p-3 text-center align-middle last:border-e-0"
                  >
                    <div class="flex flex-col items-center gap-1">
                      <span class="font-semibold">
                        {{ formatAttendanceDate(date.dateTime) }}
                      </span>
                      <span
                        v-if="date.duration !== undefined && date.duration !== null"
                        class="text-caption font-normal text-gray-50"
                      >
                        {{ t("{0} min", [date.duration]) }}
                      </span>

                      <span
                        v-if="date.effectiveRoom"
                        class="text-caption font-normal text-gray-50"
                      >
                        {{ t("Room") }}: {{ formatRoom(date.effectiveRoom) }}
                      </span>

                      <div
                        v-if="isTeacherUI"
                        class="flex gap-1"
                      >
                        <BaseButton
                          :label="t('View for tablet')"
                          icon="view-table"
                          only-icon
                          size="small"
                          type="primary-text"
                          @click="viewForTablet(date.id)"
                        />
                        <BaseButton
                          v-if="canManageLocks"
                          :icon="isColumnLocked(date.id) ? 'lock' : 'unlock'"
                          :label="isColumnLocked(date.id) ? t('Unlock column') : t('Lock column')"
                          only-icon
                          size="small"
                          type="secondary-text"
                          @click="toggleLock(date.id)"
                        />
                        <BaseButton
                          :label="t('Set all Present')"
                          icon="account-check"
                          only-icon
                          size="small"
                          type="success-text"
                          @click="setAllAttendance(date.id, 1)"
                        />
                        <BaseButton
                          :label="t('Set all Absent')"
                          icon="account-cancel"
                          only-icon
                          size="small"
                          type="danger-text"
                          @click="setAllAttendance(date.id, 0)"
                        />
                      </div>
                    </div>
                  </th>
                </template>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredDates.length === 0 && selectedFilter === 'today'">
                <td
                  class="py-6 text-center text-gray-50"
                  colspan="100"
                >
                  {{ t("No attendance data for today") }}
                </td>
              </tr>
              <template v-else>
                <tr
                  v-for="user in filteredAttendanceSheets"
                  :key="user.id + '-' + selectedFilter"
                  class="h-28 border-b border-gray-25 last:border-b-0 hover:bg-gray-15"
                >
                  <td
                    v-for="date in filteredDates"
                    :key="date.id"
                    :class="{ 'bg-gray-20': isColumnLocked(date.id) || !canEdit }"
                    class="relative border-e border-gray-25 p-3 text-center last:border-e-0"
                  >
                    <div
                      v-if="isColumnLocked(date.id) || !canEdit"
                      :title="t('Column is locked or read-only')"
                      class="cursor-not-allowed opacity-50"
                    >
                      <Checkbox
                        v-if="!allowMultilevelGrading"
                        :aria-label="`${user.firstName} ${user.lastName}`"
                        :model-value="attendanceData[`${user.id}-${date.id}`] === 1"
                        binary
                        disabled
                      />
                      <div
                        v-else
                        :class="getStateClass(attendanceData[`${user.id}-${date.id}`])"
                        class="mx-auto h-10 w-10 rounded-full"
                      ></div>
                    </div>
                    <div v-else>
                      <Checkbox
                        v-if="!allowMultilevelGrading"
                        :aria-label="`${user.firstName} ${user.lastName}`"
                        :model-value="attendanceData[`${user.id}-${date.id}`] === 1"
                        binary
                        @update:model-value="toggleAttendanceState(user.id, date.id)"
                      />
                      <div
                        v-else
                        :class="getStateClass(attendanceData[`${user.id}-${date.id}`])"
                        :title="getStateLabel(attendanceData[`${user.id}-${date.id}`])"
                        class="mx-auto h-10 w-10 cursor-pointer rounded-full"
                        @click="openMenu(user.id, date.id)"
                      ></div>
                    </div>
                    <div
                      v-if="
                        allowMultilevelGrading &&
                        contextMenu.show &&
                        contextMenu.userId === user.id &&
                        contextMenu.dateId === date.id
                      "
                      class="absolute z-10 rounded-lg border border-gray-25 bg-white p-2 text-start shadow-lg"
                      style="top: 40px; left: 50%; transform: translateX(-50%)"
                    >
                      <div
                        v-for="(state, key) in ATTENDANCE_STATES"
                        :key="key"
                        class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-gray-15"
                        @click="selectState(user.id, date.id, state.id)"
                      >
                        <div
                          :class="getStateClass(state.id)"
                          class="h-5 w-5 rounded-full"
                        ></div>
                        <span>{{ state.label }}</span>
                      </div>
                      <div
                        class="flex cursor-pointer items-center gap-2 rounded-lg p-2 hover:bg-gray-15"
                        @click="selectState(user.id, date.id, null)"
                      >
                        <div class="h-5 w-5 rounded-full bg-gray-30"></div>
                        <span>{{ t("Remove state") }}</span>
                      </div>
                    </div>
                    <div
                      v-if="canEdit"
                      class="absolute end-2 top-2 flex gap-1"
                    >
                      <BaseButton
                        v-if="allowComments && !isColumnLocked(date.id)"
                        :label="t('Comment')"
                        icon="comment"
                        only-icon
                        size="small"
                        type="primary-text"
                        @click="openCommentDialog(user.id, date.id)"
                      />
                      <BaseButton
                        v-if="enableSignature && !isColumnLocked(date.id)"
                        :label="t('Sign')"
                        icon="drawing"
                        only-icon
                        size="small"
                        type="primary-text"
                        @click="openSignatureDialog(user.id, date.id)"
                      />
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <div
        v-if="canEdit && filteredDates.some((date) => !isColumnLocked(date.id))"
        class="flex justify-end"
      >
        <BaseButton
          :disabled="isSaving"
          :is-loading="isSaving"
          :label="isSaving ? t('Saving...') : t('Save attendance')"
          icon="save"
          type="success"
          @click="saveAttendanceSheet"
        />
      </div>
    </div>

    <BaseDialog
      v-model:is-visible="showCommentDialog"
      :title="t('Add comment')"
      header-icon="comment"
    >
      <BaseTextArea
        id="attendance-comment"
        v-model="currentComment"
        :label="t('Comment')"
        :readonly="isStudentUI && !canEdit"
      />
      <template #footer>
        <BaseButton
          v-if="isTeacherUI && canEdit"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveComment"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="showSignatureDialog"
      :title="t('Signature')"
      header-icon="drawing"
    >
      <canvas
        ref="signaturePadCanvas"
        class="h-48 w-full rounded-lg border border-gray-25"
      ></canvas>
      <template #footer>
        <BaseButton
          :label="t('Clear')"
          icon="clear-all"
          type="tertiary-alternative"
          @click="clearSignature"
        />
        <BaseButton
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveSignature"
        />
      </template>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="showQrDialog"
      :close-label="t('Close')"
      :title="t('Generate QR code')"
      header-icon="qrcode"
    >
      <div class="flex items-center justify-center p-4">
        <img
          v-if="qrImageUrl"
          :src="qrImageUrl"
          alt=""
          class="h-64 w-64 object-contain"
        />
      </div>
    </BaseDialog>
  </section>
</template>
<script setup>
import { computed, nextTick, onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import SignaturePad from "signature_pad"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import Checkbox from "primevue/checkbox"
import Message from "primevue/message"
import Skeleton from "primevue/skeleton"
import { useNotification } from "../../composables/notification"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import attendanceService, { ATTENDANCE_STATES } from "../../services/attendanceService"
import RoomFloorPlanViewer from "../../components/room/RoomFloorPlanViewer.vue"
import { getCourseContext } from "../../utils/courseContext"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCourseSettings } from "../../store/courseSettingStore"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import { useFormatDate } from "../../composables/formatDate"
import { DateTime } from "luxon"
import { useStudentViewRefresh } from "../../composables/useStudentViewRefresh"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { abbreviatedDatetime, getCurrentTimezone } = useFormatDate()
const { showSuccessNotification, showErrorNotification, showWarningNotification } = useNotification()

// Measured at 1440 px with the local portal (one student, four dates). The teacher sheet rows
// are h-28 (112 px), header included, plus the 2 px of the table border.
const TEACHER_SKELETON = { rows: 1, rowHeight: 112, borders: 2 }
const STUDENT_SKELETON = { items: 4, itemHeight: 58 }

const formatAttendanceDate = (dateTimeStr) => abbreviatedDatetime(dateTimeStr) || dateTimeStr
const formatRoom = (room) => {
  if (!room) return ""
  return [room.branchTitle, room.title].filter(Boolean).join(" — ")
}
const { sid, cid, gid } = getCourseContext()

const getAttendanceRequestContext = () => {
  const context = {}

  if (cid) {
    context.cid = Number(cid)
  }

  if (sid) {
    context.sid = Number(sid)
  }

  if (gid) {
    context.gid = Number(gid)
  }

  return context
}
const isLoading = ref(true)
const attendanceTitle = ref("")
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const courseSettingsStore = useCourseSettings()

const isTeacherUser = computed(() => securityStore.isGranted("ROLE_TEACHER") || securityStore.isCourseAdmin)
const isTeacherUI = computed(() => isTeacherUser.value && !platformConfigStore.isStudentViewActive)
const isStudentUI = computed(() => !isTeacherUser.value || platformConfigStore.isStudentViewActive)

async function loadAttendanceDataForCurrentMode() {
  if (isStudentUI.value) {
    attendanceSheetUsers.value = []
    await fetchStudentAttendanceData(route.params.id)

    return
  }

  await fetchFullAttendanceData(route.params.id)
  await fetchAttendanceSheetUsers(route.params.id)
}

const currentUserId = computed(() => securityStore.user?.id)

const cidReqStore = useCidReqStore()
const { course } = storeToRefs(cidReqStore)

const enableSignature = computed(
  () => platformConfigStore.getSetting("attendance.enable_sign_attendance_sheet") === "true",
)
const allowComments = computed(() => platformConfigStore.getSetting("attendance.attendance_allow_comments") === "true")
const allowMultilevelGrading = computed(
  () => platformConfigStore.getSetting("attendance.multilevel_grading") === "true",
)
const showOfficialCode = computed(
  () => platformConfigStore.getSetting("attendance.attendance_add_official_code") === "true",
)
const canEdit = computed(() => {
  const readonly = route.query.readonly === "1"
  return !readonly && isTeacherUI.value
})

const canManageLocks = computed(() => canEdit.value)

const studentCanValidateOwnAttendance = computed(
  () => isStudentUI.value && courseSettingsStore.getSetting("student_validate_own_attendance") === "1",
)

const signedCount = computed(
  () => filteredDates.value.filter((d) => attendanceData.value[`${currentUserId.value}-${d.id}`] === 1).length,
)
const totalCount = computed(() => filteredDates.value.length)

const isTodayScheduled = computed(() =>
  attendanceDates.value.some((date) => {
    if (!date.dateTime) return false
    const dt = DateTime.fromISO(date.dateTime, { zone: "utc" }).setZone(getCurrentTimezone())
    return dt.isValid && dt.toISODate() === DateTime.now().setZone(getCurrentTimezone()).toISODate()
  }),
)

const attendanceDates = ref([])
const attendanceSheetUsers = ref([])
const selectedFilter = ref("all")
const comments = ref({})
const signatures = ref({})
const showQrDialog = ref(false)
const qrImageUrl = ref("")

const redirectToCalendarList = () => {
  if (!isTeacherUI.value) return
  router.push({
    name: "AttendanceCalendarList",
    params: { node: route.params.node, id: route.params.id },
    query: { sid, cid, gid },
  })
}

const redirectToAttendanceList = () => {
  router.push({
    name: "AttendanceList",
    params: { node: String(course.value?.resourceNode?.id) },
    query: { sid, cid, gid },
  })
}

const filteredAttendanceSheets = computed(() => {
  if (isStudentUI.value) {
    const currentUser = securityStore.user
    return attendanceSheetUsers.value.filter((user) => user.id === currentUser?.id)
  }
  return attendanceSheetUsers.value
})

const isSaving = ref(false)
const isSavingStudentAttendance = ref(false)
const attendanceData = ref({})

/**
 * Normalize presence before saving:
 * - Binary mode: anything not explicitly "present" (1) is saved as "absent" (0).
 * - Multi-level mode: keep null to represent "no state" (NP) when user removes a state.
 */
const normalizePresenceForSave = (rawPresence) => {
  if (allowMultilevelGrading.value) {
    return rawPresence !== undefined ? rawPresence : null
  }

  return Number(rawPresence) === 1 ? 1 : 0
}

const saveAttendanceSheet = async () => {
  if (!canEdit.value) return

  if (!attendanceData.value || Object.keys(attendanceData.value).length === 0) {
    showWarningNotification(t("No attendance data to save"))
    return
  }

  const preparedData = []

  filteredAttendanceSheets.value.forEach((user) => {
    filteredDates.value.forEach((date) => {
      if (isColumnLocked(date.id)) {
        return
      }

      const key = `${user.id}-${date.id}`
      preparedData.push({
        userId: user.id,
        calendarId: date.id,
        presence: normalizePresenceForSave(attendanceData.value[key]),
        comment: comments.value[key] ?? null,
        signature: signatures.value[key] ?? null,
      })
    })
  })

  isSaving.value = true
  try {
    await attendanceService.saveAttendanceSheet({
      attendanceId: Number(route.params.id),
      courseId: parseInt(cid, 10),
      sessionId: sid ? parseInt(sid, 10) : null,
      groupId: gid ? parseInt(gid, 10) : null,
      attendanceData: preparedData,
    })

    // Refresh "Not attended" column after saving
    await fetchAttendanceSheetUsers(route.params.id)
    showSuccessNotification(t("Attendance saved successfully"))
  } catch (error) {
    console.error("Error saving attendance data:", error)
    showErrorNotification(t("Failed to save attendance. Please try again."))
  } finally {
    isSaving.value = false
  }
}

const onStudentAttendanceChange = (dateId, checked) => {
  const key = `${currentUserId.value}-${dateId}`
  attendanceData.value[key] = checked ? 1 : 0
}

const saveStudentOwnAttendance = async () => {
  isSavingStudentAttendance.value = true
  try {
    const entries = filteredDates.value.map((date) => ({
      calendarId: date.id,
      presence: attendanceData.value[`${currentUserId.value}-${date.id}`] ?? 0,
    }))

    await attendanceService.saveStudentOwnAttendance(
      {
        courseId: parseInt(cid, 10),
        entries,
      },
      getAttendanceRequestContext(),
    )

    showSuccessNotification(t("Attendance saved successfully"))
  } catch (error) {
    console.error("Error saving student attendance:", error)
    showErrorNotification(t("Failed to save attendance. Please try again."))
  } finally {
    isSavingStudentAttendance.value = false
  }
}

const showCommentDialog = ref(false)
const showSignatureDialog = ref(false)
const currentComment = ref("")
const signaturePadCanvas = ref(null)
const signaturePad = ref(null)

const fetchAttendanceSheetUsers = async (attendanceId) => {
  try {
    const params = { courseId: cid, sessionId: sid || null, groupId: gid || null }
    const users = await attendanceService.getAttendanceSheetUsers(attendanceId, params)

    attendanceSheetUsers.value = users.map((user) => ({
      id: user.id,
      photo: user.photo || "/img/default-avatar.png",
      lastName: user.lastname,
      firstName: user.firstname,
      officialCode: user.officialCode || "",
      notAttended: user.notAttended,
    }))
  } catch (error) {
    console.error("Failed to fetch attendance sheet users:", error)
    showErrorNotification(error)
  }
}

const fetchFullAttendanceData = async (attendanceId) => {
  try {
    const data = await attendanceService.getFullAttendanceData(attendanceId, getAttendanceRequestContext())
    attendanceDates.value = data.attendanceDates
    attendanceData.value = data.attendanceData
    comments.value = data.commentData || {}
    signatures.value = data.signatureData || {}
  } catch (error) {
    console.error("Failed to fetch attendance data:", error)
    showErrorNotification(error)
  }
}

const fetchStudentAttendanceData = async (attendanceId) => {
  try {
    const data = await attendanceService.getStudentAttendanceData(attendanceId, getAttendanceRequestContext())
    attendanceDates.value = data.attendanceDates
    attendanceData.value = data.attendanceData
    comments.value = data.commentData || {}
    signatures.value = data.signatureData || {}
  } catch (error) {
    console.error("Failed to fetch student attendance data:", error)
    showErrorNotification(error)
  }
}

const fetchAttendanceTitle = async () => {
  try {
    const attendanceId = route.params.id
    const response = await attendanceService.getAttendance(attendanceId, getAttendanceRequestContext())
    attendanceTitle.value = response.title || t("Unknown attendance")
  } catch (error) {
    console.error("Error fetching attendance title:", error)
    attendanceTitle.value = t("Unknown attendance")
  }
}

const todayInTz = DateTime.now().setZone(getCurrentTimezone()).toISODate()
const isoDateFromDateTime = (dateTimeStr) => {
  if (!dateTimeStr) return null
  const dt = DateTime.fromISO(dateTimeStr, { zone: "utc" }).setZone(getCurrentTimezone())
  return dt.isValid ? dt.toISODate() : null
}

const filteredDates = ref([])
const availableFilters = ref([])

const updateAvailableFilters = () => {
  availableFilters.value = [
    { label: t("All"), value: "all" },
    { label: t("Today"), value: "today" },
    { label: t("All done"), value: "done" },
    { label: t("All not done"), value: "not_done" },
  ]

  attendanceDates.value.forEach((date) => {
    availableFilters.value.push({ label: formatAttendanceDate(date.dateTime), value: date.id })
  })
}

const filterAttendanceSheets = () => {
  if (selectedFilter.value === "all") {
    filteredDates.value = attendanceDates.value
  } else if (selectedFilter.value === "today") {
    const todayEntry = attendanceDates.value.find((date) => {
      return isoDateFromDateTime(date.dateTime) === todayInTz
    })
    filteredDates.value = todayEntry ? [todayEntry] : []
  } else if (selectedFilter.value === "done") {
    filteredDates.value = attendanceDates.value.filter((date) => date.done === true)
  } else if (selectedFilter.value === "not_done") {
    filteredDates.value = attendanceDates.value.filter((date) => date.done !== true)
  } else {
    filteredDates.value = attendanceDates.value.filter((date) => date.id === parseInt(selectedFilter.value, 10))
  }
}

const contextMenu = ref({ show: false, userId: null, dateId: null })
const columnLocks = ref({})
const isColumnLocked = (dateId) => !!columnLocks.value[dateId]
const initializeColumnLocks = (dates) => {
  columnLocks.value = {}
  dates.forEach((date) => {
    columnLocks.value[date.id] = true
    filteredAttendanceSheets.value.forEach((user) => {
      const key = `${user.id}-${date.id}`
      if (attendanceData.value[key] === undefined) {
        attendanceData.value[key] = null
      }
    })
  })
}

const isToggling = ref(false)
const toggleLock = (dateId) => {
  if (!canManageLocks.value) return
  if (isToggling.value) return
  isToggling.value = true
  columnLocks.value = {
    ...columnLocks.value,
    [dateId]: !columnLocks.value[dateId],
  }

  setTimeout(() => {
    isToggling.value = false
  }, 100)
}

onMounted(async () => {
  isLoading.value = true
  await fetchAttendanceTitle()

  await loadAttendanceDataForCurrentMode()
  initializeColumnLocks(attendanceDates.value)
  updateAvailableFilters()

  if (isTeacherUI.value) {
    selectedFilter.value = "today"
    filterAttendanceSheets()
  } else {
    filteredDates.value = attendanceDates.value
  }
  isLoading.value = false
})

// Recompute when the global Student View toggle changes
useStudentViewRefresh(async () => {
  isLoading.value = true
  await loadAttendanceDataForCurrentMode()
  updateAvailableFilters()

  if (isTeacherUI.value) {
    selectedFilter.value = "today"
    filterAttendanceSheets()
  } else {
    filteredDates.value = attendanceDates.value
  }
  isLoading.value = false
})

const ATTENDANCE_STATES_BY_ID = Object.values(ATTENDANCE_STATES).reduce((acc, state) => {
  acc[state.id] = state
  return acc
}, {})

const getStateLabel = (stateId) => {
  if (!allowMultilevelGrading.value) {
    return stateId === 1 ? t("Present") : t("Absent")
  }

  return ATTENDANCE_STATES_BY_ID[stateId]?.label || t("Unknown")
}

const getStateClass = (stateId) => {
  if (!allowMultilevelGrading.value) {
    return stateId === 1 ? "bg-[rgb(var(--color-success-base))]" : "bg-[rgb(var(--color-danger-base))]"
  }
  return (
    {
      0: "bg-[rgb(var(--color-danger-base))]",
      1: "bg-[rgb(var(--color-success-base))]",
      2: "bg-[rgb(var(--color-warning-base))]",
      3: "bg-[rgb(var(--color-secondary-base))]",
      4: "bg-[rgb(var(--color-info-base))]",
      null: "bg-gray-30",
    }[stateId] || "bg-gray-30"
  )
}

const toggleAttendanceState = (userId, dateId) => {
  if (!canEdit.value) return
  if (!allowMultilevelGrading.value) {
    attendanceData.value[`${userId}-${dateId}`] = attendanceData.value[`${userId}-${dateId}`] === 1 ? 0 : 1
  } else {
    const currentState = attendanceData.value[`${userId}-${dateId}`] || 0
    attendanceData.value[`${userId}-${dateId}`] = (currentState + 1) % Object.keys(ATTENDANCE_STATES).length
  }
}

const setAllAttendance = (dateId, stateId) => {
  if (!canEdit.value || isColumnLocked(dateId)) return

  filteredAttendanceSheets.value.forEach((user) => {
    attendanceData.value[`${user.id}-${dateId}`] = stateId
  })
}

const openMenu = (userId, dateId) => {
  if (!canEdit.value) return
  if (allowMultilevelGrading.value) {
    contextMenu.value = { show: true, userId, dateId }
  } else {
    toggleAttendanceState(userId, dateId)
  }
}

const closeMenu = () => {
  contextMenu.value = { show: false, userId: null, dateId: null }
}

const selectState = (userId, dateId, stateId) => {
  if (!canEdit.value) return
  if (stateId === null) {
    delete attendanceData.value[`${userId}-${dateId}`]
  } else {
    attendanceData.value[`${userId}-${dateId}`] = stateId
  }
  closeMenu()
}

const openCommentDialog = (userId, dateId) => {
  const key = `${userId}-${dateId}`
  dialogUserId.value = userId
  dialogDateId.value = dateId
  currentComment.value = comments.value[key] || ""
  showCommentDialog.value = true
}

const openSignatureDialog = (userId, dateId) => {
  const key = `${userId}-${dateId}`
  dialogUserId.value = userId
  dialogDateId.value = dateId
  showSignatureDialog.value = true

  nextTick(() => {
    const canvas = signaturePadCanvas.value
    if (canvas) {
      canvas.width = canvas.offsetWidth
      canvas.height = canvas.offsetHeight
      signaturePad.value = new SignaturePad(canvas)

      if (signatures.value[key]) {
        const img = new Image()
        img.src = signatures.value[key]
        img.onload = () => {
          const ctx = canvas.getContext("2d")
          ctx.drawImage(img, 0, 0)
        }
      }
    }
  })
}

const saveComment = () => {
  if (!canEdit.value) return
  const key = `${dialogUserId.value}-${dialogDateId.value}`
  comments.value[key] = currentComment.value
  closeCommentDialog()
}

const saveSignature = async () => {
  if (!signaturePad.value) return
  const key = `${dialogUserId.value}-${dialogDateId.value}`
  signatures.value[key] = signaturePad.value.toDataURL()

  if (isStudentUI.value) {
    try {
      await attendanceService.saveStudentSignature({
        calendarId: dialogDateId.value,
        signature: signatures.value[key],
      })
    } catch (error) {
      console.error("Error saving student signature:", error)
      showErrorNotification(t("Failed to save signature. Please try again."))
      return
    }
  }

  closeSignatureDialog()
}

const clearSignature = () => {
  if (signaturePad.value) {
    signaturePad.value.clear()
  }
}

const closeCommentDialog = () => {
  showCommentDialog.value = false
}

const closeSignatureDialog = () => {
  showSignatureDialog.value = false
}

const viewForTablet = (dateId) => {
  if (!router.hasRoute("AttendanceSheetTablet")) {
    console.error("[Attendance] Missing route: AttendanceSheetTablet")
    showErrorNotification("Tablet route is not registered. Check router config.")
    return
  }

  const isLockedForDate = isColumnLocked(dateId)

  router.push({
    name: "AttendanceSheetTablet",
    params: { node: route.params.node, id: route.params.id, calendarId: dateId },
    query: {
      cid,
      sid,
      gid,
      readonly: route.query.readonly ?? "0",
      locked: isLockedForDate ? "1" : "0",
      gradingMode: allowMultilevelGrading.value ? "multi" : "binary",
    },
  })
}

const exportToPdf = async () => {
  if (!isTeacherUI.value) return
  try {
    const blob = await attendanceService.exportAttendanceToPdf(route.params.id, {
      cid,
      sid,
      gid,
    })
    const url = window.URL.createObjectURL(new Blob([blob], { type: "application/pdf" }))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `attendance-${route.params.id}.pdf`)
    document.body.appendChild(link)
    link.click()
  } catch (error) {
    showErrorNotification(t("Error exporting to PDF"))
    console.error(error)
  }
}

const exportToXls = async () => {
  if (!isTeacherUI.value) return
  try {
    const blob = await attendanceService.exportAttendanceToXls(route.params.id, {
      cid,
      sid,
      gid,
    })
    const url = window.URL.createObjectURL(new Blob([blob], { type: "application/vnd.ms-excel" }))
    const link = document.createElement("a")
    link.href = url
    link.setAttribute("download", `attendance-${route.params.id}.xls`)
    document.body.appendChild(link)
    link.click()
  } catch (error) {
    showErrorNotification(t("Error exporting to Excel"))
    console.error(error)
  }
}
const generateQrCode = async () => {
  if (!isTeacherUI.value) return
  try {
    const response = await attendanceService.generateQrCode(route.params.id, {
      cid,
      sid,
      gid,
    })

    qrImageUrl.value = window.URL.createObjectURL(new Blob([response], { type: "image/png" }))
    showQrDialog.value = true
  } catch (error) {
    showErrorNotification(t("Failed to generate QR code"))
    console.error(error)
  }
}

// Dialog & context refs
const dialogUserId = ref(null)
const dialogDateId = ref(null)
</script>
