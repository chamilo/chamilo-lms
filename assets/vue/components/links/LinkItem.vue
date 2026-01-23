<template>
  <div class="flex flex-col pb-4 xl:flex-row xl:items-start xl:justify-between">
    <div class="flex items-start gap-3 pb-2 min-w-0">
      <!-- Drag handle: only visible when the user can edit -->
      <div
        v-if="securityStore.isAuthenticated && canEdit(link)"
        class="link-drag-handle select-none rounded border border-gray-20 bg-gray-10 px-2 py-1 text-gray-600 hover:text-black"
        :title="t('Drag to reorder')"
      >
        ⠿
      </div>

      <div class="min-w-0">
        <h6 class="min-w-0">
          <a
            :href="link.url"
            class="inline-flex items-center gap-2 min-w-0"
          >
            <BaseIcon
              icon="link-external"
              size="normal"
            />
            <span class="truncate">{{ link.title }}</span>
          </a>

          <BaseIcon
            v-if="isAllowedToEdit && link.sessionId && Number(link.sessionId) === sidValue"
            class="ml-2"
            icon="session-star"
            size="normal"
            title="Session Item"
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
      </div>
    </div>

    <!-- Icon-only actions (same style as categories) -->
    <div
      v-if="securityStore.isAuthenticated && canEdit(link)"
      class="flex items-center gap-3 text-gray-700"
    >
      <BaseIcon
        icon="check"
        size="normal"
        :title="t('Check link')"
        class="hover:text-black"
        @click="emit('check', link)"
      />
      <BaseIcon
        icon="edit"
        size="normal"
        :title="t('Edit')"
        class="hover:text-black"
        @click="emit('edit', link)"
      />
      <BaseIcon
        :icon="isVisible(link.linkVisible) ? 'eye-on' : 'eye-off'"
        size="normal"
        :title="t('Toggle visibility')"
        class="hover:text-black"
        @click="emit('toggle', link)"
      />
      <BaseIcon
        icon="delete"
        size="normal"
        :title="t('Delete')"
        class="hover:text-red-600"
        @click="emit('delete', link)"
      />
    </div>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { isVisible } from "./linkVisibility"
import { useSecurityStore } from "../../store/securityStore"
import { computed, onMounted, ref } from "vue"
import { checkIsAllowedToEdit } from "../../composables/userPermissions"
import { useRoute } from "vue-router"
import { useCidReq } from "../../composables/cidReq"

const securityStore = useSecurityStore()
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher)
const route = useRoute()
const { t } = useI18n()
const { sid } = useCidReq()
const sidValue = computed(() => Number(route.query.sid || (sid && typeof sid === "object" ? sid.value : sid) || 0))

defineProps({
  link: {
    type: Object,
    required: true,
  },
  isLinkValid: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(["check", "edit", "toggle", "moveUp", "moveDown", "delete"])

const isAllowedToEdit = ref(false)

const canEdit = (item) => {
  const sessionId = item.sessionId ? Number(item.sessionId) : 0
  const isSessionItem = sessionId > 0 && sessionId === sidValue.value
  const isBaseCourseItem = !sessionId

  return (isSessionItem && isAllowedToEdit.value) || (isBaseCourseItem && !sidValue.value && isCurrentTeacher.value)
}

onMounted(async () => {
  try {
    isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
  } catch (error) {
    console.error("Error checking edit permission for link item:", error)
    isAllowedToEdit.value = false
  }
})
</script>
