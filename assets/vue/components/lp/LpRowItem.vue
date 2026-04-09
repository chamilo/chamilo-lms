<script setup>
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseDropdownMenu from "../basecomponents/BaseDropdownMenu.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseMenu from "../basecomponents/BaseMenu.vue"
import lpService from "../../services/lpService"

const { t } = useI18n()

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

const emit = defineEmits([
  "open",
  "edit",
  "report",
  "settings",
  "toggle-visible",
  "toggle-publish",
  "delete",
  "export-scorm",
  "export-pdf",
  "toggle-auto-launch",
  "update-scorm",
])

const lpType = computed(() => {
  const v = props.lp?.lpType ?? props.lp?.lp_type ?? props.lp?.type ?? props.lp?.lpTypeId ?? props.lp?.lp_type_id ?? 0

  return Number(v) || 0
})

// Only SCORM packages (type = 2 in Chamilo legacy)
const canUpdateScorm = computed(() => props.canEdit && lpType.value === 2)

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
      command: () => emit("report", props.lp),
      visible: true,
    },
    {
      label: t("Visibility"),
      icon: "visible",
      command: () => emit("toggle-visible", props.lp),
      visible: true,
    },
    {
      label: t("Settings"),
      icon: "cog",
      command: () => emit("settings", props.lp),
      visible: true,
    },
    {
      label: t("Export as SCORM"),
      icon: "zip-pack",
      command: () => emit("export-scorm", props.lp),
      visible: props.canExportScorm,
      styleClass: "hidden md:flex",
    },
    {
      label: t("Update SCORM"),
      visible: canUpdateScorm.value,
      icon: "upload",
      command: () => emit("update-scorm", props.lp),
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
      command: () => emit("toggle-auto-launch", props.lp),
      styleClass: "hidden md:flex",
    },
  ].filter((a) => a.visible),
)

const mItemActions = ref()
const itemActions = [
  {
    label: t("Publish / Hide"),
    command: () => emit("toggle-publish", props.lp),
  },
  {
    label: t("Update SCORM"),
    visible: canUpdateScorm.value,
    command: () => emit("update-scorm", props.lp),
  },
  {
    label: t("Delete"),
    command: () => emit("delete", props.lp),
  },
]

const mItemActionsMobile = ref()
const itemActionsMobile = [
  {
    label: t("Publish / Hide"),
    command: () => emit("toggle-publish", props.lp),
  },
  {
    label: t("Export as SCORM"),
    command: () => emit("export-scorm", props.lp),
    visible: props.canExportScorm,
  },
  {
    label: t("Update SCORM"),
    visible: canUpdateScorm.value,
    command: () => emit("update-scorm", props.lp),
  },
  {
    label: t("Settings"),
    command: () => emit("settings", props.lp),
  },
  {
    label: t("Delete"),
    command: () => emit("delete", props.lp),
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
          <button
            :title="t('Open')"
            class="lp-panel__title"
            @click="emit('open', lp)"
          >
            {{ lp.title || t("Learning path title here") }}
          </button>
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
          icon="dots-vertical"
          only-icon
          popup-identifier="lp-menu-mobile"
          size="small"
          type="tertiary-alternative-text"
          @click="mItemActionsMobile.toggle($event)"
          class="lp-panel__mobile-dropdown"
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
              icon="dots-vertical"
              only-icon
              popup-identifier="lp-menu"
              size="small"
              type="tertiary-alternative-text"
              @click="mItemActions.toggle($event)"
              class="hidden md:flex"
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

            <button
              :title="t('Open')"
              class="lp-panel__student-button"
              @click="emit('open', lp)"
            >
              <i class="mdi mdi-open-in-new text-lg" />
            </button>
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
