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
// - It always returns a root-relative URL ("/path?query#hash") or null if invalid
// - Strips any origin from absolute URLs (prevents open redirects)
// - Handles protocol-relative paths ("//evil.com/path" → "/path")
function normalizeRedirectUrl(rawRedirect) {
  if (!rawRedirect) {
    return null
  }

  try {
    // Root-relative path: parse with a dummy base to normalize "//" prefix attacks
    if (rawRedirect.startsWith("/")) {
      const url = new URL(rawRedirect, "https://x")

      return url.pathname + url.search + url.hash
    }

    // Absolute URL: validate protocol, then strip origin (prevents open redirects)
    if (!isValidHttpUrl(rawRedirect)) {
      return null
    }

    const url = new URL(rawRedirect)

    return url.pathname + url.search + url.hash
  } catch (e) {
    console.warn("[login] Invalid redirect param:", rawRedirect, e)
    return null
  }
}

function hardRedirect(target) {
  const [withoutHash, hash = ""] = target.split("#")
  const [path, rawQuery = ""] = withoutHash.split("?")
  const sp = new URLSearchParams(rawQuery)

  // Cache buster only for legacy PHP pages
  if (path.endsWith(".php")) {
    sp.set("_", Date.now().toString())
  }

  const qs = sp.toString()

  window.location.replace(path + (qs ? `?${qs}` : "") + (hash ? `#${hash}` : ""))
}

export function useLogin() {
  const route = useRoute()
  const router = useRouter()
  const securityStore = useSecurityStore()
  const platformConfigurationStore = usePlatformConfig()
  const { showErrorNotification } = useNotification()

  const isLoading = ref(false)
  const requires2FA = ref(false)

  async function performLogin({
    login,
    password,
    _remember_me,
    totp = null,
    captcha_code = null,
    isLoginLdap = false,
  }) {
    isLoading.value = true
    requires2FA.value = false

    try {
      // Prepare payload as expected by securityService
      const payload = {
        login,
        password,
        _remember_me,
        totp,
        captcha_code,
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

        return {
          success: false,
          error: responseData.error,
          captchaRequired: !!responseData.captchaRequired,
          captchaBlocked: !!responseData.captchaBlocked,
          captchaBlockedSeconds: responseData.captchaBlockedSeconds || 0,
        }
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
        } catch (error) {
          console.warn("[redirect_after_login] Malformed JSON:", error)
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

      return {
        success: false,
        error: errorMessage,
        captchaRequired: !!error.response?.data?.captchaRequired,
        captchaBlocked: !!error.response?.data?.captchaBlocked,
        captchaBlockedSeconds: error.response?.data?.captchaBlockedSeconds || 0,
      }
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
