<template>
  <div class="course-tool">
    <router-link
      v-if="tool.to"
      :aria-labelledby="`course-tool-${tool.iid}`"
      :to="tool.to"
      class="course-tool__link hover:primary-gradient"
      :class="cardCustomClass"
    >
      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </router-link>
    <a
      v-else
      :aria-labelledby="`course-tool-${tool.iid}`"
      :href="tool.url"
      class="course-tool__link"
      :class="cardCustomClass"
    >
      <span
        :class="tool.tool.icon + ' ' + iconCustomClass"
        aria-hidden="true"
        class="course-tool__icon mdi"
      />
    </a>

    <router-link
      v-if="tool.to"
      :id="`course-tool-${tool.iid}`"
      :class="titleCustomClass"
      :to="tool.to"
      class="course-tool__title"
    >
      {{ tool.tool.titleToShow }}
    </router-link>
    <a
      v-else
      :id="`course-tool-${tool.iid}`"
      v-t="tool.tool.titleToShow"
      :href="tool.url"
      class="course-tool__title"
      :class="titleCustomClass"
    />

    <div class="course-tool__options">
      <button
        v-if="
          isAllowedToEdit &&
          !isSorting &&
          !isCustomizing &&
          (session?.id ? 'true' === getSetting('course.allow_edit_tool_visibility_in_session') : true)
        "
        @click="changeVisibility(tool)"
      >
        <BaseIcon
          v-if="isVisible"
          icon="eye-on"
        />
        <BaseIcon
          v-else
          icon="eye-off"
          class="text-gray-50"
        />
      </button>

      <a
        v-if="securityStore.isCurrentTeacher && isCustomizing"
        href="#"
      >
        <BaseIcon
          icon="edit"
          size="small"
        />
      </a>

      <!-- a
        v-if="securityStore.isCurrentTeacher"
        :href="goToSettingCourseTool(tool)"
      >
        <BaseIcon
          icon="cog"
          size="lg"
        />
      </a -->
    </div>
  </div>
</template>

<script setup>
import { computed, inject } from "vue"
import BaseIcon from "../basecomponents/BaseIcon.vue"
import { useSecurityStore } from "../../store/securityStore"
import { usePlatformConfig } from "../../store/platformConfig"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"

const securityStore = useSecurityStore()
const platformConfigStore = usePlatformConfig()
const cidReqStore = useCidReqStore()

const { session } = storeToRefs(cidReqStore)
const { getSetting } = storeToRefs(platformConfigStore)

const isSorting = inject("isSorting")
const isCustomizing = inject("isCustomizing")

// eslint-disable-next-line no-undef
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
