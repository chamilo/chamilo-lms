<template>
  <div
    v-show="!loading"
    class="install-step"
  >
    <h2
      v-t="'Step 6 - Last check before install'"
      class="RequirementHeading mb-8"
    />

    <p
      v-t="'Here are the values you entered'"
      class="RequirementContent mb-4"
    />

    <div>
      <h3
        v-t="'Administrator'"
        class="mb-4"
      />

      <div
        v-if="'new' === installerData.installType"
        class="formgroup-inline"
      >
        <div
          v-t="'Administrator login'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.loginForm"
        />
      </div>

      <div
        v-if="'new' === installerData.installType"
        class="formgroup-inline"
      >
        <div
          v-t="'Administrator password'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.passForm"
        />
        <div
          v-t="'You may want to change this'"
          class="field text-body-2 text-error"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Administrator first name'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminFirstName"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Administrator last name'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminLastName"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Administrator e-mail'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.emailForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Administrator telephone'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.adminPhoneForm"
        />
      </div>

      <div class="field">
        <h3 v-t="'Portal'" />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Your portal name'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.campusForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Main language'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.languageForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Allow self-registration'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.allowSelfRegistrationLiteral"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Your company short name'"
          class="field text-body-2font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.institutionForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'URL of this company'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.institutionUrlForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Encryption method'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.encryptPassForm"
        />
      </div>

      <div class="field">
        <h3 v-t="'Database'" />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Database Host'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbHostForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Port'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbPortForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Database Login'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbUsernameForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Database Password'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbPassForm"
        />
      </div>

      <div class="formgroup-inline">
        <div
          v-t="'Database name'"
          class="field text-body-2 font-semibold"
        />
        <div
          class="field text-body-2"
          v-text="installerData.stepData.dbNameForm"
        />
      </div>

      <Message
        v-if="'new' === installerData.installType"
        :closable="false"
        severity="warn"
      >
        {{ t('The install script will erase all tables of the selected database. We heavily recommend you do a full backup of them before confirming this last install step.') }}
      </Message>
    </div>

    <hr>

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
        <input id="is_executable" v-model="isExecutable" name="is_executable" type="hidden" />
        <input type="hidden" name="step6" value="1" />
      </div>
      <Button
        id="button_step6"
        :label="installerData.isUpdateAvailable ? t('Update Chamilo') : t('Install Chamilo')"
        :loading="loading"
        :disabled="isButtonDisabled"
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
      v-t="'Step 7 - Installation process execution'"
      class="RequirementHeading mb-8"
    />
    <h2
      v-else
      v-t="'Step 7 - Update process execution'"
      class="RequirementHeading mb-8"
    />

    <p>
      <strong
        v-if="installerData.installationProfile"
        v-text="installerData.installationProfile"
      />
    </p>

    <Message
      id="pleasewait"
      :closable="false"
      severity="success"
    >
      <p
        v-t="'Please wait. This could take a while...'"
        class="mb-3"
      />
      <ProgressBar mode="indeterminate" />
    </Message>
    <ProgressBar :value="progressPercentage" style="height: 22px"> {{ progressPercentage }}/100 </ProgressBar>
    <div class="log-container">
      <div v-html="logTerminalContent"></div>
    </div>
  </div>
</template>

<script setup>
import { inject, ref} from 'vue';
import { useI18n } from 'vue-i18n';

import Message from 'primevue/message';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';

const { t } = useI18n();

const installerData = inject('installerData');

const loading = ref(false);
const isButtonDisabled = ref(installerData.value.isUpdateAvailable);
const isExecutable = ref('');


const logTerminalContent = ref('');
const progressPercentage = ref(0);

function updateLog() {
  var xhr = new XMLHttpRequest();
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
      const response = JSON.parse(xhr.responseText);
      logTerminalContent.value = response.log_terminal;
      progressPercentage.value = response.progress_percentage;
      scrollToBottom();

      isButtonDisabled.value = false;
    }
  };
  xhr.open('GET', installerData.value.logUrl, true);
  xhr.send();
}

function btnStep6OnClick() {
  loading.value = true;
  isExecutable.value = 'step6';
  document.getElementById('install_form').submit();
}

function scrollToBottom() {
  const logContainer = document.querySelector('.log-container');
  logContainer.scrollTop = logContainer.scrollHeight;
}

setInterval(updateLog, 2000);
</script>
