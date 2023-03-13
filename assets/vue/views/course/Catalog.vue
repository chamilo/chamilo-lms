<template>
  <div class="card">
    <DataTable
      v-model:filters="filters"
      :value="courses"
      edit-mode="cell"
      :paginator="true"
      class="p-datatable-courses p-datatable-lg"
      :rows="9"
      data-key="id"
      filter-display="menu"
      :loading="status"
      responsive-layout="scroll"
      striped-rows
      :global-filter-fields="['title','description','category.name','courseLanguage']"
    >
      <template #header>
        <div class="table-header-container">
          <div class="flex justify-content-end">
            <Button
              type="button"
              icon="pi pi-filter-slash"
              :label="$t('Clear filter results')"
              class="p-button-outlined mr-2"
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
        {{ $t('No course available') }}
      </template>
      <template #loading>
        {{ $t('Loading courses. Please wait.') }}
      </template>
      <Column header="">
        <template #body="{data}">
          <img
            :src="data.illustrationUrl"
            :alt="data.title"
            class="course-image"
          >
        </template>
      </Column>
      <Column
        field="title"
        :header="$t('Title')"
        :sortable="true"
        style="min-width:10rem"
      >
        <template #body="{data}">
          {{ data.title }}
        </template>
      </Column>
      <Column
        field="description"
        :header="$t('Course description')"
        :sortable="true"
        style="min-width:12rem"
      >
        <template #body="{data}">
          {{ data.description }}
        </template>
      </Column>
      <Column
        field="teachers"
        :header="$t('Teachers')"
        :sortable="true"
        style="min-width:20rem"
      >
        <template #body="{data}">
          <TeacherBar
            :teachers="data.teachers.map(
              teacher => ({
                id: teacher.id,
                ...teacher.user,
              })
            )"
          />
        </template>
      </Column>
      <Column
        field="courseLanguage"
        :header="$t('Language')"
        :sortable="true"
        style="min-width:7rem"
      >
        <template #body="{data}">
          {{ data.courseLanguage }}
        </template>
      </Column>
      <Column
        field="categories"
        :header="$t('Categories')"
        :sortable="true"
        style="min-width:11rem"
      >
        <template #body="{data}">
          <span
            v-for="category in data.categories"
            :key="category.id"
          >
            <em class="pi pi-tag course-category-icon" />
            <span class="course-category">{{ category.name }}</span><br>
          </span>
        </template>
      </Column>
      <Column
        field="trackCourseRanking.realTotalScore"
        :header="$t('Ranking')"
        :sortable="true"
        style="min-width:8rem"
      >
        <template #body="{data}">
          <Rating
            :model-value="data.trackCourseRanking ? data.trackCourseRanking.realTotalScore : 0"
            :stars="5"
            :cancel="false"
            class="pointer-events: none"
            @change="onRatingChange($event, data.trackCourseRanking, data.id)"
          />
        </template>
      </Column>
      <Column
        field="link"
        header=""
        style="min-width:8rem"
      >
        <template #body="{data}">
          <router-link
            v-slot="{ navigate }"
            :to="{ name: 'CourseHome', params: {id: data.id} }"
          >
            <Button
              :label="$t('Go to the course')"
              class="p-button-sm"
              icon="pi pi-external-link"
              @click="navigate"
            />
          </router-link>
        </template>
      </Column>
      <template #footer>
        {{ $t('Total number of courses').concat(": ", courses ? courses.length.toString() : "0") }}
      </template>
    </DataTable>
  </div>
</template>
<script>

import {ENTRYPOINT} from '../../config/entrypoint';
import axios from "axios";
import {FilterMatchMode} from "primevue/api";
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Rating from 'primevue/rating';
import TeacherBar from '../../components/TeacherBar.vue'

export default {
  name: 'Catalog',
  components: {
    DataTable,
    Column,
    Button,
    TeacherBar,
    Rating,
  },

  data() {
    return {
      status: null,
      courses: [],
      filters: null,
      teachers: [],
    };
  },

  created: function () {
    this.load();
    this.initFilters();
  },
  mounted: function () {

  },
  methods: {
    load: function () {
      this.status = true;
        axios.get(ENTRYPOINT + 'courses.json').then(response => {
          this.status = false;
          if (Array.isArray(response.data)) {
              response.data.forEach(course => course.courseLanguage = this.getOriginalLanguageName(course.courseLanguage));
              this.courses = response.data;
          }
        }).catch(function (error) {
          console.log(error);
        });
    },
    updateRating: function (id, value) {
        this.status = true;
        axios.patch(ENTRYPOINT + 'track_course_rankings/' + id,
            {"totalScore": value},
            {headers: {'Content-Type': 'application/merge-patch+json'}}
        ).then(response => {
            this.courses.forEach(
                course => {
                  if (course.trackCourseRanking && course.trackCourseRanking.id === id) {
                      course.trackCourseRanking.realTotalScore = response.data.realTotalScore;
                  }
              }
            );
            this.status = false;
        }).catch(function (error) {
            console.log(error);
        });
    },
    newRating: function (courseId, value) {
        this.status = true;
        axios.post(ENTRYPOINT + 'track_course_rankings',
            {
                totalScore: value,
                course: ENTRYPOINT + "courses/" + courseId,
                url_id: window.access_url_id,
                sessionId: 0
            },
            {headers: {'Content-Type': 'application/ld+json'}}
        ).then(response => {
            this.courses.forEach(
                course => {
                    if (course.id === courseId) {
                        course.trackCourseRanking = response.data;
                    }
                }
            );
            this.status = false;
        }).catch(function (error) {
            console.log(error);
        });
    },
    clearFilter() {
        this.initFilters();
    },
    initFilters() {
        this.filters = {
            'global': {value: null, matchMode: FilterMatchMode.CONTAINS},
        }
    },
    getOriginalLanguageName(courseLanguage) {
        const languages = window.languages;
        let language =  languages.find(element => element.isocode === courseLanguage);
        if (language) {
            return language.originalName;
        } else {
            return '';
        }
    },
    onRatingChange(event, trackCourseRanking, courseId) {
        let { value } = event;
        if (value > 0) {
            if (trackCourseRanking)
                this.updateRating(trackCourseRanking.id, value);
            else
                this.newRating(courseId, value);
        } else {
            event.preventDefault();
        }

    },
    onNewRatingChange(event, courseId) {
        let { value } = event;
        if (value > 0)
            this.newRating(courseId, value);
        else
            event.preventDefault();
    }
  }
};

</script>
<style lang="scss" scoped>
@import 'primeflex/primeflex.scss';

::v-deep(.p-paginator) {
  .p-paginator-current {
    margin-left: auto;
  }
}
.course-image {
  width: 130px;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23)
}
.p-input-icon-left > i:first-of-type {
  left: 0.75rem;
  color: #6c757d;
}
.p-input-icon-left > i, .p-input-icon-right > i {
  margin-top: -.5rem;
  position: absolute;
  top: 50%;
}
.p-input-icon-left > .p-inputtext {
  padding-left: 2.5rem;
}
.p-inputtext {
  font-size: 1rem;
  color: #495057;
  background: #ffffff;
  padding: 0.75rem 0.75rem;
  border: 1px solid #ced4da;
  transition: background-color 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
  appearance: none;
  border-radius: 6px;
}
.p-inputtext:enabled:hover {
  border-color: #3B82F6;
}
.p-inputtext:enabled:focus {
  outline: 0 none;
  outline-offset: 0;
  box-shadow: 0 0 0 0.2rem #BFDBFE;
  border-color: #3B82F6;
}
::v-deep(.p-datatable.p-datatable-courses) {
  .p-datatable-header {
    padding: 1rem;
    text-align: left;
    font-size: 1.5rem;
  }

  .p-paginator {
    padding: 1rem;
  }

  .p-datatable-thead > tr > th {
    text-align: left;
  }

  .p-datatable-tbody > tr > td {
    cursor: auto;
  }
}
</style>
