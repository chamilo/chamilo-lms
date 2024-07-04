import { defineStore } from "pinia"
import { usePlatformConfig } from "./platformConfig"
import courseService from "../services/courseService"
import { computed, ref } from "vue"
import sessionService from "../services/sessionService"
import { useCourseSettings } from "./courseSettingStore"

export const useCidReqStore = defineStore("cidReq", () => {
  const course = ref(null)
  const session = ref(null)
  const group = ref(null)
  const isCourseLoaded = ref(true)

  const courseSettingsStore = useCourseSettings()

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
    group.value = null

    courseSettingsStore.resetCourseSettings()
  }

  const setCourseByIri = async (cId, sid = 0) => {
    const courseIri = `/api/courses/${cId}`

    if (course.value && courseIri === course.value["@id"]) {
      return
    }

    isCourseLoaded.value = false

    const coursePromise = courseService.find(courseIri, { sid })
    const courseSettingsPromise = courseSettingsStore.loadCourseSettings(cId, sid)

    try {
      await Promise.all([coursePromise, courseSettingsPromise]).then((responses) => (course.value = responses[0]))
    } catch (error) {
      console.error(error)
    } finally {
      isCourseLoaded.value = true
    }
  }

  const setSessionByIri = async (sId, useBasic = true) => {
    const sessionIri = `/api/sessions/${sId}`

    if (session.value && sessionIri === session.value["@id"]) {
      return
    }

    try {
      session.value = await sessionService.find(sessionIri, useBasic)
    } catch (error) {
      console.error(error)
    }
  }

  const setCourseAndSessionById = (cId, sId = undefined, useBasic = true) => {
    if (!cId) {
      return Promise.resolve()
    }

    const coursePromise = setCourseByIri(cId, sId)

    if (!sId) {
      return coursePromise
    }

    const sessionPromise = setSessionByIri(sId, useBasic)

    return Promise.all([coursePromise, sessionPromise])
  }

  return {
    course,
    session,
    group,

    userIsCoach,

    resetCid,
    setCourseAndSessionById,

    isCourseLoaded,
  }
})
