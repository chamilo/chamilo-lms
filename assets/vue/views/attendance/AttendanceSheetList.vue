<template>
  <div class="p-4">
    <!-- Toolbar -->
    <BaseToolbar class="flex justify-between items-center mb-4">
      <BaseButton
        v-if="canEdit"
        :label="t('Go to Calendar')"
        icon="calendar"
        type="info"
        @click="redirectToCalendarList"
      />
      <div class="flex items-center gap-2 ml-auto">
        <select
          v-if="!isStudent"
          v-model="selectedFilter"
          @change="filterAttendanceSheets"
          class="p-2 border border-gray-300 rounded focus:ring-primary focus:border-primary w-64"
        >
          <option value="all">{{ t("All") }}</option>
          <option value="today">{{ t("Today") }}</option>
          <option value="done">{{ t("All done") }}</option>
          <option value="not_done">{{ t("All not done") }}</option>
          <option
            v-for="date in attendanceDates"
            :key="date.id"
            :value="date.id"
          >
            {{ date.label }}
          </option>
        </select>
      </div>
    </BaseToolbar>

    <!-- Loading Spinner -->
    <div
      v-if="isLoading"
      class="flex justify-center items-center h-64"
    >
      <div class="loader"></div>
      <span class="ml-4 text-lg text-primary">{{ t("Loading attendance data...") }}</span>
    </div>

    <!-- Attendance Table -->
    <div v-else>
      <!-- Alert if no class today -->
      <div
        v-if="!isTodayScheduled"
        class="p-4 mb-4 text-warning bg-yellow-50 border border-yellow-300 rounded"
      >
        {{
          t(
            "There is no class scheduled today, try picking another day or add your attendance entry yourself using the action icons.",
          )
        }}
      </div>

      <!-- Informative Message -->
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
        <!-- Fixed User Information -->
        <div
          class="overflow-hidden flex-shrink-0"
          style="width: 520px"
        >
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-15 h-28">
                <th class="p-3 border border-gray-25 text-left">#</th>
                <th class="p-3 border border-gray-25 text-left">{{ t("Photo") }}</th>
                <th class="p-3 border border-gray-25 text-left">{{ t("Last Name") }}</th>
                <th class="p-3 border border-gray-25 text-left w-32">{{ t("First Name") }}</th>
                <th class="p-3 border border-gray-25 text-left">{{ t("Not Attended") }}</th>
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
                <td class="p-3 border border-gray-25 text-center">
                  {{ user.notAttended }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Scrollable Dates -->
        <div class="overflow-x-auto flex-1">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-15 h-28">
                <th
                  v-for="date in filteredDates"
                  :key="date.id"
                  class="p-3 border border-gray-25 text-center align-middle"
                  :class="{ 'bg-gray-200 cursor-not-allowed': isColumnLocked(date.id) }"
                >
                  <div class="flex flex-col items-center">
                    <span class="font-bold">{{ date.label }}</span>
                    <div
                      class="flex gap-2 mt-1"
                      v-if="!isStudent"
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
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="user in filteredAttendanceSheets"
                :key="user.id"
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
                    title="Column is locked or read-only"
                  >
                    <div
                      :class="getStateIconClass(attendanceData[`${user.id}-${date.id}`])"
                      class="w-10 h-10 rounded-full mx-auto"
                    ></div>
                  </div>

                  <div
                    v-else
                    :class="getStateIconClass(attendanceData[`${user.id}-${date.id}`])"
                    @click="openMenu(user.id, date.id)"
                    class="w-10 h-10 rounded-full cursor-pointer mx-auto"
                    :title="getStateLabel(attendanceData[`${user.id}-${date.id}`])"
                  ></div>

                  <div
                    v-if="contextMenu.show && contextMenu.userId === user.id && contextMenu.dateId === date.id"
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
                        :class="getStateIconClass(state.id)"
                        class="w-5 h-5 rounded-full"
                      ></div>
                      <span>{{ state.label }}</span>
                    </div>
                  </div>

                  <div
                    v-if="canEdit"
                    class="absolute top-2 right-2 flex gap-3"
                  >
                    <BaseIcon
                      icon="comment"
                      size="normal"
                      @click="openCommentDialog(user.id, date.id)"
                      class="cursor-pointer text-info"
                    />
                    <BaseIcon
                      icon="drawing"
                      size="normal"
                      @click="openSignatureDialog(user.id, date.id)"
                      class="cursor-pointer text-success"
                    />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save Button -->
      <div class="mt-4 flex justify-end">
        <BaseButton
          v-if="canEdit"
          :label="t('Save Attendance')"
          icon="check"
          type="success"
          @click="saveAttendanceSheet"
        />
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
        ></textarea>
        <template #footer>
          <BaseButton
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
        title="Add Signature"
      >
        <div class="relative w-full h-48">
          <canvas
            ref="signaturePad"
            class="border border-gray-300 rounded w-full h-full"
          ></canvas>
          <button
            @click="clearSignature"
            class="mt-2 text-primary"
          >
            Clear
          </button>
        </div>
        <template #footer>
          <BaseButton
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
    </div>
  </div>
</template>
<script setup>
import { ref, nextTick, computed, onMounted, watch } from "vue"
import { useRouter, useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import SignaturePad from "signature_pad"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import attendanceService, { ATTENDANCE_STATES } from "../../services/attendanceService"
import { useCidReq } from "../../composables/cidReq"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const { sid, cid, gid } = useCidReq()
const isLoading = ref(true)
const securityStore = useSecurityStore()

const canEdit = computed(() => securityStore.isAdmin || securityStore.isTeacher || securityStore.isHRM)
const isStudent = computed(() => securityStore.isStudent)
const isAdmin = computed(() => securityStore.isAdmin)

const todayDate = new Date().toLocaleDateString("en-US", {
  year: "numeric",
  month: "short",
  day: "2-digit",
})

const isTodayScheduled = computed(() => {
  return attendanceDates.value.some((date) => date.label.includes(todayDate))
})

const attendanceDates = ref([])
const attendanceSheetUsers = ref([])
const selectedFilter = ref("all")
const comments = ref({})
const signatures = ref({})
const redirectToCalendarList = () => {
  router.push({
    name: "CalendarList",
    params: { id: route.params.id },
    query: { sid, cid, gid },
  })
}

const filteredAttendanceSheets = computed(() => {
  if (isStudent.value) {
    const currentUser = securityStore.user
    return attendanceSheetUsers.value.filter((user) => user.id === currentUser?.id)
  }
  return attendanceSheetUsers.value
})

const saveAttendanceSheet = async () => {
  if (!canEdit.value) return

  if (!attendanceData.value || Object.keys(attendanceData.value).length === 0) {
    alert(t("No attendance data to save."))
    return
  }

  const preparedData = []

  filteredAttendanceSheets.value.forEach((user) => {
    filteredDates.value.forEach((date) => {
      const key = `${user.id}-${date.id}`
      preparedData.push({
        userId: user.id,
        calendarId: date.id,
        presence: attendanceData.value[key] ?? 0,
        comment: comments.value[key] ?? null,
        signature: signatures.value[key] ?? null,
      })
    })
  })

  try {
    const response = await attendanceService.saveAttendanceSheet({
      courseId: parseInt(cid),
      sessionId: sid ? parseInt(sid) : null,
      groupId: gid ? parseInt(gid) : null,
      attendanceData: preparedData,
    })

    console.log("Attendance data saved:", response)
    alert(t("Attendance saved successfully"))
  } catch (error) {
    console.error("Error saving attendance data:", error)
    alert(t("Failed to save attendance. Please try again."))
  }
}

const showCommentDialog = ref(false)
const showSignatureDialog = ref(false)
const currentComment = ref("")
const currentUserId = ref(null)
const currentDateId = ref(null)
const signaturePad = ref(null)
const attendanceData = ref({})

const fetchAttendanceSheetUsers = async () => {
  isLoading.value = true
  try {
    const params = {
      courseId: cid,
      sessionId: sid || null,
      groupId: gid || null,
    }
    const users = await attendanceService.getAttendanceSheetUsers(params)

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
  } catch (error) {
    console.error("Failed to fetch attendance data:", error)
  } finally {
    isLoading.value = false
  }
}

const filteredDates = ref([])

const filterAttendanceSheets = () => {
  switch (selectedFilter.value) {
    case "all":
      filteredDates.value = attendanceDates.value
      break

    case "done":
      filteredDates.value = attendanceDates.value.filter((date) =>
        Object.keys(attendanceData.value).some(
          (key) => key.endsWith(`-${date.id}`) && attendanceData.value[key] !== null,
        ),
      )
      break

    case "not_done":
      filteredDates.value = attendanceDates.value.filter(
        (date) =>
          !Object.keys(attendanceData.value).some(
            (key) => key.endsWith(`-${date.id}`) && attendanceData.value[key] !== null,
          ),
      )
      break

    default:
      filteredDates.value = attendanceDates.value.filter((date) => date.id === parseInt(selectedFilter.value, 10))
      break
  }
}

watch([attendanceDates, attendanceData, selectedFilter], filterAttendanceSheets, {
  immediate: true,
})

const contextMenu = ref({
  show: false,
  userId: null,
  dateId: null,
})

const columnLocks = ref({})
const isColumnLocked = (dateId) => !!columnLocks.value[dateId]
const initializeColumnLocks = (dates) => {
  columnLocks.value = {}
  dates.forEach((date) => {
    columnLocks.value[date.id] = false
  })
}

const isToggling = ref(false)
const toggleLock = (dateId) => {
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

onMounted(() => {
  fetchFullAttendanceData(route.params.id)
  fetchAttendanceSheetUsers()
  initializeColumnLocks(attendanceDates.value)
})

initializeColumnLocks(attendanceDates.value)

const getStateLabel = (stateId) => Object.values(ATTENDANCE_STATES).find((state) => state.id === stateId)?.label
const getStateIconClass = (stateId) => {
  const stateColors = {
    0: "bg-red-500", // Absent
    1: "bg-green-500", // Present
    2: "bg-orange-300", // Late < 15 min
    3: "bg-orange-500", // Late > 15 min
    4: "bg-pink-400", // Absent, justified
  }
  return stateColors[stateId] || "bg-gray-200"
}

const setAllAttendance = (dateId, stateId) => {
  filteredAttendanceSheets.value.forEach((user) => {
    attendanceData.value[`${user.id}-${dateId}`] = stateId
  })
}

const openMenu = (userId, dateId) => {
  contextMenu.value = { show: true, userId, dateId }
}

const closeMenu = () => {
  contextMenu.value = { show: false, userId: null, dateId: null }
}

const selectState = (userId, dateId, stateId) => {
  attendanceData.value[`${userId}-${dateId}`] = stateId
  closeMenu()
}

const openCommentDialog = (userId, dateId) => {
  const key = `${userId}-${dateId}`
  currentUserId.value = userId
  currentDateId.value = dateId
  currentComment.value = comments.value[key] || ""
  showCommentDialog.value = true
}

const openSignatureDialog = (userId, dateId) => {
  const key = `${userId}-${dateId}`
  currentUserId.value = userId
  currentDateId.value = dateId
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
  const key = `${currentUserId.value}-${currentDateId.value}`
  comments.value[key] = currentComment.value
  console.log(`Saved comment for ${key}:`, currentComment.value)
  closeCommentDialog()
}

const saveSignature = () => {
  if (signaturePad.value) {
    const key = `${currentUserId.value}-${currentDateId.value}`
    signatures.value[key] = signaturePad.value.toDataURL()
    console.log(`Saved signature for ${key}`)
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
  console.log(`View for tablet clicked for date ID: ${dateId}`)
}
</script>
<style scoped>
canvas {
  width: 100%;
  height: 100%;
  display: block;
}

tr {
  height: 100px;
}
th,
td {
  height: 100px;
  vertical-align: middle;
}

.flex {
  display: flex;
}

.flex-col {
  flex-direction: column;
}

.align-middle {
  vertical-align: middle;
}

.mt-1 {
  margin-top: 4px;
}

.gap-2 {
  gap: 8px;
}

.bg-red-500 {
  background-color: #f87171;
}
.bg-green-500 {
  background-color: #4ade80;
}
.bg-orange-300 {
  background-color: #fdba74;
}
.bg-orange-500 {
  background-color: #f97316;
}
.bg-pink-400 {
  background-color: #f472b6;
}
.bg-gray-200 {
  background-color: #e5e7eb;
}

.opacity-50 {
  opacity: 0.5;
}

.cursor-not-allowed {
  cursor: not-allowed;
}

.loader {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
