<template>
  <div
    v-for="(link, index) in item.resourceLinkListFromEntity"
    :key="index"
    class="field space-y-2"
  >
    <div v-if="link.course" :class="{ 'text-right text-body-2': editStatus }">
      <span class="mdi mdi-book"></span>
      <BaseAppLink
        v-if="clickableCourse"
        :to="{
          name: 'CourseHome',
          params: { id: courseId(link.course) }
        }"
      >
        {{ $t("Course") }}: {{ link.course.resourceNode.title }}
      </BaseAppLink>
      <span v-else>{{ $t("Course") }}: {{ link.course.resourceNode.title }}</span>
    </div>

    <div
      v-if="link.session"
      :class="{ 'text-right text-body-2': editStatus }"
    >
      <span class="mdi mdi-book-open" />
      {{ $t("Session") }}: {{ link.session.title }}
    </div>

    <div
      v-if="link.group"
      :class="{ 'text-right text-body-2': editStatus }"
    >
      <span class="mdi mdi-people" />
      {{ $t("Group") }}: {{ link.group.resourceNode.title }}
    </div>

    <div v-if="link.userGroup">
      {{ $t("Class") }}: {{ link.userGroup.resourceNode.title }}
    </div>

    <div v-if="link.user">
      <span class="mdi mdi-account"></span>
      {{ link.user.username }}
    </div>

    <div v-if="showStatus">
      {{ $t("Status") }}: {{ link.visibilityName }}
    </div>

    <div v-if="editStatus">
      <div class="p-float-label">
        <Dropdown
          v-model="link.visibility"
          :input-id="`link-${link.id}-status`"
          :options="visibilityOptions"
          option-label="label"
          option-value="value"
        />
        <label for="`link-${link.id}-status`">{{ $t("Status") }}</label>
      </div>
    </div>
  </div>
</template>

<script setup>
import { RESOURCE_LINK_DRAFT, RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useI18n } from "vue-i18n"
import BaseAppLink from "../basecomponents/BaseAppLink.vue"

const { t } = useI18n()

defineProps({
  item: {
    type: Object,
    required: true,
    default: () => ({
      resourceLinkListFromEntity: [],
    }),
  },
  showStatus: {
    type: Boolean,
    required: false,
    default: true,
  },
  editStatus: {
    type: Boolean,
    required: false,
    default: false,
  },
  clickableCourse: {
    type: Boolean,
    required: false,
    default: false,
  },
})

const courseId = (course) => {
  return course['@id'] ? course['@id'].split('/').pop() : null;
}

const visibilityOptions = [
  { value: RESOURCE_LINK_PUBLISHED, label: t("Published") },
  { value: RESOURCE_LINK_DRAFT, label: t("Draft") },
]
</script>
