<template>
  <div
    v-if="isLoadingBlocks"
    class="admin-index"
  >
    <Skeleton
      v-for="i in 9"
      :key="`skeleton-${i}`"
      height="30rem"
      shape="rectangle"
    />
  </div>
  <div
    v-else
    class="admin-index"
  >
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

    <div
      v-if="securityStore.isAdmin"
      class="admin-index__block-container block-admin-version"
    >
      <div class="admin-index__block">
        <h4 v-t="'Version check'" />

        <div
          v-if="blockVersionStatusEl"
          class="block-admin-version__status"
          v-html="blockVersionStatusEl"
        />
        <div
          v-else
          class="block-admin-version__form"
        >
          <i18n-t
            class="mb-3"
            keypath="In order to enable the automatic version checking you have to register your portal on chamilo.org. The information obtained by clicking this button is only for internal use and only aggregated data will be publicly available (total number of portals, total number of Chamilo course, total number of Chamilo users, ...) (see {0}). When registering you will also appear on the worldwide list ({1}). If you do not want to appear in this list you have to check the checkbox below. The registration is as easy as it can be: you only have to click this button:"
            tag="p"
          >
            <a
              href="https://stats.chamilo.org/"
              target="_blank"
              v-text="'https://stats.chamilo.org/'"
            />
            <a
              href="https://version.chamilo.org/community.php"
              target="_blank"
              v-text="'https://version.chamilo.org/community.php'"
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
      </div>
    </div>

    <AdminBlock
      v-if="blockPlatform"
      :description="t('Configure your platform, view reports, publish and send announcements globally')"
      :items="blockPlatform.items"
      :search-url="blockPlatform.searchUrl"
      :title="t('Platform management')"
      class="block-admin-platform"
      icon="admin-settings"
    >
      <li
        :aria-label="t('Colors')"
        class="p-menuitem"
        role="menuitem"
      >
        <div class="p-menuitem-content">
          <router-link class="p-menuitem-link" :to="{name: 'AdminConfigurationColors'}">
            <span class="p-menuitem-text" v-text="t('Colors')" />
          </router-link>
        </div>
      </li>
    </AdminBlock>

    <AdminBlock
      v-if="blockChamilo"
      :description="t('Learn more about Chamilo and its use, official references links')"
      :items="blockChamilo.items"
      class="block-admin-chamilo"
      icon="admin-settings"
      title="Chamilo.org"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Button from "primevue/button"
import Checkbox from "primevue/checkbox"
import Skeleton from "primevue/skeleton"
import AdminBlock from "../../components/admin/AdminBlock"

import { useSecurityStore } from "../../store/securityStore"

import { useIndexBlocks } from "../../composables/admin/indexBlocks"

const { t } = useI18n()

const securityStore = useSecurityStore()

const doNotListCampus = ref(false)

const {
  blockVersionStatusEl,
  checkVersion,
  blockUsers,
  blockCourses,
  blockSessions,
  blockGradebook,
  blockSkills,
  blockPrivacy,
  blockSettings,
  blockPlatform,
  blockChamilo,
  loadBlocks,
} = useIndexBlocks()

function checkVersionOnSubmit() {
  checkVersion(doNotListCampus.value)
}

const isLoadingBlocks = ref(true)

loadBlocks().then(() => (isLoadingBlocks.value = false))
</script>
