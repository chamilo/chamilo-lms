<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import lpService from "../../services/lpService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const isSaving = ref(false)
const errorMessage = ref("")
const search = ref("")
const lp = ref(null)
const course = ref(null)
const session = ref(null)
const users = ref([])
const groups = ref([])
const selectedUserId = ref(null)
const selectedGroupId = ref(null)
const form = ref({
  startDate: "",
  endDate: "",
  isOpenWithoutDate: true,
})

const lpId = computed(() => Number(route.query.lp_id || 0))
const cid = computed(() => Number(route.query.cid || 0))
const sid = computed(() => Number(route.query.sid || 0))
const contextQuery = computed(() => `cid=${cid.value}&sid=${sid.value}`)

const filteredUsers = computed(() => {
  const query = normalizeSearch(search.value)

  if (!query) {
    return users.value
  }

  return users.value.filter((user) =>
    [
      user.firstname,
      user.lastname,
      user.username,
      user.email,
      `${user.firstname} ${user.lastname}`,
      `${user.lastname} ${user.firstname}`,
      ...(user.groups || []).map((group) => group.title),
    ]
      .filter(Boolean)
      .some((value) => normalizeSearch(value).includes(query)),
  )
})

const filteredGroups = computed(() => {
  const query = normalizeSearch(search.value)

  if (!query) {
    return groups.value
  }

  return groups.value.filter((group) => normalizeSearch(group.title).includes(query))
})

const selectedUser = computed(() => users.value.find((user) => Number(user.id) === Number(selectedUserId.value)) || null)
const selectedGroup = computed(() => groups.value.find((group) => Number(group.id) === Number(selectedGroupId.value)) || null)

const dateRangeError = computed(() => {
  if (form.value.isOpenWithoutDate || !form.value.startDate || !form.value.endDate) {
    return ""
  }

  const startDate = new Date(form.value.startDate)
  const endDate = new Date(form.value.endDate)

  if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
    return t("Invalid date")
  }

  return startDate > endDate ? t("The end date must be after the start date.") : ""
})

const canSaveRestriction = computed(() => Boolean(selectedUser.value || selectedGroup.value) && !dateRangeError.value && !isSaving.value)

function normalizeSearch(value) {
  return String(value || "")
    .trim()
    .toLocaleLowerCase()
}

function userFullName(user) {
  return [user.firstname, user.lastname].filter(Boolean).join(" ") || user.username
}

function formatRestriction(restriction) {
  if (!restriction) {
    return "-"
  }

  if (restriction.isOpenWithoutDate) {
    return t("Always visible")
  }

  const start = restriction.startDate ? restriction.startDate.replace("T", " ") : t("No start date")
  const end = restriction.endDate ? restriction.endDate.replace("T", " ") : t("No end date")

  return `${start} → ${end}`
}

function effectiveStatus(user) {
  if (user.individualRestriction) {
    return {
      label: formatRestriction(user.individualRestriction),
      className: "bg-success/10 text-success",
    }
  }

  if (user.groupRestriction) {
    return {
      label: formatRestriction(user.groupRestriction),
      className: "bg-support-5/10 text-support-5",
    }
  }

  return {
    label: t("No restriction"),
    className: "bg-gray-15 text-gray-60",
  }
}

function selectUser(user) {
  selectedGroupId.value = null
  selectedUserId.value = user.id
  loadRestrictionIntoForm(user.individualRestriction)
}

function selectGroup(group) {
  selectedUserId.value = null
  selectedGroupId.value = group.id
  loadRestrictionIntoForm(group.restriction)
}

function loadRestrictionIntoForm(restriction) {
  form.value = {
    startDate: restriction?.startDate || "",
    endDate: restriction?.endDate || "",
    isOpenWithoutDate: restriction ? Boolean(restriction.isOpenWithoutDate) : true,
  }
}

async function loadData() {
  if (!lpId.value || !cid.value) {
    errorMessage.value = t("Missing learning path or course identifier")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await lpService.getAdvancedAccessData(lpId.value, contextQuery.value)

    lp.value = data.lp
    course.value = data.course
    session.value = data.session
    users.value = data.users || []
    groups.value = data.groups || []
  } catch (error) {
    errorMessage.value = error?.response?.data?.error || t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

async function saveSelectedRestriction() {
  if (!selectedUser.value && !selectedGroup.value) {
    errorMessage.value = t("Select a user or a group first")
    return
  }

  if (dateRangeError.value) {
    errorMessage.value = dateRangeError.value
    return
  }

  isSaving.value = true
  errorMessage.value = ""

  try {
    if (selectedUser.value) {
      await lpService.saveUserAdvancedAccess(lpId.value, contextQuery.value, {
        userId: selectedUser.value.id,
        ...form.value,
      })
    } else {
      await lpService.saveGroupAdvancedAccess(lpId.value, contextQuery.value, {
        groupId: selectedGroup.value.id,
        ...form.value,
      })
    }

    await loadData()
  } catch (error) {
    errorMessage.value = error?.response?.data?.error || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

async function removeUserRestriction(user) {
  isSaving.value = true
  errorMessage.value = ""

  try {
    await lpService.removeUserAdvancedAccess(lpId.value, user.id, contextQuery.value)
    await loadData()
  } catch (error) {
    errorMessage.value = error?.response?.data?.error || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

async function removeGroupRestriction(group) {
  isSaving.value = true
  errorMessage.value = ""

  try {
    await lpService.removeGroupAdvancedAccess(lpId.value, group.id, contextQuery.value)
    await loadData()
  } catch (error) {
    errorMessage.value = error?.response?.data?.error || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

async function clearDates() {
  if (!window.confirm(t("Remove all date restrictions for this learning path?"))) {
    return
  }

  isSaving.value = true
  errorMessage.value = ""

  try {
    await lpService.clearAdvancedAccessDates(lpId.value, contextQuery.value)
    await loadData()
  } catch (error) {
    errorMessage.value = error?.response?.data?.error || t("An error occurred")
  } finally {
    isSaving.value = false
  }
}

function goBack() {
  router.push({
    name: "LpList",
    params: {
      node: route.params.node,
    },
    query: {
      cid: cid.value,
      sid: sid.value,
    },
  })
}

onMounted(loadData)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Advanced learning path access')">
      <BaseButton
        :label="t('Back to learning paths')"
        icon="back"
        type="plain"
        @click="goBack"
      />
    </SectionHeader>

    <div
      v-if="errorMessage"
      class="rounded-xl border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-25 bg-white p-6 text-gray-60 shadow-sm"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else>
      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">
              {{ t("Learning path") }}
            </p>
            <h2 class="mt-1 text-2xl font-bold text-gray-90">
              {{ lp?.title }}
            </h2>
            <p class="mt-2 text-sm text-gray-60">
              {{ course?.title }}
              <span v-if="session">· {{ session.title }}</span>
            </p>
          </div>

          <BaseButton
            :disabled="isSaving"
            :label="t('Remove date restrictions')"
            icon="delete"
            type="danger"
            @click="clearDates"
          />
        </div>
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <label
          for="lp-advanced-access-search"
          class="mb-2 block text-sm font-semibold text-gray-90"
        >
          {{ t("Search") }}
        </label>
        <input
          id="lp-advanced-access-search"
          v-model="search"
          class="w-full rounded-xl border border-gray-30 bg-white px-4 py-2 text-sm text-gray-90 focus:border-primary focus:outline-none"
          type="search"
          :placeholder="t('Search by name, email, username or group')"
        />
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-gray-90">
          {{ selectedUser ? t("Edit user restriction") : selectedGroup ? t("Edit group restriction") : t("Select a user or group") }}
        </h2>

        <div
          v-if="selectedUser || selectedGroup"
          class="mb-4 rounded-xl bg-primary/10 px-4 py-3 text-sm font-semibold text-primary"
        >
          {{ selectedUser ? userFullName(selectedUser) : selectedGroup.title }}
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <label class="flex flex-col gap-2 text-sm font-semibold text-gray-90">
            {{ t("Start date") }}
            <input
              v-model="form.startDate"
              :disabled="form.isOpenWithoutDate"
              class="rounded-xl border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90 disabled:bg-gray-15 disabled:text-gray-50"
              type="datetime-local"
            />
          </label>

          <label class="flex flex-col gap-2 text-sm font-semibold text-gray-90">
            {{ t("End date") }}
            <input
              v-model="form.endDate"
              :disabled="form.isOpenWithoutDate"
              class="rounded-xl border border-gray-30 bg-white px-3 py-2 text-sm text-gray-90 disabled:bg-gray-15 disabled:text-gray-50"
              type="datetime-local"
            />
          </label>

          <label class="flex items-center gap-2 pt-7 text-sm font-semibold text-gray-90">
            <input
              v-model="form.isOpenWithoutDate"
              class="h-4 w-4 rounded border-gray-30 text-primary focus:ring-primary"
              type="checkbox"
            />
            {{ t("Always visible") }}
          </label>
        </div>

        <p
          v-if="dateRangeError"
          class="mt-3 rounded-xl border border-warning/20 bg-warning/10 px-4 py-3 text-sm font-semibold text-warning"
        >
          {{ dateRangeError }}
        </p>

        <div class="mt-4 flex justify-end">
          <BaseButton
            :disabled="!canSaveRestriction"
            :label="isSaving ? t('Saving') : t('Save restriction')"
            icon="save"
            type="primary"
            @click="saveSelectedRestriction"
          />
        </div>
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-90">
            {{ t("Users") }}
          </h2>
          <span class="rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
            {{ filteredUsers.length }} / {{ users.length }}
          </span>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-25">
          <div class="hidden grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto] gap-3 border-b border-gray-25 bg-gray-15 px-4 py-3 text-sm font-bold text-gray-70 md:grid">
            <div>{{ t("Name") }}</div>
            <div>{{ t("Email") }}</div>
            <div>{{ t("Groups") }}</div>
            <div>{{ t("Visibility") }}</div>
            <div>{{ t("Actions") }}</div>
          </div>

          <div
            v-for="user in filteredUsers"
            :key="user.id"
            class="grid gap-3 border-b border-gray-25 px-4 py-3 last:border-b-0 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1.2fr)_auto] md:items-center"
          >
            <div>
              <div class="font-semibold text-gray-90">{{ userFullName(user) }}</div>
              <div class="text-xs text-gray-50">{{ user.username }}</div>
            </div>
            <div class="break-all text-sm text-gray-60">{{ user.email }}</div>
            <div class="text-sm text-gray-60">
              <span v-if="user.groups?.length">
                {{ user.groups.map((group) => group.title).join(", ") }}
              </span>
              <span v-else>-</span>
            </div>
            <div>
              <span
                :class="effectiveStatus(user).className"
                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
              >
                {{ effectiveStatus(user).label }}
              </span>
            </div>
            <div class="flex justify-end gap-2">
              <BaseButton
                :label="t('Edit')"
                icon="edit"
                only-icon
                size="small"
                type="secondary"
                @click="selectUser(user)"
              />
              <BaseButton
                :disabled="!user.individualRestriction || isSaving"
                :label="t('Remove')"
                icon="delete"
                only-icon
                size="small"
                type="danger"
                @click="removeUserRestriction(user)"
              />
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-gray-90">
            {{ t("Groups") }}
          </h2>
          <span class="rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
            {{ filteredGroups.length }} / {{ groups.length }}
          </span>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <article
            v-for="group in filteredGroups"
            :key="group.id"
            class="rounded-xl border border-gray-25 bg-white p-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <h3 class="font-bold text-gray-90">{{ group.title }}</h3>
                <p class="text-sm text-gray-60">
                  {{ group.membersCount }} {{ t("members") }}
                </p>
                <p class="mt-2 text-sm text-gray-70">
                  {{ formatRestriction(group.restriction) }}
                </p>
              </div>

              <div class="flex gap-2">
                <BaseButton
                  :label="t('Edit')"
                  icon="edit"
                  only-icon
                  size="small"
                  type="secondary"
                  @click="selectGroup(group)"
                />
                <BaseButton
                  :disabled="!group.restriction || isSaving"
                  :label="t('Remove')"
                  icon="delete"
                  only-icon
                  size="small"
                  type="danger"
                  @click="removeGroupRestriction(group)"
                />
              </div>
            </div>
          </article>
        </div>
      </section>
    </template>
  </div>
</template>
