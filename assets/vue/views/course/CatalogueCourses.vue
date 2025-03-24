<template>
  <DataTable
    v-model:filters="filters"
    :global-filter-fields="['title', 'description', 'category.title', 'courseLanguage']"
    :loading="isLoading"
    :paginator="true"
    :rows="9"
    :value="courses"
    data-key="id"
    edit-mode="cell"
    filter-display="menu"
    responsive-layout="scroll"
    striped-rows
  >
    <template #header>
      <div class="flex gap-4 justify-between items-center">
        <BaseButton
          :label="t('Clear filter results')"
          icon="filter"
          type="primary"
          @click="initFilters()"
        />
        <BaseIconField v-model="filters.global.value" />
      </div>
    </template>
    <template #empty>
      {{ t("No course available") }}
    </template>
    <template #loading>
      {{ t("Loading courses. Please wait.") }}
    </template>
    <Column header="">
      <template #body="{ data }">
        <img
          :alt="data.title"
          :src="data.illustrationUrl"
          class="w-28"
        />
      </template>
    </Column>
    <Column
      :header="t('Title')"
      :sortable="true"
      field="title"
    >
    </Column>
    <Column
      v-if="showCourseDuration"
      :header="t('Course description')"
      :sortable="true"
      field="description"
    >
    </Column>

    <Column
      :header="t('Duration')"
      :sortable="true"
      field="duration"
      class="text-center"
    >
      <template #body="{ data }">
        <div
          v-if="data.duration"
          v-t="{ path: '%d hours', args: [(data.duration / 60 / 60).toFixed(2)] }"
        />
      </template>
    </Column>

    <Column
      :header="t('Teachers')"
      :sortable="true"
      field="teachers"
    >
      <template #body="{ data }">
        <div v-if="data.teachers && data.teachers.length > 0">
          {{ data.teachers.map((teacher) => teacher.user.fullName).join(", ") }}
        </div>
      </template>
    </Column>
    <Column
      :header="t('Language')"
      :sortable="true"
      field="courseLanguage"
    >
      <template #body="{ data }">
        {{ data.courseLanguage }}
      </template>
    </Column>
    <Column
      :header="t('Categories')"
      :sortable="true"
      field="categories"
    >
      <template #body="{ data }">
        <span
          v-for="category in data.categories"
          :key="category.id"
        >
          <em class="pi pi-tag course-category-icon" />
          <span class="course-category">{{ category.title }}</span
          ><br />
        </span>
      </template>
    </Column>
    <Column
      :header="t('Ranking')"
      :sortable="true"
      field="trackCourseRanking.realTotalScore"
      class="text-center"
    >
      <template #body="{ data }">
        <BaseRating
          :model-value="data.trackCourseRanking ? data.trackCourseRanking.realTotalScore : 0"
          @change="onRatingChange($event, data)"
        />
      </template>
    </Column>
    <Column
      field="link"
      :header="t('Actions')"
      class="text-center"
    >
      <template #body="{ data }">
        <BaseAppLink
          v-if="data.visibility === 3"
          :to="{ name: 'CourseHome', params: { id: data.id } }"
        >
          <BaseButton
            :label="t('Go to the course')"
            icon="link-external"
            size="small"
            type="primary-alternative"
          />
        </BaseAppLink>
        <BaseAppLink
          v-else-if="data.visibility === 2 && isUserInCourse(data)"
          :to="{ name: 'CourseHome', params: { id: data.id } }"
        >
          <BaseButton
            :label="t('Go to the course')"
            icon="link-external"
            size="small"
            type="primary-alternative"
          />
        </BaseAppLink>
        <BaseButton
          v-else-if="data.visibility === 2 && !isUserInCourse(data)"
          :label="t('Not subscribed')"
          disabled
          icon="close"
          size="small"
          type="primary-alternative"
        />
        <BaseButton
          v-else
          :label="t('Private course')"
          disabled
          icon="eye"
          size="small"
          type="primary-alternative"
        />
      </template>
    </Column>
    <template #footer>
      {{ t("Total number of courses").concat(": ", courses ? courses.length.toString() : "0") }}
    </template>
  </DataTable>
</template>
<script setup>
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseRating from "../../components/basecomponents/BaseRating.vue"
import BaseIconField from "../../components/basecomponents/BaseIconField.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

import { useI18n } from "vue-i18n"

import { useCatalogueCourseList } from "../../composables/catalogue/catalogueCourseList"

const { t } = useI18n()

const securityStore = useSecurityStore()

const { isLoading, courses, filters, load, initFilters, onRatingChange } = useCatalogueCourseList()

const platformConfigStore = usePlatformConfig()
const showCourseDuration = "true" === platformConfigStore.getSetting("course.show_course_duration")

const isUserInCourse = (course) => {
  return Array.isArray(course.users) && course.users.some((user) => user.user.id === securityStore.user.id)
}

load()
initFilters()
</script>
