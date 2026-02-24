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

    <div>
      <h3
        v-text="t('Administrator')"
        class="mb-4"
      />

      <div
        v-if="'new' === installerData.installType"
        class="formgroup-inline"
      >
        <div
          v-text="t('Administrator login')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.loginForm"
        />
      </div>

      <div
        v-if="'new' === installerData.installType"
        class="formgroup-inline items-center gap-2"
      >
        <div
          v-text="t('Administrator password')"
          class="field text-body-2 font-semibold"
        />
        <div class="field text-body-2 flex items-center">
          <span v-if="!showAdminPass">********</span>
          <span v-else>{{ installerData.stepData.passForm }}</span>
          <Button
            icon="mdi mdi-eye"
            class="p-button-text ml-2"
            @click="toggleAdminPass"
            :aria-label="showAdminPass ? t('Hide password') : t('Show password')"
          />
        </div>
        <div
          v-text="t('You may want to change this')"
          class="field text-body-2 text-error"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Administrator first name')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminFirstName"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Administrator last name')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminLastName"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Administrator e-mail')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.emailForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Administrator telephone')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminPhoneForm"
        />
      </div>

      <div class="field">
        <h3 v-text="t('Portal')" />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Your portal name')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.campusForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Main language')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.languageForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Allow self-registration')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.allowSelfRegistrationLiteral"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Your company short name')"
          class="field text-body-2font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.institutionForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('URL of this company')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.institutionUrlForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Encryption method')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.encryptPassForm"
        />
      </div>

      <div class="field">
        <h3 v-text="t('Database')" />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Database Host')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbHostForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Port')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbPortForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Database user')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbUsernameForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Database Password')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbPassForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-text="t('Database name')"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="sanitizedDbName"
        />
      </div>

      <Message
        v-if="'new' === installerData.installType"
        :closable="false"
        severity="warn"
      >
        {{
          t(
            "The install script will erase all tables of the selected database. We heavily recommend you do a full backup of them before confirming this last install step.",
          )
        }}
      </Message>
    </div>

    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <Button
          v-if="!installerData.isUpdateAvailable"
          :label="t('Previous')"
          class="p-button-secondary"
          icon="mdi mdi-page-previous"
          name="step4"
          type="submit"
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
      <Button
        id="button_step6"
        :label="installerData.isUpdateAvailable ? t('Update Chamilo') : t('Install Chamilo')"
        :loading="loading"
        class="p-button-success"
        icon="mdi mdi-progress-download"
        name="button_step6"
        type="button"
        @click="btnStep6OnClick"
      />
    </div>
  </div>

  <div
    v-show="loading"
    class="install-step"
  >
    <h2
      v-if="'update' !== installerData.installType"
      v-text="t('Step 7 - Installation process execution')"
      class="RequirementHeading mb-8"
    />
    <h2
      v-else
      v-text="t('Step 7 - Update process execution')"
      class="RequirementHeading mb-8"
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

    <div v-if="'update' === installerData.installType">
      <ProgressBar
        :value="progressPercentage"
        style="height: 22px"
        >{{ progressPercentage }}%</ProgressBar
      >
      <p class="current-migration">
        <strong>{{ t("Verifying migration:") }}</strong> {{ currentMigration }}
      </p>
    </div>
  </div>

  <Dialog
    v-model:visible="successDialogVisible"
    :closable="false"
    :modal="true"
    :show-header="false"
  >
    <div class="p-d-flex p-ai-center p-jc-center">
      <h3
        v-text="t('Migration completed successfully!')"
        class="mb-4"
      />
    </div>
    <div class="formgroup-inline">
      <div class="field">
        <Button
          :label="t('Go to your newly created portal.')"
          class="p-button-success"
          type="button"
          @click="btnFinishOnClick"
        />
      </div>
    </div>
  </Dialog>

  <Dialog
    v-model:visible="errorDialogVisible"
    :closable="false"
    :modal="true"
    :show-header="false"
  >
    <div class="p-d-flex p-ai-center p-jc-center">
      <h3
        v-text="t('Migration failed!')"
        class="mb-4 text-error"
      />
    </div>
    <div class="p-d-flex p-ai-center p-jc-center">
      <p class="text-error">{{ errorMessage }}</p>
    </div>
    <div
      v-if="currentMigration"
      class="p-d-flex p-ai-center p-jc-center mt-4"
    >
      <p class="text-body-2">
        {{ t("The last migration executed successfully was:") }} <br /><strong>{{ currentMigration }}</strong>
      </p>
    </div>
    <div class="formgroup-inline">
      <div class="field">
        <Button
          :label="t('Contact support')"
          class="p-button-danger mt-4"
          type="button"
          @click="btnSupportOnClick"
        />
      </div>
    </div>
  </Dialog>
</template>

<script setup>
import { inject, ref, computed } from "vue"
import { useI18n } from "vue-i18n"

import Message from "primevue/message"
import Button from "primevue/button"
import ProgressBar from "primevue/progressbar"
import Dialog from "primevue/dialog"
import SectionHeader from "../layout/SectionHeader.vue"

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

const loading = ref(false)
const isButtonDisabled = ref(installerData.value.isUpdateAvailable)
const isExecutable = ref("")

const progressPercentage = ref(0)
const currentMigration = ref("")
const successDialogVisible = ref(false)
const errorDialogVisible = ref(false)
const errorMessage = ref("")

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
    if (xhr.readyState === 4 && xhr.status !== 200) {
      loading.value = false
      isButtonDisabled.value = false
      errorDialogVisible.value = true
      errorMessage.value = `
        ${t("Please check the following error:")} ${xhr.status} - ${xhr.statusText}.
      `
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
        const response = JSON.parse(xhr.responseText)
        progressPercentage.value = response.progress_percentage
        currentMigration.value = response.current_migration

        if (response.progress_percentage < 100) {
          pollMigrationStatus()
        } else {
          loading.value = false
          isButtonDisabled.value = false
          successDialogVisible.value = true
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
  alert(t("Please contact support with the error details."))
}
</script>
