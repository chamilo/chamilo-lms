<template>
  <div class="p-6 space-y-6">
    <Card>
      <template #header>
        <img
          :src="course.illustrationUrl || PLACEHOLDER"
          :alt="course.title"
          class="w-full h-40 object-cover rounded-t-xl"
        />
      </template>

      <template #title>
        <h2 class="text-xl font-semibold">{{ course.title }}</h2>
        <p class="text-sm text-gray-500">{{ course.code }}</p>
      </template>

      <template #content>
        <p class="text-gray-700">
          {{ course.description || t("No description available") }}
        </p>
      </template>
    </Card>

    <div class="bg-white p-4 rounded-xl shadow space-y-4">
      <h3 class="text-lg font-medium">{{ t("Search student") }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <InputText
          v-model="form.lastname"
          :placeholder="t('Last name')"
          class="w-full"
        />
        <InputText
          v-model="form.firstname"
          :placeholder="t('First name')"
          class="w-full"
        />
        <InputText
          v-if="extraFieldKey"
          v-model="dynamicExtraField"
          :placeholder="extraFieldKey"
          class="w-full"
        />
      </div>

      <div class="flex items-center gap-4 pt-2">
        <Button
          :label="t('Search student')"
          icon="pi pi-search"
          @click="searchStudent"
        />
        <Button
          :label="t('Create new user')"
          icon="pi pi-user-plus"
          text
          @click="showCreateModal = true"
        />
      </div>

      <div
        v-if="matches.length"
        class="mt-6 border-t pt-4 space-y-3"
      >
        <h4 class="font-medium text-gray-700">{{ t("Matching students") }} ({{ matches.length }})</h4>
        <ul class="divide-y">
          <li
            v-for="user in matches"
            :key="user.id"
            class="flex items-center justify-between py-2"
          >
            <div class="flex items-center gap-3">
              <img
                :src="user.illustrationUrl || '/img/icons/32/unknown.png'"
                :alt="user.fullname"
                class="w-10 h-10 rounded-full object-cover border"
              />
              <div>
                <p class="text-sm font-medium">{{ user.fullname }}</p>
                <p class="text-xs text-gray-500">{{ user.email }}</p>
              </div>
            </div>

            <Button
              icon="pi pi-send"
              size="small"
              :label="t('Send course')"
              @click="sendCourseTo(user)"
            />
          </li>
        </ul>
      </div>

      <Message
        v-else-if="searchAttempted"
        severity="error"
      >
        {{ t("User not found.") }}
      </Message>
    </div>

    <Dialog
      v-model:visible="showCreateModal"
      modal
      :header="t('Create new user')"
      :style="{ width: '30rem' }"
    >
      <div class="space-y-4">
        <InputText
          v-model="createForm.firstname"
          :placeholder="t('First name')"
          class="w-full"
        />
        <InputText
          v-model="createForm.lastname"
          :placeholder="t('Last name')"
          class="w-full"
        />
        <InputText
          v-model="createForm.email"
          type="email"
          :placeholder="t('Email')"
          class="w-full"
        />
        <InputText
          v-model="createForm.password"
          :placeholder="t('Password (leave blank to auto)')"
          class="col-span-2"
        />
        <InputText
          v-if="extraFieldKey"
          v-model="createForm[extraFieldKey]"
          :placeholder="extraFieldKey"
          class="w-full"
        />
        <div class="flex justify-end gap-2">
          <Button
            text
            :label="t('Cancel')"
            @click="showCreateModal = false"
          />
          <Button
            icon="pi pi-check"
            :label="t('Save')"
            :disabled="createLoading"
            @click="handleCreateUser"
          />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, watch } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import Card from "primevue/card"
import InputText from "primevue/inputtext"
import Button from "primevue/button"
import Dialog from "primevue/dialog"
import Message from "primevue/message"
import userService from "../../services/userService"
import courseService from "../../services/courseService"
import sessionService from "../../services/sessionService"
import { useNotification } from "../../composables/notification"
import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()
const { showErrorNotification, showSuccessNotification } = useNotification()
const route = useRoute()
const courseId = parseInt(route.params.courseId, 10)
const PLACEHOLDER = "/img/session_default.svg"
const dynamicExtraField = ref("")
const platformConfigStore = usePlatformConfig()
const extraFieldKey = platformConfigStore.getSetting(
  "platform.session_admin_user_subscription_search_extra_field_to_search",
)

const course = ref({
  title: "Loading...",
  code: "",
  description: "",
  illustrationUrl: null,
})

const createForm = ref({
  firstname: "",
  lastname: "",
  email: "",
  password: "",
  accessUrlId: 1,
  [extraFieldKey]: "",
})

const createLoading = ref(false)

loadCourse()
async function loadCourse() {
  try {
    course.value = await courseService.findCourseForSessionAdmin(courseId)
  } catch (e) {
    console.error("[RegisterStudent] course load failed:", e)
  }
}

const form = ref({ lastname: "", firstname: "", tempId: "" })
const student = ref(null)
const searchAttempted = ref(false)
const showCreateModal = ref(false)
const matches = ref([])

async function searchStudent() {
  searchAttempted.value = false
  student.value = null
  matches.value = []

  const filters = {
    lastname: form.value.lastname,
    firstname: form.value.firstname,
    pagination: false,
  }

  if (extraFieldKey && dynamicExtraField.value) {
    filters[`extraFilters[${extraFieldKey}]`] = dynamicExtraField.value
  }

  try {
    const response = await userService.findUsersForSessionAdmin(filters)
    const items = response.items

    if (items && items.length > 0) {
      matches.value = items
    } else {
      searchAttempted.value = true
    }
  } catch (e) {
    console.error("[RegisterStudent] search error:", e)
    searchAttempted.value = true
  }
}

async function sendCourseTo(user) {
  const today = new Date()
  const oneWeekLater = new Date(today)
  oneWeekLater.setDate(today.getDate() + 7)

  const payload = {
    title: `${user.lastname} ${user.firstname} (${user.extra?.[extraFieldKey] || dynamicExtraField.value}) ${course.value.title}`,
    accessStartDate: today.toISOString(),
    accessEndDate: oneWeekLater.toISOString(),
    displayStartDate: today.toISOString(),
    displayEndDate: oneWeekLater.toISOString(),
    coachAccessStartDate: today.toISOString(),
    coachAccessEndDate: oneWeekLater.toISOString(),
    visibility: 1,
    duration: 0,
    showDescription: false,
    validityInDays: 0,
    courseIds: [courseId],
    studentIds: [user.id],
    tutorIds: [],
  }

  try {
    const session = await sessionService.createWithCoursesAndUsers(payload)
    await sessionService.sendCourseNotification(session.id, user.id)

    showSuccessNotification(`${t("Course sent to")} ${user.email}`)
  } catch (e) {
    console.error(e)
    showErrorNotification(t("An error occurred while sending the course."))
  }
}

async function handleCreateUser() {
  if (createLoading.value) return
  createLoading.value = true

  const pwd = createForm.value.password || randomPassword()
  const username = slugify(`${createForm.value.firstname}.${createForm.value.lastname}`)

  const payload = {
    username,
    email: createForm.value.email,
    firstname: createForm.value.firstname,
    lastname: createForm.value.lastname,
    password: pwd,
    accessUrlId: createForm.value.accessUrlId,
  }

  if (extraFieldKey && createForm.value[extraFieldKey]) {
    payload.extraFields = {
      [extraFieldKey]: createForm.value[extraFieldKey],
    }
  }

  try {
    const newUser = await userService.createOnAccessUrl(payload)
    if (extraFieldKey && createForm.value[extraFieldKey]) {
      newUser.extra = {
        [extraFieldKey]: createForm.value[extraFieldKey],
      }
    } else {
      newUser.extra = {}
    }

    showCreateModal.value = false
    showSuccessNotification(t("User created") + `: ${newUser.email}\n${t("Password")}: ${pwd}`)
    matches.value.unshift(newUser)
  } catch (e) {
    console.error(e)
    showErrorNotification(t("Could not create user"))
  } finally {
    createLoading.value = false
  }
}

watch(showCreateModal, (val) => {
  if (val) {
    createForm.value = {
      firstname: "",
      lastname: "",
      email: "",
      password: "",
      accessUrlId: window.access_url_id,
    }
  }
})

function slugify(str) {
  return str
    .toString()
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, ".")
    .replace(/[^a-z0-9.]/g, "")
}

function randomPassword(len = 10) {
  return Math.random().toString(36).slice(-len)
}
</script>
