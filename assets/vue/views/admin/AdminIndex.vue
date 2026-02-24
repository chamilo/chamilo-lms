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
    <!-- Main admin blocks -->
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
      :description="t('Create course packages for a certain time with training sessions')"
      :editable="blockSessions.editable"
      :items="blockSessions.items"
      :search-url="blockSessions.searchUrl"
      :title="t('Sessions management')"
      icon="sessions"
    />

    <AdminBlock
      v-if="blockPlatform"
      :id="blockPlatform.id"
      v-model:extra-content="blockPlatform.extraContent"
      :description="t('Configure your platform, view reports, publish and send announcements globally')"
      :editable="blockPlatform.editable"
      :items="blockPlatform.items"
      :search-url="blockPlatform.searchUrl"
      :title="t('Platform management')"
      icon="admin-settings"
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
      :description="t('Manage the skills of your users, through courses and badges')"
      :editable="blockSkills.editable"
      :items="blockSkills.items"
      :title="t('Skills')"
      icon="gradebook"
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

    <AdminBlock
      v-if="blockRooms"
      :id="blockRooms.id"
      v-model:extra-content="blockRooms.extraContent"
      :editable="blockRooms.editable"
      :items="blockRooms.items"
      :title="t('Rooms')"
      icon="room"
    />

    <AdminBlock
      v-if="blockSecurity"
      :id="blockSecurity.id"
      v-model:extra-content="blockSecurity.extraContent"
      :description="t('Security tools and reports')"
      :editable="blockSecurity.editable"
      :items="blockSecurity.items"
      :title="t('Security')"
      icon="shield-check"
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
      v-if="blockPlugins.items.length > 0"
      :id="blockPlugins.id"
      :items="blockPlugins.items"
      :title="t('Plugins')"
      icon="plugin"
    />

    <AdminBlock
      v-if="blockChamilo"
      :id="blockChamilo.id"
      v-model:extra-content="blockChamilo.extraContent"
      :description="t('Learn more about Chamilo and its use, official references links')"
      :editable="blockChamilo.editable"
      :items="blockChamilo.items"
      icon="promotion"
      title="Chamilo.org"
    />

    <!-- Small / secondary blocks: sent to the bottom -->
    <AdminBlock
      v-if="blockHealthCheck && blockHealthCheck.items.length > 0"
      :id="blockHealthCheck.id"
      :items="blockHealthCheck.items"
      :title="t('Health check')"
      icon="health-check"
    />

    <div
      v-if="securityStore.isAdmin"
      class="p-card p-component block-admin-version admin-index__block-container"
    >
      <div class="p-card-body">
        <h4><i class="mdi mdi-checkbox-multiple-marked text-xl" /> {{t('Version check')}}</h4>

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
                v-text="t('Hide campus from public platforms list')"
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

    <div
      v-if="securityStore.isAdmin"
      class="p-card p-component block-admin-support admin-index__block-container"
    >
      <div class="p-card-body">
        <h4><i class="mdi mdi-face-agent text-xl" /> {{t('Professional support')}}</h4>

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
      class="p-card p-component block-admin-news admin-index__block-container"
    >
      <div class="p-card-body">
        <h4><i class="mdi mdi-bullhorn text-xl" /> {{t('News from Chamilo')}}</h4>

        <div
          v-if="blockNewsStatusEl"
          class="block-admin-news__status"
          v-html="blockNewsStatusEl"
        />
        <div
          v-else
          v-text="t('Disabled')"
          class="block-admin-news__status"
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
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"

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
  blockSecurity,
  loadBlocks,
  blockNewsStatusEl,
  blockSupportStatusEl,
  blockPlugins,
  blockHealthCheck,
  blockRooms,
} = useIndexBlocks()

function checkVersionOnSubmit() {
  checkVersion(doNotListCampus.value)
}

const isLoadingBlocks = ref(true)

loadBlocks().then(() => {
  isLoadingBlocks.value = false
})
</script>
