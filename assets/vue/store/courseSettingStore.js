import { defineStore } from "pinia"
import platformConfigService from "../services/platformConfigService"
import { ref } from "vue"

export const useCourseSettings = defineStore("courseSettings", () => {
  const isLoading = ref(false)
  const settings = ref({})

  async function loadCourseSettings(courseId, sessionId = null) {
    isLoading.value = true

    try {
      const params = { cid: courseId }
      if (sessionId) {
        params.sid = sessionId
      }

      const data = await platformConfigService.listCourseSettings(params)

      settings.value = data.settings || {}
    } catch (e) {
      console.error("Error loading course settings:", e)
    } finally {
      isLoading.value = false
    }
  }

  function resetCourseSettings() {
    settings.value = {}
  }

  const getSetting = (variable) => settings.value[variable] || null

  return {
    isLoading,
    settings,
    loadCourseSettings,
    resetCourseSettings,
    getSetting,
  }
})
