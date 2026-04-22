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
      bg-image="images/bg-block-admin-users.png"
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
      bg-image="images/bg-block-admin-courses.png"
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
      bg-image="images/bg-block-admin-sessions.png"
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
      bg-image="images/bg-block-admin-platform.png"
      icon="admin-settings"
    />

    <AdminBlock
      v-if="blockTracking"
      :id="blockTracking.id"
      v-model:extra-content="blockTracking.extraContent"
      :description="t('View reports, statistics and tracking data')"
      :editable="blockTracking.editable"
      :items="blockTracking.items"
      :title="t('Tracking')"
      bg-image="images/bg-block-admin-tracking.png"
      icon="tracking"
    />

    <AdminBlock
      v-if="blockGradebook"
      :id="blockGradebook.id"
      v-model:extra-content="blockGradebook.extraContent"
      :editable="blockGradebook.editable"
      :items="blockGradebook.items"
      :title="t('Assessments')"
      bg-image="images/bg-block-admin-gradebook.png"
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
      bg-image="images/bg-block-admin-skills.png"
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
      bg-image="images/bg-block-admin-settings.png"
      icon="settings"
    />

    <AdminBlock
      v-if="blockRooms"
      :id="blockRooms.id"
      v-model:extra-content="blockRooms.extraContent"
      :editable="blockRooms.editable"
      :items="blockRooms.items"
      :title="t('Rooms')"
      bg-image="images/bg-block-admin-rooms.png"
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
      bg-image="images/bg-block-admin-security.png"
      icon="shield-check"
    />

    <AdminBlock
      v-if="blockPrivacy"
      :id="blockPrivacy.id"
      v-model:extra-content="blockPrivacy.extraContent"
      :editable="blockPrivacy.editable"
      :items="blockPrivacy.items"
      :title="t('Personal data protection')"
      bg-image="images/bg-block-admin-privacy.png"
      icon="anonymous"
    />

    <AdminBlock
      v-if="blockPlugins.items.length > 0"
      :id="blockPlugins.id"
      :items="blockPlugins.items"
      :title="t('Plugins')"
      bg-image="images/bg-block-plugins.png"
      icon="plugin"
    />

    <AdminBlock
      v-if="blockHealthCheck && blockHealthCheck.items.length > 0"
      :id="blockHealthCheck.id"
      :items="blockHealthCheck.items"
      :title="t('Health check')"
      bg-image="images/bg-block-admin-health-check.png"
      icon="health-check"
    />

    <!-- Small / secondary blocks: sent to the bottom -->
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

    <div
      v-if="securityStore.isAdmin"
      class="p-card p-component block-admin-version admin-index__block-container"
    >
      <div class="p-card-body">
        <div class="p-card-caption">
          <div class="p-card-title">
            <span
              aria-hidden="true"
              class="base-icon base-icon--normal"
            >
              <i
                aria-hidden="true"
                class="mdi mdi-checkbox-multiple-marked"
              />
            </span>
            {{ t("Version check") }}
          </div>
        </div>

        <div
          v-if="blockVersionStatusEl"
          class="block-admin-version__status text-body-2"
          v-html="blockVersionStatusEl"
        />
        <div
          v-else
          class="block-admin-version__form"
        >
          <i18n-t
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
            <BaseCheckbox
              id="checkbox"
              v-model="doNotListCampus"
              :label="t('Hide campus from public platforms list')"
              name="donotlistcampus"
              class="text-body-2"
            />

            <BaseButton
              id="register"
              :label="t('Enable version check')"
              icon="check"
              isSubmit
              name="Register"
              type="secondary"
            />
          </form>
        </div>
      </div>
    </div>

    <BaseCard
      v-if="securityStore.isAdmin"
      class="block-admin-support admin-index__block-container"
    >
      <template #title>
        <i
          class="mdi mdi-face-agent"
          aria-hidden="true"
        />
        {{ t("Professional support") }}
      </template>

      <div
        v-if="blockSupportStatusEl"
        class="block-admin-support__status text-body-2"
        v-html="blockSupportStatusEl"
      />
      <div
        v-else
        class="block-admin-news__status"
      >
        <p class="mb-3">{{ t("Disabled") }}</p>
      </div>
    </BaseCard>

    <BaseCard
      v-if="securityStore.isAdmin"
      class="block-admin-news admin-index__block-container"
    >
      <template #title>
        <i
          class="mdi mdi-bullhorn"
          aria-hidden="true"
        />
        {{ t("News from Chamilo") }}
      </template>

      <div
        v-if="blockNewsStatusEl"
        class="block-admin-news__status"
        v-html="blockNewsStatusEl"
      />
      <div
        v-else
        class="block-admin-news__status"
        v-text="t('Disabled')"
      />
    </BaseCard>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import Skeleton from "primevue/skeleton"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import AdminBlock from "../../components/admin/AdminBlock"

import { useSecurityStore } from "../../store/securityStore"
import { useIndexBlocks } from "../../composables/admin/indexBlocks"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

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
  blockTracking,
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
