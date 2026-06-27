<script setup>
import { computed, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseMenu from "../basecomponents/BaseMenu.vue"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import lpService from "../../services/lpService"
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
  buildDates: { type: Function, required: true },
  legacyContext: { type: Object, required: true },
  csrfToken: { type: String, default: "" },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
})

const emit = defineEmits(["export-chamilo", "export-pdf", "management-changed", "visibility-changed"])

// Only SCORM packages (type = 2 in Chamilo legacy)
const canUpdateScorm = computed(() => {
  if (!props.canEdit) {
    return false
  }

  const v = props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? props.lp?.lpTypeId ?? props.lp?.lp_type_id ?? 0

  return Number(v) === 2
})

const canDownloadScormPackage = computed(() => {
  const type = Number(
    props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? props.lp?.lpTypeId ?? props.lp?.lp_type_id ?? 0,
  )

  return props.canExportScorm && type === 2
})

const canCopyLearningPath = computed(() => {
  const type = Number(props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? 0)

  return props.canCopy && !isCStudioLearningPath.value && type !== 3 && (type !== 2 || props.canCopyScorm)
})

const openUrl = computed(() =>
  lpService.buildRuntimeUrl(props.lp.iid, {
    ...props.legacyContext,
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

  if (props.legacyContext.cid) {
    search.set("cid", props.legacyContext.cid)
  }

  if (props.legacyContext.sid) {
    search.set("sid", props.legacyContext.sid)
  }

  if (props.legacyContext.gid) {
    search.set("gid", props.legacyContext.gid)
  }

  return `/plugin/CStudio/oel_tools_teachdoc_link.php?${search.toString()}`
})

const editLearningPathRoute = computed(() =>
  isCStudioLearningPath.value ? null : { name: "LpBuilder", params: { lpId: props.lp.iid }, query: route.query },
)

const editLearningPathUrl = computed(() => (isCStudioLearningPath.value ? cstudioEditorUrl.value : null))

const exportScormUrl = computed(() =>
  lpService.buildScormPackageDownloadUrl(props.lp.iid, {
    cid: props.legacyContext.cid || 0,
    sid: props.legacyContext.sid || 0,
    gid: props.legacyContext.gid || 0,
    node: props.legacyContext.node || undefined,
  }),
)


const updateScormRoute = computed(() => ({
  name: "LpScormUpdate",
  params: { lpId: props.lp.iid },
  query: route.query,
}))

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

const managementParams = computed(() => ({
  cid: props.legacyContext.cid || 0,
  sid: props.legacyContext.sid || 0,
  gid: props.legacyContext.gid || 0,
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
  icon: props.lp?.publishedOnCourseHome ? "checkbox-multiple-blank" : "checkbox-multiple-blank-outline",
  disabled: !props.csrfToken || !manageableInContext.value,
  command: () => onManage("toggle_publish"),
}))

const attemptModeAction = computed(() => {
  const seriousGame = Boolean(props.lp?.seriousgameMode)
  const preventReinit = Boolean(props.lp?.preventReinit)
  const isMultiple = !seriousGame && !preventReinit

  return {
    label: isMultiple ? t("Prevent multiple attempts") : t("Allow multiple attempts"),
    icon: seriousGame ? "sync-circle" : "sync",
    disabled: !props.csrfToken || !manageableInContext.value,
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
    disabled: true,
  }
})

const debugAction = computed(() => ({
  label: props.lp?.debug ? t("Hide debug") : t("Show debug"),
  icon: props.lp?.debug ? "bug-check" : "bug-outline",
  disabled: !props.csrfToken || !manageableInContext.value,
  command: () => onManage("switch_scorm_debug"),
}))

const seriousGameAction = computed(() => ({
  label: props.lp?.seriousgameMode ? t("Disable gamification mode") : t("Enable gamification mode"),
  icon: "trophy",
  disabled: !props.csrfToken || !manageableInContext.value,
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

  search.set("cid", props.legacyContext.cid || 0)
  search.set("sid", props.legacyContext.sid || 0)
  search.set("gid", props.legacyContext.gid || 0)
  search.set("lp_id", props.lp.iid)

  return `/resources/lp/${props.legacyContext.node}/advanced-access?${search.toString()}`
})

const onToggleVisibility = async () => {
  if (!props.csrfToken || isLpSubscriptionMode.value) {
    return
  }

  try {
    await lpService.toggleVisibility(
      props.lp.iid,
      {
        cid: props.legacyContext.cid || 0,
        sid: props.legacyContext.sid || 0,
        gid: props.legacyContext.gid || 0,
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
      command: null,
    }
  }

  return {
    label: isLpVisible.value ? t("Hide") : t("Show"),
    icon: isLpVisible.value ? "eye-on" : "eye-off",
    disabled: !props.csrfToken,
    command: onToggleVisibility,
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

const buttonActions = computed(() =>
  [
    {
      label: t("Edit learnpath"),
      icon: "edit",
      route: editLearningPathRoute.value,
      toUrl: editLearningPathUrl.value,
      disabled: !manageableInContext.value,
      visible: true,
    },
    {
      label: t("Reports"),
      icon: "tracking",
      route: { name: "LpReporting", params: { lpId: props.lp.iid }, query: route.query },
      disabled: !manageableInContext.value,
      visible: true,
    },
    {
      label: visibilityAction.value.label,
      icon: visibilityAction.value.icon,
      command: visibilityAction.value.command,
      disabled: visibilityAction.value.disabled,
      visible: true,
    },
    {
      label: t("Subscribe users to learning path"),
      icon: "join-group",
      toUrl: advancedAccessUrl.value,
      disabled: !manageableInContext.value,
      visible: isLpSubscriptionMode.value,
    },
    {
      label: t("Settings"),
      icon: "cog",
      route: { name: "LpSettings", params: { lpId: props.lp.iid }, query: route.query },
      disabled: !manageableInContext.value,
      visible: true,
    },
    {
      label: t("Copy"),
      icon: "copy",
      command: onCopy,
      disabled: !manageableInContext.value || !props.csrfToken,
      visible: canCopyLearningPath.value,
    },
    {
      label: t("Export as SCORM"),
      icon: "zip-pack",
      toUrl: exportScormUrl.value,
      visible: canDownloadScormPackage.value,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Export to Chamilo format"),
      icon: "download",
      command: () => emit("export-chamilo", props.lp),
      visible: props.canExportChamilo,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Update SCORM"),
      visible: canUpdateScorm.value && !isCStudioLearningPath.value,
      icon: "upload",
      command: () => router.push(updateScormRoute.value),
      disabled: !manageableInContext.value,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Export to PDF"),
      icon: "file-pdf",
      visible: props.canExportPdf && !isCStudioLearningPath.value,
      command: () => emit("export-pdf", props.lp),
      styleClass: "hidden md:flex",
    },
    {
      ...autoLaunchAction.value,
      visible: props.canAutoLaunch,
      styleClass: "hidden md:flex",
    },
  ].filter((action) => action.visible),
)

const mItemActionsMobile = ref()
const rowMenuOpen = ref(false)

const viewModeMobileAction = computed(() => ({
  label: `${viewModeAction.value.label}: ${viewModeAction.value.value}`,
  disabled: true,
}))

const itemActionsMobile = computed(() =>
  [
    publishAction.value,
    ...(isCStudioLearningPath.value ? [] : [attemptModeAction.value]),
    viewModeMobileAction.value,
    ...(securityStore.isAdmin && !isCStudioLearningPath.value ? [debugAction.value] : []),
    ...(props.canSeriousGame ? [seriousGameAction.value] : []),
    ...(props.canAutoLaunch ? [autoLaunchAction.value] : []),
    {
      label: t("Subscribe users to learning path"),
      url: advancedAccessUrl.value,
      disabled: !manageableInContext.value,
      visible: isLpSubscriptionMode.value,
    },
    { label: t("Export as SCORM"), url: exportScormUrl.value, visible: canDownloadScormPackage.value },
    { label: t("Export to Chamilo format"), command: () => emit("export-chamilo", props.lp), visible: props.canExportChamilo },
    {
      label: t("Update SCORM"),
      visible: canUpdateScorm.value && !isCStudioLearningPath.value,
      disabled: !manageableInContext.value,
      command: () => router.push(updateScormRoute.value),
    },
    {
      label: t("Export to PDF"),
      command: () => emit("export-pdf", props.lp),
      visible: props.canExportPdf && !isCStudioLearningPath.value,
    },
    {
      label: t("Settings"),
      disabled: !manageableInContext.value,
      command: () =>
        router.push({ name: "LpSettings", params: { lpId: props.lp.iid }, query: route.query }),
    },
    {
      label: t("Copy"),
      command: onCopy,
      disabled: !manageableInContext.value || !props.csrfToken,
      visible: canCopyLearningPath.value,
    },
    { label: t("Delete"), command: onDelete, disabled: !manageableInContext.value },
  ].filter((item) => item.visible !== false),
)

</script>

<template>
  <div
    :class="['lp-panel', { 'lp-panel--menu-open': rowMenuOpen, 'lp-panel--cstudio': isCStudioLearningPath }]"
    :data-lp-id="lp.iid"
    :data-lp-cstudio="isCStudioLearningPath ? '1' : '0'"
  >
    <article class="lp-panel__container">
      <button
        v-if="canReorder"
        :aria-label="t('Drag to reorder')"
        :title="t('Drag to reorder')"
        class="lp-panel__drag-handler drag-handle"
      >
        <svg
          aria-hidden="true"
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

      <div class="lp-panel__body">
        <div
          class="lp-panel__cover overflow-hidden rounded-xl"
          :class="{ 'ml-4': !canEdit }"
        >
          <img
            v-if="lp.coverUrl"
            :src="lp.coverUrl"
            alt=""
            class="lp-panel__cover-image block h-full w-full max-w-full object-cover"
          />
          <div
            v-else
            class="lp-panel__cover-image flex h-full w-full items-center justify-center"
          >
            <svg
              class="opacity-70"
              fill="none"
              height="30"
              stroke="currentColor"
              viewBox="0 0 24 24"
              width="30"
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

        <div class="lp-panel__info">
          <BaseAppLink
            :title="t('Open')"
            :url="openUrl"
            class="lp-panel__title"
          >
            {{ lp.title || t("Learning path title here") }}
          </BaseAppLink>
          <p
            v-if="dateText"
            class="lp-panel__dates lp-panel__dates--desktop"
          >
            {{ dateText }}
          </p>
          <div
            v-if="lp.prerequisiteName"
            class="lp-panel__prerequisite lp-panel__prerequisite--desktop"
          >
            <span class="lp-panel__prerequisite-label">{{ t("Prerequisites") }}</span>
            <span class="lp-panel__prerequisite-value">{{ lp.prerequisiteName }}</span>
          </div>
        </div>

        <BaseButton
          v-if="canEdit"
          :label="t('More actions')"
          class="lp-panel__mobile-dropdown"
          icon="dots-vertical"
          only-icon
          popup-identifier="lp-menu-mobile"
          size="small"
          type="tertiary-alternative-text"
          @click="mItemActionsMobile.toggle($event)"
        />
        <BaseMenu
          v-if="canEdit"
          id="lp-menu-mobile"
          ref="mItemActionsMobile"
          :model="itemActionsMobile"
        />
      </div>
      <p
        v-if="dateText"
        class="lp-panel__dates lp-panel__dates--mobile"
      >
        {{ dateText }}
      </p>
      <div
        v-if="lp.prerequisiteName"
        class="lp-panel__prerequisite"
      >
        <span class="lp-panel__prerequisite-label">{{ t("Prerequisites") }}</span>
        <span class="lp-panel__prerequisite-value">{{ lp.prerequisiteName }}</span>
      </div>

      <template v-if="canEdit">
        <div class="lp-panel__actions">
          <div class="lp-panel__action-buttons flex items-center gap-2">
            <BaseButton
              v-for="(buttonAction, i) in buttonActions"
              :key="i"
              :class="buttonAction.styleClass"
              :icon="buttonAction.icon"
              :disabled="buttonAction.disabled"
              :label="buttonAction.label"
              :route="buttonAction.route"
              :to-url="buttonAction.toUrl"
              only-icon
              size="small"
              type="tertiary-alternative-text"
              @click="buttonAction.command"
            />

            <div class="relative hidden md:flex shrink-0 items-center ml-4">
              <BaseDropdownMenu
                :dropdown-id="`lp-row-menu-${lp.iid}`"
                @close="rowMenuOpen = false"
                @open="rowMenuOpen = true"
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
                  <div class="min-w-[18rem] overflow-hidden rounded-xl border border-gray-25 bg-white py-1 text-body-2 shadow-xl">
                    <button
                      :disabled="publishAction.disabled"
                      class="block w-full whitespace-nowrap px-4 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                      type="button"
                      @click="publishAction.command"
                    >
                      {{ publishAction.label }}
                    </button>
                    <button
                      v-if="!isCStudioLearningPath"
                      :disabled="attemptModeAction.disabled"
                      class="block w-full whitespace-nowrap px-4 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                      type="button"
                      @click="attemptModeAction.command"
                    >
                      {{ attemptModeAction.label }}
                    </button>

                    <div class="my-1 border-t border-gray-25"></div>

                    <div class="mx-2 my-1 rounded-lg bg-gray-15 px-3 py-2 text-left">
                      <div class="text-caption font-semibold uppercase tracking-wide text-gray-50">
                        {{ viewModeAction.label }}
                      </div>
                      <div class="mt-0.5 text-body-2 font-semibold text-gray-90">
                        {{ viewModeAction.value }}
                      </div>
                    </div>

                    <div class="my-1 border-t border-gray-25"></div>

                    <button
                      v-if="securityStore.isAdmin && !isCStudioLearningPath"
                      :disabled="debugAction.disabled"
                      class="block w-full whitespace-nowrap px-4 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                      type="button"
                      @click="debugAction.command"
                    >
                      {{ debugAction.label }}
                    </button>
                    <button
                      v-if="canSeriousGame"
                      :disabled="seriousGameAction.disabled"
                      class="block w-full whitespace-nowrap px-4 py-2 text-left hover:bg-gray-15 disabled:opacity-50"
                      type="button"
                      @click="seriousGameAction.command"
                    >
                      {{ seriousGameAction.label }}
                    </button>

                    <div class="my-1 border-t border-gray-25"></div>

                    <button
                      :disabled="!manageableInContext"
                      class="block w-full whitespace-nowrap px-4 py-2 text-left font-semibold text-danger hover:bg-danger/10 disabled:opacity-50"
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

          <div class="lp-panel__progress">
            <span class="lp-panel__progress-label">
              {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
            </span>
            <div class="lp-panel__progress-ring">
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
                aria-hidden="true"
                class="lp-panel__progress-dot"
              />
              <div class="lp-panel__progress-value">{{ ringValue(lp.progress) }}%</div>
            </div>
          </div>
        </div>
      </template>

      <template v-else>
        <div class="lp-panel__student flex items-center justify-end gap-4">
          <div
            :aria-label="t('Actions')"
            class="lp-panel__student-actions flex items-center gap-2 shrink-0"
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

          <div class="lp-panel__student-progress flex items-center gap-2 shrink-0">
            <span class="lp-panel__progress-label">
              {{ ringValue(lp.progress) === 100 ? t("Completed") : t("Progress") }}
            </span>
            <div class="lp-panel__progress-ring">
              <svg
                class="w-10 h-10"
                viewBox="0 0 40 40"
              >
                <circle
                  class="text-gray-25"
                  cx="20"
                  cy="20"
                  fill="none"
                  r="16"
                  stroke="currentColor"
                  stroke-width="3.5"
                />
                <circle
                  :stroke-dasharray="ringDash(lp.progress)"
                  class="text-support-5"
                  cx="20"
                  cy="20"
                  fill="none"
                  r="16"
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-width="3.5"
                  transform="rotate(-90 20 20)"
                />
              </svg>
              <span
                aria-hidden="true"
                class="lp-panel__progress-dot lp-panel__progress-dot--student"
              />
              <div class="lp-panel__progress-value">{{ ringValue(lp.progress) }}%</div>
            </div>
          </div>
        </div>
      </template>
    </article>
  </div>
</template>
