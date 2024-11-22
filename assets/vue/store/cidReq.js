import { defineStore } from "pinia"
import courseService from "../services/courseService"
import { ref } from "vue"
import sessionService from "../services/sessionService"
import { useCourseSettings } from "./courseSettingStore"

export const useCidReqStore = defineStore("cidReq", () => {
  const course = ref(null)
  const session = ref(null)
  const group = ref(null)
  const isCourseLoaded = ref(true)

  const courseSettingsStore = useCourseSettings()

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

    resetCid,
    setCourseAndSessionById,

    isCourseLoaded,
  }
})
