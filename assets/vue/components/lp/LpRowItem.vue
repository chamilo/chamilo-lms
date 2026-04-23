<script setup>
import { computed, ref } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseMenu from "../basecomponents/BaseMenu.vue"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"
import lpService from "../../services/lpService"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const route = useRoute()

const props = defineProps({
  lp: { type: Object, required: true },
  canEdit: { type: Boolean, default: false },
  canExportScorm: { type: Boolean, default: false },
  canExportPdf: { type: Boolean, default: false },
  canAutoLaunch: { type: Boolean, default: false },
  buildDates: { type: Function, required: true },
  legacyContext: { type: Object, required: true },
  ringDash: { type: Function, required: true },
  ringValue: { type: Function, required: true },
})

const emit = defineEmits(["export-pdf"])

const lpType = computed(() => {
  const v = props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? props.lp?.lpTypeId ?? props.lp?.lp_type_id ?? 0

  return Number(v) || 0
})

// Only SCORM packages (type = 2 in Chamilo legacy)
const canUpdateScorm = computed(() => props.canEdit && lpType.value === 2)

const isStudentView = computed(() => route.query?.isStudentView === "true")

const openUrl = computed(() =>
  lpService.buildLegacyViewUrl(props.lp.iid, {
    cid: props.legacyContext.cid || 0,
    sid: props.legacyContext.sid || 0,
    isStudentView: isStudentView.value ? "true" : "false",
  }),
)

const exportScormUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "export", { ...props.legacyContext }),
)

const updateScormUrl = computed(() =>
  lpService.buildLegacyActionUrl("update_scorm", {
    ...props.legacyContext,
    params: { lp_id: props.lp.iid },
  }),
)

const togglePublishUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "toggle_publish", {
    ...props.legacyContext,
    params: { new_status: props.lp.published === "v" ? "i" : "v" },
  }),
)

const toggleVisibleUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "toggle_visible", {
    ...props.legacyContext,
    params: { new_status: typeof props.lp.visible !== "undefined" ? (props.lp.visible ? 0 : 1) : 1 },
  }),
)

const toggleAutoLaunchUrl = computed(() =>
  lpService.buildLegacyActionUrl(props.lp.iid, "auto_launch", {
    ...props.legacyContext,
    params: { status: Number(props.lp.autolaunch) === 1 ? 0 : 1 },
  }),
)

const deleteUrl = computed(() => lpService.buildLegacyActionUrl(props.lp.iid, "delete", { ...props.legacyContext }))

const onDelete = () => {
  const label = (props.lp.title || "").trim() || t("Learning path")
  requireConfirmation({
    message: `${t("Are you sure to delete")} ${label}?`,
    accept: () => {
      window.location.href = deleteUrl.value
    },
  })
}

const dateText = computed(() => {
  const v = props.buildDates ? props.buildDates(props.lp) : ""
  return typeof v === "string" ? v.trim() : ""
})

const progressBgClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? "bg-success" : "bg-support-5"
})

const progressTextClass = computed(() => {
  return props.ringValue(props.lp.progress) === 100 ? "text-success" : "text-support-5"
})

const buttonActions = computed(() =>
  [
    {
      label: t("Edit learnpath"),
      icon: "edit",
      toUrl: lpService.buildLegacyActionUrl(props.lp.iid, "add_item", {
        ...props.legacyContext,
        params: { type: "step", isStudentView: "false" },
      }),
      visible: true,
    },
    {
      label: t("Reports"),
      icon: "tracking",
      toUrl: lpService.buildLegacyActionUrl(props.lp.iid, "report", { ...props.legacyContext }),
      visible: true,
    },
    {
      label: t("Visibility"),
      icon: "visible",
      toUrl: toggleVisibleUrl.value,
      visible: true,
    },
    {
      label: t("Settings"),
      icon: "cog",
      toUrl: lpService.buildLegacyActionUrl(props.lp.iid, "edit", { ...props.legacyContext }),
      visible: true,
    },
    {
      label: t("Export as SCORM"),
      icon: "zip-pack",
      toUrl: exportScormUrl.value,
      visible: props.canExportScorm,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Update SCORM"),
      visible: canUpdateScorm.value,
      icon: "upload",
      toUrl: updateScormUrl.value,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Export to PDF"),
      icon: "file-pdf",
      visible: props.canExportPdf,
      command: () => emit("export-pdf", props.lp),
      styleClass: "hidden md:flex",
    },
    {
      label:
        Number(props.lp.autolaunch) === 1
          ? t("Disable learning path auto-launch")
          : t("Enable learning path auto-launch"),
      icon: Number(props.lp.autolaunch) === 1 ? "autolunch" : "autolunch-off",
      visible: props.canAutoLaunch,
      toUrl: toggleAutoLaunchUrl.value,
      styleClass: "hidden md:flex",
    },
  ].filter((a) => a.visible),
)

const mItemActions = ref()
const itemActions = [
  {
    label: t("Publish / Hide"),
    url: togglePublishUrl.value,
  },
  {
    label: t("Update SCORM"),
    visible: canUpdateScorm.value,
    url: updateScormUrl.value,
  },
  {
    label: t("Delete"),
    command: onDelete,
  },
]

const mItemActionsMobile = ref()
const itemActionsMobile = [
  {
    label: t("Publish / Hide"),
    url: togglePublishUrl.value,
  },
  {
    label: t("Export as SCORM"),
    url: exportScormUrl.value,
    visible: props.canExportScorm,
  },
  {
    label: t("Update SCORM"),
    visible: canUpdateScorm.value,
    url: updateScormUrl.value,
  },
  {
    label: t("Settings"),
    url: lpService.buildLegacyActionUrl(props.lp.iid, "edit", props.legacyContext),
  },
  {
    label: t("Delete"),
    command: onDelete,
  },
]
</script>

<template>
  <div class="lp-panel">
    <article class="lp-panel__container">
      <button
        v-if="canEdit"
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
        <div class="lp-panel__cover">
          <img
            v-if="lp.coverUrl"
            :src="lp.coverUrl"
            alt=""
            class="lp-panel__cover-image"
          />
          <div
            v-else
            class="lp-panel__cover-image"
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
          <div class="lp-panel__action-buttons">
            <BaseButton
              v-for="(buttonAction, i) in buttonActions"
              :key="i"
              :class="buttonAction.styleClass"
              :icon="buttonAction.icon"
              :label="buttonAction.label"
              :route="buttonAction.route"
              :to-url="buttonAction.toUrl"
              only-icon
              size="small"
              type="tertiary-alternative-text"
              @click="buttonAction.command"
            />

            <BaseButton
              :label="t('More actions')"
              class="hidden md:flex"
              icon="dots-vertical"
              only-icon
              popup-identifier="lp-menu"
              size="small"
              type="tertiary-alternative-text"
              @click="mItemActions.toggle($event)"
            />
            <BaseMenu
              id="lp-menu"
              ref="mItemActions"
              :model="itemActions"
            />
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
        <div class="lp-panel__student">
          <div
            aria-label="Student actions"
            class="lp-panel__student-actions"
            role="toolbar"
          >
            <button
              v-if="canExportPdf"
              :aria-label="t('Export to PDF')"
              :title="t('Export to PDF')"
              class="lp-panel__student-button"
              @click="emit('export-pdf', lp)"
            >
              <i class="mdi mdi-file-pdf-box text-xl" />
            </button>

            <BaseAppLink
              :title="t('Open')"
              :url="openUrl"
              class="lp-panel__student-button"
            >
              <i class="mdi mdi-open-in-new text-lg" />
            </BaseAppLink>
          </div>

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
      </template>
    </article>
  </div>
</template>
