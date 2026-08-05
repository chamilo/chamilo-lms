<template>
  <div class="flex flex-col pb-4 xl:flex-row xl:items-start xl:justify-between">
    <div class="flex min-w-0 items-start gap-3 pb-2">
      <!-- Drag handle: only visible when the user can edit -->
      <div
        v-if="securityStore.isAuthenticated && canEditItem(link)"
        class="link-drag-handle select-none rounded border border-gray-20 bg-gray-10 px-2 py-1 text-gray-600 hover:text-black"
        :title="t('Drag to reorder')"
      >
        ⠿
      </div>

      <div class="min-w-0 flex-1">
        <h6 class="min-w-0">
          <a
            :href="link.url"
            :target="link.target || '_self'"
            class="inline-flex min-w-0 items-center gap-2"
          >
            <BaseIcon
              icon="link-external"
              size="normal"
            />
            <span class="truncate">{{ link.title }}</span>
          </a>

          <BaseIcon
            v-if="isAllowedToEdit && link.sessionId && Number(link.sessionId) === sidValue"
            :title="t('Session Item')"
            class="ml-2"
            icon="session-star"
            size="normal"
          />

          <BaseIcon
            v-if="isLinkValid.isValid"
            :title="t('Link is valid')"
            class="ml-2 text-green-500"
            icon="check"
            size="normal"
          />
          <BaseIcon
            v-else-if="isLinkValid.isValid === false"
            :title="t('Link is not valid')"
            class="ml-2 text-red-500"
            icon="alert"
            size="normal"
          />
        </h6>

        <div
          v-if="hasDescription"
          class="mt-1"
        >
          <p
            class="whitespace-pre-wrap text-sm text-gray-500"
            :class="{ 'line-clamp-2': !isDescriptionExpanded }"
          >
            {{ normalizedDescription }}
          </p>
          <button
            v-if="isDescriptionExpandable"
            type="button"
            class="mt-1 text-xs font-semibold text-primary hover:underline"
            @click="isDescriptionExpanded = !isDescriptionExpanded"
          >
            {{ isDescriptionExpanded ? t("Show less") : t("Show full description") }}
          </button>
        </div>
      </div>
    </div>

    <!-- Icon-only actions (same style as categories) -->
    <div
      v-if="securityStore.isAuthenticated && canEditItem(link)"
      class="flex items-center gap-2 text-gray-700"
    >
      <BaseButton
        :label="t('Check link')"
        icon="check"
        only-icon
        size="small"
        type="black"
        @click="emit('check', link)"
      />
      <BaseButton
        :label="t('Edit')"
        icon="edit"
        only-icon
        size="small"
        type="black"
        @click="emit('edit', link)"
      />
      <BaseButton
        :icon="isVisible(link.linkVisible) ? 'eye-on' : 'eye-off'"
        :label="t('Toggle visibility')"
        only-icon
        size="small"
        type="black"
        @click="emit('toggle', link)"
      />
      <BaseButton
        :label="t('Delete')"
        icon="delete"
        only-icon
        size="small"
        type="danger"
        @click="emit('delete', link)"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { isVisible } from "./linkVisibility"
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"
import { getCourseContext } from "../../utils/courseContext"

const props = defineProps({
  link: {
    type: Object,
    required: true,
  },
  isLinkValid: {
    type: Object,
    default: () => ({}),
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(["check", "edit", "toggle", "moveUp", "moveDown", "delete"])

const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)
const route = useRoute()
const { t } = useI18n()
const { sid } = getCourseContext()
const sidValue = computed(() => Number(route.query.sid || (sid && typeof sid === "object" ? sid.value : sid) || 0))
const isDescriptionExpanded = ref(false)

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })

const normalizedDescription = computed(() => String(props.link.description || "").trim())
const hasDescription = computed(() => "" !== normalizedDescription.value)
const isDescriptionExpandable = computed(() => {
  return normalizedDescription.value.length > 220 || normalizedDescription.value.split(/\r?\n/).length > 2
})

watch(
  () => props.link?.iid,
  () => {
    isDescriptionExpanded.value = false
  },
)

function canEditItem(item) {
  if (!props.canEdit) {
    return false
  }

  const sessionId = item.sessionId ? Number(item.sessionId) : 0
  const isSessionItem = sessionId > 0 && sessionId === sidValue.value
  const isBaseCourseItem = !sessionId

  return (isSessionItem && isAllowedToEdit.value) || (isBaseCourseItem && !sidValue.value && isCurrentTeacher.value)
}
</script>
