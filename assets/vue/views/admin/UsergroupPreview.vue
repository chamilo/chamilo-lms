<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import usergroupAdminService from "../../services/usergroupAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref("")
const search = ref("")
const group = ref(null)
const users = ref([])
const courses = ref([])

const usergroupId = computed(() => Number(route.params.id || 0))

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
    ]
      .filter(Boolean)
      .some((value) => normalizeSearch(value).includes(query)),
  )
})

const filteredCourses = computed(() => {
  const query = normalizeSearch(search.value)

  if (!query) {
    return courses.value
  }

  return courses.value.filter((course) =>
    [course.title, course.code, course.visualCode]
      .filter(Boolean)
      .some((value) => normalizeSearch(value).includes(query)),
  )
})

function normalizeSearch(value) {
  return String(value || "")
    .trim()
    .toLocaleLowerCase()
}

function userFullName(user) {
  return [user.firstname, user.lastname].filter(Boolean).join(" ")
}

function courseLabel(course) {
  const code = course.visualCode || course.code

  if (!code) {
    return course.title
  }

  return `[${code}] - ${course.title}`
}

async function loadData() {
  if (!usergroupId.value) {
    errorMessage.value = t("Class not found")
    return
  }

  isLoading.value = true
  errorMessage.value = ""

  try {
    const data = await usergroupAdminService.preview(usergroupId.value)

    group.value = data.group
    users.value = data.users || []
    courses.value = data.courses || []
  } catch {
    errorMessage.value = t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function goBack() {
  router.push({ name: "AdminUsergroupList" })
}

onMounted(loadData)
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="group?.title || t('Class overview')">
      <BaseButton
        :label="t('Back to classes')"
        icon="back"
        type="plain"
        @click="goBack"
      />
    </SectionHeader>

    <div
      v-if="errorMessage"
      class="rounded-lg border border-danger/20 bg-danger/10 px-4 py-3 text-sm text-danger"
    >
      {{ errorMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-25 bg-white p-6 text-gray-60 shadow-sm"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="group">
      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h2 class="text-xl font-semibold text-gray-90">
              {{ group.title }}
            </h2>
            <p
              v-if="group.description"
              class="mt-2 max-w-3xl text-sm text-gray-60"
            >
              {{ group.description }}
            </p>
          </div>

          <div class="grid grid-cols-2 gap-3 text-sm md:grid-cols-3">
            <div class="rounded-xl bg-primary/10 px-4 py-3 text-primary">
              <div class="text-2xl font-bold">{{ users.length }}</div>
              <div class="font-medium">{{ t("Members") }}</div>
            </div>
            <div class="rounded-xl bg-primary/10 px-4 py-3 text-primary">
              <div class="text-2xl font-bold">{{ courses.length }}</div>
              <div class="font-medium">{{ t("Courses") }}</div>
            </div>
          </div>
        </div>
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <label
          for="usergroup-preview-search"
          class="mb-2 block text-sm font-semibold text-gray-90"
        >
          {{ t("Search members or courses") }}
        </label>
        <input
          id="usergroup-preview-search"
          v-model="search"
          type="search"
          class="w-full rounded-xl border border-gray-30 bg-white px-4 py-2 text-sm text-gray-90 focus:border-primary focus:outline-none"
          :placeholder="t('Search by name, email, username or course title')"
        />
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Class members") }}
          </h2>
          <span class="rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
            {{ filteredUsers.length }} / {{ users.length }}
          </span>
        </div>

        <div
          v-if="filteredUsers.length"
          class="overflow-hidden rounded-xl border border-gray-25"
        >
          <div
            v-for="user in filteredUsers"
            :key="user.id"
            class="grid gap-2 border-b border-gray-25 px-4 py-3 last:border-b-0 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_minmax(0,1fr)] md:items-center"
          >
            <div class="font-semibold text-gray-90">
              {{ userFullName(user) || user.username }}
            </div>
            <div class="text-sm text-gray-60">
              {{ user.email || t("No email") }}
            </div>
            <div class="text-sm text-gray-50 md:text-right">
              {{ user.username }}
            </div>
          </div>
        </div>

        <p
          v-else
          class="rounded-xl bg-gray-15 px-4 py-3 text-sm text-gray-60"
        >
          {{ t("No members found") }}
        </p>
      </section>

      <section class="rounded-2xl border border-gray-25 bg-white p-6 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
          <h2 class="text-lg font-semibold text-gray-90">
            {{ t("Courses subscribed to the class") }}
          </h2>
          <span class="rounded-full bg-primary px-3 py-1 text-xs font-semibold text-white">
            {{ filteredCourses.length }} / {{ courses.length }}
          </span>
        </div>

        <div
          v-if="filteredCourses.length"
          class="overflow-hidden rounded-xl border border-gray-25"
        >
          <div
            v-for="course in filteredCourses"
            :key="course.id"
            class="border-b border-gray-25 px-4 py-3 last:border-b-0"
          >
            <div class="font-semibold text-gray-90">
              {{ courseLabel(course) }}
            </div>
            <div
              v-if="course.code && course.visualCode && course.code !== course.visualCode"
              class="mt-1 text-sm text-gray-50"
            >
              {{ course.code }}
            </div>
          </div>
        </div>

        <p
          v-else
          class="rounded-xl bg-gray-15 px-4 py-3 text-sm text-gray-60"
        >
          {{ t("No courses found") }}
        </p>
      </section>
    </template>
  </div>
</template>
