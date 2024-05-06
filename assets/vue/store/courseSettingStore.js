import { defineStore } from "pinia"
import axios from "axios"
import { ref } from "vue"

export const useCourseSettings = defineStore("courseSettings", () => {
  const isLoading = ref(false)
  const settings = ref({})

  async function loadCourseSettings(courseId) {
    isLoading.value = true

    try {
      const { data } = await axios.get(`/platform-config/list/course_settings?cid=${courseId}`)
      settings.value = data.settings
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
