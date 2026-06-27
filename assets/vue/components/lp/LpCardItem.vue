<script setup>
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import lpService from "../../services/lpService"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import { useSecurityStore } from "../../store/securityStore"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const { showErrorNotification } = useNotification()
const route = useRoute()
const router = useRouter()
const securityStore = useSecurityStore()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canReorder: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  canExportChamilo: { type: Boolean, default: false },
  canAutoLaunch: { type: Boolean, default: false },
  canCopy: { type: Boolean, default: false },
  canCopyScorm: { type: Boolean, default: false },
  canSeriousGame: { type: Boolean, default: false },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
  buildDates: { type: Function, required: true },
  csrfToken: { type: String, default: "" },
})
const emit = defineEmits(["export-chamilo", "export-pdf", "management-changed", "visibility-changed"])

const routeCtx = computed(() => ({
  cid: Number(route.query?.cid ?? 0) || undefined,
  sid: Number(route.query?.sid ?? 0) || undefined,
  node: Number(route.params?.node ?? 0) || undefined,
  gid: Number(route.query?.gid ?? 0),
}))

const openUrl = computed(() =>
  lpService.buildRuntimeUrl(props.lp.iid, {
    ...routeCtx.value,
    isStudentView: "true",
  }),
)

const isCStudioLearningPath = computed(() => {
  const type = Number(props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? 0)
  const path = String(props.lp?.path ?? "").toLowerCase()

  return type === 2 && path.startsWith("teachcs-")
})

const cstudioEditorUrl = computed(() => {
  const search = new URLSearchParams()

  search.set("action", "redir")
  search.set("idLudiLP", props.lp.iid)

  if (routeCtx.value.cid) {
    search.set("cid", routeCtx.value.cid)
  }

  if (routeCtx.value.sid) {
    search.set("sid", routeCtx.value.sid)
  }

  if (routeCtx.value.gid) {
    search.set("gid", routeCtx.value.gid)
  }

  return `/plugin/CStudio/oel_tools_teachdoc_link.php?${search.toString()}`
})

const reportingRoute = computed(() => ({
  name: "LpReporting",
  params: { lpId: props.lp.iid },
  query: route.query,
}))

const settingsRoute = computed(() => ({ name: "LpSettings", params: { lpId: props.lp.iid }, query: route.query }))

const updateScormRoute = computed(() => ({
  name: "LpScormUpdate",
  params: { lpId: props.lp.iid },
  query: route.query,
}))

const buildRoute = computed(() =>
  isCStudioLearningPath.value ? null : { name: "LpBuilder", params: { lpId: props.lp.iid }, query: route.query },
)

const buildUrl = computed(() => (isCStudioLearningPath.value ? cstudioEditorUrl.value : null))

const exportScormUrl = computed(() =>
  lpService.buildScormPackageDownloadUrl(props.lp.iid, {
    cid: routeCtx.value.cid || 0,
    sid: routeCtx.value.sid || 0,
    gid: routeCtx.value.gid || 0,
    node: routeCtx.value.node || undefined,
  }),
)


const isLpSubscriptionMode = computed(() => Number(props.lp?.subscribeUsers ?? props.lp?.subscribe_users ?? 0) === 1)

const isLpVisible = computed(() => {
  const value = props.lp?.visible ?? props.lp?.visibility

  if (typeof value === "undefined" || value === null || value === "") {
    return true
  }

  if (typeof value === "string") {
    return ["1", "true", "v", "visible", "published"].includes(value.toLowerCase())
  }

  return Boolean(value)
})

const manageableInContext = computed(() => props.lp?.manageableInContext !== false)

const canUpdateScorm = computed(() => {
  if (!props.canEdit) {
    return false
  }

  const type = Number(props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? 0)

  return type === 2
})

const canDownloadScormPackage = computed(() => {
  const type = Number(props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? 0)

  return props.canExportScorm && type === 2
})

const canCopyLearningPath = computed(() => {
  const type = Number(props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? 0)

  return props.canCopy && !isCStudioLearningPath.value && type !== 3 && (type !== 2 || props.canCopyScorm)
})

const managementParams = computed(() => ({
  cid: routeCtx.value.cid || 0,
  sid: routeCtx.value.sid || 0,
  gid: routeCtx.value.gid || 0,
}))

const onManage = async (action, extra = {}) => {
  if (!props.csrfToken || !manageableInContext.value) {
    return
  }

  try {
    await lpService.manageLearningPath(props.lp.iid, managementParams.value, {
      action,
      csrfToken: props.csrfToken,
      ...extra,
    })
    emit("management-changed")
  } catch (error) {
    showErrorNotification(error)
  }
}

const publishAction = computed(() => ({
  label: props.lp?.publishedOnCourseHome ? t("do not publish") : t("Publish on course homepage"),
  command: () => onManage("toggle_publish"),
}))

const attemptModeAction = computed(() => {
  const seriousGame = Boolean(props.lp?.seriousgameMode)
  const preventReinit = Boolean(props.lp?.preventReinit)
  const isMultiple = !seriousGame && !preventReinit

  return {
    label: isMultiple ? t("Prevent multiple attempts") : t("Allow multiple attempts"),
    command: () => onManage("switch_attempt_mode"),
  }
})

const viewModeAction = computed(() => {
  const mode = String(props.lp?.defaultViewMod || "embedded")
  const value = {
    fullscreen: "fullscreen",
    embedded: "embedded",
    embedframe: "external embed",
    impress: "Impress",
  }[mode] || "embedded"

  return {
    label: t("Current view mode"),
    value,
  }
})

const debugAction = computed(() => ({
  label: props.lp?.debug ? t("Hide debug") : t("Show debug"),
  command: () => onManage("switch_scorm_debug"),
}))

const seriousGameAction = computed(() => ({
  label: props.lp?.seriousgameMode ? t("Disable gamification mode") : t("Enable gamification mode"),
  command: () => onManage("toggle_serious_game"),
}))

const autoLaunchAction = computed(() => ({
  label:
    Number(props.lp?.autolaunch) === 1
      ? t("Disable learning path auto-launch")
      : t("Enable learning path auto-launch"),
  icon: Number(props.lp?.autolaunch) === 1 ? "autolunch" : "autolunch-off",
  disabled: !props.csrfToken || !manageableInContext.value,
  command: () => onManage("toggle_auto_launch", { enabled: Number(props.lp?.autolaunch) !== 1 }),
}))

const advancedAccessUrl = computed(() => {
  const search = new URLSearchParams()

  search.set("cid", routeCtx.value.cid || 0)
  search.set("sid", routeCtx.value.sid || 0)
  search.set("gid", routeCtx.value.gid || 0)
  search.set("lp_id", props.lp.iid)

  return `/resources/lp/${routeCtx.value.node}/advanced-access?${search.toString()}`
})

const onToggleVisibility = async () => {
  if (!props.csrfToken || isLpSubscriptionMode.value) {
    return
  }

  try {
    await lpService.toggleVisibility(
      props.lp.iid,
      {
        cid: routeCtx.value.cid || 0,
        sid: routeCtx.value.sid || 0,
        gid: routeCtx.value.gid || 0,
      },
      {
        visible: !isLpVisible.value,
        csrfToken: props.csrfToken,
      },
    )
    emit("visibility-changed")
  } catch (error) {
    showErrorNotification(error)
  }
}

const visibilityAction = computed(() => {
  if (isLpSubscriptionMode.value) {
    return {
      label: t("Learning path only visible to selected learners"),
      icon: "eye-on",
      disabled: true,
    }
  }

  return {
    label: isLpVisible.value ? t("Hide") : t("Show"),
    icon: isLpVisible.value ? "eye-on" : "eye-off",
    disabled: !props.csrfToken,
  }
})

const onCopy = () => {
  const label = (props.lp.title || "").trim() || t("Learning path")

  requireConfirmation({
    message: `${t("Are you sure to copy")} ${label}?`,
    accept: () => onManage("copy"),
  })
}

const onDelete = () => {
  const label = (props.lp.title || "").trim() || t("Learning path")

  requireConfirmation({
    message: `${t("Are you sure to delete")} ${label}?`,
    accept: () => onManage("delete"),
  })
}

const dateText = computed(() => {
  const v = props.buildDates(props.lp)

  return typeof v === "string" ? v.trim() : ""
})

const progressBgClass = computed(() => (props.ringValue(props.lp.progress) === 100 ? "bg-success" : "bg-support-5"))

const progressTextClass = computed(() =>
  props.ringValue(props.lp.progress) === 100 ? "text-success" : "text-support-5",
)
</script>

<template>
  <div
    class="relative rounded-2xl border border-gray-25 bg-white px-2 sm:px-4 pt-3 pb-4 min-h-[220px] flex flex-col"
    :data-lp-id="lp.iid"
    :data-lp-cstudio="isCStudioLearningPath ? '1' : '0'"
  >
    <button
      v-if="canReorder"
      :aria-label="t('Drag to reorder')"
      :title="t('Drag to reorder')"
      class="drag-handle absolute left-0 sm:left-3 top-3 w-8 h-8 grid place-content-center rounded-lg text-gray-50 hover:text-gray-90 hover:bg-gray-15 cursor-move"
      type="button"
    >
      <svg
        aria-hidden
        fill="currentColor"
        height="14"
        viewBox="0 0 14 14"
        width="14"
      >
        <circle
          cx="4"
          cy="3"
          r="1.2"
        />
        <circle
          cx="4"
          cy="7"
          r="1.2"
        />
        <circle
          cx="4"
          cy="11"
          r="1.2"
        />
        <circle
          cx="10"
          cy="3"
          r="1.2"
        />
        <circle
          cx="10"
          cy="7"
          r="1.2"
        />
        <circle
          cx="10"
          cy="11"
          r="1.2"
        />
      </svg>
    </button>

    <div class="mt-2 grid grid-cols-[80px_1fr] gap-3 items-start md:pr-10 sm:ml-8 ml-5 mr-2 md:mr-0">
      <div class="w-20 h-20 rounded-xl overflow-hidden ring-1 ring-gray-25 bg-gray-15 shrink-0 ml-2 sm:ml-0">
        <img
          v-if="lp.coverUrl"
          :src="lp.coverUrl"
          alt=""
          class="w-full h-full object-cover"
        />
        <div
          v-else
          class="w-full h-full grid place-content-center text-gray-40"
        >
          <svg
            class="opacity-70"
            fill="none"
            height="26"
            stroke="currentColor"
            viewBox="0 0 24 24"
            width="26"
          >
            <rect
              height="18"
              rx="3"
              stroke-width="1.5"
              width="18"
              x="3"
              y="3"
            />
            <path
              d="M3 16l4-4 3 3 5-5 6 6"
              stroke-width="1.5"
            />
            <circle
              cx="9"
              cy="8"
              r="1.3"
              stroke-width="1.2"
            />
          </svg>
        </div>
      </div>

      <div class="min-w-0 flex ml-2 md:ml-0">
        <div class="flex-1">
          <BaseAppLink
            :title="t('Open')"
            :url="openUrl"
            class="lp-panel__title"
          >
            {{ lp.title || t("Learning path title here") }}
          </BaseAppLink>
          <div
            v-if="lp.prerequisiteName"
            class="mt-1 text-caption text-support-5 flex items-center gap-1.5"
          >
            <svg
              aria-hidden
              class="w-4 h-4"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <circle
                cx="12"
                cy="12"
                r="3"
              />
            </svg>
            <span class="font-medium">{{ t("Prerequisites") }}</span>
            <span class="text-support-5">{{ lp.prerequisiteName }}</span>
          </div>
        </div>
        <div class="relative w-8 h-8 block md:hidden">
          <BaseDropdownMenu
            v-if="canEdit"
            :dropdown-id="`card-${lp.iid}`"
            class="absolute"
          >
            <template #button>
              <span
                :aria-label="t('More')"
                :title="t('More')"
                class="w-8 h-8 grid place-content-center rounded-lg border border-gray-25 hover:bg-gray-15 cursor-pointer"
              >
                <i
                  aria-hidden
                  class="mdi mdi-dots-vertical text-lg"
                ></i>
              </span>
            </template>
            <template #menu>
              <div
                class="absolute right-0 min-w-[18rem] bg-white border border-gray-25 rounded-xl shadow-xl p-2 z-40 mb-2"
                style="bottom: calc(-100% + 2.5rem)"
              >
                <button
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="publishAction.command"
                >
                  {{ publishAction.label }}
                </button>
                <button
                  v-if="!isCStudioLearningPath"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="attemptModeAction.command"
                >
                  {{ attemptModeAction.label }}
                </button>
                <div class="my-2 rounded-lg bg-gray-15 px-3 py-2 text-left">
                  <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ viewModeAction.label }}
                  </div>
                  <div class="mt-1 text-body-2 font-semibold text-gray-90">
                    {{ viewModeAction.value }}
                  </div>
                </div>
                <div class="my-2 border-t border-gray-25"></div>
                <button
                  v-if="securityStore.isAdmin && !isCStudioLearningPath"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="debugAction.command"
                >
                  {{ debugAction.label }}
                </button>
                <button
                  v-if="canSeriousGame"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="seriousGameAction.command"
                >
                  {{ seriousGameAction.label }}
                </button>
                <button
                  v-if="canAutoLaunch"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="autoLaunchAction.command"
                >
                  {{ autoLaunchAction.label }}
                </button>
                <BaseAppLink
                  v-if="isLpSubscriptionMode"
                  :url="advancedAccessUrl"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 md:hidden"
                >
                  {{ t("Subscribe users to learning path") }}
                </BaseAppLink>
                <BaseAppLink
                  v-if="canDownloadScormPackage"
                  :url="exportScormUrl"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 md:hidden"
                >
                  {{ t("Export as SCORM") }}
                </BaseAppLink>
                <button
                  v-if="canExportChamilo"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 md:hidden"
                  type="button"
                  @click="emit('export-chamilo', lp)"
                >
                  {{ t("Export to Chamilo format") }}
                </button>
                <button
                  v-if="canUpdateScorm && !isCStudioLearningPath"
                  :disabled="!manageableInContext"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50 md:hidden"
                  type="button"
                  @click="router.push(updateScormRoute)"
                >
                  {{ t("Update SCORM") }}
                </button>
                <button
                  v-if="canExportPdf && !isCStudioLearningPath"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 md:hidden"
                  type="button"
                  @click="emit('export-pdf', lp)"
                >
                  {{ t("Export to PDF") }}
                </button>
                <router-link
                  v-if="manageableInContext"
                  :to="settingsRoute"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 md:hidden"
                >
                  {{ t("Settings") }}
                </router-link>
                <button
                  v-if="canCopyLearningPath && !isCStudioLearningPath"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="onCopy"
                >
                  {{ t("Copy") }}
                </button>
                <div class="my-2 border-t border-gray-25"></div>
                <button
                  :disabled="!manageableInContext"
                  class="w-full whitespace-nowrap rounded px-3 py-2 text-left font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
                  type="button"
                  @click="onDelete"
                >
                  {{ t("Delete") }}
                </button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>

      <p class="col-span-2 md:mt-3 text-caption text-gray-50 ml-2 md:ml-0">
        {{ dateText }}
      </p>
    </div>

    <div class="mt-auto pt-3 flex items-center ml-5 pl-2 sm:ml-8 sm:pl-0 mr-2 md:mr-0">
      <div class="flex items-center gap-2">
        <div class="relative w-10 h-10">
          <svg
            class="w-10 h-10"
            viewBox="0 0 37 37"
          >
            <circle
              class="text-gray-25"
              cx="18.5"
              cy="19"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-width="3.5"
            />
            <circle
              :class="progressTextClass"
              :stroke-dasharray="ringDash(lp.progress)"
              cx="21"
              cy="18.5"
              fill="none"
              r="16"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-width="3.5"
              transform="rotate(-90 20 20)"
            />
          </svg>
          <span
            :class="progressBgClass"
            aria-hidden
            class="absolute -top-0.5 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full ring-2 ring-white"
          />
          <div class="absolute inset-0 grid place-content-center text-tiny font-semibold text-gray-90">
            {{ ringValue(lp.progress) }}%
          </div>
        </div>
        <span class="text-caption text-gray-50">
          {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
        </span>
      </div>

      <div
        v-if="canEdit"
        class="ml-auto flex items-center gap-2"
      >
        <BaseButton
          :disabled="!manageableInContext"
          :label="t('Edit learnpath')"
          :route="buildRoute"
          :to-url="buildUrl"
          icon="edit"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :disabled="!manageableInContext"
          :label="t('Reports')"
          :route="reportingRoute"
          icon="tracking"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :disabled="visibilityAction.disabled"
          :label="visibilityAction.label"
          :icon="visibilityAction.icon"
          only-icon
          size="small"
          type="tertiary-alternative-text"
          @click="onToggleVisibility"
        />

        <BaseButton
          v-if="isLpSubscriptionMode"
          :label="t('Subscribe users to learning path')"
          :to-url="advancedAccessUrl"
          icon="join-group"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          :disabled="!manageableInContext"
          :label="t('Settings')"
          :route="settingsRoute"
          icon="cog"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          v-if="canCopyLearningPath && !isCStudioLearningPath"
          :disabled="!manageableInContext || !csrfToken"
          :label="t('Copy')"
          icon="copy"
          only-icon
          size="small"
          type="tertiary-alternative-text"
          @click="onCopy"
        />

        <BaseButton
          v-if="canDownloadScormPackage"
          :label="t('Export as SCORM')"
          :to-url="exportScormUrl"
          icon="zip-pack"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          v-if="canExportChamilo"
          :label="t('Export to Chamilo format')"
          icon="download"
          only-icon
          size="small"
          type="tertiary-alternative-text"
          @click="emit('export-chamilo', lp)"
        />

        <BaseButton
          v-if="canUpdateScorm && !isCStudioLearningPath"
          :disabled="!manageableInContext"
          :label="t('Update SCORM')"
          :route="updateScormRoute"
          icon="upload"
          only-icon
          size="small"
          type="tertiary-alternative-text"
        />

        <BaseButton
          v-if="canExportPdf && !isCStudioLearningPath"
          :label="t('Export to PDF')"
          icon="file-pdf"
          only-icon
          size="small"
          type="tertiary-alternative-text"
          @click="emit('export-pdf', lp)"
        />

        <BaseButton
          v-if="canAutoLaunch"
          :disabled="autoLaunchAction.disabled"
          :label="autoLaunchAction.label"
          :icon="autoLaunchAction.icon"
          only-icon
          size="small"
          type="tertiary-alternative-text"
          @click="autoLaunchAction.command"
        />
        <div class="relative hidden md:flex shrink-0 items-center ml-4">
          <BaseDropdownMenu
            v-if="canEdit"
            :dropdown-id="`card-${lp.iid}`"
          >
            <template #button>
              <BaseButton
                :label="t('More actions')"
                icon="dots-vertical"
                only-icon
                size="small"
                type="tertiary-alternative-text"
              />
            </template>
            <template #menu>
              <div
                class="absolute right-0 min-w-[18rem] bg-white border border-gray-25 rounded-xl shadow-xl p-2 z-40 mb-2"
                style="bottom: calc(-100% + 2.5rem)"
              >
                <button
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="publishAction.command"
                >
                  {{ publishAction.label }}
                </button>
                <button
                  v-if="!isCStudioLearningPath"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="attemptModeAction.command"
                >
                  {{ attemptModeAction.label }}
                </button>
                <div class="my-2 rounded-lg bg-gray-15 px-3 py-2 text-left">
                  <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                    {{ viewModeAction.label }}
                  </div>
                  <div class="mt-1 text-body-2 font-semibold text-gray-90">
                    {{ viewModeAction.value }}
                  </div>
                </div>
                <div class="my-2 border-t border-gray-25"></div>
                <button
                  v-if="securityStore.isAdmin && !isCStudioLearningPath"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="debugAction.command"
                >
                  {{ debugAction.label }}
                </button>
                <button
                  v-if="canSeriousGame"
                  :disabled="!manageableInContext || !csrfToken"
                  class="block w-full whitespace-nowrap rounded px-3 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                  type="button"
                  @click="seriousGameAction.command"
                >
                  {{ seriousGameAction.label }}
                </button>
                <div class="my-2 border-t border-gray-25"></div>
                <button
                  :disabled="!manageableInContext"
                  class="w-full whitespace-nowrap rounded px-3 py-2 text-left font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
                  type="button"
                  @click="onDelete"
                >
                  {{ t("Delete") }}
                </button>
              </div>
            </template>
          </BaseDropdownMenu>
        </div>
      </div>

      <div
        v-else
        class="ml-auto flex items-center gap-2 shrink-0"
      >
        <div
          :aria-label="t('Actions')"
          class="flex items-center gap-2"
          role="toolbar"
        >
          <BaseButton
            v-if="canDownloadScormPackage"
            :label="t('Export as SCORM')"
            :to-url="exportScormUrl"
            icon="zip-pack"
            only-icon
            size="small"
            type="tertiary-alternative-text"
          />

          <BaseButton
            v-if="canExportPdf && !isCStudioLearningPath"
            :label="t('Export to PDF')"
            icon="file-pdf"
            only-icon
            size="small"
            type="tertiary-alternative-text"
            @click="emit('export-pdf', lp)"
          />

          <BaseButton
            :label="t('Open')"
            :to-url="openUrl"
            icon="link-external"
            only-icon
            size="small"
            type="tertiary-alternative-text"
          />
        </div>
      </div>
    </div>
  </div>
</template>
