import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"
import api from "../config/api"
import { computed, reactive, ref, unref, watch } from "vue"
import { useSecurityStore } from "../store/securityStore"
import { usePlatformConfig } from "../store/platformConfig"

// ─── Shared reactive cache ────────────────────────────────────────────────────
// Keyed by buildCacheKey(); invalidated when student view is toggled.
const permissionCache = reactive(new Map()) // key → boolean
const pendingRequests = new Map() // key → Promise (dedup only)

function buildCacheKey(tutor, coach, sessionCoach, checkStudentView, cid, sid) {
  return `${+tutor}${+coach}${+sessionCoach}${+checkStudentView}-${cid ?? ""}-${sid ?? ""}`
}

async function fetchPermission(tutor, coach, sessionCoach, checkStudentView, cid, sid) {
  const key = buildCacheKey(tutor, coach, sessionCoach, checkStudentView, cid, sid)

  if (pendingRequests.has(key)) {
    return pendingRequests.get(key)
  }

  const promise = api
    .get("/permissions/is_allowed_to_edit", {
      params: {
        tutor,
        coach,
        sessioncoach: sessionCoach,
        checkstudentview: checkStudentView,
        cid,
        sid,
      },
    })
    .then(({ data }) => {
      permissionCache.set(key, data.isAllowedToEdit)
    })
    .catch(() => {
      permissionCache.set(key, false)
    })
    .finally(() => {
      pendingRequests.delete(key)
    })

  pendingRequests.set(key, promise)

  return promise
}

// Reactive composable

/**
 * Reactive composable for checking edit permissions.
 *
 * Returns a computed ref that stays in sync with the permission cache and
 * automatically re-fetches when the student view is toggled, without any
 * boilerplate in the calling component.
 *
 * Multiple components calling this with the same arguments share one HTTP
 * request and one cached result.
 *
 * @param {{ tutor?: boolean, coach?: boolean, sessionCoach?: boolean, checkStudentView?: boolean }} options
 * @returns {{ isAllowedToEdit: import('vue').ComputedRef<boolean> }}
 *
 * @example
 * // Default (equivalent to api_is_allowed_to_edit())
 * const { isAllowedToEdit } = useIsAllowedToEdit()
 *
 * @example
 * // With tutor/coach/sessionCoach allowed
 * const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })
 */
export function useIsAllowedToEdit({
  tutor = false,
  coach = false,
  sessionCoach = false,
  checkStudentView = true,
} = {}) {
  const cidReqStore = useCidReqStore()
  const platformConfigStore = usePlatformConfig()
  const { course, session } = storeToRefs(cidReqStore)

  const key = computed(() =>
    buildCacheKey(tutor, coach, sessionCoach, checkStudentView, course.value?.id, session.value?.id),
  )

  const isAllowedToEdit = computed(() => permissionCache.get(key.value) ?? false)

  // Trigger initial fetch (no-op if a result is already cached or pending)
  void fetchPermission(tutor, coach, sessionCoach, checkStudentView, course.value?.id, session.value?.id)

  // Re-fetch on student view toggle, invalidating cache first so the computed updates
  watch(
    () => platformConfigStore.isStudentViewActive,
    () => {
      const k = key.value
      permissionCache.delete(k)
      pendingRequests.delete(k)
      void fetchPermission(tutor, coach, sessionCoach, checkStudentView, course.value?.id, session.value?.id)
    },
  )

  return { isAllowedToEdit }
}

// Async helper (navigation guards, one-off imperative calls)

/**
 * Resolves the edit permission for the current course/session context.
 * Uses the shared cache so repeated calls are free after the first fetch.
 *
 * Prefer `useIsAllowedToEdit` inside components — it is reactive and requires
 * no manual `onMounted` / `watch` boilerplate.
 *
 * @param {boolean} tutor
 * @param {boolean} coach
 * @param {boolean} sessionCoach
 * @param {boolean} checkStudentView
 * @returns {Promise<boolean>}
 */
export async function checkIsAllowedToEdit(
  tutor = false,
  coach = false,
  sessionCoach = false,
  checkStudentView = true,
) {
  const cidReqStore = useCidReqStore()
  const { course, session } = storeToRefs(cidReqStore)
  const cid = course.value?.id
  const sid = session.value?.id
  const key = buildCacheKey(tutor, coach, sessionCoach, checkStudentView, cid, sid)

  if (permissionCache.has(key)) {
    return permissionCache.get(key)
  }

  await fetchPermission(tutor, coach, sessionCoach, checkStudentView, cid, sid)
  return permissionCache.get(key) ?? false
}

// Session/coach helper

export function useUserSessionSubscription(session = null, course = null) {
  const isGeneralCoach = ref(false)
  const isCurrentCourseCoach = ref(false)
  const isCourseCoach = ref(false)
  const isCoach = computed(() => isGeneralCoach.value || isCurrentCourseCoach.value || isCourseCoach.value)

  const cidReqStore = useCidReqStore()
  const securityStore = useSecurityStore()

  session = session || unref(cidReqStore.session)
  course = course || unref(cidReqStore.course)

  if (session) {
    isGeneralCoach.value = session.generalCoachesSubscriptions.some(
      (sessionRelUser) => sessionRelUser.user === securityStore.user["@id"],
    )

    for (const sessionRelCourseRelUser of session.courseCoachesSubscriptions) {
      if (securityStore.user["@id"] === sessionRelCourseRelUser.user) {
        isCourseCoach.value = true

        if (course) {
          if (course["@id"] === sessionRelCourseRelUser.course) {
            isCurrentCourseCoach.value = true

            break
          }
        } else {
          break
        }
      }
    }
  }

  return {
    isGeneralCoach,
    isCurrentCourseCoach,
    isCourseCoach,
    isCoach,
  }
}
