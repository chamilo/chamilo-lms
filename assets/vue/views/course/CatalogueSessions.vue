<template>
  <div class="card">
    <DataTable
      v-model:expandedRows="expandedRows"
      v-model:filters="filters"
      :global-filter-fields="['title', 'description', 'category.title', 'course.courseLanguage']"
      :loading="status"
      :paginator="true"
      :rows="9"
      :value="sessions"
      class="p-datatable-sessions p-datatable-lg"
      data-key="id"
      filter-display="menu"
      responsive-layout="scroll"
      striped-rows
    >
      <template #header>
        <div class="table-header-container">
          <div class="flex justify-space-between">
            <div class="justify-content-left">
              <Button
                :label="$t('Expand')"
                class="mr-2"
                icon="pi pi-plus"
                @click="expandAll"
              />
              <Button
                :label="$t('Collapse')"
                icon="pi pi-minus"
                @click="collapseAll"
              />
            </div>
            <div class="justify-content-right">
              <Button
                :label="$t('Clear filter results')"
                class="p-button-outlined mr-2"
                icon="pi pi-filter-slash"
                type="button"
                @click="clearFilter()"
              />
              <span class="p-input-icon-left">
                <i class="mdi mdi-search" />
                <InputText
                  v-model="filters['global'].value"
                  :placeholder="$t('Search')"
                />
              </span>
            </div>
          </div>
        </div>
      </template>
      <template #empty>
        {{ $t("There are no sessions available") }}
      </template>
      <template #loading>
        {{ $t("Loading sessions. Please wait.") }}
      </template>
      <Column
        :expander="true"
        header-style="width: 3rem"
      />
      <Column
        :header="$t('Title')"
        :sortable="true"
        class="session-name"
        field="title"
        style="min-width: 12rem"
      >
        <template #body="{ data }">
          {{ data.title }}
        </template>
      </Column>
      <Column
        :header="$t('Session description')"
        :sortable="true"
        field="description"
        style="min-width: 12rem"
      >
        <template #body="{ data }">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="data.description" />
        </template>
      </Column>
      <Column
        :header="$t('Category')"
        :sortable="true"
        field="category"
        style="min-width: 12rem"
      >
        <template #body="{ data }">
          <span v-if="data.category">
            <em class="pi pi-tag course-category-icon" />
            {{ data.category.title }}
          </span>
        </template>
      </Column>
      <Column
        :header="$t('Start Date')"
        :sortable="true"
        field="displayStartDate"
        style="min-width: 12rem"
      >
        <template #body="{ data }">
          <i class="pi pi-calendar-times" /> {{ formatDate(data.displayStartDate) }}
        </template>
      </Column>
      <Column
        field="sessionlink"
        header=""
        style="min-width: 8rem"
      >
        <template #body="{ data }">
          <BaseAppLink
            :url="'/main/session/resume_session.php?id_session=' + data.id"
            class="p-button-sm"
          >
            <BaseIcon icon="link-external" />
            {{ t("Go to the session") }}
          </BaseAppLink>
        </template>
      </Column>
      <template #expansion="item">
        <div class="orders-subtable">
          <h5>{{ $t("Courses in this session") + " - " + item.data.title }}</h5>
          <DataTable
            :value="item.data.courses"
            responsive-layout="scroll"
            striped-rows
          >
            <Column header="">
              <template #body="{ data }">
                <img
                  :alt="data.course.title"
                  :src="data.course.illustrationUrl"
                  class="course-image"
                />
              </template>
            </Column>
            <Column
              :header="$t('Title')"
              :sortable="true"
              field="course.title"
            >
              <template #body="{ data }">
                {{ data.course.title }}
              </template>
            </Column>
            <Column
              :header="$t('Language')"
              :sortable="true"
              field="course.courseLanguage"
              style="min-width: 6rem"
            >
              <template #body="{ data }">
                {{ getOriginalLanguageName(data.course.courseLanguage) }}
              </template>
            </Column>
            <Column
              :header="$t('Categories')"
              :sortable="true"
              field="course.categories"
              style="min-width: 8rem"
            >
              <template #body="{ data }">
                <span
                  v-for="category in data.course.categories"
                  :key="category.id"
                >
                  <em class="pi pi-tag course-category-icon" />
                  <span class="course-category">{{ category.title }}</span
                  ><br />
                </span>
              </template>
            </Column>
            <Column
              field="link"
              header=""
              style="min-width: 8rem"
            >
              <template #body="{ data }">
                <BaseAppLink :to="{ name: 'CourseHome', params: { id: data.course.id } }">
                  <BaseIcon
                    icon="link-external"
                    class="p-button-sm"
                  />
                  {{ t("Go to the course") }}
                </BaseAppLink>
              </template>
            </Column>
          </DataTable>
        </div>
      </template>
      <template #footer>
        {{ $t("Number of sessions").concat(": ", sessions ? sessions.length.toString() : "0") }}
      </template>
    </DataTable>
  </div>
</template>
<script>
import { ENTRYPOINT } from "../../config/entrypoint"
import axios from "axios"
import { FilterMatchMode } from "primevue/api"
import Button from "primevue/button"
import DataTable from "primevue/datatable"
import Column from "primevue/column"
import BaseAppLink from "../../components/basecomponents/BaseAppLink.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

export default {
  name: "SessionCatalog",
  components: {
    BaseIcon,
    BaseAppLink,
    DataTable,
    Column,
    Button,
  },
  data() {
    return {
      status: null,
      sessions: [],
      filters: null,
      expandedRows: [],
    }
  },
  created: function () {
    this.load()
    this.initFilters()
  },
  mounted: function () {},
  methods: {
    load: function () {
      this.status = true
      axios
        .get(ENTRYPOINT + "sessions.json")
        .then((response) => {
          this.status = false
          if (Array.isArray(response.data)) {
            this.sessions = response.data
          }
        })
        .catch(function (error) {
          console.log(error)
        })
    },
    clearFilter() {
      this.initFilters()
    },
    initFilters() {
      this.filters = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
      }
    },
    expandAll() {
      this.expandedRows = this.sessions.filter((p) => p.id)
    },
    collapseAll() {
      this.expandedRows = null
    },
    formatDate(value) {
      return new Date(value).toLocaleDateString(undefined, {
        month: "long",
        day: "numeric",
        year: "numeric",
      })
    },
    getOriginalLanguageName(courseLanguage) {
      const languages = window.languages
      let language = languages.find((element) => element.isocode === courseLanguage)
      if (language) {
        return language.originalName
      } else {
        return ""
      }
    },
  },
}
</script>
