<template>
  <section class="space-y-4">
    <SectionHeader
      :title="t('Attendances (tablet)')"
      icon="attendance"
    />

    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <BaseButton
          :label="t('Exit tablet mode')"
          :title="t('Go back to standard view')"
          icon="back"
          type="tertiary-alternative"
          @click="goBack"
        />
        <Skeleton
          v-if="isLoading"
          height="24px"
          width="20rem"
        />
        <p
          v-else
          class="text-body-1 font-semibold text-gray-90"
        >
          {{ attendanceTitle }} — {{ dateLabel }}
        </p>
      </div>

      <div class="flex items-start gap-2">
        <BaseInputText
          id="attendance-tablet-search"
          v-model="search"
          :label="t('Search for student...')"
          name="search"
        />
        <BaseButton
          v-if="canEdit && !isLocked"
          :disabled="isLoading || isSaving || !isDirty"
          :label="isSaving ? t('Saving...') : t('Save')"
          icon="save"
          type="success"
          @click="save"
        />
      </div>
    </div>

    <Skeleton
      v-if="isLoading"
      :height="`${TABLE_LOADING_HEIGHT}px`"
      aria-busy="true"
    />

    <template v-else>
      <Message
        v-if="isLocked"
        :closable="false"
        icon="mdi mdi-lock"
        severity="info"
      >
        {{ t("This date is locked or read-only.") }}
      </Message>

      <div class="overflow-x-auto rounded-lg border border-gray-25 bg-white shadow-sm">
        <table class="w-full border-collapse text-body-2 text-gray-90">
          <thead>
            <tr class="border-b border-gray-25 bg-gray-15 text-start font-semibold">
              <th class="p-3 text-start">#</th>
              <th class="p-3 text-start">{{ t("Photo") }}</th>
              <th class="p-3 text-start">{{ t("Last name") }}</th>
              <th class="p-3 text-start">{{ t("First name") }}</th>
              <th class="p-3 text-center">{{ t("Attended") }}</th>
              <th
                v-if="allowComments"
                class="p-3 text-center"
              >
                {{ t("Comment") }}
              </th>
              <th
                v-if="enableSignature"
                class="p-3 text-center"
              >
                {{ t("Signature") }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(user, idx) in filteredUsers"
              :key="user.id"
              class="border-b border-gray-25 last:border-b-0 hover:bg-gray-15"
            >
              <td class="p-3">{{ idx + 1 }}</td>
              <td class="p-3">
                <img
                  :src="user.photo"
                  alt=""
                  class="h-10 w-10 rounded-full"
                />
              </td>
              <td class="p-3">{{ user.lastName }}</td>
              <td class="p-3">{{ user.firstName }}</td>

              <td class="p-3 text-center">
                <button
                  v-if="allowMultilevelGrading"
                  :aria-label="getStateLabel(value(user.id))"
                  :class="getStateClass(value(user.id))"
                  :disabled="!canEdit || isLocked"
                  :title="getStateLabel(value(user.id))"
                  class="inline-block h-10 w-10 cursor-pointer rounded-full disabled:cursor-not-allowed"
                  type="button"
                  @click="cycle(user.id)"
                />
                <Checkbox
                  v-else
                  :aria-label="`${user.firstName} ${user.lastName}`"
                  :disabled="!canEdit || isLocked"
                  :model-value="value(user.id) === 1"
                  binary
                  @update:model-value="toggle(user.id)"
                />
              </td>

              <td
                v-if="allowComments"
                class="p-3 text-center"
              >
                <BaseButton
                  :disabled="!canEdit || isLocked"
                  :label="t('Add comment')"
                  icon="comment"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="openComment(user.id)"
                />
              </td>

              <td
                v-if="enableSignature"
                class="p-3 text-center"
              >
                <BaseButton
                  :disabled="!canEdit || isLocked"
                  :label="t('Sign')"
                  icon="drawing"
                  only-icon
                  size="small"
                  type="primary-text"
                  @click="openSignature(user.id)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <BaseDialog
      v-model:is-visible="showCommentDialog"
      :title="t('Add comment')"
      header-icon="comment"
    >
      <BaseTextArea
        id="attendance-tablet-comment"
        v-model="currentComment"
        :label="t('Comment')"
      />
      <template #footer>
        <BaseButton
          v-if="canEdit && !isLocked"
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
        ref="signatureCanvas"
        class="h-48 w-full rounded-lg border border-gray-25"
      ></canvas>
      <template #footer>
        <BaseButton
          v-if="canEdit && !isLocked"
          :label="t('Clear')"
          icon="clear-all"
          type="tertiary-alternative"
          @click="clearSignature"
        />
        <BaseButton
          v-if="canEdit && !isLocked"
          :label="t('Save')"
          icon="save"
          type="success"
          @click="saveSignature"
        />
      </template>
    </BaseDialog>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount } from "vue"
import { useRoute, useRouter, onBeforeRouteLeave } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import Checkbox from "primevue/checkbox"
import Message from "primevue/message"
import Skeleton from "primevue/skeleton"
import { useNotification } from "../../composables/notification"
import attendanceService, { ATTENDANCE_STATES } from "../../services/attendanceService"
import SignaturePad from "signature_pad"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { getCourseContext } from "../../utils/courseContext"
import { useFormatDate } from "../../composables/formatDate"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid } = getCourseContext()
const { abbreviatedDatetime } = useFormatDate()
const { showSuccessNotification, showErrorNotification, showInfoNotification } = useNotification()

// One-date sheet of the local portal at 1440 px: table header and one student row.
const TABLE_LOADING_HEIGHT = 107

// --------------------------- Permissions / flags -----------------------------
const securityStore = useSecurityStore()
const platformConfig = usePlatformConfig()
const isTeacherUser = computed(() => securityStore.isGranted("ROLE_TEACHER") || securityStore.isCourseAdmin)
const isStudentView = computed(() => platformConfig.isStudentViewActive)
const canEdit = computed(() => isTeacherUser.value && !isStudentView.value && route.query.readonly !== "1")

const enableSignature = computed(() => platformConfig.getSetting("attendance.enable_sign_attendance_sheet") === "true")
const allowComments = computed(() => platformConfig.getSetting("attendance.attendance_allow_comments") === "true")

// Make sure tablet view uses same grading mode as parent view
const allowMultilevelGrading = computed(() => {
  const mode = route.query.gradingMode
  if (mode === "multi") {
    return true
  }
  if (mode === "binary") {
    return false
  }

  // Fallback to global setting if no explicit mode is provided
  return platformConfig.getSetting("attendance.multilevel_grading") === "true"
})

// ------------------------------- Data models --------------------------------
const attendanceTitle = ref("")
const dateLabel = ref("")
const isLocked = ref(false)
const isSaving = ref(false)
const isLoading = ref(true)
const loadError = ref("")

const users = ref([]) // each: { id, firstName, lastName, photo }
const data = ref({}) // presence by key `${userId}-${calendarId}`
const comments = ref({})
const signatures = ref({})
const calendarId = computed(() => Number(route.params.calendarId))
const attendanceId = computed(() => Number(route.params.id))

// Snapshots to detect dirty state
const initialData = ref({})
const initialComments = ref({})
const initialSignatures = ref({})

// Search filter
const search = ref("")
const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return users.value
  return users.value.filter(
    (u) => (u.firstName || "").toLowerCase().includes(q) || (u.lastName || "").toLowerCase().includes(q),
  )
})

// ----------------------------- State helpers --------------------------------
const ATTENDANCE_STATES_BY_ID = Object.values(ATTENDANCE_STATES).reduce((acc, s) => {
  acc[s.id] = s
  return acc
}, {})

const getStateLabel = (id) => {
  if (!allowMultilevelGrading.value) {
    return id === 1 ? t("Present") : t("Absent")
  }

  return ATTENDANCE_STATES_BY_ID[id]?.label ?? t("Unknown")
}

const getStateClass = (id) => {
  if (!allowMultilevelGrading.value) {
    return id === 1 ? "bg-[rgb(var(--color-success-base))]" : "bg-[rgb(var(--color-danger-base))]"
  }

  return (
    {
      0: "bg-[rgb(var(--color-danger-base))]",
      1: "bg-[rgb(var(--color-success-base))]",
      2: "bg-[rgb(var(--color-warning-base))]",
      3: "bg-[rgb(var(--color-secondary-base))]",
      4: "bg-[rgb(var(--color-info-base))]",
    }[id] || "bg-gray-30"
  )
}

const key = (uid) => `${uid}-${calendarId.value}`
const value = (uid) => data.value[key(uid)]

const toggle = (uid) => {
  if (!canEdit.value || isLocked.value) return

  const current = value(uid)

  // If it's undefined/null, treat as "present" and toggle to "absent"
  if (current === undefined || current === null) {
    data.value[key(uid)] = 0
    return
  }

  data.value[key(uid)] = current === 1 ? 0 : 1
}

const cycle = (uid) => {
  if (!canEdit.value || isLocked.value) return
  const total = Object.keys(ATTENDANCE_STATES).length
  const cur = value(uid) ?? 0
  data.value[key(uid)] = (cur + 1) % total
}

// -------------------------- Comment / Signature -----------------------------
const showCommentDialog = ref(false)
const currentComment = ref("")
const dialogUserId = ref(null)

const openComment = (uid) => {
  dialogUserId.value = uid
  currentComment.value = comments.value[key(uid)] || ""
  showCommentDialog.value = true
}

const saveComment = () => {
  if (!canEdit.value || isLocked.value) return
  comments.value[key(dialogUserId.value)] = currentComment.value
  showCommentDialog.value = false
}

const showSignatureDialog = ref(false)
const signatureCanvas = ref(null)
const signaturePad = ref(null)

const openSignature = (uid) => {
  dialogUserId.value = uid
  showSignatureDialog.value = true
  nextTick(() => {
    const canvas = signatureCanvas.value
    if (!canvas) return
    canvas.width = canvas.offsetWidth
    canvas.height = canvas.offsetHeight
    signaturePad.value = new SignaturePad(canvas)
    const imgSrc = signatures.value[key(uid)]
    if (imgSrc) {
      const img = new Image()
      img.src = imgSrc
      img.onload = () => {
        canvas.getContext("2d").drawImage(img, 0, 0)
      }
    }
  })
}

const clearSignature = () => {
  if (signaturePad.value) {
    signaturePad.value.clear()
  }
}

const saveSignature = () => {
  if (!canEdit.value || isLocked.value) return
  if (signaturePad.value) {
    signatures.value[key(dialogUserId.value)] = signaturePad.value.toDataURL()
  }
  showSignatureDialog.value = false
}

// ------------------------------ Load / Save ---------------------------------
const snapshot = () => {
  // Take immutable snapshots to compare later (dirty check)
  initialData.value = { ...data.value }
  initialComments.value = { ...comments.value }
  initialSignatures.value = { ...signatures.value }
}

const isDirty = computed(() => {
  const allUserIds = users.value.map((u) => u.id)
  for (const uid of allUserIds) {
    const k = `${uid}-${calendarId.value}`
    if (data.value[k] !== initialData.value[k]) return true
    if (comments.value[k] !== initialComments.value[k]) return true
    if (signatures.value[k] !== initialSignatures.value[k]) return true
  }
  return false
})

const load = async () => {
  isLoading.value = true
  loadError.value = ""
  try {
    // Title
    const att = await attendanceService.getAttendance(attendanceId.value)
    attendanceTitle.value = att.title ?? t("Attendance")

    // One-day sheet
    const payload = await attendanceService.getDateSheet(attendanceId.value, calendarId.value, { cid, sid, gid })

    dateLabel.value = payload.dateTime ? (abbreviatedDatetime(payload.dateTime) ?? "") : ""

    // Lock flag from backend (if any)
    const lockFlag = payload.isLocked ?? payload.locked ?? payload.is_locked
    let lockedFromBackend = lockFlag === true || lockFlag === 1 || lockFlag === "1"

    // Lock flag from route (comes from AttendanceSheetList column lock)
    const routeLock = route.query.locked
    if (routeLock === "0") {
      // Explicitly unlocked in list view → override backend
      lockedFromBackend = false
    } else if (routeLock === "1") {
      // Explicitly locked in list view
      lockedFromBackend = true
    }

    isLocked.value = lockedFromBackend

    users.value = payload.users || []
    data.value = payload.presence || {}
    comments.value = payload.comments || {}
    signatures.value = payload.signatures || {}

    snapshot()
  } catch (e) {
    console.error("[AttendanceTablet] load failed", e)
    loadError.value = t("Failed to load attendance. Please try again later.")
    showErrorNotification(loadError.value)
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

// Save for this single date
const save = async () => {
  if (!canEdit.value || isLocked.value) return
  if (!isDirty.value) {
    showInfoNotification(t("Nothing to save"))
    return
  }
  isSaving.value = true
  try {
    const prepared = users.value.map((u) => ({
      userId: u.id,
      calendarId: calendarId.value,
      presence: data.value[key(u.id)] ?? null,
      comment: comments.value[key(u.id)] ?? null,
      signature: signatures.value[key(u.id)] ?? null,
    }))

    await attendanceService.saveAttendanceSheet({
      attendanceId: attendanceId.value,
      courseId: parseInt(cid),
      sessionId: sid ? parseInt(sid) : null,
      groupId: gid ? parseInt(gid) : null,
      attendanceData: prepared,
    })

    showSuccessNotification(t("Attendance saved successfully"))
    snapshot()
  } catch (e) {
    console.error("[AttendanceTablet] save failed", e)
    showErrorNotification(t("Failed to save attendance. Please try again."))
  } finally {
    isSaving.value = false
  }
}

// -------------------------- Leave protections -------------------------------
onBeforeRouteLeave((to, from, next) => {
  if (isDirty.value && !isLocked.value && canEdit.value) {
    if (confirm(t("You have unsaved changes. Leave anyway?"))) next()
    else next(false)
  } else {
    next()
  }
})

const beforeUnload = (e) => {
  if (isDirty.value && !isLocked.value && canEdit.value) {
    e.preventDefault()
    e.returnValue = ""
  }
}
window.addEventListener("beforeunload", beforeUnload)
onBeforeUnmount(() => window.removeEventListener("beforeunload", beforeUnload))

// ------------------------------- Navigation ---------------------------------
const goBack = () => {
  router.push({
    name: "AttendanceSheetList",
    params: { node: route.params.node, id: route.params.id },
    query: {
      cid,
      sid,
      gid,
      readonly: route.query.readonly ?? "0",
    },
  })
}
</script>
