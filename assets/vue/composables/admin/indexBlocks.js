import { onMounted, ref } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import adminService from "../../services/adminService"
import { useToast } from "primevue/usetoast"
import { useSecurityStore } from "../../store/securityStore"
import { useI18n } from "vue-i18n"

export function useIndexBlocks() {
  const { t } = useI18n()

  const toast = useToast()

  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()

  const blockVersionStatusEl = ref()
  const blockNewsStatusEl = ref()
  const blockSupportStatusEl = ref()

  onMounted(() => {
    if (!securityStore.isAdmin) {
      return
    }

    if ("false" === platformConfigStore.getSetting("platform.registered")) {
      blockVersionStatusEl.value = null
    } else {
      loadVersion()
    }
    if ("false" === platformConfigStore.getSetting("admin.admin_chamilo_announcements_disable")) {
      loadSupport()
      loadNews()
    }
  })

  /**
   * @param {boolean} doNotListCampus
   */
  function checkVersion(doNotListCampus) {
    adminService.registerCampus(doNotListCampus).then(() => {
      loadVersion()

      toast.add({
        severity: "success",
        detail: t("Version check enabled"),
      })
    })
  }

  async function loadVersion() {
    blockVersionStatusEl.value = t("Loading")

    blockVersionStatusEl.value = await adminService.findVersion()
  }

  async function loadNews() {
    blockNewsStatusEl.value = t("Loading")

    blockNewsStatusEl.value = await adminService.findAnnouncements()
  }

  async function loadSupport() {
    blockSupportStatusEl.value = t("Loading")

    blockSupportStatusEl.value = await adminService.findSupport()
  }

  const blockUsers = ref(null)
  const blockCourses = ref(null)
  const blockSessions = ref(null)
  const blockGradebook = ref(null)
  const blockSkills = ref(null)
  const blockPrivacy = ref(null)
  const blockSettings = ref(null)
  const blockPlatform = ref(null)
  const blockChamilo = ref(null)

  async function loadBlocks() {
    const blocks = await adminService.findBlocks()

    blockUsers.value = blocks.users || null
    blockCourses.value = blocks.courses || null
    blockSessions.value = blocks.sessions || null
    blockGradebook.value = blocks.gradebook || null
    blockSkills.value = blocks.skills || null
    blockPrivacy.value = blocks.data_privacy || null
    blockSettings.value = blocks.settings || null
    blockPlatform.value = blocks.platform || null
    blockChamilo.value = blocks.chamilo || null
  }

  return {
    blockVersionStatusEl,
    checkVersion,
    blockUsers,
    blockCourses,
    blockSessions,
    blockGradebook,
    blockSkills,
    blockPrivacy,
    blockSettings,
    blockPlatform,
    blockChamilo,
    loadBlocks,
    blockNewsStatusEl,
    loadNews,
    blockSupportStatusEl,
    loadSupport,
  }
}
