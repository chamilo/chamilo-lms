import { usePlatformConfig } from "../../store/platformConfig"
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex"
import { useSecurityStore } from "../../store/securityStore"

function isValidHttpUrl(string) {
  let url

  try {
    url = new URL(string)
  } catch (_) {
    return false
  }

  return url.protocol === "http:" || url.protocol === "https:"
}

export function useLogin() {
  const route = useRoute()
  const router = useRouter()
  const store = useStore()
  const securityStore = useSecurityStore()

  async function performLogin(payload) {
    const responseData = await store.dispatch("security/login", payload)

    if (store.getters["security/hasError"]) {
      return
    }

    if (route.query.redirect) {
      // Check if 'redirect' is an absolute URL
      if (isValidHttpUrl(route.query.redirect.toString())) {
        // If it's an absolute URL, redirect directly
        window.location.href = route.query.redirect.toString()

        return
      }

      securityStore.user = responseData

      const platformConfigurationStore = usePlatformConfig()
      await platformConfigurationStore.initialize()

      // If 'redirect' is a relative path, use 'router.push' to navigate
      await router.push({ path: route.query.redirect.toString() })

      return
    }

    if (responseData.load_terms) {
      window.location.href = responseData.redirect
    } else {
      window.location.href = "/home"
    }
  }

  async function redirectNotAuthenticated() {
    if (!securityStore.isAuthenticated) {
      return
    }

    if (route.query.redirect) {
      await router.push({ path: route.query.redirect.toString() })
    } else {
      await router.replace({ name: "Home" })
    }
  }

  return {
    performLogin,
    redirectNotAuthenticated,
  }
}
