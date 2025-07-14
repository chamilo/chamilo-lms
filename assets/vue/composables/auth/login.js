import { ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useSecurityStore } from "../../store/securityStore";
import { usePlatformConfig } from "../../store/platformConfig";
import securityService from "../../services/securityService";
import { useNotification } from "../notification";

function isValidHttpUrl(string) {
  try {
    const url = new URL(string);
    return url.protocol === "http:" || url.protocol === "https:";
  } catch (_) {
    return false;
  }
}

export function useLogin() {
  const route = useRoute();
  const router = useRouter();
  const securityStore = useSecurityStore();
  const platformConfigurationStore = usePlatformConfig();
  const { showErrorNotification } = useNotification();

  const isLoading = ref(false);
  const requires2FA = ref(false);

  async function performLogin(payload) {
    isLoading.value = true;
    requires2FA.value = false;

    try {
      const responseData = await securityService.login(payload);

      // Check if the backend demands 2FA and no TOTP was provided yet
      if (responseData.requires2FA && !payload.totp) {
        requires2FA.value = true;
        return { success: false, requires2FA: true };
      }

      // Check rotate password flow
      if (responseData.rotate_password && responseData.redirect) {
        window.location.href = responseData.redirect;
        return { success: true, rotate: true };
      }

      // Handle explicit backend error message
      if (responseData.error) {
        showErrorNotification(responseData.error);
        return { success: false, error: responseData.error };
      }

      // Special flow for terms acceptance
      if (responseData.load_terms && responseData.redirect) {
        window.location.href = responseData.redirect;
        return { success: true, redirect: responseData.redirect };
      }

      // Handle external redirect param
      const redirectParam = route.query.redirect?.toString();
      if (redirectParam) {
        if (isValidHttpUrl(redirectParam)) {
          window.location.href = redirectParam;
        } else {
          await router.replace({ path: redirectParam });
        }
        return { success: true };
      }

      if (responseData.redirect) {
        window.location.href = responseData.redirect;
        return { success: true };
      }

      securityStore.setUser(responseData);
      await platformConfigurationStore.initialize();

      // Handle redirect param again after login
      if (route.query.redirect) {
        await router.replace({ path: route.query.redirect.toString() });
        return { success: true };
      }

      // Determine post-login route from settings
      const setting = platformConfigurationStore.getSetting("registration.redirect_after_login");
      let target = "/";

      if (setting && typeof setting === "string") {
        try {
          const map = JSON.parse(setting);
          const roles = responseData.roles || [];

          const getProfile = () => {
            if (roles.includes("ROLE_ADMIN")) return "ADMIN";
            if (roles.includes("ROLE_SESSION_MANAGER")) return "SESSIONADMIN";
            if (roles.includes("ROLE_TEACHER")) return "COURSEMANAGER";
            if (roles.includes("ROLE_STUDENT_BOSS")) return "STUDENT_BOSS";
            if (roles.includes("ROLE_DRH")) return "DRH";
            if (roles.includes("ROLE_INVITEE")) return "INVITEE";
            if (roles.includes("ROLE_STUDENT")) return "STUDENT";
            return null;
          };

          const profile = getProfile();
          const value = profile && map[profile] ? map[profile] : "";

          switch (value) {
            case "user_portal.php":
            case "index.php":
              target = "/home";
              break;
            case "main/auth/courses.php":
              target = "/courses";
              break;
            case "":
            case null:
              target = "/";
              break;
            default:
              target = `/${value.replace(/^\/+/, "")}`;
          }
        } catch (e) {
          console.warn("[redirect_after_login] Malformed JSON:", e);
        }
      }

      await router.replace({ path: target });
      return { success: true };
    } catch (error) {
      const errorMessage =
        error.response?.data?.error || "An error occurred during login.";
      showErrorNotification(errorMessage);
      return { success: false, error: errorMessage };
    } finally {
      isLoading.value = false;
    }
  }

  async function redirectNotAuthenticated() {
    if (!securityStore.isAuthenticated) {
      return;
    }

    const redirectParam = route.query.redirect?.toString();
    if (redirectParam) {
      await router.push({ path: redirectParam });
    } else {
      await router.replace({ name: "Home" });
    }
  }

  return {
    isLoading,
    requires2FA,
    performLogin,
    redirectNotAuthenticated,
  };
}
