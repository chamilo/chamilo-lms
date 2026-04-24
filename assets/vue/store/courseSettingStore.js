import { defineStore } from "pinia"
import axios from "axios"
import { ref } from "vue"

export const useCourseSettings = defineStore("courseSettings", () => {
  const isLoading = ref(false)
  const settings = ref({})
  const settingsByCategory = ref({})

  async function loadCourseSettings(courseId, sessionId = null) {
    isLoading.value = true

    try {
      const params = { cid: courseId }
      if (sessionId) {
        params.sid = sessionId
      }

      const { data } = await axios.get("/platform-config/list/course_settings", { params })

      settings.value = data.settings || {}
      settingsByCategory.value = data.settings_by_category || {}
    } catch (e) {
      console.error("Error loading course settings:", e)
      settings.value = {}
      settingsByCategory.value = {}
    } finally {
      isLoading.value = false
    }
  }

  function resetCourseSettings() {
    settings.value = {}
    settingsByCategory.value = {}
  }

  function hasOwn(object, key) {
    return Object.prototype.hasOwnProperty.call(object || {}, key)
  }

  function getSetting(variable, category = null) {
    if (!variable) {
      return null
    }

    if (category) {
      const categorySettings = settingsByCategory.value?.[category] || {}

      if (hasOwn(categorySettings, variable)) {
        return categorySettings[variable]
      }
    }

    // Backward-compatible fallback.
    // Keep the original flat behavior for all existing callers.
    return settings.value[variable] || null
  }

  function isSettingEnabled(variable, category = null) {
    const value = getSetting(variable, category)

    if (value === true || value === 1) {
      return true
    }

    const normalized = String(value ?? "")
      .trim()
      .toLowerCase()

    return normalized === "true" || normalized === "1"
  }

  return {
    isLoading,
    settings,
    settingsByCategory,
    loadCourseSettings,
    resetCourseSettings,
    getSetting,
    isSettingEnabled,
  }
})
