<template>
  <div class="grid grid-cols-3 gap-4 rounded bg-white p-6 mb-4">
    <aside class="install-steps hidden md:block">
      <a
        class="logo-install"
        href="index.php"
      >
        <img
          alt="Chamilo"
          src="/build/css/themes/chamilo/images/header-logo.png"
        >
      </a>
      <ol>
        <li
          v-for="{step, stepTitle} in steps"
          :key="step"
          :class="{ 'install-steps__step--active': step === installerData.currentStep }"
          class="install-steps__step"
        >
          {{ stepTitle }}
        </li>
      </ol>
      <div id="note">
        <a
          class="p-button p-component p-button-info p-button-outlined"
          href="../../documentation/installation_guide.html"
          target="_blank"
        >
          <span
            aria-hidden="true"
            class="p-button-icon p-button-icon-left mdi mdi-text-box-search-outline"
          />
          <span class="p-button-text">{{ t('Read the installation guide') }}</span>
        </a>
      </div>
    </aside>

    <main class="install-step-container col-span-3 md:col-span-2 row-span-2">
      <h1
        v-if="'new' === installerData.installType"
        v-t="'New installation'"
        class="mb-4"
      />
      <h1
        v-else-if="'update' === installerData.installType"
        v-t="{
          path: 'Update from Chamilo {versions}',
          args: { 'versions': installerData.upgradeFromVersion.join(' | ') }
        }"
        class="mb-4"
      />
      <h1
        v-else
        v-t="'Chamilo\'s installation wizard'"
        class="mb-8"
      />

      <form
        id="install_form"
        :action="installerData.formAction"
        method="post"
      >
        <input
          :value="installerData.updatePath"
          name="updatePath"
          type="hidden"
        >
        <input
          :value="installerData.urlAppendPath"
          name="urlAppendPath"
          type="hidden"
        >
        <input
          :value="installerData.pathForm"
          name="pathForm"
          type="hidden"
        >
        <input
          :value="installerData.urlForm"
          name="urlForm"
          type="hidden"
        >
        <input
          :value="installerData.dbHostForm"
          name="dbHostForm"
          type="hidden"
        >
        <input
          :value="installerData.dbPortForm"
          name="dbPortForm"
          type="hidden"
        >
        <input
          :value="installerData.dbUsernameForm"
          name="dbUsernameForm"
          type="hidden"
        >
        <input
          :value="installerData.dbPassForm"
          name="dbPassForm"
          type="hidden"
        >
        <input
          :value="installerData.dbNameForm"
          name="dbNameForm"
          type="hidden"
        >
        <input
          :value="installerData.allowSelfReg"
          name="allowSelfReg"
          type="hidden"
        >
        <input
          :value="installerData.allowSelfRegProf"
          name="allowSelfRegProf"
          type="hidden"
        >
        <input
          :value="installerData.emailForm"
          name="emailForm"
          type="hidden"
        >
        <input
          :value="installerData.adminLastName"
          name="adminLastName"
          type="hidden"
        >
        <input
          :value="installerData.adminFirstName"
          name="adminFirstName"
          type="hidden"
        >
        <input
          :value="installerData.adminPhoneForm"
          name="adminPhoneForm"
          type="hidden"
        >
        <input
          :value="installerData.loginForm"
          name="loginForm"
          type="hidden"
        >
        <input
          :value="installerData.passForm"
          name="passForm"
          type="hidden"
        >
        <input
          :value="installerData.languageForm"
          name="languageForm"
          type="hidden"
        >
        <input
          :value="installerData.campusForm"
          name="campusForm"
          type="hidden"
        >
        <input
          :value="installerData.educationForm"
          name="educationForm"
          type="hidden"
        >
        <input
          :value="installerData.institutionForm"
          name="institutionForm"
          type="hidden"
        >
        <input
          :value="installerData.institutionUrlForm"
          name="institutionUrlForm"
          type="hidden"
        >
        <input
          :value="installerData.checkEmailByHashSent"
          name="checkEmailByHashSent"
          type="hidden"
        >
        <input
          :value="installerData.showEmailNotCheckedToStudent"
          name="ShowEmailNotCheckedToStudent"
          type="hidden"
        >
        <input
          :value="installerData.userMailCanBeEmpty"
          name="userMailCanBeEmpty"
          type="hidden"
        >
        <input
          :value="installerData.encryptPassForm"
          name="encryptPassForm"
          type="hidden"
        >
        <input
          :value="installerData.session_lifetime"
          name="session_lifetime"
          type="hidden"
        >
        <input
          :value="installerData.old_version"
          name="old_version"
          type="hidden"
        >
        <input
          :value="installerData.new_version"
          name="new_version"
          type="hidden"
        >
        <input
          :value="installerData.installationProfile"
          name="installationProfile"
          type="hidden"
        >

        <Step1 v-if="1 === installerData.currentStep" />

        <Step2 v-else-if="2 === installerData.currentStep" />

        <Step3 v-else-if="3 === installerData.currentStep" />

        <Step4 v-else-if="4 === installerData.currentStep" />

        <Step5 v-else-if="5 === installerData.currentStep" />

        <Step6 v-else-if="6 === installerData.currentStep" />

        <Step7 v-else-if="7 === installerData.currentStep" />
      </form>
    </main>
  </div>
  <footer class="text-center">
    <p v-html="installerData.poweredBy" />
  </footer>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { onMounted, provide, ref } from 'vue';

import Step1 from './components/installer/Step1';
import Step2 from './components/installer/Step2';
import Step3 from './components/installer/Step3';
import Step4 from './components/installer/Step4';
import Step5 from './components/installer/Step5';
import Step6 from './components/installer/Step6';
import Step7 from './components/installer/Step7';

const { t } = useI18n();

const installerData = ref(window.installerData);

provide('installerData', installerData);

const steps = ref([
  {
    step: 1,
    stepTitle: t('Installation language')
  },
  {
    step: 2,
    stepTitle: t('Requirements')
  },
  {
    step: 3,
    stepTitle: t('Licence')
  },
  {
    step: 4,
    stepTitle: t('Database settings')
  },
  {
    step: 5,
    stepTitle: t('Config settings')
  },
  {
    step: 6,
    stepTitle: t('Show Overview')
  },
  {
    step: 7,
    stepTitle: t('Install')
  },
]);

onMounted(() => {
  const txtIsExecutable = document.getElementById('is_executable');

  if (!txtIsExecutable) {
    return;
  }

  document.querySelectorAll('button')
    .forEach(
      button => button.addEventListener(
        'click',
        () => txtIsExecutable.value = button.name
      )
    );
});
</script>
