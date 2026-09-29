import { onMounted, ref } from "vue"
import { storeToRefs } from "pinia"
import { usePlatformConfig } from "../../store/platformConfig"
import adminService from "../../services/adminService"
import { useNotification } from "../notification"
import { useSecurityStore } from "../../store/securityStore"
import { useAdminIndexBlocksStore } from "../../store/adminIndexBlocksStore"
import { useI18n } from "vue-i18n"

export function useIndexBlocks() {
  const { t } = useI18n()

  const { showSuccessNotification } = useNotification()

  const platformConfigStore = usePlatformConfig()
  const securityStore = useSecurityStore()
  const adminIndexBlocksStore = useAdminIndexBlocksStore()

  const { blockVersionStatusEl, blockNewsStatusEl, blockSupportStatusEl } = storeToRefs(adminIndexBlocksStore)

  onMounted(() => {
    if (!securityStore.isAdmin) {
      return
    }

    if ("false" === platformConfigStore.getSetting("platform.registered")) {
      blockVersionStatusEl.value = null
    } else {
      loadVersion().then(() => {})
    }

    if ("true" === platformConfigStore.getSetting("admin.chamilo_support")) {
      loadSupport().then(() => {})
    }

    if ("true" === platformConfigStore.getSetting("admin.chamilo_latest_news")) {
      loadNews().then(() => {})
    }
  })

  /**
   * @param {boolean} doNotListCampus
   */
  function checkVersion(doNotListCampus) {
    adminService.registerCampus(doNotListCampus).then(() => {
      adminIndexBlocksStore
        .loadVersion({ force: true, onFetchStart: () => (blockVersionStatusEl.value = t("Loading")) })
        .then(() => {})

      showSuccessNotification(t("Version check enabled"))
    })
  }

  async function loadVersion() {
    await adminIndexBlocksStore.loadVersion({
      onFetchStart: () => (blockVersionStatusEl.value = t("Loading")),
    })
  }

  async function loadNews() {
    await adminIndexBlocksStore.loadNews({
      onFetchStart: () => (blockNewsStatusEl.value = t("Loading")),
    })
  }

  async function loadSupport() {
    await adminIndexBlocksStore.loadSupport({
      onFetchStart: () => (blockSupportStatusEl.value = t("Loading")),
    })
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
  const blockSecurity = ref(null)
  const blockTracking = ref(null)
  const blockPlugins = ref(null)
  const blockHealthCheck = ref(null)
  const blockRooms = ref(null)

  async function loadBlocks() {
    const blocks = await adminService.findBlocks()

    blockUsers.value = blocks.users || null
    blockCourses.value = blocks.courses || null
    blockSessions.value = blocks.sessions || null
    blockGradebook.value = blocks.gradebook || null
    blockSkills.value = blocks.skills || null
    blockPrivacy.value = blocks.data_privacy || null
    blockTracking.value = blocks.tracking || null
    blockSettings.value = blocks.settings || null
    blockPlatform.value = blocks.platform || null
    blockChamilo.value = blocks.chamilo || null
    blockSecurity.value = blocks.security || null
    blockPlugins.value = blocks.plugins || null
    blockHealthCheck.value = blocks.health_check || null
    blockRooms.value = blocks.rooms || null
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
    blockTracking,
    blockSecurity,
    loadBlocks,
    blockNewsStatusEl,
    loadNews,
    blockSupportStatusEl,
    loadSupport,
    blockPlugins,
    blockHealthCheck,
    blockRooms,
  }
}
