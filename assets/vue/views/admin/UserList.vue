<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('User list')">
      <BaseButton
        :label="t('Add a user')"
        :to-url="'/main/admin/user_add.php'"
        icon="user-add"
      />
    </SectionHeader>

    <!-- Tabs -->
    <div class="flex gap-2 border-b border-gray-200">
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('all')"
      >
        {{ t("All users") }}
      </button>
      <button
        :class="[
          'px-4 py-2 text-sm font-medium border-b-2 transition-colors',
          view === 'deleted' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700',
        ]"
        @click="switchView('deleted')"
      >
        {{ t("Deleted users") }}
      </button>
    </div>

    <!-- Simple search + Advanced toggle -->
    <div class="flex flex-col gap-4">
      <form
        class="flex gap-4 items-end"
        @submit.prevent="onSearch"
      >
        <div class="flex flex-col gap-1 flex-1 max-w-md">
          <input
            v-model="simpleKeyword"
            :placeholder="t('Search users')"
            class="form-control w-full"
            type="text"
          />
        </div>
        <button
          class="btn btn--primary"
          type="submit"
        >
          {{ t("Search") }}
        </button>
        <button
          class="btn btn--plain flex items-center gap-1"
          type="button"
          @click="showAdvanced = !showAdvanced"
        />
      </form>

      <!-- Advanced search form -->
      <div
        v-if="showAdvanced"
        class="border border-gray-200 rounded p-4 bg-gray-50"
      >
        <h3 class="text-lg font-medium mb-4">{{ t("Advanced search") }}</h3>
        <form
          class="grid grid-cols-1 md:grid-cols-3 gap-4"
          @submit.prevent="onAdvancedSearch"
        >
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("First name") }}</label>
            <input
              v-model="advancedFilters.keyword_firstname"
              class="form-control"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Last name") }}</label>
            <input
              v-model="advancedFilters.keyword_lastname"
              class="form-control"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Login") }}</label>
            <input
              v-model="advancedFilters.keyword_username"
              class="form-control"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("E-mail") }}</label>
            <input
              v-model="advancedFilters.keyword_email"
              class="form-control"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Official code") }}</label>
            <input
              v-model="advancedFilters.keyword_officialcode"
              class="form-control"
              type="text"
            />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-sm text-gray-600">{{ t("Roles") }}</label>
            <select
              v-model="advancedFilters.keyword_roles"
              class="form-control"
              multiple
              size="6"
            >
              <option
                v-for="(label, code) in roleOptions"
                :key="code"
                :value="code"
              >
                {{ label }}
              </option>
            </select>
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm text-gray-600">{{ t("Account") }}</label>
            <label class="flex items-center gap-2 text-sm">
              <input
                v-model="advancedFilters.keyword_active"
                type="checkbox"
              />
              {{ t("Active") }}
            </label>
            <label class="flex items-center gap-2 text-sm">
              <input
                v-model="advancedFilters.keyword_inactive"
                type="checkbox"
              />
              {{ t("Inactive") }}
            </label>
          </div>
          <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2 text-sm mt-6">
              <input
                v-model="advancedFilters.check_easy_passwords"
                type="checkbox"
              />
              {{ t("Check passwords too easy to guess") }}
            </label>
          </div>
          <div class="flex items-end md:col-span-3">
            <BaseButton
              :label="t('Search users')"
              icon="search"
              is-submit
            />
          </div>
        </form>
      </div>
    </div>

    <!-- User table -->
    <BaseTable
      v-model:rows="pageSize"
      v-model:selectedItems="selectedItems"
      :is-loading="isLoading"
      :lazy="true"
      :text-for-empty="t('No data available')"
      :total-items="total"
      :values="items"
      data-key="id"
      @page="onPage"
      @sort="onSort"
    >
      <Column
        header-style="width: 3rem"
        selection-mode="multiple"
      />
      <Column
        :header="t('Photo')"
        field="avatarUrl"
      >
        <template #body="{ data }">
          <img
            :alt="data.firstname + ' ' + data.lastname"
            :src="data.avatarUrl"
            class="w-8 h-8 rounded-full object-cover"
          />
        </template>
      </Column>
      <Column
        :header="t('Official code')"
        field="officialCode"
        sortable
      />
      <Column
        :header="t('First name')"
        field="firstname"
        sortable
      >
        <template #body="{ data }">
          <BaseAppLink
            :url="`/main/admin/user_information.php?user_id=${data.id}`"
            class="text-blue-600 hover:underline"
          >
            {{ data.firstname }}
          </BaseAppLink>
        </template>
      </Column>
      <Column
        :header="t('Last name')"
        field="lastname"
        sortable
      >
        <template #body="{ data }">
          <BaseAppLink
            :url="`/main/admin/user_information.php?user_id=${data.id}`"
            class="text-blue-600 hover:underline"
          >
            {{ data.lastname }}
          </BaseAppLink>
        </template>
      </Column>
      <Column
        :header="t('Username')"
        field="username"
        sortable
      />
      <Column
        :header="t('E-mail')"
        field="email"
        sortable
      />
      <Column
        :header="t('Roles')"
        field="roles"
      >
        <template #body="{ data }">
          <span
            v-for="role in data.roles"
            :key="role"
            class="block text-xs"
            >{{ formatRole(role) }}</span
          >
        </template>
      </Column>
      <Column
        :header="t('Active')"
        field="active"
        sortable
      >
        <template #body="{ data }">
          <!-- Expired / auto-disabled: non-clickable -->
          <span
            v-if="data.active === -1"
            :title="t('Account expired')"
            class="mdi mdi-timer-alert-outline ch-tool-icon text-orange-500 cursor-default"
          />
          <!-- Soft deleted: non-clickable -->
          <span
            v-else-if="data.active === -2"
            :title="t('The account has been removed temporarily.')"
            class="mdi mdi-cancel ch-tool-icon text-red-500 cursor-default"
          />
          <!-- Active: clickable to lock (unless current user) -->
          <span
            v-else-if="data.active === 1"
            :class="[
              'mdi mdi-check-circle ch-tool-icon text-green-600',
              canToggleActive(data) ? 'cursor-pointer' : 'cursor-default',
            ]"
            :title="t('Lock')"
            @click="canToggleActive(data) && toggleActive(data)"
          />
          <!-- Inactive: clickable to unlock (unless current user) -->
          <span
            v-else
            :class="[
              'mdi mdi-alert ch-tool-icon text-yellow-600',
              canToggleActive(data) ? 'cursor-pointer' : 'cursor-default',
            ]"
            :title="t('Unlock')"
            @click="canToggleActive(data) && toggleActive(data)"
          />
        </template>
      </Column>
      <Column
        :header="t('Registration date')"
        field="createdAt"
        sortable
      />
      <Column
        :header="t('Latest login')"
        field="lastLogin"
        sortable
      />
      <Column
        :header="t('Actions')"
        field="id"
      >
        <template #body="{ data }">
          <div
            v-if="view === 'deleted'"
            class="flex gap-1 flex-nowrap"
          >
            <!-- Edit (deleted view) -->
            <BaseButton
              :label="t('Edit')"
              :to-url="`/main/admin/user_edit.php?user_id=${data.id}`"
              icon="edit"
              only-icon
              size="small"
              type="primary"
            />
            <!-- Restore -->
            <BaseButton
              :label="t('Restore')"
              icon="restore"
              only-icon
              size="small"
              type="primary"
              @click="confirmAction('restore', data)"
            />
            <!-- Delete permanently -->
            <BaseButton
              v-if="viewer.isPlatformAdmin"
              :label="t('Delete permanently')"
              icon="delete-forever"
              only-icon
              size="small"
              type="danger"
              @click="confirmAction('destroy', data)"
            />
          </div>
          <div
            v-else
            class="flex gap-1 flex-nowrap"
          >
            <!-- Information -->
            <template v-if="viewer.isPlatformAdmin">
              <BaseButton
                v-if="!data.isAnonymous"
                :label="t('Information')"
                :disabled="data.isAnonymous"
                :to-url="`/main/admin/user_information.php?user_id=${data.id}`"
                icon="information"
                only-icon
                size="small"
                type="primary"
              />
            </template>

            <!-- Login as -->
            <template v-if="showLoginAs(data)">
              <BaseButton
                v-if="canLoginAs(data)"
                :label="t('Login as')"
                :disabled="!canLoginAs(data)"
                :to-url="`/admin/user-list-login-as?user_id=${data.id}&sec_token=${loginAsToken}`"
                icon="account-key"
                only-icon
                size="small"
                type="primary"
              />
            </template>
            <BaseButton
              v-else
              :label="t('Login as')"
              disabled
              icon="account-key"
              only-icon
              size="small"
              type="primary"
            />

            <!-- Reporting (students only) -->
            <BaseButton
              :disabled="!data.isStudent"
              :label="t('Reporting')"
              :to-url="`/main/my_space/myStudents.php?student=${data.id}`"
              icon="tracking"
              only-icon
              size="small"
              type="primary"
            />

            <!-- Edit -->
            <template v-if="viewer.isPlatformAdmin || viewer.isSessionAdmin">
              <BaseButton
                v-if="!data.isAnonymous"
                :disabled="data.isAnonymous"
                :label="t('Edit')"
                :to-url="`/main/admin/user_edit.php?user_id=${data.id}`"
                icon="edit"
                only-icon
                size="small"
                type="primary"
              />
            </template>

            <!-- Assign skill -->
            <BaseButton
              v-if="viewer.isPlatformAdmin"
              :label="t('Assign skill')"
              :to-url="`/main/skills/assign.php?user=${data.id}`"
              icon="shield-star"
              only-icon
              size="small"
              type="primary"
            />

            <!-- Anonymize (platform admin only, not self, not anonymous) -->
            <BaseButton
              v-if="viewer.isPlatformAdmin && data.id !== viewer.id && !data.isAnonymous && !data.isAdmin"
              :label="t('Anonymize')"
              icon="anonymous"
              only-icon
              size="small"
              type="primary"
              @click="confirmAction('anonymize', data)"
            />

            <!-- Delete (platform admin, not self, not anonymous) -->
            <BaseButton
              v-if="viewer.isPlatformAdmin && data.id !== viewer.id && !data.isAnonymous && !data.isAdmin"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger"
              @click="confirmAction('delete_user', data)"
            />

            <!-- Assign sessions (session manager) -->
            <BaseButton
              v-if="!viewer.isSessionAdmin && data.isSessionManager"
              :label="t('Assign sessions')"
              :to-url="`/main/admin/dashboard_add_sessions_to_user.php?user=${data.id}`"
              icon="sessions"
              only-icon
              size="small"
              type="primary"
            />

            <!-- Assign users (HR / admin / student boss, not session manager) -->
            <template v-if="!viewer.isSessionAdmin && !data.isSessionManager">
              <BaseButton
                v-if="data.isHR || data.isAdmin || data.isStudentBoss"
                :label="t('Assign users')"
                :to-url="`/main/admin/dashboard_add_users_to_user.php?user=${data.id}`"
                icon="assign-users"
                only-icon
                size="small"
                type="primary"
              />

              <!-- Assign courses (HR / admin) -->
              <BaseButton
                v-if="data.isHR || data.isAdmin"
                :label="t('Assign courses')"
                :to-url="`/main/admin/dashboard_add_courses_to_user.php?user=${data.id}`"
                icon="courses"
                only-icon
                size="small"
                type="primary"
              />

              <!-- Assign sessions (HR / admin) -->
              <BaseButton
                v-if="data.isHR || data.isAdmin"
                :label="t('Assign sessions')"
                :to-url="`/main/admin/dashboard_add_sessions_to_user.php?user=${data.id}`"
                icon="sessions"
                only-icon
                size="small"
                type="primary"
              />
            </template>
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- Bulk actions toolbar -->
    <div
      v-if="selectedItems.length > 0 && view !== 'deleted' && viewer.isPlatformAdmin"
      class="flex items-center gap-4"
    >
      <span class="text-sm text-gray-600">{{ selectedItems.length }} {{ t("selected") }}</span>
      <BaseButton
        :label="t('Remove from portal')"
        icon="delete"
        size="small"
        type="danger"
        @click="confirmBulkAction('delete_users')"
      />
      <BaseButton
        :label="t('Disable')"
        icon="lock"
        size="small"
        type="black"
        @click="confirmBulkAction('disable_users')"
      />
      <BaseButton
        :label="t('Enable')"
        icon="unlock"
        size="small"
        type="primary"
        @click="confirmBulkAction('enable_users')"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseAppLink from "../../components/basecomponents/BaseAppLink.vue"
import baseService from "../../services/baseService"

const { t } = useI18n()

const items = ref([])
const selectedItems = ref([])
const total = ref(0)
const isLoading = ref(false)
const page = ref(1)
const pageSize = ref(20)
const sortField = ref("lastname")
const sortOrder = ref(1)
const view = ref("all")

const simpleKeyword = ref("")
const showAdvanced = ref(false)

const advancedFilters = reactive({
  keyword_firstname: "",
  keyword_lastname: "",
  keyword_username: "",
  keyword_email: "",
  keyword_officialcode: "",
  keyword_roles: [],
  keyword_active: true,
  keyword_inactive: true,
  check_easy_passwords: false,
})

const viewer = reactive({ id: 0, isPlatformAdmin: false, isSessionAdmin: false })
const roleLabelsMap = ref({})
const csrfToken = ref("")
const loginAsToken = ref("")

const roleOptions = {
  ROLE_STUDENT: "Learner",
  ROLE_TEACHER: "Teacher",
  ROLE_HR: "Human Resources Manager",
  ROLE_SESSION_MANAGER: "Session administrator",
  ROLE_STUDENT_BOSS: "Superior (n+1)",
  ROLE_INVITEE: "Invitee",
  ROLE_QUESTION_MANAGER: "Question manager",
  ROLE_PLATFORM_ADMIN: "Administrator",
}

function formatRole(role) {
  const upper = role.toUpperCase()
  const label = roleLabelsMap.value[upper] || roleLabelsMap.value[role]
  if (label) return label
  return role
    .replace(/^ROLE_/, "")
    .replace(/_/g, " ")
    .toLowerCase()
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

function canToggleActive(data) {
  return data.id !== viewer.id && (data.active === 0 || data.active === 1)
}

async function toggleActive(data) {
  const newStatus = data.active === 1 ? 0 : 1
  const msg =
    data.active === 1 ? t("Are you sure you want to lock this user?") : t("Are you sure you want to unlock this user?")

  if (!confirm(msg)) return

  try {
    const res = await fetch(`/main/inc/ajax/user_manager.ajax.php?a=active_user&user_id=${data.id}&status=${newStatus}`)
    const text = await res.text()
    data.active = text.trim() === "1" ? 1 : 0
  } catch (e) {
    console.error(e)
  }
}

function showLoginAs(data) {
  return viewer.isPlatformAdmin || viewer.isSessionAdmin
}

function canLoginAs(data) {
  if (data.isAnonymous) return false
  if (data.id === viewer.id) return false
  if (viewer.isPlatformAdmin) return true
  // Session admins can login as students (and optionally teachers via setting)
  if (viewer.isSessionAdmin && data.isStudent) return true
  return false
}

function confirmAction(action, data) {
  if (!confirm(t("Please confirm your choice"))) return

  const form = document.createElement("form")
  form.method = "POST"
  form.action = `/admin/user-list-action`

  const fields = { action, user_id: data.id, view: view.value, _token: csrfToken.value }
  for (const [k, v] of Object.entries(fields)) {
    const input = document.createElement("input")
    input.type = "hidden"
    input.name = k
    input.value = v
    form.appendChild(input)
  }
  document.body.appendChild(form)
  form.submit()
}

async function confirmBulkAction(action) {
  if (!confirm(t("Please confirm your choice"))) return

  try {
    const formData = new URLSearchParams()
    formData.set("action", action)
    formData.set("_token", csrfToken.value)
    selectedItems.value.forEach((item) => formData.append("user_ids[]", String(item.id)))

    await fetch("/admin/user-list-action", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString(),
    })

    selectedItems.value = []
    load()
  } catch (e) {
    console.error("Error performing bulk action:", e)
  }
}

async function load() {
  isLoading.value = true
  try {
    const params = new URLSearchParams({
      page: String(page.value),
      limit: String(pageSize.value),
      sortField: sortField.value,
      sortOrder: sortOrder.value === 1 ? "ASC" : "DESC",
      view: view.value,
    })

    if (showAdvanced.value) {
      for (const [key, val] of Object.entries(advancedFilters)) {
        if (key === "keyword_roles" && val.length > 0) {
          val.forEach((r) => params.append("keyword_roles[]", r))
        } else if (key === "keyword_active" && val) {
          params.set("keyword_active", "1")
        } else if (key === "keyword_inactive" && val) {
          params.set("keyword_inactive", "1")
        } else if (key === "check_easy_passwords" && val) {
          params.set("check_easy_passwords", "1")
        } else if (typeof val === "string" && val) {
          params.set(key, val)
        }
      }
    } else if (simpleKeyword.value) {
      params.set("keyword", simpleKeyword.value)
    }

    const data = await baseService.get(`/admin/user-list-data?${params.toString()}`)
    items.value = data.items
    total.value = data.total
    if (data.viewer) {
      viewer.id = data.viewer.id
      viewer.isPlatformAdmin = data.viewer.isPlatformAdmin
      viewer.isSessionAdmin = data.viewer.isSessionAdmin
    }
    if (data.roleLabels) {
      roleLabelsMap.value = data.roleLabels
    }
    if (data.csrfToken) {
      csrfToken.value = data.csrfToken
    }
    if (data.loginAsToken) {
      loginAsToken.value = data.loginAsToken
    }
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  load()
}

function onSort(event) {
  sortField.value = event.sortField ?? "lastname"
  sortOrder.value = event.sortOrder ?? 1
  page.value = 1
  load()
}

function onSearch() {
  page.value = 1
  load()
}

function onAdvancedSearch() {
  page.value = 1
  load()
}

function switchView(newView) {
  view.value = newView
  page.value = 1
  load()
}

onMounted(load)
</script>
