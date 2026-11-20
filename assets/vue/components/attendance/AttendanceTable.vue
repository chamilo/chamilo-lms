<template>
  <BaseTable
    :aria-busy="loading ? 'true' : undefined"
    :is-loading="loading"
    :row-class="getRowClass"
    :style="loading ? { minHeight: `${TABLE_LOADING_HEIGHT}px` } : undefined"
    :text-for-empty="loading ? '' : t('No data available')"
    :total-items="totalRecords"
    :values="attendances"
    data-key="id"
    @page="onPageChange"
  >
    <!-- Name -->
    <Column
      field="title"
      :header="t('Name')"
      sortable
    >
      <template #body="slotProps">
        <BaseAppLink
          :to="{
            name: 'AttendanceSheetList',
            params: {
              node: getNodeId(slotProps.data.resourceNode),
              id: slotProps.data.id,
            },
            query: {
              cid: route.query.cid,
              sid: route.query.sid,
              gid: route.query.gid,
            },
          }"
          class="text-primary hover:underline"
        >
          {{ slotProps.data.title }}
        </BaseAppLink>
      </template>
    </Column>

    <!-- Description -->
    <Column
      field="description"
      :header="t('Description')"
      sortable
    >
      <template #body="slotProps">
        <div
          dir="auto"
          v-html="sanitizeHtml(slotProps.data.description)"
        ></div>
      </template>
    </Column>

    <!-- # attended -->
    <Column
      field="doneCalendars"
      :header="t('# attended')"
      sortable
    >
      <template #body="slotProps">
        <div class="text-center">{{ slotProps.data.doneCalendars ?? 0 }}</div>
      </template>
    </Column>

    <!-- Actions -->
    <Column
      v-if="showActions"
      :header="t('Actions')"
    >
      <template #body="slotProps">
        <div class="flex justify-center gap-2">
          <BaseButton
            :label="t('Edit')"
            icon="pencil"
            only-icon
            size="small"
            type="secondary-text"
            @click="onEdit(slotProps.data)"
          />
          <BaseButton
            :icon="getVisibilityIcon(slotProps.data)"
            :label="getVisibilityTooltip(slotProps.data)"
            only-icon
            size="small"
            type="primary-text"
            @click="onView(slotProps.data)"
          />
          <BaseButton
            :label="t('Delete')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="onDelete(slotProps.data)"
          />
        </div>
      </template>
    </Column>
  </BaseTable>
</template>
<script setup>
import { useRoute } from "vue-router"
import { computed, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTable from "../basecomponents/BaseTable.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import DOMPurify from "dompurify"
import { useLocale } from "../../composables/locale"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

// Measured at 1440 px with the local portal: header, one row and the paginator.
const TABLE_LOADING_HEIGHT = 155

const route = useRoute()

const props = defineProps({
  attendances: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  totalRecords: { type: Number, default: 0 },
  readonly: { type: Boolean, default: false },
})

const emit = defineEmits(["edit", "view", "delete", "pageChange"])

// The parent (AttendanceList) already sets `readonly` from the edit capability,
// so it is the single source of truth for whether row actions are shown.
const showActions = computed(() => !props.readonly)

const onEdit = (attendance) => emit("edit", attendance)
const onView = (attendance) => emit("view", attendance)
const onDelete = (attendance) => emit("delete", attendance)
const onPageChange = (event) => emit("pageChange", event)

// Sanitize rich HTML content before rendering it with v-html.
const sanitizeHtml = (html, options = {}) => {
  return DOMPurify.sanitize(html ?? "", {
    ADD_ATTR: ["target", "rel"],
    ...options,
  })
}

/**
 * @param {Object} attendance
 * @returns {boolean}
 */
const isVisible = (attendance) => RESOURCE_LINK_PUBLISHED === attendance.resourceLinkListFromEntity?.[0]?.visibility

/**
 * @param {Object} attendance
 * @returns {string}
 */
const getVisibilityIcon = (attendance) => (isVisible(attendance) ? "eye-on" : "eye-off")

/**
 * @param {Object} attendance
 * @returns {string}
 */
const getVisibilityTooltip = (attendance) => (isVisible(attendance) ? t("Visible") : t("Hidden"))

/**
 * Greys out a hidden attendance in the read-only table. Only a teacher in the student
 * view gets one: the API leaves them out for the users who cannot edit them.
 *
 * @param {Object} attendance
 * @returns {string}
 */
const getRowClass = (attendance) => (props.readonly && !isVisible(attendance) ? "opacity-50" : "")

const { t } = useI18n()
const { appLocale } = useLocale()
const localePrefix = ref(getLocalePrefix(appLocale.value))

function getLocalePrefix(locale) {
  const defaultLang = "en"
  return typeof locale === "string" ? locale.split("_")[0] : defaultLang
}

function getNodeId(resourceNode) {
  if (!resourceNode || !resourceNode["@id"]) return 0
  const parts = resourceNode["@id"].split("/")
  return parseInt(parts[parts.length - 1])
}
</script>
