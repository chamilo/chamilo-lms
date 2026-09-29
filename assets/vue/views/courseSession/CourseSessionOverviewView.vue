<template>
  <section class="space-y-6">
    <BaseToolbar>
      <template #start>
        <BaseButton
          :label="t('Session list')"
          :route="buildListRoute()"
          icon="back"
          type="primary-text"
        />
      </template>
      <template #end>
        <BaseButton
          v-if="canManageUsers"
          :label="t('Subscribe users to this session')"
          :route="buildUsersRoute()"
          icon="account-plus"
          only-icon
          size="normal"
          :tooltip="t('Subscribe users to this session')"
          type="success"
        />
      </template>
    </BaseToolbar>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
    >
      {{ errorMessage }}
    </div>
    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="loading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-gray-500 shadow-sm"
    >
      {{ t("Loading") }}
    </div>

    <template v-else-if="session.id">
      <div class="rounded-xl border border-gray-20 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-xl font-semibold text-gray-900">
          {{ session.title }}
        </h2>
        <dl class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Tutor") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.generalCoaches?.join(", ") || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Category name") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.category || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Duration") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.duration || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Access dates") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.accessDates || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Coach access dates") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.coachDates || "-" }}</dd>
          </div>
          <div>
            <dt class="text-sm font-semibold text-gray-600">{{ t("Visibility") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.visibility || "-" }}</dd>
          </div>
          <div
            v-if="session.urls?.length"
            class="md:col-span-2 xl:col-span-3"
          >
            <dt class="text-sm font-semibold text-gray-600">{{ t("Access URL") }}</dt>
            <dd class="mt-1 text-gray-900">{{ session.urls.join(", ") }}</dd>
          </div>
        </dl>
      </div>

      <div class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ t("Courses") }}</h2>
        <BaseTable
          :is-loading="loading"
          :text-for-empty="t('No courses found')"
          :total-items="courses.length"
          :values="courses"
          data-key="id"
        >
          <Column
            field="title"
            :header="t('Name')"
          >
            <template #body="{ data }">
              <a
                class="text-primary hover:underline"
                :href="data.url"
              >
                {{ data.title }}
              </a>
            </template>
          </Column>
          <Column
            field="code"
            :header="t('Code')"
          />
          <Column :header="t('Tutor')">
            <template #body="{ data }">
              {{ data.coaches?.join(", ") || "-" }}
            </template>
          </Column>
          <Column
            field="nbrUsers"
            :header="t('Number of users')"
          />
        </BaseTable>
      </div>

      <div class="space-y-3">
        <h2 class="text-lg font-semibold text-gray-900">{{ t("Users") }}</h2>
        <BaseTable
          :is-loading="loading"
          :text-for-empty="t('No users found')"
          :total-items="users.length"
          :values="users"
          data-key="id"
        >
          <Column
            field="fullname"
            :header="t('Name')"
          >
            <template #body="{ data }">
              <a
                v-if="data.profileUrl"
                class="text-primary hover:underline"
                :href="data.profileUrl"
              >
                {{ data.fullname }}
              </a>
              <span v-else>{{ data.fullname }}</span>
            </template>
          </Column>
          <Column
            field="username"
            :header="t('Login')"
          />
          <Column :header="t('Detail')">
            <template #body="{ data }">
              <div class="flex items-center justify-center gap-1">
                <BaseButton
                  :label="t('Reporting')"
                  :to-url="data.reportingUrl"
                  icon="tracking"
                  only-icon
                  size="small"
                  type="primary-text"
                />
                <BaseButton
                  v-if="data.canManageCourses"
                  :label="t('Block user from courses in this session')"
                  :route="buildUserCoursesRoute(data.id)"
                  icon="account-cancel"
                  only-icon
                  size="small"
                  type="secondary-text"
                />
                <BaseButton
                  v-if="data.canAddToCurrentUrl"
                  :label="t('Add user to this URL')"
                  icon="account-plus"
                  only-icon
                  size="small"
                  type="success-text"
                  @click="addUserToCurrentUrl(data)"
                />
                <BaseButton
                  v-if="canManageUsers"
                  :label="t('Unsubscribe')"
                  icon="user-delete"
                  only-icon
                  size="small"
                  type="danger-text"
                  @click="confirmUnsubscribe(data)"
                />
              </div>
            </template>
          </Column>
        </BaseTable>
      </div>
    </template>
  </section>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import courseSessionService from "../../services/courseSessionService"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const loading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")
const session = ref({})
const courses = ref([])
const users = ref([])
const canManageUsers = ref(false)

const sessionId = Number(route.params.sessionId)

async function loadOverview() {
  loading.value = true
  errorMessage.value = ""

  try {
    const response = await courseSessionService.getOverview(sessionId)
    session.value = response.session || {}
    courses.value = response.courses || []
    users.value = response.users || []
    canManageUsers.value = Boolean(response.canManageUsers)
  } catch (error) {
    console.error("[CourseSession] Failed to load session overview", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

function buildListRoute() {
  return { name: "CourseSessionList", query: { ...route.query } }
}

function buildUsersRoute() {
  return {
    name: "CourseSessionUsers",
    params: { sessionId },
    query: { ...route.query, view: "available" },
  }
}

function buildUserCoursesRoute(userId) {
  return {
    name: "CourseSessionUserCourses",
    params: { sessionId, userId },
    query: { ...route.query },
  }
}

function confirmUnsubscribe(user) {
  requireConfirmation({
    message: t("Are you sure you want to unsubscribe this user?"),
    accept: () => unsubscribeUser(user.id),
  })
}

async function unsubscribeUser(userId) {
  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseSessionService.unsubscribeUsers(sessionId, [userId])
    successMessage.value = response.message ? t(response.message) : t("Update successful")
    await loadOverview()
  } catch (error) {
    console.error("[CourseSession] Failed to unsubscribe session user", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

async function addUserToCurrentUrl(user) {
  loading.value = true
  errorMessage.value = ""
  successMessage.value = ""

  try {
    const response = await courseSessionService.addUserToUrl(sessionId, user.id)
    successMessage.value = response.message ? t(response.message) : t("Update successful")
    await loadOverview()
  } catch (error) {
    console.error("[CourseSession] Failed to add user to current URL", error)
    errorMessage.value = error?.response?.data?.detail || error?.message || t("An error occurred")
  } finally {
    loading.value = false
  }
}

onMounted(loadOverview)
</script>
