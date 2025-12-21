import { defineStore } from "pinia"
import axios from "axios"
import { computed, ref } from "vue"

export const usePlatformConfig = defineStore("platformConfig", () => {
  const isLoading = ref(false)
  const settings = ref([])
  const studentView = ref("teacherview")
  const plugins = ref([])
  const visualTheme = ref("chamilo")
  const oauth2Providers = ref([])
  const ldapAuth = ref(null)
  const forcedLoginMethod = ref(null)

  async function findSettingsRequest() {
    isLoading.value = true

    try {
      const { data } = await axios.get("/platform-config/list")

      visualTheme.value = data.visual_theme

      settings.value = data.settings

      studentView.value = data.studentview

      plugins.value = data.plugins

      oauth2Providers.value = data.oauth2_providers

      ldapAuth.value = data.ldap_auth

      forcedLoginMethod.value = data.forced_login_method
    } catch (e) {
      console.log(e)
    } finally {
      isLoading.value = false
    }
  }

  async function initialize() {
    await findSettingsRequest()
  }

  const getSetting = computed(
    () => (variable) => (settings.value && settings.value[variable] ? settings.value[variable] : null),
  )

  const isStudentViewActive = computed(() => "studentview" === studentView.value)

  function setStudentViewEnabled(enabled) {
    studentView.value = enabled ? "studentview" : "teacherview"
  }

  function setStudentViewMode(mode) {
    const m = (mode || "").toString().toLowerCase() === "studentview" ? "studentview" : "teacherview"
    studentView.value = m
  }

  return {
    isLoading,
    settings,
    studentView,
    plugins,
    initialize,
    getSetting,
    isStudentViewActive,
    visualTheme,
    oauth2Providers,
    ldapAuth,
    forcedLoginMethod,
    setStudentViewEnabled,
    setStudentViewMode,
  }
})
