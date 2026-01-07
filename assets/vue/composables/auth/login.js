import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import securityService from "../../services/securityService"
import { useNotification } from "../notification"
import i18n, { setLocale } from "../../i18n"

function isValidHttpUrl(string) {
  try {
    const url = new URL(string)
    return url.protocol === "http:" || url.protocol === "https:"
  } catch (_) {
    return false
  }
}

// Extract a usable language code from backend response
function pickUserLang(data) {
  // Try common fields first, then nested user fields
  return (
    data?.language ||
    data?.locale ||
    data?.lang ||
    data?.user?.language ||
    data?.user?.locale ||
    data?.user?.lang ||
    null
  )
}

// Apply language immediately (no page reload)
function applyUserLocale(data) {
  const lang = pickUserLang(data)
  if (lang) {
    const next = String(lang)
    if (i18n.global.locale.value !== next) {
      setLocale(next)
    }
  }
}

// Normalize and sanitize redirect URL so that:
// - It stays on the same origin
// - It preserves path + query + hash
// - It returns a relative URL ("/path?query#hash") or null if invalid/unsafe
function normalizeRedirectUrl(rawRedirect) {
  if (!rawRedirect) {
    return null
  }

  try {
    const currentOrigin = window.location.origin

    // Root-relative path ("/resources/pages/edit?id=...")
    if (rawRedirect.startsWith("/")) {
      const url = new URL(rawRedirect, currentOrigin)
      return url.pathname + url.search + url.hash
    }

    // Absolute URL - validate protocol first
    if (!isValidHttpUrl(rawRedirect)) {
      return null
    }

    const url = new URL(rawRedirect)

    // Prevent open redirects: only allow same-origin URLs
    if (url.origin !== currentOrigin) {
      console.warn("[login] Blocked redirect to different origin:", url.origin)
      return null
    }

    // Strip origin, keep path + query + hash
    return url.pathname + url.search + url.hash
  } catch (e) {
    console.warn("[login] Invalid redirect param:", rawRedirect, e)
    return null
  }
}

function hardRedirect(target) {
  const origin = window.location.origin
  const url = new URL(target, origin)

  // Cache buster only for legacy PHP pages
  if (url.pathname.endsWith(".php")) {
    url.searchParams.set("_", Date.now().toString())
  }

  window.location.replace(url.pathname + url.search + url.hash)
}

export function useLogin() {
  const route = useRoute()
  const router = useRouter()
  const securityStore = useSecurityStore()
  const platformConfigurationStore = usePlatformConfig()
  const { showErrorNotification } = useNotification()

  const isLoading = ref(false)
  const requires2FA = ref(false)

  async function performLogin({ login, password, _remember_me, totp = null, isLoginLdap = false }) {
    isLoading.value = true
    requires2FA.value = false

    try {
      // Prepare payload as expected by securityService
      const payload = {
        login,
        password,
        _remember_me,
        totp,
      }

      // Add returnUrl if exists in query param, but sanitize it first
      const rawReturnUrl = route.query.redirect?.toString() || null
      const safeReturnUrl = rawReturnUrl ? normalizeRedirectUrl(rawReturnUrl) : null
      if (safeReturnUrl) {
        payload.returnUrl = safeReturnUrl
      }

      const responseData =
        isLoginLdap || "ldap" === platformConfigurationStore.forcedLoginMethod
          ? await securityService.loginLdap(payload)
          : await securityService.login(payload)

      // Handle 2FA flow
      if (responseData.requires2FA && !payload.totp) {
        requires2FA.value = true
        return { success: false, requires2FA: true }
      }

      // If backend forces password rotation, still apply locale before redirect
      if (responseData.rotate_password && responseData.redirect) {
        applyUserLocale(responseData)
        const safeRedirect = normalizeRedirectUrl(responseData.redirect.toString())
        window.location.href = safeRedirect || "/"
        return { success: true, rotate: true }
      }

      // Backend explicit error
      if (responseData.error) {
        showErrorNotification(responseData.error)
        return { success: false, error: responseData.error }
      }

      // Terms and conditions redirect (apply locale before navigating)
      if (responseData.load_terms && responseData.redirect) {
        applyUserLocale(responseData)
        const safeRedirect = normalizeRedirectUrl(responseData.redirect.toString())
        const target = safeRedirect || "/"
        window.location.href = target
        return { success: true, redirect: target }
      }

      // External redirect param (apply locale before navigating)
      if (route.query.redirect) {
        applyUserLocale(responseData)

        const rawRedirect = route.query.redirect.toString()
        const safeRedirect = normalizeRedirectUrl(rawRedirect)

        if (safeRedirect) {
          // Full reload here is intentional so the full app shell is rebuilt.
          window.location.href = safeRedirect
          return { success: true }
        }
      }

      // Fallback backend redirect (apply locale before navigating)
      if (responseData.redirect) {
        applyUserLocale(responseData)
        const safeRedirect = normalizeRedirectUrl(responseData.redirect.toString())
        window.location.href = safeRedirect || "/"
        return { success: true }
      }

      // Save user info
      securityStore.setUser(responseData)

      // Apply locale NOW so the UI switches before we route
      applyUserLocale(responseData)

      await platformConfigurationStore.initialize()

      // Redirect again if redirect param still exists (after login)
      if (route.query.redirect) {
        const rawRedirectAfter = route.query.redirect.toString()
        const safeRedirectAfter = normalizeRedirectUrl(rawRedirectAfter)

        if (safeRedirectAfter) {
          await router.replace(safeRedirectAfter)
          return { success: true }
        }
      }

      // Default platform redirect after login
      const setting = platformConfigurationStore.getSetting("registration.redirect_after_login")
      let target = "/"

      if (setting && typeof setting === "string") {
        try {
          const map = JSON.parse(setting)
          const roles = responseData.roles || []

          const getProfile = () => {
            if (roles.includes("ROLE_ADMIN")) return "ADMIN"
            if (roles.includes("ROLE_SESSION_MANAGER")) return "SESSIONADMIN"
            if (roles.includes("ROLE_TEACHER")) return "COURSEMANAGER"
            if (roles.includes("ROLE_STUDENT_BOSS")) return "STUDENT_BOSS"
            if (roles.includes("ROLE_DRH")) return "DRH"
            if (roles.includes("ROLE_INVITEE")) return "INVITEE"
            if (roles.includes("ROLE_STUDENT")) return "STUDENT"
            return null
          }

          const profile = getProfile()
          const value = profile && map[profile] ? map[profile] : ""

          switch (value) {
            case "user_portal.php":
            case "index.php":
              target = "/home"
              break
            case "main/auth/courses.php":
              target = "/courses"
              break
            case "":
            case null:
              target = "/"
              break
            default:
              target = `/${String(value).replace(/^\/+/, "")}`
          }
        } catch (e) {
          console.warn("[redirect_after_login] Malformed JSON:", e)
        }
      }

      const isLegacyTarget = target.includes(".php") || target.startsWith("/main/")
      if (isLegacyTarget) {
        hardRedirect(target)
        return { success: true, redirect: target }
      }

      await router.replace({ path: target })

      return { success: true }
    } catch (error) {
      const errorMessage = error.response?.data?.error || "An error occurred during login."
      showErrorNotification(errorMessage)
      return { success: false, error: errorMessage }
    } finally {
      isLoading.value = false
    }
  }

  async function redirectNotAuthenticated() {
    if (!securityStore.isAuthenticated) {
      return
    }

    const rawRedirect = route.query.redirect?.toString()
    const safeRedirect = rawRedirect ? normalizeRedirectUrl(rawRedirect) : null

    if (safeRedirect) {
      await router.push(safeRedirect)
    } else {
      await router.replace({ name: "Home" })
    }
  }

  return {
    isLoading,
    requires2FA,
    performLogin,
    redirectNotAuthenticated,
  }
}
