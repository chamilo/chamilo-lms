<template>
  <div class="space-y-4 admin-index">
    <div
      v-if="isLoadingBlocks"
      class="space-y-4"
    >
      <Skeleton
        v-for="i in 9"
        :key="`skeleton-${i}`"
        height="10rem"
      />
    </div>

    <AdminBlock
      v-if="blockUsers"
      :description="t('Here you can manage registered users within your platform')"
      :items="blockUsers.items"
      :search-url="blockUsers.searchUrl"
      :title="t('User management')"
      class="block-admin-users"
      icon="account"
    />

    <AdminBlock
      v-if="blockCourses"
      :description="t('Create and manage your courses in a simple way')"
      :items="blockCourses.items"
      :search-url="blockCourses.searchUrl"
      :title="t('Course management')"
      class="block-admin-courses"
      icon="courses"
    />

    <AdminBlock
      v-if="blockSessions"
      :description="t('Create course packages for a certain time with training sessions')"
      :items="blockSessions.items"
      :search-url="blockSessions.searchUrl"
      :title="t('Sessions management')"
      class="block-admin-sessions"
      icon="sessions"
    />

    <AdminBlock
      v-if="blockGradebook"
      :items="blockGradebook.items"
      :title="t('Assessments')"
      class="block-admin-gradebook"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockSkills"
      :description="t('Manage the skills of your users, through courses and badges')"
      :items="blockSkills.items"
      :title="t('Skills')"
      class="block-admin-skills"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockPrivacy"
      :items="blockPrivacy.items"
      :title="t('Personal data protection')"
      class="block-admin-privacy"
      icon="anonymous"
    />

    <AdminBlock
      v-if="blockSettings"
      :description="t('View the status of your server, perform performance tests')"
      :items="blockSettings.items"
      :title="t('System')"
      class="block-admin-settings"
      icon="settings"
    />

    <AdminBlock
      v-if="blockPlatform"
      :description="t('Configure your platform, view reports, publish and send announcements globally')"
      :items="blockPlatform.items"
      :search-url="blockPlatform.searchUrl"
      :title="t('Platform management')"
      class="block-admin-platform"
      icon="admin-settings"
    />

    <AdminBlock
      v-if="blockChamilo"
      :description="t('Learn more about Chamilo and its use, official references links')"
      :items="blockChamilo.items"
      class="block-admin-chamilo"
      icon="admin-settings"
      title="Chamilo.org"
    />

    <div
      v-if="isAdmin"
      class="block-admin-version p-4 rounded-lg shadow-lg space-y-3"
    >
      <h4 v-t="'Version Check'" />

      <div
        v-if="'false' === platformConfigurationStore.getSetting('platform.registered')"
        class="admin-block-version"
      >
        <i18n-t
          class="mb-3"
          keypath="In order to enable the automatic version checking you have to register your portal on chamilo.org. The information obtained by clicking this button is only for internal use and only aggregated data will be publicly available (total number of portals, total number of Chamilo course, total number of Chamilo users, ...) (see {0}). When registering you will also appear on the worldwide list ({1}). If you do not want to appear in this list you have to check the checkbox below. The registration is as easy as it can be: you only have to click this button:"
          tag="p"
        >
          <a
            href="https://www.chamilo.org/stats/"
            target="_blank"
            v-text="'https://www.chamilo.org/stats/'"
          />
          <a
            href="https://www.chamilo.org/community.php"
            target="_blank"
            v-text="'https://www.chamilo.org/community.php'"
          />
        </i18n-t>

        <form
          id="VersionCheck"
          class="version-checking"
          method="post"
          name="VersionCheck"
          @submit.prevent="checkVersionOnSubmit"
        >
          <div class="field-checkbox">
            <Checkbox
              v-model="doNotListCampus"
              binary
              input-id="checkbox"
              name="donotlistcampus"
            />
            <label
              v-t="'Hide campus from public platforms list'"
              for="checkbox"
            />
          </div>

          <Button
            id="register"
            :label="t('Enable version check')"
            name="Register"
            severity="secondary"
            type="submit"
          />
        </form>
      </div>
      <div
        ref="blockAdminVersionCheck"
        class="block-admin-version_check"
      />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useStore } from "vuex"
import { useToast } from "primevue/usetoast"
import Button from "primevue/button"
import Checkbox from "primevue/checkbox"
import Skeleton from "primevue/skeleton"
import AdminBlock from "../../components/admin/AdminBlock"
import axios from "axios"

import { usePlatformConfig } from "../../store/platformConfig"

const { t } = useI18n()

const store = useStore()
const platformConfigurationStore = usePlatformConfig()

const toast = useToast()

const isAdmin = computed(() => store.getters["security/isAdmin"])

const doNotListCampus = ref(false)

function checkVersionOnSubmit() {
  axios
    .post("/admin/register-campus", {
      donotlistcampus: doNotListCampus.value,
    })
    .then(() =>
      toast.add({
        severity: "success",
        detail: t("Version check enabled"),
      }),
    )
}

const blockAdminVersionCheck = ref()

onMounted(() => {
  if (isAdmin.value) {
    if ("false" === platformConfigurationStore.getSetting("admin.admin_chamilo_announcements_disable")) {
      axios
        .get("/main/inc/ajax/admin.ajax.php?a=get_latest_news")
        .then(({ data }) => toast.add({ severity: "info", detail: data }))
    }

    axios.get("/main/inc/ajax/admin.ajax.php?a=version").then(({ data }) => {
      if (blockAdminVersionCheck.value) {
        blockAdminVersionCheck.value.innerHTML += data
      }
    })
  }
})

const isLoadingBlocks = ref(true)
const blockUsers = ref(null)
const blockCourses = ref(null)
const blockSessions = ref(null)
const blockGradebook = ref(null)
const blockSkills = ref(null)
const blockPrivacy = ref(null)
const blockSettings = ref(null)
const blockPlatform = ref(null)
const blockChamilo = ref(null)

axios.get("/admin/index").then(({ data }) => {
  isLoadingBlocks.value = false

  blockUsers.value = data.users || null
  blockCourses.value = data.courses || null
  blockSessions.value = data.sessions || null
  blockGradebook.value = data.gradebook || null
  blockSkills.value = data.skills || null
  blockPrivacy.value = data.data_privacy || null
  blockSettings.value = data.settings || null
  blockPlatform.value = data.platform || null
  blockChamilo.value = data.chamilo || null
})
</script>