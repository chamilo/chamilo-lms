import { defineStore } from "pinia"
import axios from "axios"
import { computed, ref } from "vue"

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

  const getSetting = computed(() => {
    return (variable) => settings.value[variable] || null
  });

  return {
    isLoading,
    settings,
    loadCourseSettings,
    getSetting
  }
})
