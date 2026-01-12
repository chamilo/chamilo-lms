<template>
  <SectionHeader :title="t('Attendances (tablet)')" />

  <div class="p-4 space-y-4">
    <!-- Loading state -->
    <div
      v-if="isLoading"
      class="flex items-center justify-center py-10 text-gray-600"
    >
      {{ t("Loading...") }}
    </div>

    <!-- Main content -->
    <div
      v-else
      class="space-y-4"
    >
      <div class="flex items-center justify-between">
        <!-- Left side: back to standard view -->
        <div class="flex items-center gap-3">
          <BaseButton
            icon="back"
            type="black"
            :label="t('Exit tablet mode')"
            :title="t('Go back to standard view')"
            @click="goBack"
          />
          <div class="text-lg font-semibold">{{ attendanceTitle }} — {{ dateLabel }}</div>
        </div>

        <!-- Right side: search + save -->
        <div class="flex items-center gap-2">
          <input
            v-model="search"
            type="text"
            class="border rounded px-3 py-2"
            :placeholder="t('Search for student...')"
          />
          <BaseButton
            v-if="canEdit && !isLocked"
            :label="isSaving ? t('Saving...') : t('Save')"
            icon="check"
            type="success"
            :disabled="isSaving || !isDirty"
            @click="save"
          />
        </div>
      </div>

      <div
        v-if="isLocked"
        class="p-3 rounded border bg-gray-15 text-gray-700"
      >
        {{ t("This date is locked or read-only.") }}
      </div>

      <div class="border rounded overflow-hidden">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-gray-15">
              <th class="p-3 text-left">#</th>
              <th class="p-3 text-left">{{ t("Photo") }}</th>
              <th class="p-3 text-left">{{ t("Last name") }}</th>
              <th class="p-3 text-left">{{ t("First name") }}</th>
              <th class="p-3 text-center">{{ t("Attended") }}</th>
              <th
                class="p-3 text-center"
                v-if="allowComments"
              >
                {{ t("Comment") }}
              </th>
              <th
                class="p-3 text-center"
                v-if="enableSignature"
              >
                {{ t("Signature") }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(user, idx) in filteredUsers"
              :key="user.id"
              class="hover:bg-gray-10"
            >
              <td class="p-3">{{ idx + 1 }}</td>
              <td class="p-3">
                <img
                  :src="user.photo"
                  alt="User"
                  class="w-10 h-10 rounded-full"
                />
              </td>
              <td class="p-3">{{ user.lastName }}</td>
              <td class="p-3">{{ user.firstName }}</td>

              <td class="p-3 text-center">
                <template v-if="allowMultilevelGrading">
                  <div
                    :class="getStateClass(value(user.id))"
                    class="w-10 h-10 rounded-full inline-block cursor-pointer"
                    :title="getStateLabel(value(user.id))"
                    @click="cycle(user.id)"
                  ></div>
                </template>
                <template v-else>
                  <input
                    type="checkbox"
                    class="w-6 h-6 cursor-pointer"
                    :checked="value(user.id) === 1"
                    :disabled="!canEdit || isLocked"
                    @change="toggle(user.id)"
                  />
                </template>
              </td>

              <td
                class="p-3 text-center"
                v-if="allowComments"
              >
                <BaseButton
                  icon="comment"
                  size="small"
                  type="info"
                  :title="t('Add comment')"
                  :disabled="!canEdit || isLocked"
                  @click="openComment(user.id)"
                />
              </td>

              <td
                class="p-3 text-center"
                v-if="enableSignature"
              >
                <BaseButton
                  icon="drawing"
                  size="small"
                  type="secondary"
                  :title="t('Sign')"
                  :disabled="!canEdit || isLocked"
                  @click="openSignature(user.id)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Comment Dialog -->
      <BaseDialog
        v-model:isVisible="showCommentDialog"
        :title="t('Add comment')"
      >
        <textarea
          class="w-full h-32 border rounded p-2"
          v-model="currentComment"
        ></textarea>
        <template #footer>
          <BaseButton
            v-if="canEdit && !isLocked"
            :label="t('Save')"
            icon="save"
            type="success"
            @click="saveComment"
          />
          <BaseButton
            :label="t('Close')"
            icon="close"
            type="danger"
            @click="showCommentDialog = false"
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
            class="border rounded w-full h-full"
          ></canvas>
          <button
            v-if="canEdit && !isLocked"
            class="mt-2 text-primary"
            @click="clearSignature"
          >
            {{ t("Clear") }}
          </button>
        </div>
        <template #footer>
          <BaseButton
            v-if="canEdit && !isLocked"
            :label="t('Save')"
            icon="save"
            type="success"
            @click="saveSignature"
          />
          <BaseButton
            :label="t('Close')"
            icon="close"
            type="danger"
            @click="showSignatureDialog = false"
          />
        </template>
      </BaseDialog>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, onBeforeUnmount } from "vue"
import { useRoute, useRouter, onBeforeRouteLeave } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import attendanceService, { ATTENDANCE_STATES } from "../../services/attendanceService"
import SignaturePad from "signature_pad"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { useCidReq } from "../../composables/cidReq"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid } = useCidReq()

// --------------------------- Permissions / flags -----------------------------
const securityStore = useSecurityStore()
const platformConfig = usePlatformConfig()
const isTeacherUser = computed(
  () => securityStore.isAdmin || securityStore.isTeacher || securityStore.isCourseAdmin || securityStore.isHRM,
)
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
const signaturePad = ref(null)

const openSignature = (uid) => {
  dialogUserId.value = uid
  showSignatureDialog.value = true
  nextTick(() => {
    const canvas = document.querySelector("canvas")
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

    dateLabel.value = payload.dateLabel ?? ""

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
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

// Save for this single date
const save = async () => {
  if (!canEdit.value || isLocked.value) return
  if (!isDirty.value) {
    alert(t("Nothing to save"))
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
      courseId: parseInt(cid),
      sessionId: sid ? parseInt(sid) : null,
      groupId: gid ? parseInt(gid) : null,
      attendanceData: prepared,
    })

    alert(t("Attendance saved successfully"))
    snapshot()
  } catch (e) {
    console.error("[AttendanceTablet] save failed", e)
    alert(t("Failed to save attendance. Please try again."))
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
