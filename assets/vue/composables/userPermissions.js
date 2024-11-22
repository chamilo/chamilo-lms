import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"
import api from "../config/api"
import { computed, ref, unref } from "vue"
import { useSecurityStore } from "../store/securityStore"

/**
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

  try {
    const { data } = await api.get("/permissions/is_allowed_to_edit", {
      params: {
        tutor,
        coach,
        sessioncoach: sessionCoach,
        checkstudentview: checkStudentView,
        cid: course.value?.id,
        sid: session.value?.id,
      },
    })

    return data.isAllowedToEdit
  } catch (e) {
    console.log(e)
  }

  return false
}

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
