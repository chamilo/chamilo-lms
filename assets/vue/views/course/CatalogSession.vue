<template>
  <div class="card">
    <DataTable
      v-model:filters="filters"
      v-model:expandedRows="expandedRows"
      :value="sessions"
      :paginator="true"
      class="p-datatable-sessions p-datatable-lg"
      :rows="9"
      data-key="id"
      filter-display="menu"
      :loading="status"
      responsive-layout="scroll"
      striped-rows
      :global-filter-fields="['name','description','category','category.name','course.courseLanguage']"
    >
      <template #header>
        <div class="table-header-container">
          <div class="flex justify-space-between">
            <div class="justify-content-left">
              <Button
                icon="pi pi-plus"
                :label="$t('Expand')"
                class="mr-2"
                @click="expandAll"
              />
              <Button
                icon="pi pi-minus"
                :label="$t('Collapse')"
                @click="collapseAll"
              />
            </div>
            <div class="justify-content-right">
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
        </div>
      </template>
      <template #empty>
        {{ $t('There are no sessions available') }}
      </template>
      <template #loading>
        {{ $t('Loading sessions. Please wait.') }}
      </template>
      <Column
        :expander="true"
        header-style="width: 3rem"
      />
      <Column
        field="name"
        :header="$t('Title')"
        :sortable="true"
        style="min-width:12rem"
        class="session-name"
      >
        <template #body="{data}">
          {{ data.name }}
        </template>
      </Column>
      <Column
        field="description"
        :header="$t('Session description')"
        :sortable="true"
        style="min-width:12rem"
      >
        <template #body="{data}">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <span v-html="data.description" />
        </template>
      </Column>
      <Column
        field="category"
        :header="$t('Category')"
        :sortable="true"
        style="min-width:12rem"
      >
        <template #body="{data}">
          <span v-if="data.category">
            <em class="pi pi-tag course-category-icon" />
            {{ data.category.name }}
          </span>
        </template>
      </Column>
      <Column
        field="displayStartDate"
        :header="$t('Start Date')"
        :sortable="true"
        style="min-width:12rem"
      >
        <template #body="{data}">
          <i class="pi pi-calendar-times" /> {{ formatDate(data.displayStartDate) }}
        </template>
      </Column>
      <Column
        field="sessionlink"
        header=""
        style="min-width:8rem"
      >
        <template #body="{data}">
          <router-link
            v-slot="{ navigate }"
            :to="'/main/session/resume_session.php?id_session=' + data.id"
          >
            <Button
              :label="$t('Go to the session')"
              class="p-button-sm"
              icon="pi pi-external-link"
              @click="navigate"
            />
          </router-link>
        </template>
      </Column>
      <template #expansion="item">
        <div class="orders-subtable">
          <h5>{{ $t('Courses in this session') + " - " + item.data.name }}</h5>
          <DataTable
            :value="item.data.courses"
            responsive-layout="scroll"
            striped-rows
          >
            <Column header="">
              <template #body="{data}">
                <img
                  :src="data.course.illustrationUrl"
                  :alt="data.course.title"
                  class="course-image"
                >
              </template>
            </Column>
            <Column
              field="course.title"
              :header="$t('Title')"
              :sortable="true"
            >
              <template #body="{data}">
                {{ data.course.title }}
              </template>
            </Column>
            <Column
              field="course.courseLanguage"
              :header="$t('Language')"
              :sortable="true"
              style="min-width:6rem"
            >
              <template #body="{data}">
                {{ getOriginalLanguageName(data.course.courseLanguage) }}
              </template>
            </Column>
            <Column
              :header="$t('Categories')"
              field="course.categories"
              :sortable="true"
              style="min-width:8rem"
            >
              <template #body="{data}">
                <span
                  v-for="category in data.course.categories"
                  :key="category.id"
                >
                  <em class="pi pi-tag course-category-icon" />
                  <span class="course-category">{{ category.name }}</span><br>
                </span>
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
                  :to="{ name: 'CourseHome', params: {id: data.course.id} }"
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
          </DataTable>
        </div>
      </template>
      <template #footer>
        {{ $t('Number of sessions').concat(": ", sessions ? sessions.length.toString() : "0") }}
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

export default {
  name: 'SessionCatalog',
  components: {
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
        axios.get(ENTRYPOINT + 'sessions.json').then(response => {
          this.status = false;
          if (Array.isArray(response.data)) {
            this.sessions = response.data;
          }
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
    expandAll() {
        this.expandedRows = this.sessions.filter(p => p.id);
    },
    collapseAll() {
        this.expandedRows = null;
    },
    formatDate(value) {
        return new Date(value).toLocaleDateString(undefined, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
        });
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
  }
};
</script>
