<template>
  <div class="course-tool">
    <BaseAppLink
      :aria-labelledby="`course-tool-${tool.iid}`"
      :class="cardCustomClass"
      :to="tool.to"
      :url="tool.url"
      class="course-tool__link hover:primary-gradient"
    >
      <svg
        class="course-tool__shadow"
        fill="none"
        viewBox="0 0 117 105"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          class="fill-current"
          d="M104.167 20.2899C104.167 43.3465 116.11 59.1799 116.11 70.2899C116.11 81.3999 109.723 104.733 58.6133 104.733C7.50333 104.733 0 73.3432 0 61.1232C0 3.89987 104.167 -20.5435 104.167 20.2899Z"
        />
      </svg>

      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </BaseAppLink>

    <BaseAppLink
      :id="`course-tool-${tool.iid}`"
      :class="titleCustomClass"
      :to="tool.to"
      :url="tool.url"
      class="course-tool__title"
    >
      {{ $t(tool.tool.titleToShow) }}
    </BaseAppLink>

    <div class="course-tool__options">
      <button
        v-if="
          isAllowedToEdit &&
          !isSorting &&
          !isCustomizing &&
          (session?.id ? 'true' === getSetting('session.allow_edit_tool_visibility_in_session') : true)
        "
        @click="changeVisibility(tool)"
      >
        <BaseIcon
          v-if="isVisible"
          icon="eye-on"
        />
        <BaseIcon
          v-else
          class="text-gray-50"
          icon="eye-off"
        />
      </button>

      <!-- a
        v-if="securityStore.isCurrentTeacher && isCustomizing"
        href="#"
      >
        <BaseIcon
          icon="edit"
          size="small"
        />
      </a -->
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()

const { session } = storeToRefs(cidReqStore)
const { getSetting } = storeToRefs(platformConfigStore)

const isSorting = inject("isSorting")
const isCustomizing = inject("isCustomizing")

const props = defineProps({
  isAllowedToEdit: {
    type: Boolean,
    required: true,
  },
  tool: {
    type: Object,
    required: true,
  },
  changeVisibility: {
    type: Function,
    required: true,
  },
  goToSettingCourseTool: {
    type: Function,
    required: true,
  },
})

const cardCustomClass = computed(() => {
  if (!isVisible.value) {
    return "bg-primary-bgdisabled hover:bg-gray-50/25 border-primary-borderdisabled shadow-none "
  }
  if (isSorting.value) {
    return "border-2 border-dashed border-primary hover:bg-primary-gradient/10 "
  }
  return "hover:bg-primary-gradient/10 "
})
const iconCustomClass = computed(() => {
  if (!isVisible.value) {
    return "bg-gradient-to-b from-gray-50 to-gray-25 "
  }
  return "bg-primary-bgdisabled "
})
const titleCustomClass = computed(() => {
  if (!isVisible.value) {
    return "text-gray-90 "
  }
  return ""
})
const isVisible = computed(() => props.tool.resourceNode.resourceLinks[0].visibility === 2)
</script>
