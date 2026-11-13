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
      :section-route="{ name: 'AdminUsersSection' }"
      v-model:extra-content="blockUsers.extraContent"
      :description="t('Here you can manage registered users within your platform')"
      :editable="blockUsers.editable"
      :items="blockUsers.items"
      :search-url="blockUsers.searchUrl"
      :title="t('User management')"
      :bg-index="0"
      icon="account"
    />

    <AdminBlock
      v-if="blockCourses"
      :id="blockCourses.id"
      :section-route="{ name: 'AdminCoursesSection' }"
      v-model:extra-content="blockCourses.extraContent"
      :description="t('Create and manage your courses in a simple way')"
      :editable="blockCourses.editable"
      :items="blockCourses.items"
      :search-url="blockCourses.searchUrl"
      :title="t('Course management')"
      :bg-index="1"
      icon="courses"
    />

    <AdminBlock
      v-if="blockSessions"
      :id="blockSessions.id"
      :section-route="{ name: 'AdminSessionsSection' }"
      v-model:extra-content="blockSessions.extraContent"
      :description="t('Create course packages for a certain time with training sessions')"
      :editable="blockSessions.editable"
      :items="blockSessions.items"
      :search-url="blockSessions.searchUrl"
      :title="t('Sessions management')"
      :bg-index="2"
      icon="sessions"
    />

    <AdminBlock
      v-if="blockPlatform"
      :id="blockPlatform.id"
      :section-route="{ name: 'AdminPlatformSection' }"
      v-model:extra-content="blockPlatform.extraContent"
      :description="t('Configure your platform, view reports, publish and send announcements globally')"
      :editable="blockPlatform.editable"
      :items="blockPlatform.items"
      :search-url="blockPlatform.searchUrl"
      :title="t('Platform management')"
      :bg-index="3"
      icon="admin-settings"
    />

    <AdminBlock
      v-if="blockTracking"
      :id="blockTracking.id"
      :section-route="{ name: 'AdminAnalyticsSection' }"
      v-model:extra-content="blockTracking.extraContent"
      :description="t('View reports, statistics and tracking data')"
      :editable="blockTracking.editable"
      :items="blockTracking.items"
      :title="t('Analytics')"
      :bg-index="4"
      icon="tracking"
    />

    <AdminBlock
      v-if="blockGradebook"
      :id="blockGradebook.id"
      :section-route="{ name: 'AdminAssessmentsSection' }"
      v-model:extra-content="blockGradebook.extraContent"
      :editable="blockGradebook.editable"
      :items="blockGradebook.items"
      :title="t('Assessments')"
      :bg-index="5"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockSkills"
      :id="blockSkills.id"
      :section-route="{ name: 'AdminSkillsSection' }"
      v-model:extra-content="blockSkills.extraContent"
      :description="t('Manage the skills of your users, through courses and badges')"
      :editable="blockSkills.editable"
      :items="blockSkills.items"
      :title="t('Skills')"
      :bg-index="6"
      icon="gradebook"
    />

    <AdminBlock
      v-if="blockSettings"
      :id="blockSettings.id"
      :section-route="{ name: 'AdminSystemSection' }"
      v-model:extra-content="blockSettings.extraContent"
      :description="t('View the status of your server, perform performance tests')"
      :editable="blockSettings.editable"
      :items="blockSettings.items"
      :title="t('System')"
      :bg-index="7"
      icon="settings"
    />

    <AdminBlock
      v-if="blockRooms"
      :id="blockRooms.id"
      :section-route="{ name: 'AdminRoomsSection' }"
      v-model:extra-content="blockRooms.extraContent"
      :editable="blockRooms.editable"
      :items="blockRooms.items"
      :title="t('Rooms')"
      :bg-index="8"
      icon="room"
    />

    <AdminBlock
      v-if="blockSecurity"
      :id="blockSecurity.id"
      :section-route="{ name: 'AdminSecuritySection' }"
      v-model:extra-content="blockSecurity.extraContent"
      :description="t('Security tools and reports')"
      :editable="blockSecurity.editable"
      :items="blockSecurity.items"
      :title="t('Security')"
      :bg-index="9"
      icon="shield-check"
    />

    <AdminBlock
      v-if="blockPrivacy"
      :id="blockPrivacy.id"
      :section-route="{ name: 'AdminPrivacySection' }"
      v-model:extra-content="blockPrivacy.extraContent"
      :editable="blockPrivacy.editable"
      :items="blockPrivacy.items"
      :title="t('Personal data protection')"
      :bg-index="10"
      icon="anonymous"
    />

    <AdminBlock
      v-if="blockPlugins.items.length > 0"
      :id="blockPlugins.id"
      :section-route="{ name: 'AdminPluginsSection' }"
      :items="blockPlugins.items"
      :title="t('Plugins')"
      :bg-index="11"
      icon="plugin"
    />

    <AdminBlock
      v-if="blockHealthCheck && blockHealthCheck.items.length > 0"
      :id="blockHealthCheck.id"
      :section-route="{ name: 'AdminHealthCheckSection' }"
      :items="blockHealthCheck.items"
      :title="t('Health check')"
      :bg-index="12"
      icon="health-check"
      @item-action="onHealthCheckAction"
    />

    <BaseDialogConfirmCancel
      v-model:is-visible="migrationHistoryDialog.visible"
      :title="t('Fill in the migration history')"
      :confirm-label="t('Fill in the history')"
      :cancel-label="t('Cancel')"
      confirm-type="success"
      @confirm-clicked="recordMigrationHistory"
      @cancel-clicked="migrationHistoryDialog.visible = false"
    >
      <div class="flex flex-col gap-3">
        <p>
          {{
            t(
              "Doctrine, which runs the updates of Chamilo, needs a record in the version table for every migration applied to the database. Chamilo 2.0 and 3.0.0 created the database without these records, so the next update would try to apply every migration again over your current database.",
            )
          }}
        </p>
        <ul class="list-disc ps-6">
          <li>
            {{
              t("Your database structure already includes the changes of {0} migrations.", [
                migrationHistoryDialog.recordable,
              ])
            }}
          </li>
          <li v-if="migrationHistoryDialog.pending > 0">
            {{ t("The changes of {0} newer migrations are not applied yet.", [migrationHistoryDialog.pending]) }}
          </li>
        </ul>
        <p>
          {{
            t(
              "This action adds the {0} missing records to the version table. These migrations are not executed again, and your data is not modified.",
              [migrationHistoryDialog.recordable],
            )
          }}
        </p>
        <p v-if="migrationHistoryDialog.pending > 0">
          {{
            t("After this action, the update applies only the {0} pending migrations.", [
              migrationHistoryDialog.pending,
            ])
          }}
        </p>
        <p v-else>
          {{ t("After this action, your platform is up to date, and future updates work normally.") }}
        </p>
        <a
          href="/documentation/installation_guide.html#cli-upgrade-history-recovery"
          target="_blank"
          rel="noopener"
          class="underline"
          v-text="t('Installation guide')"
        />
      </div>
    </BaseDialogConfirmCancel>

    <!-- Small / secondary blocks: sent to the bottom -->
    <AdminBlock
      v-if="blockChamilo"
      :id="blockChamilo.id"
      :section-route="{ name: 'AdminChamiloSection' }"
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
            {{ t("Portal registration") }}
          </div>
        </div>

        <div
          v-if="blockVersionStatusEl"
          class="block-admin-version__status text-body-2"
          dir="auto"
          v-html="blockVersionStatusEl"
        />
        <div
          v-else
          class="block-admin-version__form"
        >
          <p class="mb-3">
            {{
              t(
                "Register your portal with Chamilo to share installation information and aggregated statistics. Registration is independent of update checks.",
              )
            }}
            <a
              href="https://stats.chamilo.org/"
              target="_blank"
              rel="noopener noreferrer"
              >https://stats.chamilo.org/</a
            >
          </p>

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
              :label="t('Register portal')"
              icon="check"
              isSubmit
              name="Register"
              type="secondary"
            />
          </form>
        </div>
        <p class="mt-3 text-body-2">
          {{ t("Automatic update notices can be enabled independently under Administration settings.") }}
        </p>
        <div class="flex flex-wrap gap-2 mt-2">
          <BaseButton
            :label="t('Administration settings')"
            to-url="/admin/settings/admin"
            type="secondary"
          />
          <BaseButton
            :label="t('System update')"
            to-url="/admin/system-update"
            type="secondary"
          />
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
        dir="auto"
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
        dir="auto"
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
import baseService from "../../services/baseService"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()

const securityStore = useSecurityStore()
const migrationHistoryDialog = ref({ visible: false, recordable: 0, pending: 0 })
const { showErrorNotification, showSuccessNotification } = useNotification()

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

/**
 * Runs the action a health check item carries, if any is known here.
 *
 * @param {{action: string, recordable?: number, pendingAfterRecording?: number}} item the clicked item
 * @returns {void}
 */
function onHealthCheckAction(item) {
  if ("record-migration-history" !== item.action) {
    return
  }

  migrationHistoryDialog.value = {
    visible: true,
    recordable: item.recordable ?? 0,
    pending: item.pendingAfterRecording ?? 0,
  }
}

/**
 * Records the migration history, then reloads the blocks so the item reports the new state.
 *
 * @returns {Promise<void>}
 */
async function recordMigrationHistory() {
  migrationHistoryDialog.value.visible = false

  try {
    const result = await baseService.post("/admin/system-status-migration-history", {})
    showSuccessNotification(result.message)
    await loadBlocks()
  } catch (e) {
    showErrorNotification(e)
  }
}

const isLoadingBlocks = ref(true)

loadBlocks().then(() => {
  isLoadingBlocks.value = false
})
</script>
