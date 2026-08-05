<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Back to group list')"
          :route="listRoute"
          icon="back"
          only-icon
          type="plain"
        />
      </template>
      <template #end>
        <div class="flex items-center gap-2">
          <BaseButton
            :label="t('CSV export')"
            icon="file-delimited-outline"
            only-icon
            :tooltip="t('CSV export')"
            type="primary"
            @click="download(data.csvExportUrl)"
          />
          <BaseButton
            :label="t('Excel export')"
            icon="file-excel"
            only-icon
            :tooltip="t('Excel export')"
            type="primary"
            @click="download(data.xlsxExportUrl)"
          />
          <BaseButton
            :label="t('Export to PDF')"
            icon="file-pdf"
            only-icon
            :tooltip="t('Export to PDF')"
            type="primary"
            @click="download(data.pdfExportUrl)"
          />
        </div>
      </template>
    </BaseToolbar>

    <form
      class="flex flex-col gap-3 rounded-xl border border-gray-20 bg-white p-4 shadow-sm md:flex-row md:items-end"
      @submit.prevent="load"
    >
      <BaseInputText
        id="course-group-overview-search"
        v-model="search"
        class="flex-1"
        :label="t('Search')"
        name="search"
      />
      <BaseButton
        :is-loading="loading"
        :label="t('Search')"
        icon="search"
        is-submit
        type="primary"
      />
    </form>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      :is-loading="loading"
      :text-for-empty="t('No groups found')"
      :total-items="data.groups.length"
      :values="data.groups"
      data-key="id"
    >
      <Column
        field="title"
        :header="t('Group name')"
      />
      <Column
        field="category"
        :header="t('Category')"
      >
        <template #body="{ data: item }">
          {{ item.category || "-" }}
        </template>
      </Column>
      <Column :header="t('Tutors')">
        <template #body="{ data: item }">
          {{ item.tutors.length ? item.tutors.join(", ") : "-" }}
        </template>
      </Column>
      <Column :header="t('Group members')">
        <template #body="{ data: item }">
          {{ item.members.length ? item.members.join(", ") : "-" }}
        </template>
      </Column>
    </BaseTable>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import courseGroupService from "../../services/courseGroupService"
import { useRouteCourseContext } from "../../composables/useRouteCourseContext"

const { t } = useI18n()
const route = useRoute()
const { cid, sid, contextQuery } = useRouteCourseContext()
const loading = ref(false)
const errorMessage = ref("")
const search = ref("")
const data = reactive({ groups: [], csvExportUrl: "", xlsxExportUrl: "", pdfExportUrl: "" })
const listRoute = computed(() => ({
  name: "CourseUserGroups",
  params: route.params,
  query: { ...contextQuery.value, gid: 0 },
}))

async function load() {
  loading.value = true
  errorMessage.value = ""
  try {
    Object.assign(data, await courseGroupService.getOverview({ cid: cid.value, sid: sid.value, search: search.value }))
  } catch (error) {
    console.error("[CourseGroup] Failed to load group overview", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function download(url) {
  if (url) window.location.assign(url)
}

onMounted(load)
</script>
