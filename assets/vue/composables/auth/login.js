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
  } catch {
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

      // Step 1: Handle 2FA
      if (responseData.requires2FA && !payload.totp) {
        requires2FA.value = true;
        return { success: false, requires2FA: true };
      }

      // Step 2: Handle explicit error message
      if (responseData.error) {
        showErrorNotification(responseData.error);
        return { success: false, error: responseData.error };
      }

      // Step 3: Set user and load platform config
      securityStore.setUser(responseData);
      await platformConfigurationStore.initialize();

      // Step 4: Honor a redirect query parameter
      const redirectParam = route.query.redirect?.toString();
      if (redirectParam) {
        if (isValidHttpUrl(redirectParam)) {
          window.location.href = redirectParam;
        } else {
          await router.replace({ path: redirectParam });
        }
        return { success: true };
      }

      // Step 5: Handle "load terms" flow
      if (responseData.load_terms && responseData.redirect) {
        window.location.href = responseData.redirect;
        return { success: true };
      }

      // Step 6: Default post-login redirect based on roles
      const setting = platformConfigurationStore.getSetting(
        "registration.redirect_after_login"
      );
      let target = "/";

      if (setting && typeof setting === "string") {
        try {
          const map = JSON.parse(setting);
          const roles = responseData.roles || [];
          const profile = roles.includes("ROLE_ADMIN")
            ? "ADMIN"
            : roles.includes("ROLE_SESSION_MANAGER")
              ? "SESSIONADMIN"
              : roles.includes("ROLE_TEACHER")
                ? "COURSEMANAGER"
                : roles.includes("ROLE_STUDENT_BOSS")
                  ? "STUDENT_BOSS"
                  : roles.includes("ROLE_DRH")
                    ? "DRH"
                    : roles.includes("ROLE_INVITEE")
                      ? "INVITEE"
                      : roles.includes("ROLE_STUDENT")
                        ? "STUDENT"
                        : null;

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
