<template>
  <div
    v-show="!loading"
    class="install-step"
  >
    <SectionHeader
      :title="t('Step 6 - Last check before install')"
      class="RequirementHeading"
    />

    <p
      v-text="t('Here are the values you entered')"
      class="RequirementContent mb-4"
    />

    <section>
      <h3
        v-text="t('Administrator')"
        class="mb-4"
      />
      <dl class="install-summary">
        <template v-if="'new' === installerData.installType">
          <dt v-text="t('Administrator login')" />
          <dd v-text="installerData.stepData.loginForm" />
          <dt v-text="t('Administrator password')" />
          <dd>
            <span class="inline-flex items-center gap-2">
              <span v-text="showAdminPass ? installerData.stepData.passForm : '********'" />
              <BaseButton
                :icon="showAdminPass ? 'eye-off' : 'eye-on'"
                :label="showAdminPass ? t('Hide password') : t('Show password')"
                only-icon
                size="small"
                type="primary-text"
                @click="toggleAdminPass"
              />
            </span>
            <small
              v-text="t('You may want to change this')"
              class="block text-caption text-error"
            />
          </dd>
        </template>
        <template
          v-for="row in adminRows"
          :key="row.label"
        >
          <dt v-text="row.label" />
          <dd v-text="row.value" />
        </template>
      </dl>
    </section>

    <section class="mt-8">
      <h3
        v-text="t('Portal')"
        class="mb-4"
      />
      <dl class="install-summary">
        <template
          v-for="row in portalRows"
          :key="row.label"
        >
          <dt v-text="row.label" />
          <dd v-text="row.value" />
        </template>
      </dl>
    </section>

    <section class="mt-8">
      <h3
        v-text="t('Database')"
        class="mb-4"
      />
      <dl class="install-summary">
        <template
          v-for="row in databaseRows"
          :key="row.label"
        >
          <dt v-text="row.label" />
          <dd v-text="row.value" />
        </template>
      </dl>
    </section>

    <Message
      v-if="'new' === installerData.installType"
      :closable="false"
      class="mt-8"
      severity="warn"
    >
      {{
        t(
          "The install script will erase all tables of the selected database. We heavily recommend you do a full backup of them before confirming this last install step.",
        )
      }}
    </Message>

    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <BaseButton
          v-if="!installerData.isUpdateAvailable"
          :label="t('Previous')"
          icon="back"
          is-submit
          name="step4"
          type="black"
        />
        <input
          id="is_executable"
          v-model="isExecutable"
          name="is_executable"
          type="hidden"
        />
        <input
          name="step6"
          type="hidden"
          value="1"
        />
      </div>
      <BaseButton
        id="button_step6"
        :is-loading="loading"
        :label="installerData.isUpdateAvailable ? t('Update Chamilo') : t('Install Chamilo')"
        icon="download"
        name="button_step6"
        type="success"
        @click="btnStep6OnClick"
      />
    </div>
  </div>

  <div
    v-show="loading"
    class="install-step"
  >
    <SectionHeader
      :title="
        'update' !== installerData.installType
          ? t('Step 7 - Installation process execution')
          : t('Step 7 - Update process execution')
      "
    />

    <Message
      id="pleasewait"
      :closable="false"
      severity="success"
    >
      <p
        v-text="t('Please wait, this could take a while...')"
        class="mb-3"
      />
    </Message>

    <div
      v-if="'update' === installerData.installType"
      class="mt-4"
    >
      <ProgressBar
        :value="progressPercentage"
        class="h-6"
        >{{ progressPercentage }}%</ProgressBar
      >
      <p class="current-migration mt-4">
        <strong>{{ t("Verifying migration:") }}</strong> {{ currentMigration }}
      </p>
    </div>
  </div>

  <BaseDialog
    v-model:is-visible="successDialogVisible"
    :breakpoints="{ '575px': '90vw' }"
    :style="{ width: '32rem' }"
    :show-close-button="false"
    :show-header-close-button="false"
    :title="t('Migration completed successfully!')"
  >
    <Message
      v-if="upgradeFlagWarning"
      :closable="false"
      severity="warn"
      class="mb-4"
    >
      {{ upgradeFlagWarning }}
    </Message>
    <Message
      :closable="false"
      severity="info"
    >
      {{
        t(
          "This version of Chamilo comes with one (or more) multi-language course(s) pre-installed. Feel free to review them in the courses list and decide whether they should be public or not.",
        )
      }}
    </Message>
    <template #footer>
      <BaseButton
        :label="t('Go to your newly created portal.')"
        icon="home"
        type="success"
        @click="btnFinishOnClick"
      />
    </template>
  </BaseDialog>

  <BaseDialog
    v-model:is-visible="errorDialogVisible"
    :breakpoints="{ '575px': '90vw' }"
    :style="{ width: '32rem' }"
    :show-close-button="false"
    :show-header-close-button="false"
    :title="t('Migration failed!')"
  >
    <p
      class="text-error"
      v-text="errorMessage"
    />
    <p
      v-if="currentMigration"
      class="mt-4 text-body-2"
    >
      {{ t("The last migration executed successfully was:") }} <br /><strong>{{ currentMigration }}</strong>
    </p>
    <template #footer>
      <BaseButton
        :label="t('Contact support')"
        icon="help"
        type="danger"
        @click="btnSupportOnClick"
      />
    </template>
  </BaseDialog>
</template>

<script setup>
import { inject, ref, computed } from "vue"
import { useI18n } from "vue-i18n"

import Message from "primevue/message"
import ProgressBar from "primevue/progressbar"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseDialog from "../basecomponents/BaseDialog.vue"

const { t } = useI18n()

const installerData = inject("installerData")

// Compute the sanitized database name as it will be created on the server.
const sanitizedDbName = computed(() => {
  const raw = installerData.value?.stepData?.dbNameForm || ""

  // For updates we trust the existing database name as-is.
  if (installerData.value.installType === "update" || installerData.value.isUpdateAvailable) {
    return raw
  }

  // Same rule as backend: only letters, digits and underscore are kept.
  return raw.replace(/[^a-zA-Z0-9_]/g, "")
})

// Rows of the summary. The administrator login and password are rendered apart, before these.
const adminRows = computed(() => {
  const data = installerData.value.stepData

  return [
    { label: t("Administrator first name"), value: data.adminFirstName },
    { label: t("Administrator last name"), value: data.adminLastName },
    { label: t("Administrator e-mail"), value: data.emailForm },
    { label: t("Administrator telephone"), value: data.adminPhoneForm },
  ]
})

const portalRows = computed(() => {
  const data = installerData.value.stepData

  return [
    { label: t("Your portal name"), value: data.campusForm },
    { label: t("Main language"), value: data.languageForm },
    { label: t("Allow self-registration"), value: data.allowSelfRegistrationLiteral },
    { label: t("Your company short name"), value: data.institutionForm },
    { label: t("URL of this company"), value: data.institutionUrlForm },
    { label: t("Encryption method"), value: data.encryptPassForm },
  ]
})

const databaseRows = computed(() => {
  const data = installerData.value.stepData

  return [
    { label: t("Database Host"), value: data.dbHostForm },
    { label: t("Port"), value: data.dbPortForm },
    { label: t("Database user"), value: data.dbUsernameForm },
    { label: t("Database Password"), value: data.dbPassForm },
    { label: t("Database name"), value: sanitizedDbName.value },
  ]
})

const loading = ref(false)
// Do NOT initialize from isUpdateAvailable: a pending/failed migration means
// isUpdateAvailable=true, but the button must stay enabled so the user can
// (re)run the migration.  The button is disabled only while the migration is
// actually running (loading=true, set in btnStep6OnClick).
const isButtonDisabled = ref(false)
const isExecutable = ref("")

const progressPercentage = ref(0)
const currentMigration = ref("")
const successDialogVisible = ref(false)
const errorDialogVisible = ref(false)
const errorMessage = ref("")
const upgradeFlagWarning = ref("")

const showAdminPass = ref(false)
const toggleAdminPass = () => {
  showAdminPass.value = !showAdminPass.value
}

function btnStep6OnClick() {
  loading.value = true
  isButtonDisabled.value = true

  const updatePath = installerData.value.updatePath || ""

  if (installerData.value.installType === "update") {
    startMigration(updatePath)
    setTimeout(pollMigrationStatus, 5000)
  } else {
    isExecutable.value = "step6"
    document.getElementById("install_form").submit()
  }
}

function startMigration(updatePath) {
  const xhr = new XMLHttpRequest()
  xhr.onreadystatechange = function () {
    if (xhr.readyState !== 4) {
      return
    }

    if (xhr.status === 200) {
      let response

      try {
        response = JSON.parse(xhr.responseText)
      } catch {
        // A 200 that is not JSON means the server wrote something else first: a PHP
        // notice, or nothing at all when the connection dropped. Show that text instead
        // of dying in the console, because it is the only copy of the reason.
        loading.value = false
        isButtonDisabled.value = false
        errorDialogVisible.value = true
        errorMessage.value = `${t("Please check the following error:")} ${
          xhr.responseText.trim().slice(0, 1000) || `${xhr.status} - ${xhr.statusText}`
        }`

        return
      }

      progressPercentage.value = response.progress_percentage
      currentMigration.value = response.current_migration
      loading.value = false
      isButtonDisabled.value = false

      if (response.redirect_to_step7) {
        upgradeFlagWarning.value = response.upgrade_flag_warning || ""
        successDialogVisible.value = true
      } else {
        errorDialogVisible.value = true
        errorMessage.value = response.message || t("Migration failed!")
      }

      return
    }

    loading.value = false
    isButtonDisabled.value = false
    errorDialogVisible.value = true

    try {
      const response = JSON.parse(xhr.responseText)
      errorMessage.value =
        response.message || `${t("Please check the following error:")} ${xhr.status} - ${xhr.statusText}`
    } catch {
      errorMessage.value = `${t("Please check the following error:")} ${xhr.status} - ${xhr.statusText}`
    }
  }

  const url = `/main/install/migrate.php?updatePath=${encodeURIComponent(updatePath)}`
  xhr.open("POST", url, true)
  xhr.send()
}

function pollMigrationStatus() {
  setTimeout(() => {
    const xhr = new XMLHttpRequest()
    xhr.onreadystatechange = function () {
      if (xhr.readyState === 4 && xhr.status === 200) {
        let response

        try {
          response = JSON.parse(xhr.responseText)
        } catch {
          // Keep polling: one unreadable answer must not stop the progress bar for the
          // rest of a migration that can run for hours.
          pollMigrationStatus()

          return
        }

        progressPercentage.value = response.progress_percentage
        currentMigration.value = response.current_migration

        if (response.progress_percentage < 100) {
          pollMigrationStatus()
        }
      } else if (xhr.readyState === 4 && xhr.status !== 200) {
        loading.value = false
        isButtonDisabled.value = false
        errorDialogVisible.value = true
        errorMessage.value = `${t("Please check the following error:")} ${xhr.status} - ${xhr.statusText}`
      }
    }

    xhr.open("GET", "/main/install/get_migration_status.php", true)
    xhr.send()
  }, 2000)
}

function btnFinishOnClick() {
  window.location = "../../"
}

function btnSupportOnClick() {
  alert(t("Please contact support with the error details"))
}
</script>
