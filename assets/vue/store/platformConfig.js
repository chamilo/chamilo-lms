import { defineStore } from "pinia"
import platformConfigService from "../services/platformConfigService"
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

  function isValidConfiguration(data) {
    return (
      data &&
      "object" === typeof data.settings &&
      !Array.isArray(data.settings) &&
      Object.keys(data.settings).length > 0
    )
  }

  async function loadConfiguration() {
    try {
      const data = await platformConfigService.list()
      if (isValidConfiguration(data)) {
        return data
      }

      console.warn("[PlatformConfig] Invalid configuration response; retrying with fetch")
    } catch (error) {
      // An explicit authentication denial must not be hidden by a second request.
      if (401 === error?.response?.status || 403 === error?.response?.status) {
        throw error
      }

      console.warn("[PlatformConfig] Unable to load configuration via Axios; retrying with fetch", error)
    }

    // Recover from an interrupted or malformed Axios response without affecting
    // other services. This endpoint is read-only and already returns plain JSON.
    const response = await fetch("/platform-config/list", {
      credentials: "same-origin",
      cache: "no-store",
      headers: { Accept: "application/json" },
    })

    if (!response.ok) {
      throw new Error(`[PlatformConfig] Configuration request failed: HTTP ${response.status}`)
    }

    const data = await response.json()
    if (!isValidConfiguration(data)) {
      throw new Error("[PlatformConfig] Invalid configuration response")
    }

    return data
  }

  async function findSettingsRequest() {
    isLoading.value = true

    try {
      const data = await loadConfiguration()

      visualTheme.value = data.visual_theme
      settings.value = data.settings
      studentView.value = data.studentview
      plugins.value = data.plugins
      oauth2Providers.value = data.oauth2_providers
      ldapAuth.value = data.ldap_auth
      forcedLoginMethod.value = data.forced_login_method
    } catch (error) {
      // Keep the last successfully loaded configuration after a transient error.
      console.error("[PlatformConfig] Unable to initialize platform settings", error)
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
  }
})
