<template>
  <div class="relative isolate">
    <Card class="course-card">
      <template #header>
        <div class="course-card__header">
          <img
            v-if="isLocked"
            :alt="courseTitle || 'Course illustration'"
            :src="imageUrl"
            loading="lazy"
            referrerpolicy="no-referrer"
          />
          <BaseAppLink
            v-else
            :to="courseHomeTo"
            aria-label="Open course"
          >
            <img
              :alt="courseTitle || 'Course illustration'"
              :src="imageUrl"
              loading="lazy"
              referrerpolicy="no-referrer"
            />
          </BaseAppLink>
        </div>

        <div
          v-if="ui.categories.length > 0"
          class="course-card__category-list"
        >
          <BaseTag
            v-for="cat in ui.categories"
            :key="cat"
            :label="cat"
            type="secondary"
          />
        </div>
      </template>
      <template #title>
        <div class="flex gap-2">
          <div class="course-card__title">
            <div
              v-if="session"
              class="session"
            >
              <span
                class="session__title"
                v-text="session.title"
              />
            </div>
            <div v-if="isLocked">
              {{ courseTitle }}
              <div
                v-if="showCourseDuration && courseDurationSeconds"
                class="text-gray-50 font-normal text-caption"
              >
                ({{ (courseDurationSeconds / 60 / 60).toFixed(2) }} hours)
              </div>
            </div>

            <BaseAppLink
              v-else
              :to="courseHomeTo"
            >
              {{ courseTitle }}
            </BaseAppLink>
            <div
              v-if="showSessionDisplayDate && sessionDisplayDate"
              class="session"
            >
              <span
                class="session__display-date"
                v-text="sessionDisplayDate"
              />
            </div>
          </div>

          <BaseButton
            v-if="isLocked && hasRequirements"
            :label="t('Requirements')"
            icon="shield-check"
            onlyIcon
            type="black"
            @click="openRequirementsModal"
          />
        </div>
      </template>
      <template #content>
        <div
          v-if="ui.showAnyStudentInfo"
          class="mt-2"
        >
          <!-- Progress -->
          <div v-if="ui.showProgress">
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-20">
              <div
                class="h-full rounded-full bg-primary transition-all"
                :style="{ width: `${ui.progressPercent}%` }"
                :aria-label="`Progress ${ui.progressPercent}%`"
                role="progressbar"
                :aria-valuenow="ui.progressPercent"
                aria-valuemin="0"
                aria-valuemax="100"
              />
            </div>
            <div class="mt-2 flex items-center justify-end text-xs">
              <span
                v-if="!ui.isCompleted"
                class="text-primary"
              >
                {{ ui.progressLabel }}% Completed
              </span>
              <span
                v-else
                class="font-semibold text-primary"
              >
                Completed!
              </span>
            </div>
          </div>
          <!-- Score -->
          <div
            v-if="ui.showScore"
            class="mt-2 flex flex-col gap-1 text-xs"
          >
            <div class="flex items-center justify-between">
              <span class="text-gray-70">{{ t("Points") }}</span>
              <span class="font-semibold text-gray-90">
                {{ ui.scoreLabel }}
              </span>
            </div>
            <div
              v-if="ui.bestScoreLabel"
              class="flex items-center justify-between"
            >
              <span class="text-gray-70">{{ t("Best score") }}</span>
              <span class="font-semibold text-gray-90">
                {{ ui.bestScoreLabel }}
              </span>
            </div>
          </div>
          <!-- Certificate -->
          <div
            v-if="ui.showCertificate"
            class="mt-2 flex items-center justify-between text-xs"
          >
            <span class="text-gray-70">{{ t("Certificate") }}</span>
            <span
              v-if="ui.certificateAvailable"
              class="font-semibold text-primary"
            >
              {{ t("Available") }}
            </span>
            <span
              v-else
              class="text-gray-70"
            >
              {{ t("Not available") }}
            </span>
          </div>
        </div>
      </template>
      <template #footer>
        <div class="flex flex-col gap-2">
          <BaseAvatarList :users="teachers" />
          <div
            v-if="languageLabel"
            class="flex items-center gap-2 text-xs mt-4 font-semibold tracking-wide text-gray-50"
          >
            <BaseIcon icon="globe" />
            {{ languageLabel }}
          </div>
        </div>
      </template>
    </Card>
    <!-- Overlays -->
    <div class="absolute inset-x-0 top-0 z-50 aspect-video pointer-events-none">
      <!-- Certificate badge -->
      <div
        v-if="ui.showCertificate && ui.certificateAvailable"
        class="absolute left-3 top-3 inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-primary shadow-sm ring-1 ring-primary/15 backdrop-blur"
        aria-label="Certificate available"
      >
        <svg
          class="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M6 2h9l3 3v17H6z" />
          <path d="M15 2v4h4" />
          <path d="M8 10h8" />
          <path d="M8 14h8" />
          <path d="M10 18l2-1 2 1v-3l-2-1-2 1z" />
        </svg>
        <span>{{ t("Certificate") }}</span>
      </div>

      <!-- Notification (bell) -->
      <button
        :aria-expanded="showNotifications ? 'true' : 'false'"
        :aria-label="t('See notifications')"
        aria-haspopup="dialog"
        class="course-card__notification-button"
        :class="{ 'course-card__notification-button--badge': ui.hasNewContent }"
        type="button"
        @click.stop="toggleNotifications"
      >
        <BaseIcon icon="notification" />
      </button>
      <!-- Notifications popover -->
      <div
        v-if="showNotifications"
        class="absolute right-3 top-14 z-[60] w-80 pointer-events-auto"
        role="dialog"
        aria-label="Notifications panel"
        @click.stop
      >
        <div class="rounded-lg bg-white shadow-lg ring-1 ring-black/10 overflow-hidden">
          <div class="px-4 py-3 border-b border-gray-10">
            <div class="text-sm font-semibold text-gray-90">
              {{ t("Notifications") }}
            </div>

            <div class="mt-1 text-xs text-gray-70">
              <span v-if="ui.hasNewContent">
                {{ t("New content available for this course.") }}
              </span>
              <span v-else>
                {{ t("No new notifications.") }}
              </span>
            </div>
          </div>
          <!-- Body -->
          <div class="px-4 py-3">
            <div
              v-if="notificationsLoading"
              class="flex items-center gap-3 text-xs text-gray-70"
            >
              <span
                class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-gray-700"
              ></span>
              <span>{{ t("Loading...") }}</span>
            </div>
            <div
              v-else-if="notificationsError"
              class="text-xs text-danger font-semibold"
            >
              {{ notificationsError }}
            </div>
            <div v-else>
              <div
                v-if="!ui.hasNewContent"
                class="text-xs text-gray-70"
              >
                {{ t("No new content found.") }}
              </div>
              <div
                v-else-if="notificationsItems.length === 0"
                class="text-xs text-gray-70"
              >
                {{ t("No tool details available yet.") }}
              </div>
              <ul
                v-else
                class="max-h-60 overflow-auto divide-y divide-gray-10"
              >
                <li
                  v-for="it in notificationsItems"
                  :key="it.key"
                  class="py-2"
                >
                  <a
                    v-if="it.url"
                    class="flex items-center justify-between gap-3 text-sm text-gray-90 hover:text-primary"
                    :href="it.url"
                    @click.stop="onNotificationClick(it)"
                  >
                    <span class="min-w-0 truncate">
                      {{ it.label }}
                      <span
                        v-if="it.count"
                        class="text-xs text-gray-70"
                        >({{ it.count }})</span
                      >
                    </span>
                    <span class="text-xs text-gray-50">{{ t("Open") }}</span>
                  </a>
                  <button
                    v-else
                    type="button"
                    class="w-full flex items-center justify-between gap-3 text-sm text-gray-90 hover:text-primary text-left"
                    @click.stop="onNotificationClick(it)"
                  >
                    <span class="min-w-0 truncate">
                      {{ it.label }}
                      <span
                        v-if="it.count"
                        class="text-xs text-gray-70"
                        >({{ it.count }})</span
                      >
                    </span>
                    <span class="text-xs text-gray-50">{{ t("Open") }}</span>
                  </button>
                </li>
              </ul>
              <div
                v-if="notificationsMeta.lastAccess"
                class="mt-3 text-[11px] text-gray-50"
              >
                {{ t("Last access") }}: {{ notificationsMeta.lastAccess }}
              </div>
            </div>
          </div>
          <!-- Footer -->
          <div class="px-4 py-3 flex items-center justify-end gap-2 border-t border-gray-10">
            <BaseButton
              :label="t('Close')"
              icon="close"
              size="small"
              type="black"
              @click.stop="closeNotifications"
            />
            <BaseButton
              v-if="!isLocked"
              :label="t('Open course')"
              icon="link-external"
              size="small"
              @click.stop="goToCourse"
            />
          </div>
        </div>
      </div>
      <div
        v-if="ui.showCompletedOverlay"
        class="course-card__completed-overlay"
        aria-hidden="true"
        :aria-label="t('Course completed')"
      >
        <BaseIcon
          icon="check"
          size="custom"
        />
      </div>
    </div>

    <CatalogueRequirementModal
      v-model="showDependenciesModal"
      :course-id="courseNumericId"
      :graph-image="graphImage"
      :requirements="requirementList"
      :session-id="sessionId"
    />
  </div>
</template>

<script setup>
import Card from "primevue/card"
import BaseAvatarList from "../basecomponents/BaseAvatarList.vue"
import { computed, onBeforeUnmount, onMounted, ref, shallowReactive, watch } from "vue"
import { useRouter } from "vue-router"
import { useFormatDate } from "../../composables/formatDate"
import { usePlatformConfig } from "../../store/platformConfig"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import CatalogueRequirementModal from "./CatalogueRequirementModal.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import BaseTag from "../basecomponents/BaseTag.vue"
import { useUserSessionSubscription } from "../../composables/userPermissions"
import { useLocale } from "../../composables/locale"
import courseService from "../../services/courseService"

function createStudentInfoBatcher() {
  const cache = shallowReactive(new Map()) // key -> studentInfo
  const pendingBySid = new Map() // sid -> Set(courseId)
  const requestedKeys = new Set()

  let flushTimer = null
  let flushTimerStart = 0
  let inFlight = false
  let abortCtrl = null

  const FLUSH_DELAY_MS = 80
  const MAX_WAIT_MS = 250

  function buildKey(courseId, sid) {
    return `${Number(courseId) || 0}:${Number(sid) || 0}`
  }

  function scheduleFlush() {
    const now = Date.now()

    if (!flushTimer) {
      flushTimerStart = now
    } else {
      if (now - flushTimerStart < MAX_WAIT_MS) {
        window.clearTimeout(flushTimer)
      }
    }

    flushTimer = window.setTimeout(() => {
      flushTimer = null
      flushTimerStart = 0
      flush()
    }, FLUSH_DELAY_MS)
  }

  function queue(courseId, sid) {
    const cId = Number(courseId) || 0
    const sId = Number(sid) || 0
    if (cId <= 0) return

    const key = buildKey(cId, sId)

    if (cache.has(key)) return
    if (requestedKeys.has(key)) return

    requestedKeys.add(key)

    if (!pendingBySid.has(sId)) pendingBySid.set(sId, new Set())
    pendingBySid.get(sId).add(cId)

    scheduleFlush()
  }

  async function flush() {
    if (inFlight) return
    if (pendingBySid.size === 0) return

    inFlight = true

    if (abortCtrl) abortCtrl.abort()
    abortCtrl = new AbortController()

    const entries = Array.from(pendingBySid.entries())
    pendingBySid.clear()

    try {
      await Promise.all(
        entries.map(async ([sid, setIds]) => {
          const ids = Array.from(setIds || [])
            .map((x) => Number(x) || 0)
            .filter((x) => x > 0)

          if (ids.length === 0) return

          const resp = await fetch("/course/student-info-batch.json", {
            method: "POST",
            credentials: "same-origin",
            headers: {
              Accept: "application/json",
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              sid: Number(sid) || 0,
              courseIds: ids,
            }),
            signal: abortCtrl.signal,
          })

          if (!resp.ok) {
            ids.forEach((cid) => requestedKeys.delete(buildKey(cid, sid)))
            return
          }

          const data = await resp.json()
          const items = data?.items || {}
          const normalizedSid = Number(data?.sid ?? sid) || 0

          Object.entries(items).forEach(([cidStr, info]) => {
            const cId = Number(cidStr) || 0
            if (cId <= 0) return
            cache.set(buildKey(cId, normalizedSid), info)
          })

          ids.forEach((cid) => {
            const k = buildKey(cid, normalizedSid)
            if (!cache.has(k)) {
              requestedKeys.delete(k)
            }
          })
        }),
      )
    } catch (e) {
      entries.forEach(([sid, setIds]) => {
        for (const cid of setIds || []) {
          requestedKeys.delete(buildKey(cid, sid))
        }
      })
    } finally {
      inFlight = false
    }

    if (pendingBySid.size > 0) {
      scheduleFlush()
    }
  }

  return { cache, queue, buildKey }
}

const studentInfoBatcher = createStudentInfoBatcher()
const router = useRouter()
const { abbreviatedDatetime } = useFormatDate()

const props = defineProps({
  course: { type: Object, required: true },
  session: { type: Object, required: false, default: null },
  sessionId: { type: Number, required: false, default: 0 },
  disabled: { type: Boolean, required: false, default: false },
  showSessionDisplayDate: { type: Boolean, required: false, default: true },
})

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const { isCoach } = useUserSessionSubscription(props.session, props.course)

/**
 * Notifications UI state (per card).
 */
const showNotifications = ref(false)
const notificationsLoading = ref(false)
const notificationsError = ref("")
const notificationsItems = ref([]) // [{ key, label, url, count, lastChange }]
const notificationsMeta = ref({ lastAccess: null })
let notificationsAbort = null
let notificationsLoadedOnce = false
const toBool = (v) => v === true || v === "true" || v === 1 || v === "1"

function buildNotificationsEndpoint() {
  const cid = Number(courseNumericId.value) || 0
  const sid = Number(props.sessionId) || 0
  return `/course/${cid}/new-content-tools.json?sid=${encodeURIComponent(String(sid))}`
}

async function loadNotifications() {
  if (!ui.value.hasNewContent) {
    notificationsItems.value = []
    notificationsError.value = ""
    notificationsMeta.value = { lastAccess: null }
    return
  }

  if (courseNumericId.value <= 0) return

  // Avoid re-fetch storms while the popover stays open.
  if (notificationsLoadedOnce && notificationsItems.value.length > 0) return

  if (notificationsAbort) notificationsAbort.abort()
  notificationsAbort = new AbortController()

  notificationsLoading.value = true
  notificationsError.value = ""

  try {
    const url = buildNotificationsEndpoint()

    const resp = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: { Accept: "application/json" },
      signal: notificationsAbort.signal,
    })

    if (!resp.ok) {
      notificationsError.value = "[CourseCard] Failed to load notifications."
      return
    }

    const data = await resp.json()

    notificationsItems.value = Array.isArray(data?.items) ? data.items : []
    notificationsMeta.value = {
      lastAccess: data?.meta?.lastAccess ?? null,
    }

    notificationsLoadedOnce = true
  } catch (e) {
    if (e?.name !== "AbortError") {
      console.warn("[CourseCard] Notifications request failed.", e)
      notificationsError.value = "[CourseCard] Failed to load notifications."
    }
  } finally {
    notificationsLoading.value = false
  }
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value

  if (showNotifications.value) {
    ensureStudentInfoLoaded()
    loadNotifications()
  }
}

function closeNotifications() {
  showNotifications.value = false
}

async function goToCourse() {
  closeNotifications()

  try {
    await router.push(courseHomeTo.value)
  } catch (e) {
    console.warn("[CourseCard] Failed to navigate to course.", e)
  }
}

function onNotificationClick(it) {
  closeNotifications()

  if (it?.url) {
    window.location.href = String(it.url)
    return
  }

  goToCourse()
}

function onWindowClick() {
  if (showNotifications.value) closeNotifications()
}

function onWindowKeydown(e) {
  if (e?.key === "Escape") closeNotifications()
}

watch(showNotifications, (open) => {
  if (open) {
    window.addEventListener("click", onWindowClick)
    window.addEventListener("keydown", onWindowKeydown)
  } else {
    window.removeEventListener("click", onWindowClick)
    window.removeEventListener("keydown", onWindowKeydown)
  }
})

const { getOriginalLanguageName, getLanguageName } = useLocale()

const courseTitle = computed(() => String(props.course?.title ?? ""))

function extractNumericId(value) {
  if (typeof value === "number" && Number.isFinite(value)) return value

  if (typeof value === "string") {
    // Extract last number from strings like "/api/courses/123"
    const m = value.match(/(\d+)(?!.*\d)/)
    return m ? Number(m[1]) : 0
  }

  if (value && typeof value === "object") {
    const candidates = [value.id, value._id, value["@id"]]
    for (const c of candidates) {
      const n = extractNumericId(c)
      if (n > 0) return n
    }
  }

  return 0
}

const courseNumericId = computed(() => {
  return extractNumericId(props.course?.id ?? props.course?._id ?? props.course?.["@id"])
})

// Prefer numeric ID for routing when available.
const courseRouteId = computed(() => {
  const n = Number(courseNumericId.value) || 0
  return n > 0 ? n : (props.course?._id ?? props.course?.id)
})

const courseHomeTo = computed(() => ({
  name: "CourseHome",
  params: { id: courseRouteId.value },
  query: { sid: Number(props.sessionId) || 0 },
}))

const courseDurationSeconds = computed(() => Number(props.course?.duration ?? 0) || 0)

function normalizeIso(iso) {
  if (!iso) return ""
  return String(iso).trim().replace(/-/g, "_")
}

const courseLanguageCode = computed(() => {
  const raw = props.course?.courseLanguage ?? props.course?.course_language ?? props.course?.language ?? ""
  return normalizeIso(raw)
})

const languageLabel = computed(() => {
  const iso = courseLanguageCode.value
  if (!iso) return null

  const fromGraph =
    props.course?.courseLanguageName || props.course?.courseLanguageLabel || props.course?.languageName || null

  const resolved = fromGraph || getOriginalLanguageName(iso) || getLanguageName(iso)

  return String(resolved || iso)
    .trim()
    .toUpperCase()
})

const showRemainingDays = computed(() => {
  const v = platformConfigStore.getSetting("session.session_list_view_remaining_days")
  return toBool(v)
})

const isDurationSession = computed(() => Number(props.session?.duration ?? 0) > 0)

const daysRemainingText = computed(() => {
  if (!showRemainingDays.value || isCoach.value || !isDurationSession.value) return null

  const daysLeft = Number(props.session?.daysLeft)
  if (!Number.isFinite(daysLeft)) return null

  if (daysLeft > 1) return `${daysLeft} days remaining`
  if (daysLeft === 1) return t("Ends tomorrow")
  if (daysLeft === 0) return t("Ends today")
  return t("Expired")
})

const sessionDurationText = computed(() => {
  if (!showRemainingDays.value || !isCoach.value || !isDurationSession.value) return null

  const d = Number(props.session?.duration ?? 0)
  if (!d) return null

  return d === 1 ? "1 day duration" : `${d} days duration`
})

const showCourseDuration = computed(() => toBool(platformConfigStore.getSetting("course.show_course_duration")))

const teachers = computed(() => {
  const courseIri = props.course?.["@id"]
  if (props.session?.courseCoachesSubscriptions && courseIri) {
    return props.session.courseCoachesSubscriptions
      .filter((srcru) => srcru.course?.["@id"] === courseIri)
      .map((srcru) => srcru.user)
  }

  if (props.course?.users?.edges) {
    return props.course.users.edges.map((edge) => ({
      id: edge.node.id,
      ...edge.node.user,
    }))
  }

  return []
})

const sessionDisplayDate = computed(() => {
  if (sessionDurationText.value) return sessionDurationText.value
  if (daysRemainingText.value) return daysRemainingText.value

  const parts = []
  if (props.session?.displayStartDate) parts.push(abbreviatedDatetime(props.session.displayStartDate))
  if (props.session?.displayEndDate) parts.push(abbreviatedDatetime(props.session.displayEndDate))
  return parts.join(" — ")
})

/**
 * Requirements status (no more per-card /next-course calls on mount).
 * These fields must come from /me/courses provider:
 * - hasRequirements
 * - allowSubscription
 */
const hasRequirements = computed(() => {
  const c = props.course || {}
  const v = c.hasRequirements ?? c.has_requirements ?? null
  return typeof v === "boolean" ? v : false
})

const allowSubscription = computed(() => {
  const c = props.course || {}
  const v = c.allowSubscription ?? c.allow_subscription ?? null
  return typeof v === "boolean" ? v : true
})

const isLockedByRequirements = computed(() => hasRequirements.value && !allowSubscription.value)
const isLocked = computed(() => props.disabled || isLockedByRequirements.value)

const showDependenciesModal = ref(false)
const requirementList = ref([])
const graphImage = ref(null)
const requirementsLoading = ref(false)

async function loadRequirementsDetails() {
  if (requirementsLoading.value) return
  if (!hasRequirements.value) return
  if (courseNumericId.value <= 0) return

  requirementsLoading.value = true

  try {
    const data = await courseService.getNextCourse(courseNumericId.value, props.sessionId || 0, false)
    requirementList.value = Array.isArray(data?.sequenceList) ? data.sequenceList : []
    graphImage.value = data?.graph || null
  } catch (e) {
    requirementList.value = []
    graphImage.value = null
    console.warn("[CourseCard] Failed to load requirements details.", e)
  } finally {
    requirementsLoading.value = false
  }
}

const imageUrl = computed(
  () =>
    props.course?.illustrationUrl ||
    props.course?.image?.url ||
    props.course?.pictureUrl ||
    props.course?.thumbnail ||
    "/img/session_default.svg",
)

const studentInfoConfig = computed(() => {
  const raw = platformConfigStore.getSetting("course.course_student_info")
  if (!raw) return { score: false, progress: false, certificate: false }

  if (typeof raw === "object") {
    return {
      score: toBool(raw.score),
      progress: toBool(raw.progress),
      certificate: toBool(raw.certificate),
    }
  }

  if (typeof raw === "string") {
    try {
      const parsed = JSON.parse(raw)
      if (parsed && typeof parsed === "object") {
        return {
          score: toBool(parsed.score),
          progress: toBool(parsed.progress),
          certificate: toBool(parsed.certificate),
        }
      }
    } catch (e) {
      // ignore
    }
  }

  return { score: false, progress: false, certificate: false }
})

const showProgress = computed(() => toBool(studentInfoConfig.value.progress))
const showScore = computed(() => toBool(studentInfoConfig.value.score))
const showCertificate = computed(() => toBool(studentInfoConfig.value.certificate))
const shouldFetchStudentInfo = computed(() => showProgress.value || showScore.value || showCertificate.value)
const studentInfoLocal = ref(null)
const studentInfoKey = computed(() => studentInfoBatcher.buildKey(courseNumericId.value, props.sessionId || 0))
const studentInfo = computed(() => {
  return (
    props.course?.studentInfo ?? studentInfoBatcher.cache.get(studentInfoKey.value) ?? studentInfoLocal.value ?? null
  )
})
const isFetchingStudentInfo = ref(false)
const singleFetchScheduled = ref(false)
let studentInfoAbort = null

async function fetchStudentInfoSingle() {
  if (!shouldFetchStudentInfo.value) return
  if (courseNumericId.value <= 0) return
  if (props.course?.studentInfo && typeof props.course.studentInfo === "object") return
  if (studentInfoBatcher.cache.has(studentInfoKey.value)) return

  if (studentInfoAbort) studentInfoAbort.abort()
  studentInfoAbort = new AbortController()

  isFetchingStudentInfo.value = true

  try {
    const url = `/course/${courseNumericId.value}/student-info.json?sid=${props.sessionId || 0}`

    const resp = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: { Accept: "application/json" },
      signal: studentInfoAbort.signal,
    })

    if (!resp.ok) return

    const data = await resp.json()
    if (data && typeof data === "object") {
      studentInfoLocal.value = data
    }
  } catch (e) {
    // Silent fail: base UI can still work.
  } finally {
    isFetchingStudentInfo.value = false
  }
}

function ensureStudentInfoLoaded() {
  if (!shouldFetchStudentInfo.value) return
  if (courseNumericId.value <= 0) return
  if (props.course?.studentInfo && typeof props.course.studentInfo === "object") return
  if (studentInfoBatcher.cache.has(studentInfoKey.value)) return

  studentInfoBatcher.queue(courseNumericId.value, props.sessionId || 0)

  if (!singleFetchScheduled.value) {
    singleFetchScheduled.value = true

    window.setTimeout(() => {
      if (!studentInfoBatcher.cache.has(studentInfoKey.value) && !studentInfoLocal.value) {
        fetchStudentInfoSingle()
      }
    }, 800)
  }
}

const clampPercent = (value) => {
  const n = Number(value)
  if (!Number.isFinite(n)) return 0
  return Math.max(0, Math.min(100, n))
}

const formatPercentLabel = (value) => {
  const n = clampPercent(value)
  const rounded = Math.round(n * 10) / 10
  return Number.isInteger(rounded) ? String(rounded) : String(rounded.toFixed(1))
}

const rawTrackingProgress = computed(() => {
  const c = props.course || {}
  const si = studentInfo.value || {}
  const v = si.progress ?? c.trackingProgress ?? c.progressPercent ?? c.progress ?? 0
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
})

const rawScore = computed(() => {
  const c = props.course || {}
  const si = studentInfo.value || {}
  const v = si.score ?? c.score ?? null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
})

const rawBestScore = computed(() => {
  const c = props.course || {}
  const si = studentInfo.value || {}
  const v = si.bestScore ?? c.bestScore ?? null
  const n = Number(v)
  return Number.isFinite(n) ? n : null
})

const certificateAvailable = computed(() => {
  const c = props.course || {}
  const si = studentInfo.value || {}
  return Boolean(si.certificateAvailable ?? c.certificateAvailable ?? false)
})

const completedFromApi = computed(() => {
  const c = props.course || {}
  const si = studentInfo.value || {}
  if (typeof si.completed === "boolean") return si.completed
  if (typeof c.completed === "boolean") return c.completed
  return null
})

const hasNewContent = computed(() => {
  const c = props.course || {}
  if (typeof c.hasNewContent === "boolean") return c.hasNewContent

  const si = studentInfo.value || {}
  if (typeof si.hasNewContent === "boolean") return si.hasNewContent

  return false
})

const categories = computed(() => {
  const c = props.course || {}
  const raw = Array.isArray(c.categoryTitles) ? c.categoryTitles : (c.categories ?? [])
  if (!Array.isArray(raw)) return []

  return raw
    .map((it) => {
      if (!it) return null
      if (typeof it === "string") return it
      if (typeof it === "object") return it.title ?? it.name ?? it.code ?? null
      return String(it)
    })
    .filter(Boolean)
    .map(String)
})

const ui = computed(() => {
  const progressPercent = clampPercent(rawTrackingProgress.value)

  const computedCompletedByProgress = showProgress.value && progressPercent >= 100
  const apiCompleted = completedFromApi.value
  const isCompleted = apiCompleted === null ? computedCompletedByProgress : Boolean(apiCompleted)

  const scoreLabel = rawScore.value == null ? "—" : `${formatPercentLabel(rawScore.value)}%`
  const bestScoreLabel = rawBestScore.value == null ? null : `${formatPercentLabel(rawBestScore.value)}%`

  return {
    showProgress: showProgress.value,
    showScore: showScore.value,
    showCertificate: showCertificate.value,
    showAnyStudentInfo: showProgress.value || showScore.value || showCertificate.value,

    progressPercent,
    progressLabel: formatPercentLabel(progressPercent),

    scoreLabel,
    bestScoreLabel,

    certificateAvailable: certificateAvailable.value,

    isCompleted,
    showCompletedOverlay: showProgress.value && isCompleted,

    hasNewContent: !isCompleted && hasNewContent.value,
    categories: categories.value,

    isFetchingStudentInfo: isFetchingStudentInfo.value,
  }
})

onMounted(() => {
  ensureStudentInfoLoaded()
})

onBeforeUnmount(() => {
  window.removeEventListener("click", onWindowClick)
  window.removeEventListener("keydown", onWindowKeydown)

  if (notificationsAbort) notificationsAbort.abort()
  if (studentInfoAbort) studentInfoAbort.abort()
})

watch(
  () => [courseNumericId.value, props.sessionId, shouldFetchStudentInfo.value],
  () => {
    ensureStudentInfoLoaded()
  },
)

watch(
  () => ui.value.hasNewContent,
  (has) => {
    if (!has) {
      notificationsItems.value = []
      notificationsError.value = ""
      notificationsMeta.value = { lastAccess: null }
      notificationsLoadedOnce = false
    }
  },
)

function openRequirementsModal() {
  showDependenciesModal.value = true
  loadRequirementsDetails()
}
</script>
