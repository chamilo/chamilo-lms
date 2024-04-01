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
      :id="blockUsers.id"
      v-model:extra-content="blockUsers.extraContent"
      :description="t('Here you can manage registered users within your platform')"
      :editable="blockUsers.editable"
      :items="blockUsers.items"
      :search-url="blockUsers.searchUrl"
      :title="t('User management')"
      icon="account"
    />

    <AdminBlock
      v-if="blockCourses"
      :id="blockCourses.id"
      v-model:extra-content="blockCourses.extraContent"
      :description="t('Create and manage your courses in a simple way')"
      :editable="blockCourses.editable"
      :items="blockCourses.items"
      :search-url="blockCourses.searchUrl"
      :title="t('Course management')"
      icon="courses"
    />

    <AdminBlock
      v-if="blockSessions"
      :id="blockSessions.id"
      v-model:extra-content="blockSessions.extraContent"
      :editable="blockSessions.editable"
      :description="t('Create course packages for a certain time with training sessions')"
      :items="blockSessions.items"
      :search-url="blockSessions.searchUrl"
      :title="t('Sessions management')"
      icon="sessions"
    />

    <AdminBlock
      v-if="blockGradebook"
      :id="blockGradebook.id"
      v-model:extra-content="blockGradebook.extraContent"
      :editable="blockGradebook.editable"
      :items="blockGradebook.items"
      :title="t('Assessments')"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockSkills"
      :id="blockSkills.id"
      v-model:extra-content="blockSkills.extraContent"
      :editable="blockSkills.editable"
      :description="t('Manage the skills of your users, through courses and badges')"
      :items="blockSkills.items"
      :title="t('Skills')"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockPrivacy"
      :id="blockPrivacy.id"
      v-model:extra-content="blockPrivacy.extraContent"
      :editable="blockPrivacy.editable"
      :items="blockPrivacy.items"
      :title="t('Personal data protection')"
      icon="anonymous"
    />

    <AdminBlock
      v-if="blockSettings"
      :id="blockSettings.id"
      v-model:extra-content="blockSettings.extraContent"
      :description="t('View the status of your server, perform performance tests')"
      :editable="blockSettings.editable"
      :items="blockSettings.items"
      :title="t('System')"
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
      :id="blockPlatform.id"
      v-model:extra-content="blockPlatform.extraContent"
      :editable="blockPlatform.editable"
      :description="t('Configure your platform, view reports, publish and send announcements globally')"
      :items="blockPlatform.items"
      :search-url="blockPlatform.searchUrl"
      :title="t('Platform management')"
      icon="admin-settings"
    />

    <AdminBlock
      v-if="blockChamilo"
      :id="blockChamilo.id"
      v-model:extra-content="blockChamilo.extraContent"
      :editable="blockChamilo.editable"
      :description="t('Learn more about Chamilo and its use, official references links')"
      :items="blockChamilo.items"
      icon="admin-settings"
      title="Chamilo.org"
    />

    <div
      v-if="securityStore.isAdmin"
      class="admin-index__block-container block-admin-support"
    >
      <div class="admin-index__block">
        <h4 v-t="'Professional support'" />

        <div
          v-if="blockSupportStatusEl"
          class="block-admin-support__status"
          v-html="blockSupportStatusEl"
        />
        <div
          v-else
          class="block-admin-news__status"
        >
          <i18n-t
            class="mb-3"
            keypath="Disabled"
            tag="p"
          >
          </i18n-t>
        </div>

      </div>
    </div>

    <div
      v-if="securityStore.isAdmin"
      class="admin-index__block-container block-admin-news"
    >
      <div class="admin-index__block">
        <h4 v-t="'News from Chamilo'" />

        <div
          v-if="blockNewsStatusEl"
          class="block-admin-news__status"
          v-html="blockNewsStatusEl"
        />
        <div
          v-else
          class="block-admin-news__status"
          v-t="'Disabled'"
        />
      </div>
    </div>

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
  blockNewsStatusEl,
  blockSupportStatusEl,
} = useIndexBlocks()

function checkVersionOnSubmit() {
  checkVersion(doNotListCampus.value)
}

const isLoadingBlocks = ref(true)

loadBlocks().then(() => (isLoadingBlocks.value = false))
</script>
