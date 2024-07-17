<template>
  <div class="install-step">
    <h2
      v-t="'Step 5 - Configuration settings'"
      class="RequirementHeading mb-8"
    />

    <div v-if="'update' === installerData.installType">
      <h3
        v-t="'System'"
        class="mb-4"
      />

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.rootWeb"
            input-id="loginForm"
            maxlength="80"
            name="loginForm"
            type="text"
          />
          <label
            v-t="'Chamilo URL'"
            for="loginForm"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.rootSys"
            input-id="loginForm"
            maxlength="80"
            name="loginForm"
            type="text"
          />
          <label
            v-t="'Path'"
            for="loginForm"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.systemVersion"
            input-id="loginForm"
            maxlength="80"
            name="loginForm"
            type="text"
          />
          <label
            v-t="'Path'"
            for="loginForm"
          />
        </div>
      </div>
    </div>

    <div>
      <h3
        v-t="'Administrator'"
        class="mb-4"
      />

      <!-- Parameter 1: administrator's login -->
      <div
        v-if="'update' !== installerData.installType"
        class="field"
      >
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.loginForm"
            input-id="loginForm"
            maxlength="80"
            name="loginForm"
            type="text"
          />
          <label
            v-t="'Administrator login'"
            for="loginForm"
          />
        </div>
      </div>
      <div
        v-else
        class="field"
      >
        <input
          v-model="installerData.stepData.loginForm"
          type="hidden"
          name="loginForm"
        />
        {{ installerData.stepData.loginForm }}
      </div>

      <!-- Parameter 2: administrator's password -->
      <div
        v-if="'update' !== installerData.installType"
        class="field"
      >
        <div class="p-float-label">
          <Password
            v-model="installerData.stepData.passForm"
            :feedback="false"
            :input-props="{ maxlength: 80, name: 'passForm' }"
            input-id="passForm"
            toggle-mask
          />
          <label
            v-t="'Administrator password'"
            for="passForm"
          />
        </div>
        <small
          v-t="'You may want to change this'"
          class="text-error"
        />
      </div>

      <!-- Parameters 3 and 4: administrator's names -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.adminFirstName"
            input-id="adminFirstName"
            maxlength="80"
            name="adminFirstName"
            type="text"
          />
          <label
            v-t="'Administrator first name'"
            for="adminFirstName"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.adminLastName"
            input-id="adminLastName"
            maxlength="80"
            name="adminLastName"
            type="text"
          />
          <label
            v-t="'Administrator last name'"
            for="adminLastName"
          />
        </div>
      </div>

      <!-- Parameter 5: administrator's email -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.emailForm"
            input-id="emailForm"
            maxlength="80"
            name="emailForm"
            type="email"
          />
          <label
            v-t="'Administrator e-mail'"
            for="emailForm"
          />
        </div>
      </div>

      <!-- Parameter 6: administrator's telephone -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.adminPhoneForm"
            input-id="adminPhoneForm"
            maxlength="80"
            name="adminPhoneForm"
            type="text"
          />
          <label
            v-t="'Administrator telephone'"
            for="adminPhoneForm"
          />
        </div>
      </div>
    </div>

    <div>
      <h3
        v-t="'Portal'"
        class="mb-4"
      />

      <!-- First parameter: language. -->
      <div class="field">
        <div class="p-float-label">
          <Dropdown
            v-if="'update' !== installerData.installType"
            v-model="installerData.stepData.languageForm"
            :filter="true"
            :options="languages"
            input-id="language_form_list"
            option-label="english_name"
            option-value="isocode"
          />
          <InputText
            v-else
            v-model="installerData.stepData.languageForm"
            :readonly="true"
            type="text"
          />
          <label
            v-t="'Language'"
            for="language_form_list"
          />
          <input
            v-model="installerData.stepData.languageForm"
            name="languageForm"
            type="hidden"
          />
        </div>
      </div>

      <!-- Second parameter: Chamilo URL -->
      <div
        v-if="'install' === installerData.installType"
        class="field"
      >
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.urlForm"
            input-id="urlForm"
            maxlength="100"
            name="urlForm"
            type="url"
          />
          <label
            v-t="'Chamilo URL'"
            for="urlForm"
          />
        </div>
      </div>

      <!-- Parameter 9: campus name -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.campusForm"
            input-id="campusForm"
            maxlength="80"
            name="campusForm"
            type="text"
          />
          <label
            v-t="'Your portal name'"
            for="campusForm"
          />
        </div>
      </div>

      <!-- Parameter 10: institute (short) name -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.institutionForm"
            input-id="institutionForm"
            maxlength="80"
            name="institutionForm"
            type="text"
          />
          <label
            v-t="'Your company short name'"
            for="institutionForm"
          />
        </div>
      </div>

      <!-- Parameter 11: institute URL -->
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.institutionUrlForm"
            input-id="institutionUrlForm"
            maxlength="80"
            name="institutionUrlForm"
            type="text"
          />
          <label
            v-t="'URL of this company'"
            for="institutionUrlForm"
          />
        </div>
      </div>

      <label v-t="'Encryption method'" />
      <div
        v-if="'update' !== installerData.installType"
        class="formgroup-inline"
      >
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.encryptPassForm"
            value="bcrypt"
            input-id="encrypt_bcrypt"
            name="encryptPassForm"
          />
          <label
            for="encrypt_bcrypt"
            v-text="'bcrypt'"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.encryptPassForm"
            value="sha1"
            input-id="encrypt_sha1"
            name="encryptPassForm"
          />
          <label
            for="encrypt_sha1"
            v-text="'SHA1'"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.encryptPassForm"
            value="md5"
            input-id="encrypt_md5"
            name="encryptPassForm"
          />
          <label
            for="encrypt_md5"
            v-text="'MD5'"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.encryptPassForm"
            value="none"
            input-id="encrypt_none"
            name="encryptPassForm"
          />
          <label
            v-t="'None'"
            for="encrypt_none"
          />
        </div>
      </div>
      <div
        v-else
        class="formgroup-inline"
      >
        <input
          v-model="installerData.stepData.encryptPassForm"
          type="hidden"
          name="encryptPassForm"
        />
        {{ installerData.stepData.encryptPassForm }}
      </div>

      <label v-t="'Allow self-registration'" />
      <div
        v-if="'update' !== installerData.installType"
        class="formgroup-inline"
      >
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.allowSelfReg"
            value="true"
            input-id="self_reg_yes"
            name="allowSelfReg"
          />
          <label
            v-t="'Yes'"
            for="self_reg_yes"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.allowSelfReg"
            value="false"
            input-id="self_reg_no"
            name="allowSelfReg"
          />
          <label
            v-t="'No'"
            for="self_reg_no"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.allowSelfReg"
            value="approval"
            input-id="self_reg_approval"
            name="allowSelfReg"
          />
          <label
            v-t="'After approval'"
            for="self_reg_approval"
          />
        </div>
      </div>
      <div v-else>
        <input
          v-model="installerData.stepData.allowSelfReg"
          type="hidden"
          name="allowSelfReg"
        />
        <span
          v-if="'true' === installerData.stepData.allowSelfReg"
          v-t="'Yes'"
        />
        <span
          v-else-if="'false' === installerData.stepData.allowSelfReg"
          v-t="'No'"
        />
        <span
          v-else
          v-t="'After approval'"
        />
      </div>

      <label v-t="'Allow self-registration as a trainer'" />
      <div
        v-if="'update' !== installerData.installType"
        class="formgroup-inline"
      >
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.allowSelfRegProf"
            value="1"
            input-id="self_reg_prof_yes"
            name="allowSelfRegProf"
          />
          <label
            v-t="'Yes'"
            for="self_reg_prof_yes"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.allowSelfRegProf"
            value="0"
            input-id="self_reg_prof_no"
            name="allowSelfRegProf"
          />
          <label
            v-t="'No'"
            for="self_reg_prof_no"
          />
        </div>
      </div>
      <div v-else>
        <input
          v-model="installerData.stepData.allowSelfRegProf"
          type="hidden"
          name="allowSelfRegProf"
        />
        <span
          v-if="1 === installerData.stepData.allowSelfRegProf"
          v-t="'Yes'"
        />
        <span
          v-else-if="0 === installerData.stepData.allowSelfRegProf"
          v-t="'No'"
        />
      </div>
    </div>

    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <Button
          :label="t('Previous')"
          class="p-button-secondary"
          icon="mdi mdi-page-previous"
          name="step3"
          type="submit"
        />
      </div>

      <Button
        :label="t('Next')"
        class="p-button-success"
        icon="mdi mdi-page-next"
        name="step5"
        type="submit"
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

import InputText from "primevue/inputtext"
import Password from "primevue/password"
import Dropdown from "primevue/dropdown"
import Button from "primevue/button"
import RadioButton from "primevue/radiobutton"

import languages from "../../utils/languages"

const { t } = useI18n()

const installerData = inject("installerData")
</script>
