<template>
  <div class="card">
    <DataTable
      v-model:filters="filters"
      :global-filter-fields="['title', 'description', 'category.title', 'courseLanguage']"
      :loading="status"
      :paginator="true"
      :rows="9"
      :value="courses"
      class="p-datatable-courses p-datatable-lg"
      data-key="id"
      edit-mode="cell"
      filter-display="menu"
      responsive-layout="scroll"
      striped-rows
    >
      <template #header>
        <div class="table-header-container">
          <div class="flex justify-content-end">
            <Button
              :label="$t('Clear filter results')"
              class="p-button-outlined mr-2"
              icon="pi pi-filter-slash"
              type="button"
              @click="clearFilter()"
            />
            <span class="p-input-icon-left">
              <i class="pi pi-search" />
              <InputText
                v-model="filters['global'].value"
                :placeholder="$t('Search')"
              />
            </span>
          </div>
        </div>
      </template>
      <template #empty>
        {{ $t("No course available") }}
      </template>
      <template #loading>
        {{ $t("Loading courses. Please wait.") }}
      </template>
      <Column
        header=""
        style="min-width: 5rem"
      >
        <template #body="{ data }">
          <img
            :alt="data.title"
            :src="data.illustrationUrl"
            class="course-image"
          />
        </template>
      </Column>
      <Column
        :header="$t('Title')"
        :sortable="true"
        field="title"
        style="min-width: 8rem; text-align: center"
      >
        <template #body="{ data }">
          {{ data.title }}
        </template>
      </Column>
      <Column
        v-if="showCourseDuration"
        :header="$t('Course description')"
        :sortable="true"
        field="description"
        style="min-width: 8rem; text-align: center"
      >
        <template #body="{ data }">
          {{ data.description }}
        </template>
      </Column>

      <Column
        :header="$t('Duration')"
        :sortable="true"
        field="duration"
        style="min-width: 8rem; text-align: center"
      >
        <template #body="{ data }">
          <div
            v-if="data.duration"
            class="course-duration"
          >
            {{ (data.duration / 60 / 60).toFixed(2) }} hours
          </div>
        </template>
      </Column>

      <Column
        :header="$t('Teachers')"
        :sortable="true"
        field="teachers"
        style="min-width: 10rem; text-align: center"
      >
        <template #body="{ data }">
          <div v-if="data.teachers && data.teachers.length > 0">
            {{ data.teachers.map((teacher) => teacher.user.fullName).join(", ") }}
          </div>
        </template>
      </Column>
      <Column
        :header="$t('Language')"
        :sortable="true"
        field="courseLanguage"
        style="min-width: 5rem; text-align: center"
      >
        <template #body="{ data }">
          {{ data.courseLanguage }}
        </template>
      </Column>
      <Column
        :header="$t('Categories')"
        :sortable="true"
        field="categories"
        style="min-width: 8rem; text-align: center"
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
        :header="$t('Ranking')"
        :sortable="true"
        field="trackCourseRanking.realTotalScore"
        style="min-width: 10rem; text-align: center"
      >
        <template #body="{ data }">
          <Rating
            :cancel="false"
            :model-value="data.trackCourseRanking ? data.trackCourseRanking.realTotalScore : 0"
            :stars="5"
            class="pointer-events: none"
            @change="onRatingChange($event, data.trackCourseRanking, data.id)"
          />
        </template>
      </Column>
      <Column
        field="link"
        header=""
        style="min-width: 10rem; text-align: center"
      >
        <template #body="{ data }">
          <router-link
            v-if="data.visibility === 3"
            :to="{ name: 'CourseHome', params: { id: data.id } }"
          >
            <Button
              :label="$t('Go to the course')"
              class="btn btn--primary text-white"
              icon="pi pi-external-link"
            />
          </router-link>
          <router-link
            v-else-if="data.visibility === 2 && isUserInCourse(data)"
            :to="{ name: 'CourseHome', params: { id: data.id } }"
          >
            <Button
              :label="$t('Go to the course')"
              class="btn btn--primary text-white"
              icon="pi pi-external-link"
            />
          </router-link>
          <Button
            v-else-if="data.visibility === 2 && !isUserInCourse(data)"
            :label="$t('Not subscribed')"
            class="btn btn--primary text-white"
            icon="pi pi-times"
            disabled
          />
          <Button
            v-else
            :label="$t('Private course')"
            class="btn btn--primary text-white"
            icon="pi pi-lock"
            disabled
          />
        </template>
      </Column>
      <template #footer>
        {{ $t("Total number of courses").concat(": ", courses ? courses.length.toString() : "0") }}
      </template>
    </DataTable>
  </div>
</template>
<script setup>
import { ref } from "vue"
import { ENTRYPOINT } from "../../config/entrypoint"
import axios from "axios"
import { FilterMatchMode } from "primevue/api"
import Button from "primevue/button"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import Rating from "primevue/rating"
import { usePlatformConfig } from "../../store/platformConfig"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
const status = ref(null)
const courses = ref([])
const filters = ref(null)
const currentUserId = securityStore.user.id

const platformConfigStore = usePlatformConfig()
const showCourseDuration = "true" === platformConfigStore.getSetting("course.show_course_duration")

const load = function () {
  status.value = true
  axios
    .get(ENTRYPOINT + "courses.json")
    .then((response) => {
      status.value = false
      if (Array.isArray(response.data)) {
        response.data.forEach((course) => {
          course.courseLanguage = getOriginalLanguageName(course.courseLanguage)

          if (course.duration) {
            course.duration = course.duration
          }
        })
        courses.value = response.data
      }
    })
    .catch(function (error) {
      console.log(error)
    })
}

const updateRating = function (id, value) {
  status.value = true
  axios
    .patch(
      ENTRYPOINT + "track_course_rankings/" + id,
      { totalScore: value },
      { headers: { "Content-Type": "application/merge-patch+json" } },
    )
    .then((response) => {
      courses.value.forEach((course) => {
        if (course.trackCourseRanking && course.trackCourseRanking.id === id) {
          course.trackCourseRanking.realTotalScore = response.data.realTotalScore
        }
      })
      status.value = false
    })
    .catch(function (error) {
      console.log(error)
    })
}

const newRating = function (courseId, value) {
  status.value = true
  axios
    .post(
      ENTRYPOINT + "track_course_rankings",
      {
        totalScore: value,
        course: ENTRYPOINT + "courses/" + courseId,
        url_id: window.access_url_id,
        sessionId: 0,
      },
      { headers: { "Content-Type": "application/ld+json" } },
    )
    .then((response) => {
      courses.value.forEach((course) => {
        if (course.id === courseId) {
          course.trackCourseRanking = response.data
        }
      })
      status.value = false
    })
    .catch(function (error) {
      console.log(error)
    })
}

const isUserInCourse = (course) => {
  return course.users.some((user) => user.user.id === currentUserId)
}

const clearFilter = function () {
  initFilters()
}

const initFilters = function () {
  filters.value = {
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
  }
}

const getOriginalLanguageName = function (courseLanguage) {
  const languages = window.languages
  let language = languages.find((element) => element.isocode === courseLanguage)
  if (language) {
    return language.originalName
  } else {
    return ""
  }
}

const onRatingChange = function (event, trackCourseRanking, courseId) {
  let { value } = event
  if (value > 0) {
    if (trackCourseRanking) updateRating(trackCourseRanking.id, value)
    else newRating(courseId, value)
  } else {
    event.preventDefault()
  }
}

load()
initFilters()
</script>
<style scoped>
.p-datatable .p-datatable-thead > tr > th {
  text-align: center !important;
}

.course-image {
  width: 100px;
  height: auto;
}

.course-duration {
  text-align: center;
  font-weight: bold;
}

.btn--primary {
  background-color: #007bff;
  border-color: #007bff;
  color: #fff;
}
</style>
