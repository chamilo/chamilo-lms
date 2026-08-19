<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from "vue"
import { useI18n } from "vue-i18n"
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler } from "chart.js"
import accessUrlAdminService from "../../services/accessUrlAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler)

const { t } = useI18n()

function toDateOnlyString(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, "0")
  const day = String(date.getDate()).padStart(2, "0")

  return `${year}-${month}-${day}`
}

const isLoading = ref(false)
const items = ref([])
const totalItems = ref(0)
const errorMessage = ref("")
const system = ref({
  chamiloVersion: "",
  phpVersion: "",
  totalUsers: 0,
  totalCourses: 0,
  totalSessions: 0,
})

const usersLoading = ref(false)
const usersItems = ref([])
const usersTotalItems = ref(0)
const usersSearch = ref("")
const usersPage = ref(1)
const usersPageSize = ref(20)

const coursesLoading = ref(false)
const coursesItems = ref([])
const coursesTotalItems = ref(0)
const coursesSearch = ref("")
const coursesPage = ref(1)
const coursesPageSize = ref(20)

const loginsLoading = ref(false)
const loginsScoped = ref(false)
const today = new Date()
const thirtyDaysAgo = new Date(today)
thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29)
const loginsFrom = ref(thirtyDaysAgo)
const loginsTo = ref(today)
const loginsChartCanvas = ref(null)
let loginsChart = null

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await accessUrlAdminService.list()
    items.value = data.items
    totalItems.value = data.totalItems
    system.value = data.system
  } catch {
    errorMessage.value = t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function loadUsers() {
  usersLoading.value = true
  try {
    const data = await accessUrlAdminService.listUsers({
      page: usersPage.value,
      limit: usersPageSize.value,
      search: usersSearch.value,
    })
    usersItems.value = data.items
    usersTotalItems.value = data.totalItems
  } finally {
    usersLoading.value = false
  }
}

async function loadCourses() {
  coursesLoading.value = true
  try {
    const data = await accessUrlAdminService.listCourses({
      page: coursesPage.value,
      limit: coursesPageSize.value,
      search: coursesSearch.value,
    })
    coursesItems.value = data.items
    coursesTotalItems.value = data.totalItems
  } finally {
    coursesLoading.value = false
  }
}

function renderLoginsChart(labels, counts, uniqueCounts) {
  if (!loginsChartCanvas.value) {
    return
  }

  if (loginsChart) {
    loginsChart.data.labels = labels
    loginsChart.data.datasets[0].data = counts
    loginsChart.data.datasets[1].data = uniqueCounts
    loginsChart.update()

    return
  }

  loginsChart = new Chart(loginsChartCanvas.value, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: t("Logins"),
          data: counts,
          borderColor: "#2563eb",
          backgroundColor: "rgba(37, 99, 235, 0.1)",
          fill: true,
          tension: 0.2,
          pointRadius: 2,
        },
        {
          label: t("Unique logins"),
          data: uniqueCounts,
          borderColor: "#16a34a",
          backgroundColor: "rgba(22, 163, 74, 0.1)",
          fill: true,
          tension: 0.2,
          pointRadius: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { precision: 0 },
        },
      },
      plugins: {
        legend: { display: true },
      },
    },
  })
}

async function loadLogins() {
  loginsLoading.value = true
  try {
    const data = await accessUrlAdminService.getLogins({
      from: toDateOnlyString(loginsFrom.value),
      to: toDateOnlyString(loginsTo.value),
    })
    loginsScoped.value = data.scoped
    renderLoginsChart(data.labels, data.counts, data.uniqueCounts)
  } finally {
    loginsLoading.value = false
  }
}

watch([loginsFrom, loginsTo], () => {
  loadLogins()
})

function onUsersSearch() {
  usersPage.value = 1
  loadUsers()
}

function onUsersPage(event) {
  usersPage.value = event.page + 1
  usersPageSize.value = event.rows
  loadUsers()
}

function onCoursesSearch() {
  coursesPage.value = 1
  loadCourses()
}

function onCoursesPage(event) {
  coursesPage.value = event.page + 1
  coursesPageSize.value = event.rows
  loadCourses()
}

function adminNames(admins) {
  if (!admins || 0 === admins.length) {
    return t("None")
  }

  return admins.map((admin) => `${admin.firstname} ${admin.lastname}`).join(", ")
}

function urlNames(urls) {
  if (!urls || 0 === urls.length) {
    return t("None")
  }

  return urls.map((url) => url.url).join(", ")
}

const userDetailVisible = ref(false)
const selectedUser = ref(null)

function showUserDetail(user) {
  selectedUser.value = user
  userDetailVisible.value = true
}

const courseDetailVisible = ref(false)
const selectedCourse = ref(null)

function showCourseDetail(course) {
  selectedCourse.value = course
  courseDetailVisible.value = true
}

onMounted(() => {
  loadData()
  loadUsers()
  loadCourses()
  loadLogins()
})

onBeforeUnmount(() => {
  if (loginsChart) {
    loginsChart.destroy()
    loginsChart = null
  }
})
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Multi URLs')">
      <BaseButton
        :label="t('Configure multiple access URL')"
        icon="hammer-wrench"
        type="secondary"
        :route="{ name: 'AccessUrlManage' }"
      />
    </SectionHeader>

    <div
      v-if="errorMessage"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <h2 class="mb-4 text-base font-semibold">{{ t("General information") }}</h2>
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div>
          <dt class="text-sm text-gray-500">{{ t("Installed version") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : system.chamiloVersion }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("PHP version") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : system.phpVersion }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Users") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : system.totalUsers }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Courses") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : system.totalCourses }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Sessions") }}</dt>
          <dd class="text-lg font-medium">{{ isLoading ? "—" : system.totalSessions }}</dd>
        </div>
      </dl>
    </div>

    <p class="text-sm text-gray-500">
      {{
        t(
          "Entities like users and courses can be active in more than one URL at the same time, so the sum of values below will not match the total.",
        )
      }}
    </p>

    <BaseTable
      :values="items"
      :total-items="totalItems"
      :is-loading="isLoading"
      :lazy="false"
      :text-for-empty="t('No results found')"
    >
      <Column
        field="url"
        :header="t('URL')"
      >
        <template #body="{ data }">
          <span :style="{ paddingLeft: `${data.depth * 24}px` }">
            <a
              :href="data.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-blue-600 hover:underline"
            >
              {{ data.url }}
            </a>
          </span>
        </template>
      </Column>
      <Column
        field="description"
        :header="t('Description')"
      />
      <Column :header="t('Status')">
        <template #body="{ data }">
          <span class="flex items-center gap-1">
            <BaseIcon :icon="data.active ? 'check-circle' : 'close-circle'" />
            {{ data.active ? t("Active") : t("Inactive") }}
          </span>
        </template>
      </Column>
      <Column
        field="userCount"
        :header="t('Users')"
      />
      <Column
        field="courseCount"
        :header="t('Courses')"
      />
      <Column
        field="sessionCount"
        :header="t('Sessions')"
      />
      <Column :header="t('Administrators')">
        <template #body="{ data }">
          {{ adminNames(data.admins) }}
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <BaseButton
              :label="t('Manage users')"
              :route="{ name: 'AccessUrlUsers', query: { access_url_id: data.id } }"
              icon="account"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              :label="t('Manage user groups')"
              :route="{ name: 'AccessUrlUserGroups', query: { access_url_id: data.id } }"
              icon="account-group"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              :label="t('Manage courses')"
              :route="{ name: 'AccessUrlCourses', query: { access_url_id: data.id } }"
              icon="courses"
              only-icon
              size="small"
              type="primary-text"
            />
            <BaseButton
              :label="t('Manage course categories')"
              :route="{ name: 'AccessUrlCourseCategories', query: { access_url_id: data.id } }"
              icon="file-tree-outline"
              only-icon
              size="small"
              type="primary-text"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <div class="rounded-3xl border border-gray-20 bg-white p-6 shadow-sm">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <h2 class="text-base font-semibold">
          {{ loginsScoped ? t("Logins (your URLs)") : t("Logins (all URLs combined)") }}
        </h2>
        <div class="flex flex-wrap gap-4">
          <BaseCalendar
            id="multi-url-logins-from"
            v-model="loginsFrom"
            :label="t('From')"
          />
          <BaseCalendar
            id="multi-url-logins-to"
            v-model="loginsTo"
            :label="t('To')"
          />
        </div>
      </div>
      <div style="height: 280px">
        <canvas ref="loginsChartCanvas"></canvas>
      </div>
    </div>

    <SectionHeader :title="t('User directory')" />

    <form
      class="flex gap-4 items-end"
      data-no-autofocus="1"
      @submit.prevent="onUsersSearch"
    >
      <div class="flex flex-col gap-1 flex-1 max-w-md">
        <input
          v-model="usersSearch"
          :placeholder="t('Search users')"
          class="form-control w-full"
          name="usersSearch"
          type="text"
        />
      </div>
      <BaseButton
        :label="t('Search')"
        icon="search"
        is-submit
      />
    </form>

    <BaseTable
      :values="usersItems"
      :total-items="usersTotalItems"
      :is-loading="usersLoading"
      :lazy="true"
      :rows="usersPageSize"
      :text-for-empty="t('No results found')"
      @page="onUsersPage"
    >
      <Column :header="t('Name')">
        <template #body="{ data }">{{ data.firstname }} {{ data.lastname }}</template>
      </Column>
      <Column
        field="username"
        :header="t('Username')"
      />
      <Column
        field="email"
        :header="t('Email')"
      />
      <Column :header="t('URLs')">
        <template #body="{ data }">
          {{ urlNames(data.urls) }}
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <BaseButton
              :label="t('Information')"
              icon="information"
              only-icon
              size="small"
              type="primary-text"
              @click="showUserDetail(data)"
            />
            <BaseButton
              :label="t('View details')"
              icon="fast-forward-outline"
              only-icon
              size="small"
              type="primary-text"
              :route="{ name: 'AdminMultiUrlUserDetail', params: { id: data.id } }"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <SectionHeader :title="t('Course directory')" />

    <form
      class="flex gap-4 items-end"
      data-no-autofocus="1"
      @submit.prevent="onCoursesSearch"
    >
      <div class="flex flex-col gap-1 flex-1 max-w-md">
        <input
          v-model="coursesSearch"
          :placeholder="t('Search courses')"
          class="form-control w-full"
          name="coursesSearch"
          type="text"
        />
      </div>
      <BaseButton
        :label="t('Search')"
        icon="search"
        is-submit
      />
    </form>

    <BaseTable
      :values="coursesItems"
      :total-items="coursesTotalItems"
      :is-loading="coursesLoading"
      :lazy="true"
      :rows="coursesPageSize"
      :text-for-empty="t('No results found')"
      @page="onCoursesPage"
    >
      <Column
        field="title"
        :header="t('Title')"
      />
      <Column
        field="code"
        :header="t('Code')"
      />
      <Column :header="t('URLs')">
        <template #body="{ data }">
          {{ urlNames(data.urls) }}
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <BaseButton
              :label="t('Information')"
              icon="information"
              only-icon
              size="small"
              type="primary-text"
              @click="showCourseDetail(data)"
            />
            <BaseButton
              :label="t('View details')"
              icon="fast-forward-outline"
              only-icon
              size="small"
              type="primary-text"
              :route="{ name: 'AdminMultiUrlCourseDetail', params: { id: data.id } }"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="userDetailVisible"
      :title="t('User details')"
      header-icon="information"
    >
      <dl
        v-if="selectedUser"
        class="grid grid-cols-1 gap-6 p-4 min-w-[26rem]"
      >
        <div>
          <dt class="text-sm text-gray-500">{{ t("Name") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.firstname }} {{ selectedUser.lastname }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Username") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.username }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Email") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.email }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Official code") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.officialCode || t("None") }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Registration date") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.registrationDate }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Created by") }}</dt>
          <dd class="text-lg font-medium">{{ selectedUser.creatorName || t("None") }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Classes") }}</dt>
          <dd>
            <ul
              v-if="selectedUser.usergroups.length"
              class="list-disc pl-5 space-y-1 text-lg font-medium"
            >
              <li
                v-for="(groupTitle, index) in selectedUser.usergroups"
                :key="index"
              >
                {{ groupTitle }}
              </li>
            </ul>
            <span
              v-else
              class="text-lg font-medium"
              >{{ t("None") }}</span
            >
          </dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("URLs") }}</dt>
          <dd>
            <ul
              v-if="selectedUser.urls.length"
              class="list-disc pl-5 space-y-1 text-lg font-medium"
            >
              <li
                v-for="url in selectedUser.urls"
                :key="url.id"
              >
                {{ url.url }}
              </li>
            </ul>
            <span
              v-else
              class="text-lg font-medium"
              >{{ t("None") }}</span
            >
          </dd>
        </div>
      </dl>
    </BaseDialog>

    <BaseDialog
      v-model:is-visible="courseDetailVisible"
      :title="t('Course details')"
      header-icon="information"
    >
      <dl
        v-if="selectedCourse"
        class="grid grid-cols-1 gap-6 p-4 min-w-[26rem]"
      >
        <div>
          <dt class="text-sm text-gray-500">{{ t("Title") }}</dt>
          <dd class="text-lg font-medium">{{ selectedCourse.title }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("Code") }}</dt>
          <dd class="text-lg font-medium">{{ selectedCourse.code }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">{{ t("URLs") }}</dt>
          <dd>
            <ul
              v-if="selectedCourse.urls.length"
              class="list-disc pl-5 space-y-1 text-lg font-medium"
            >
              <li
                v-for="url in selectedCourse.urls"
                :key="url.id"
              >
                {{ url.url }}
              </li>
            </ul>
            <span
              v-else
              class="text-lg font-medium"
              >{{ t("None") }}</span
            >
          </dd>
        </div>
      </dl>
    </BaseDialog>
  </div>
</template>
