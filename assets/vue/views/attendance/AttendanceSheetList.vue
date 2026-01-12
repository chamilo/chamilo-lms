<template>
  <SectionHeader :title="t('Attendance')">
    <template #end>
      <StudentViewButton
        v-if="securityStore.isAuthenticated"
        @change="onStudentViewChange"
      />
    </template>
  </SectionHeader>

  <div class="attendance-page p-4">
    <!-- Toolbar -->
    <BaseToolbar class="flex justify-between items-center mb-4">
      <template #start>
        <BaseButton
          icon="back"
          size="normal"
          type="black"
          @click="redirectToAttendanceList"
          :title="t('Go back')"
        />
        <template v-if="isTeacherUI">
          <BaseButton
            icon="calendar-plus"
            size="normal"
            type="info"
            @click="redirectToCalendarList"
            :title="t('Go to calendar')"
          />
          <BaseButton
            icon="file-pdf"
            type="danger"
            :title="t('Export to PDF')"
            @click="exportToPdf"
          />
          <BaseButton
            icon="file-excel"
            type="success"
            :title="t('Export to Excel')"
            @click="exportToXls"
          />
          <BaseButton
            icon="qrcode"
            type="secondary"
            :title="t('Generate QR code')"
            @click="generateQrCode"
          />
        </template>
      </template>
      <template #end>
        <div class="flex items-center gap-4">
          <select
            v-if="isTeacherUI"
            v-model="selectedFilter"
            @change="filterAttendanceSheets"
            class="p-2 border border-gray-300 rounded focus:ring-primary focus:border-primary w-64"
          >
            <option
              v-for="filter in availableFilters"
              :key="filter.value"
              :value="filter.value"
            >
              {{ filter.label }}
            </option>
          </select>
          <div
            v-if="!isLoading"
            class="text-lg font-semibold text-gray-800 whitespace-nowrap"
          >
            {{ attendanceTitle }}
          </div>
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="isLoading"
      class="flex justify-center items-center h-64"
    >
      <div class="loader"></div>
      <span class="ml-4 text-lg text-primary">{{ t("Loading attendance data...") }}</span>
    </div>

    <!-- Student UI -->
    <div v-else>
      <div v-if="isStudentUI">
        <h2 class="text-xl font-semibold mb-4">{{ t("Report of attendance sheets") }}</h2>

        <div
          v-if="filteredDates.length > 0"
          class="mb-4 text-sm text-gray-700"
        >
          {{ t("To attend") }}
          <span class="bg-orange-500 text-white px-2 py-1 rounded ml-2">
            {{ signedCount }}/{{ totalCount }} ({{ Math.round((signedCount / totalCount) * 100) }}%)
          </span>
        </div>

        <div
          v-if="filteredDates.length === 0"
          class="p-4 mb-4 text-yellow-900 bg-yellow-100 border border-yellow-300 rounded"
        >
          {{ t("No attendance assigned yet.") }}
        </div>

        <div
          v-for="date in filteredDates"
          :key="date.id"
          class="flex items-center justify-between bg-gray-10 border rounded p-3 mb-2"
        >
          <div class="flex items-center">
            <template v-if="allowMultilevelGrading">
              <div
                :class="getStateClass(attendanceData[`${currentUserId}-${date.id}`])"
                class="w-10 h-10 rounded-full mr-2"
                :title="getStateLabel(parseInt(attendanceData[`${currentUserId}-${date.id}`]))"
              ></div>
            </template>
            <template v-else>
              <input
                type="checkbox"
                class="mr-2"
                :checked="attendanceData[`${currentUserId}-${date.id}`] === 1"
                disabled
              />
            </template>
            <span>{{ date.label }}</span>
          </div>
          <div class="flex gap-2">
            <BaseButton
              v-if="allowComments"
              icon="comment"
              size="small"
              type="info"
              :title="t('View comment')"
              @click="openCommentDialog(currentUserId, date.id)"
            />
            <BaseButton
              v-if="enableSignature"
              icon="drawing"
              size="small"
              type="info"
              :title="t('Sign')"
              @click="openSignatureDialog(currentUserId, date.id)"
            />
          </div>
        </div>
      </div>

      <!-- Teacher UI -->
      <div v-else>
        <div
          v-if="!isTodayScheduled"
          class="p-4 mb-4 text-yellow-900 bg-warning border rounded"
        >
          {{
            t(
              "There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons.",
            )
          }}
        </div>

        <div class="p-4 mb-4 text-primary bg-gray-15 border border-gray-25 rounded">
          <p>
            {{
              t(
                "The attendance calendar allows you to register attendance lists (one per real session the students need to attend).",
              )
            }}
          </p>
        </div>

        <!-- Attendance Sheet Table -->
        <div class="relative flex">
          <!-- Fixed user column -->
          <div
            class="overflow-hidden flex-shrink-0"
            style="width: 520px"
          >
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-gray-15 h-28">
                  <th class="p-3 border border-gray-25 text-left">#</th>
                  <th class="p-3 border border-gray-25 text-left">{{ t("Photo") }}</th>
                  <th class="p-3 border border-gray-25 text-left">{{ t("Last name") }}</th>
                  <th class="p-3 border border-gray-25 text-left w-32">{{ t("First name") }}</th>
                  <th class="p-3 border border-gray-25 text-left">{{ t("Not attended") }}</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(user, index) in filteredAttendanceSheets"
                  :key="user.id"
                  class="hover:bg-gray-10 h-28"
                >
                  <td class="p-3 border border-gray-25">{{ index + 1 }}</td>
                  <td class="p-3 border border-gray-25">
                    <img
                      :src="user.photo"
                      alt="User photo"
                      class="w-10 h-10 rounded-full"
                    />
                  </td>
                  <td
                    class="p-3 border border-gray-25 truncate"
                    :title="user.lastName"
                  >
                    {{ user.lastName }}
                  </td>
                  <td
                    class="p-3 border border-gray-25 truncate"
                    :title="user.firstName"
                  >
                    {{ user.firstName }}
                  </td>
                  <td class="p-3 border border-gray-25 text-center">{{ user.notAttended }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Scrollable dates -->
          <div class="overflow-x-auto flex-1">
            <table class="w-full border-collapse">
              <thead>
                <tr class="bg-gray-15 h-28">
                  <template v-if="filteredDates.length === 0 && selectedFilter === 'today'">
                    <th
                      colspan="100"
                      class="text-center bg-yellow-50 text-warning font-medium border border-yellow-300 py-6"
                    >
                      <div class="flex justify-center items-center gap-4">
                        <BaseIcon
                          icon="calendar"
                          class="text-warning"
                        />
                        <span>{{
                          t(
                            "There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons.",
                          )
                        }}</span>
                        <BaseButton
                          icon="calendar-plus"
                          type="info"
                          size="small"
                          @click="redirectToCalendarList"
                        />
                      </div>
                    </th>
                  </template>
                  <template v-else>
                    <th
                      v-for="date in filteredDates"
                      :key="date.id"
                      class="p-3 border border-gray-25 text-center align-middle"
                      :class="{ 'bg-gray-200 cursor-not-allowed': isColumnLocked(date.id) }"
                    >
                      <div class="flex flex-col items-center">
                        <span class="font-bold">
                          {{ date.label }}
                        </span>
                        <span
                          v-if="date.duration !== undefined && date.duration !== null"
                          class="text-xs text-gray-600 mt-1"
                        >
                          {{ t("{0} min", [date.duration]) }}
                        </span>

                        <div
                          class="flex gap-2 mt-1"
                          v-if="isTeacherUI"
                        >
                          <BaseIcon
                            icon="view-table"
                            size="normal"
                            @click="viewForTablet(date.id)"
                            class="cursor-pointer text-primary"
                            title="View for tablet"
                          />
                          <BaseIcon
                            v-if="isAdmin"
                            :icon="isColumnLocked(date.id) ? 'lock' : 'unlock'"
                            size="normal"
                            @click="toggleLock(date.id)"
                            :class="isColumnLocked(date.id) ? 'text-gray-500' : 'text-warning'"
                            :title="isColumnLocked(date.id) ? 'Unlock column' : 'Lock column'"
                          />
                          <BaseIcon
                            icon="account-check"
                            @click="setAllAttendance(date.id, 1)"
                            class="text-success"
                            title="Set all Present"
                          />
                          <BaseIcon
                            icon="account-cancel"
                            @click="setAllAttendance(date.id, 0)"
                            class="text-danger"
                            title="Set all Absent"
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
                    colspan="100"
                    class="text-center text-gray-500 border border-gray-25 py-6"
                  >
                    {{ t("No attendance data for today.") }}
                  </td>
                </tr>
                <template v-else>
                  <tr
                    v-for="user in filteredAttendanceSheets"
                    :key="user.id + '-' + selectedFilter"
                    class="hover:bg-gray-10 h-28"
                  >
                    <td
                      v-for="date in filteredDates"
                      :key="date.id"
                      class="p-3 border border-gray-25 text-center relative"
                      :class="{ 'bg-gray-200': isColumnLocked(date.id) || !canEdit }"
                    >
                      <div
                        v-if="isColumnLocked(date.id) || !canEdit"
                        class="cursor-not-allowed opacity-50"
                        :title="t('Column is locked or read-only')"
                      >
                        <template v-if="!allowMultilevelGrading">
                          <input
                            type="checkbox"
                            class="w-5 h-5 cursor-not-allowed"
                            :checked="attendanceData[`${user.id}-${date.id}`] === 1"
                            disabled
                          />
                        </template>
                        <template v-else>
                          <div
                            :class="getStateClass(attendanceData[`${user.id}-${date.id}`])"
                            class="w-10 h-10 rounded-full mx-auto"
                          ></div>
                        </template>
                      </div>
                      <div v-else>
                        <template v-if="!allowMultilevelGrading">
                          <input
                            type="checkbox"
                            :checked="attendanceData[`${user.id}-${date.id}`] === 1"
                            @change="toggleAttendanceState(user.id, date.id)"
                            class="w-5 h-5 cursor-pointer"
                          />
                        </template>
                        <template v-else>
                          <div
                            :class="getStateClass(attendanceData[`${user.id}-${date.id}`])"
                            @click="openMenu(user.id, date.id)"
                            class="w-10 h-10 rounded-full cursor-pointer mx-auto"
                            :title="getStateLabel(attendanceData[`${user.id}-${date.id}`])"
                          ></div>
                        </template>
                      </div>
                      <div
                        v-if="
                          allowMultilevelGrading &&
                          contextMenu.show &&
                          contextMenu.userId === user.id &&
                          contextMenu.dateId === date.id
                        "
                        class="absolute bg-white border border-gray-300 rounded shadow-lg z-10 p-2"
                        style="top: 40px; left: 50%; transform: translateX(-50%)"
                      >
                        <div
                          v-for="(state, key) in ATTENDANCE_STATES"
                          :key="key"
                          class="flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-100 rounded"
                          @click="selectState(user.id, date.id, state.id)"
                        >
                          <div
                            :class="getStateClass(state.id)"
                            class="w-5 h-5 rounded-full"
                          ></div>
                          <span>{{ state.label }}</span>
                        </div>
                        <div
                          class="flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-100 rounded"
                          @click="selectState(user.id, date.id, null)"
                        >
                          <div class="w-5 h-5 rounded-full bg-gray-30"></div>
                          <span>{{ t("Remove state") }}</span>
                        </div>
                      </div>
                      <div
                        v-if="canEdit"
                        class="absolute top-2 right-2 flex gap-3"
                      >
                        <BaseIcon
                          v-if="allowComments && !isColumnLocked(date.id)"
                          icon="comment"
                          size="normal"
                          @click="openCommentDialog(user.id, date.id)"
                          class="cursor-pointer text-info"
                        />
                        <BaseIcon
                          v-if="enableSignature && !isColumnLocked(date.id)"
                          icon="drawing"
                          size="normal"
                          @click="openSignatureDialog(user.id, date.id)"
                          class="cursor-pointer text-success"
                        />
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Save Button -->
        <div class="mt-4 flex justify-end">
          <BaseButton
            v-if="canEdit && filteredDates.some((date) => !isColumnLocked(date.id))"
            :label="isSaving ? t('Saving...') : t('Save attendance')"
            icon="check"
            type="success"
            @click="saveAttendanceSheet"
            :disabled="isSaving"
          />
          <div
            v-if="isSaving"
            class="ml-2 loader"
          ></div>
        </div>
      </div>

      <!-- Comment Dialog -->
      <BaseDialog
        v-model:isVisible="showCommentDialog"
        title="Add Comment"
      >
        <textarea
          v-model="currentComment"
          class="w-full h-32 border border-gray-300 rounded"
          placeholder="Write your comment..."
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
          <BaseButton
            :label="t('Close')"
            icon="close"
            type="danger"
            @click="closeCommentDialog"
          />
        </template>
      </BaseDialog>

      <!-- Signature Dialog -->
      <BaseDialog
        v-model:isVisible="showSignatureDialog"
        :title="t('Signature')"
      >
        <div class="relative w-full h-48">
          <canvas
            ref="signaturePad"
            class="border border-gray-300 rounded w-full h-full"
          ></canvas>
          <button
            v-if="isTeacherUI && canEdit"
            @click="clearSignature"
            class="mt-2 text-primary"
          >
            {{ t("Clear") }}
          </button>
        </div>
        <template #footer>
          <BaseButton
            v-if="isTeacherUI && canEdit"
            :label="t('Save')"
            icon="save"
            type="success"
            @click="saveSignature"
          />
          <BaseButton
            :label="t('Close')"
            icon="close"
            type="danger"
            @click="closeSignatureDialog"
          />
        </template>
      </BaseDialog>
      <BaseDialog
        v-model:isVisible="showQrDialog"
        title="QR Code"
      >
        <div class="flex justify-center items-center p-4">
          <img
            v-if="qrImageUrl"
            :src="qrImageUrl"
            alt="QR Code"
            class="w-64 h-64 object-contain"
          />
        </div>
        <template #footer>
          <BaseButton
            :label="t('Close')"
            icon="close"
            type="danger"
            @click="showQrDialog = false"
          />
        </template>
      </BaseDialog>
    </div>
  </div>
</template>
<script setup>
import { computed, nextTick, onMounted, ref, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import SignaturePad from "signature_pad"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import StudentViewButton from "../../components/StudentViewButton.vue"
import attendanceService, { ATTENDANCE_STATES } from "../../services/attendanceService"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { sid, cid, gid } = useCidReq()
const isLoading = ref(true)
const attendanceTitle = ref("")
const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()

const isTeacherUser = computed(
  () => securityStore.isAdmin || securityStore.isTeacher || securityStore.isCourseAdmin || securityStore.isHRM,
)
const isTeacherUI = computed(() => isTeacherUser.value && !platformConfigStore.isStudentViewActive)
const isStudentUI = computed(() => !isTeacherUser.value || platformConfigStore.isStudentViewActive)

function onStudentViewChange() {
  if (isStudentUI.value) {
    fetchStudentAttendanceData(route.params.id)
  } else {
    fetchFullAttendanceData(route.params.id)
  }
}

const isAdmin = computed(() => securityStore.isAdmin)
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
const canEdit = computed(() => {
  const readonly = route.query.readonly === "1"
  return !readonly && isTeacherUI.value
})

const signedCount = computed(
  () => filteredDates.value.filter((d) => attendanceData.value[`${currentUserId.value}-${d.id}`] === 1).length,
)
const totalCount = computed(() => filteredDates.value.length)

const todayDate = new Date().toLocaleDateString("en-US", { year: "numeric", month: "short", day: "2-digit" })
const isTodayScheduled = computed(() => attendanceDates.value.some((date) => date.label.includes(todayDate)))

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
    alert(t("No attendance data to save."))
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
      courseId: parseInt(cid),
      sessionId: sid ? parseInt(sid) : null,
      groupId: gid ? parseInt(gid) : null,
      attendanceData: preparedData,
    })

    // Refresh "Not attended" column after saving
    await fetchAttendanceSheetUsers(route.params.id)
    alert(t("Attendance saved successfully"))
  } catch (error) {
    console.error("Error saving attendance data:", error)
    alert(t("Failed to save attendance. Please try again."))
  } finally {
    isSaving.value = false
  }
}

const showCommentDialog = ref(false)
const showSignatureDialog = ref(false)
const currentComment = ref("")
const signaturePad = ref(null)

const fetchAttendanceSheetUsers = async (attendanceId) => {
  isLoading.value = true
  try {
    const params = { courseId: cid, sessionId: sid || null, groupId: gid || null }
    const users = await attendanceService.getAttendanceSheetUsers(attendanceId, params)

    attendanceSheetUsers.value = users.map((user) => ({
      id: user.id,
      photo: user.photo || "/img/default-avatar.png",
      lastName: user.lastname,
      firstName: user.firstname,
      notAttended: user.notAttended,
    }))
  } catch (error) {
    console.error("Failed to fetch attendance sheet users:", error)
  } finally {
    isLoading.value = false
  }
}

const fetchFullAttendanceData = async (attendanceId) => {
  isLoading.value = true
  try {
    const data = await attendanceService.getFullAttendanceData(attendanceId)
    attendanceDates.value = data.attendanceDates
    attendanceData.value = data.attendanceData
    comments.value = data.commentData || {}
    signatures.value = data.signatureData || {}
  } catch (error) {
    console.error("Failed to fetch attendance data:", error)
  } finally {
    isLoading.value = false
  }
}

const fetchStudentAttendanceData = async (attendanceId) => {
  isLoading.value = true
  try {
    const data = await attendanceService.getStudentAttendanceData(attendanceId)
    attendanceDates.value = data.attendanceDates
    attendanceData.value = data.attendanceData
    comments.value = data.commentData || {}
    signatures.value = data.signatureData || {}
  } catch (error) {
    console.error("Failed to fetch student attendance data:", error)
  } finally {
    isLoading.value = false
  }
}

const fetchAttendanceTitle = async () => {
  try {
    isLoading.value = true
    const attendanceId = route.params.id
    const response = await attendanceService.getAttendance(attendanceId)
    attendanceTitle.value = response.title || t("Unknown attendance")
  } catch (error) {
    console.error("Error fetching attendance title:", error)
    attendanceTitle.value = t("Unknown attendance")
  } finally {
    isLoading.value = false
  }
}

const today = new Date().toISOString().split("T")[0]
const isoFromDateLabel = (label) => {
  try {
    const dateOnly = label.split(" - ")[0]
    const parsed = new Date(dateOnly)
    return isNaN(parsed) ? null : parsed.toISOString().split("T")[0]
  } catch {
    return null
  }
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
    availableFilters.value.push({ label: date.label, value: date.id })
  })
}

const filterAttendanceSheets = () => {
  if (selectedFilter.value === "all") {
    filteredDates.value = attendanceDates.value
  } else if (selectedFilter.value === "today") {
    const todayEntry = attendanceDates.value.find((date) => {
      if (!date.label) return false
      const formatted = isoFromDateLabel(date.label)
      return formatted === today
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
  if (!isTeacherUI.value) return
  if (isToggling.value) return
  isToggling.value = true
  columnLocks.value = { ...columnLocks.value, [dateId]: !columnLocks.value[dateId] }
  setTimeout(() => {
    isToggling.value = false
  }, 100)
}

onMounted(async () => {
  await fetchAttendanceTitle()

  // Fetch data based on UI mode
  if (isStudentUI.value) {
    await fetchStudentAttendanceData(route.params.id)
  } else {
    await fetchFullAttendanceData(route.params.id)
  }

  await fetchAttendanceSheetUsers(route.params.id)
  initializeColumnLocks(attendanceDates.value)
  updateAvailableFilters()

  if (isTeacherUI.value) {
    selectedFilter.value = "today"
    filterAttendanceSheets()
  } else {
    const userId = currentUserId.value
    filteredDates.value = attendanceDates.value.filter(
      (d) =>
        attendanceData.value[`${userId}-${d.id}`] !== undefined && attendanceData.value[`${userId}-${d.id}`] !== null,
    )
  }
})

// Recompute when the global Student View toggle changes
watch(
  () => platformConfigStore.isStudentViewActive,
  async () => {
    if (isStudentUI.value) {
      await fetchStudentAttendanceData(route.params.id)
    } else {
      await fetchFullAttendanceData(route.params.id)
    }
    updateAvailableFilters()
    filterAttendanceSheets()
  },
)

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
  if (!canEdit.value) return
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
    const canvas = document.querySelector("canvas")
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

const saveSignature = () => {
  if (!canEdit.value) return
  if (signaturePad.value) {
    const key = `${dialogUserId.value}-${dialogDateId.value}`
    signatures.value[key] = signaturePad.value.toDataURL()
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
    alert("Tablet route is not registered. Check router config.")
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
    alert(t("Error exporting to PDF"))
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
    alert(t("Error exporting to Excel"))
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
    alert(t("Failed to generate QR code"))
    console.error(error)
  }
}

// Dialog & context refs
const dialogUserId = ref(null)
const dialogDateId = ref(null)
</script>
