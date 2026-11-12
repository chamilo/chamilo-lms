<template>
  <div class="install-step">
    <SectionHeader
      :title="t('Step 5 - Configuration settings')"
      class="RequirementHeading"
    />

    <div
      v-if="'update' === installerData.installType"
      class="mt-8"
    >
      <h3
        v-text="t('System')"
        class="mb-4"
      />

      <!-- Read-only and not submitted: the server reads these values from the old configuration. -->
      <BaseInputText
        id="rootWeb"
        v-model="installerData.stepData.rootWeb"
        :label="t('Chamilo URL')"
        readonly
      />

      <BaseInputText
        id="rootSys"
        v-model="installerData.stepData.rootSys"
        :label="t('Path')"
        readonly
      />

      <BaseInputText
        id="systemVersion"
        v-model="installerData.stepData.systemVersion"
        :label="t('Source Chamilo version')"
        readonly
      />
    </div>

    <div class="mt-8">
      <h3
        v-text="t('Administrator')"
        class="mb-4"
      />

      <!-- Parameter 1: administrator's login -->
      <BaseInputText
        v-if="'update' !== installerData.installType"
        id="loginForm"
        v-model="installerData.stepData.loginForm"
        :label="t('Administrator login')"
        maxlength="80"
        name="loginForm"
      />
      <div
        v-else
        class="field"
      >
        <input
          v-model="installerData.stepData.loginForm"
          name="loginForm"
          type="hidden"
        />
        {{ installerData.stepData.loginForm }}
      </div>

      <!-- Parameter 2: administrator's password -->
      <div
        v-if="'update' !== installerData.installType"
        class="field"
      >
        <FloatLabel variant="on">
          <Password
            v-model="installerData.stepData.passForm"
            :feedback="false"
            :input-props="{ maxlength: 80, name: 'passForm' }"
            input-id="passForm"
            toggle-mask
          />
          <label
            v-text="t('Administrator password')"
            for="passForm"
          />
        </FloatLabel>
        <small
          v-text="t('You may want to change this')"
          class="text-error"
        />
        <input
          v-model="installerData.stepData.passForm"
          name="passForm"
          type="hidden"
        />
      </div>

      <!-- Parameters 3 and 4: administrator's names -->
      <BaseInputText
        id="adminFirstName"
        v-model="installerData.stepData.adminFirstName"
        :label="t('Administrator first name')"
        maxlength="80"
        name="adminFirstName"
      />

      <BaseInputText
        id="adminLastName"
        v-model="installerData.stepData.adminLastName"
        :label="t('Administrator last name')"
        maxlength="80"
        name="adminLastName"
      />

      <!-- Parameter 5: administrator's email -->
      <BaseInputText
        id="emailForm"
        v-model="installerData.stepData.emailForm"
        :label="t('Administrator e-mail')"
        maxlength="80"
        name="emailForm"
        type="email"
      />

      <!-- Parameter 6: administrator's telephone -->
      <BaseInputText
        id="adminPhoneForm"
        v-model="installerData.stepData.adminPhoneForm"
        :label="t('Administrator telephone')"
        maxlength="80"
        name="adminPhoneForm"
      />
    </div>

    <div class="mt-8">
      <h3
        v-text="t('Portal')"
        class="mb-4"
      />

      <!-- First parameter: language. -->
      <BaseSelect
        v-if="'update' !== installerData.installType"
        id="language_form_list"
        v-model="installerData.stepData.languageForm"
        :label="t('Language')"
        :options="languages"
        filter
        option-label="original_name"
        option-value="isocode"
      />
      <BaseInputText
        v-else
        id="language_form_list"
        v-model="installerData.stepData.languageForm"
        :label="t('Language')"
        readonly
      />
      <input
        v-model="installerData.stepData.languageForm"
        name="languageForm"
        type="hidden"
      />

      <!-- Second parameter: Chamilo URL -->
      <BaseInputText
        v-if="'install' === installerData.installType"
        id="urlForm"
        v-model="installerData.stepData.urlForm"
        :label="t('Chamilo URL')"
        maxlength="100"
        name="urlForm"
        type="url"
      />

      <!-- Parameter 9: campus name -->
      <BaseInputText
        id="campusForm"
        v-model="installerData.stepData.campusForm"
        :label="t('Your portal name')"
        maxlength="80"
        name="campusForm"
      />

      <!-- Parameter 10: institute (short) name -->
      <BaseInputText
        id="institutionForm"
        v-model="installerData.stepData.institutionForm"
        :label="t('Your company short name')"
        maxlength="80"
        name="institutionForm"
      />

      <!-- Parameter 11: institute URL -->
      <BaseInputText
        id="institutionUrlForm"
        v-model="installerData.stepData.institutionUrlForm"
        :label="t('URL of this company')"
        maxlength="80"
        name="institutionUrlForm"
      />

      <BaseRadioButtons
        v-if="'update' !== installerData.installType"
        v-model="installerData.stepData.encryptPassForm"
        :options="[
          { label: 'bcrypt', value: 'bcrypt' },
          { label: 'SHA1', value: 'sha1' },
          { label: 'MD5', value: 'md5' },
          { label: t('None'), value: 'none' },
        ]"
        :title="t('Encryption method')"
        class="mb-4"
        name="encryptPassForm"
      />
      <div
        v-else
        class="formgroup-inline"
      >
        <label v-text="t('Encryption method')" />
        <input
          v-model="installerData.stepData.encryptPassForm"
          name="encryptPassForm"
          type="hidden"
        />
        {{ installerData.stepData.encryptPassForm }}
      </div>

      <BaseRadioButtons
        v-if="'update' !== installerData.installType"
        v-model="installerData.stepData.allowSelfReg"
        :options="[
          { label: t('Yes'), value: 'true' },
          { label: t('No'), value: 'false' },
          { label: t('After approval'), value: 'approval' },
        ]"
        :title="t('Allow self-registration')"
        class="mb-4"
        name="allowSelfReg"
      />
      <div v-else>
        <label v-text="t('Allow self-registration')" />
        <input
          v-model="installerData.stepData.allowSelfReg"
          name="allowSelfReg"
          type="hidden"
        />
        <span
          v-if="'true' === installerData.stepData.allowSelfReg"
          v-text="t('Yes')"
        />
        <span
          v-else-if="'false' === installerData.stepData.allowSelfReg"
          v-text="t('No')"
        />
        <span
          v-else
          v-text="t('After approval')"
        />
      </div>

      <BaseRadioButtons
        v-if="'update' !== installerData.installType"
        v-model="installerData.stepData.allowSelfRegProf"
        :options="[
          { label: t('Yes'), value: '1' },
          { label: t('No'), value: '0' },
        ]"
        :title="t('Allow self-registration as a trainer')"
        name="allowSelfRegProf"
      />
      <div v-else>
        <label v-text="t('Allow self-registration as a trainer')" />
        <input
          v-model="installerData.stepData.allowSelfRegProf"
          name="allowSelfRegProf"
          type="hidden"
        />
        <span
          v-if="1 === installerData.stepData.allowSelfRegProf"
          v-text="t('Yes')"
        />
        <span
          v-else-if="0 === installerData.stepData.allowSelfRegProf"
          v-text="t('No')"
        />
      </div>
    </div>

    <EmailSettings />
    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <BaseButton
          :label="t('Previous')"
          icon="back"
          is-submit
          name="step3"
          type="black"
        />
      </div>

      <BaseButton
        :label="t('Next')"
        icon="next"
        is-submit
        name="step5"
        type="success"
      />

      <input
        id="is_executable"
        name="is_executable"
        type="hidden"
        value="-"
      />
    </div>
  </div>
</template>

<script setup>
import { inject } from "vue"
import { useI18n } from "vue-i18n"

import FloatLabel from "primevue/floatlabel"
import Password from "primevue/password"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"

import languages from "../../utils/languages"
import EmailSettings from "./EmailSettings.vue"

const { t } = useI18n()

const installerData = inject("installerData")
</script>
