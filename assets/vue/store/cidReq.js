import { defineStore } from "pinia"
import { usePlatformConfig } from "./platformConfig"
import courseService from "../services/course"
import sessionService from "../services/session"
import { computed, ref } from "vue"

export const useCidReqStore = defineStore("cidReq", () => {
  const course = ref(null)
  const session = ref(null)

  const userIsCoach = computed(() => {
    const platformConfigStore = usePlatformConfig()

    return (userId, cId = 0, checkStudentView = true) => {
      if (checkStudentView && platformConfigStore.isStudentViewActive) {
        return false
      }

      if (!session.value || !userId) {
        return false
      }

      const sessionIsCoach = []

      if (cId) {
        const courseCoachSubscription = session.value?.sessionRelCourseRelUsers?.find(
          (srcru) => srcru.course.id === cId && srcru.user.id === userId && 2 === srcru.status,
        )

        if (courseCoachSubscription) {
          sessionIsCoach.push(courseCoachSubscription)
        }
      }

      const generalCoachSubscription = session.value?.users?.find(
        (sru) => sru.user.id === userId && 3 === sru.relationType,
      )

      if (generalCoachSubscription) {
        sessionIsCoach.push(generalCoachSubscription)
      }

      return sessionIsCoach.length > 0
    }
  })

  const resetCid = () => {
    course.value = null
    session.value = null
  }

  const setCourseByIri = async (iri, sid = 0) => {
    if (course.value && iri === course.value["@id"]) {
      return
    }

    course.value = await courseService.find(iri, { sid }).then((response) => response.json())
  }

  const setSessionByIri = async (iri) => {
    if (session.value && iri === session.value["@id"]) {
      return
    }

    session.value = await sessionService.find(iri).then((response) => response.json())
  }

  const setCourseAndSessionByIri = async (courseIri, sId = 0) => {
    if (!courseIri) {
      return
    }

    await setCourseByIri(courseIri, sId)

    let sessionIri = sId ? `/api/sessions/${sId}` : undefined

    if (!sessionIri) {
      return
    }

    await setSessionByIri(sessionIri)
  }

  const setCourseAndSessionById = async (cid, sid = undefined) => {
    let courseIri = cid ? `/api/courses/${cid}` : undefined

    await setCourseAndSessionByIri(courseIri, sid)
  }

  return {
    course,
    session,

    userIsCoach,

    resetCid,
    setCourseAndSessionByIri,
    setCourseAndSessionById,
  }
})
