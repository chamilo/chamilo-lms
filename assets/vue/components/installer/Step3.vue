<template>
  <div class="install-step">
    <h2
      v-t="'Step 3 - Licence'"
      class="install-title mb-8"
    />

    <p
      v-t="'Chamilo is free software distributed under the GNU General Public licence (GPL).'"
      class="RequirementHeading mb-4"
    />

    <a
      v-t="'Printable version'"
      class="mb-4"
      href="/main/documentation/license.html"
      target="_blank"
    />

    <div class="field">
      <pre
        class="bg-gray-15 py-3 px-6 h-80 overflow-y-auto text-sm border border-solid border-gray-25 rounded-md"
        v-html="installerData.stepData.license"
      />
    </div>

    <div class="field-checkbox">
      <Checkbox
        v-model="acceptLicence"
        :binary="true"
        input-id="accept_licence"
        name="accept"
        value="1"
      />
      <label
        v-t="'I accept'"
        for="accept_licence"
      />
    </div>

    <hr>

    <InlineMessage severity="info">
      <i18n-t
        keypath="The images and media galleries of Chamilo use images from Nuvola, Crystal Clear and Tango icon galleries. Other images and media like diagrams and Flash animations are borrowed from Wikimedia and Ali Pakdel's and Denis Hoa's courses with their agreement and released under BY-SA Creative Commons license. You may find the license details at the {0}, where a link to the full text of the license is provided at the bottom of the page."
      >
        <a
          v-t="'CC website'"
          href="https://creativecommons.org/licenses/by-sa/3.0/"
        />
      </i18n-t>
    </InlineMessage>

    <Fieldset
      :legend="t('Contact information')"
      :toggleable="true"
      class="mt-4"
    >
      <p
        v-t="'Dear user'"
        class="mb-3"
      />
      <p
        v-t="'You are about to start using one of the best open-source e-learning platform on the market. Like many other open-source project, this project is backed up by a large community of students, teachers, developers and content creators who would like to promote the project better.'"
        class="mb-3"
      />
      <p
        v-t="'By knowing a little bit more about you, one of our most important users, who will manage this e-learning system, we will be able to let people know that our software is used and let you know when we organize events that might be relevant to you.'"
        class="mb-3"
      />
      <p
        v-t="'By filling this form, you accept that the Chamilo association or its members might send you information by e-mail about important events or updates in the Chamilo software or community. This will help the community grow as an organized entity where information flow, with a permanent respect of your time and your privacy.'"
        class="mb-3"
      />
      <p
        class="mb-3"
        v-html="t('Please note that you are <b>not required</b> to fill this form. If you want to remain anonymous, we will loose the opportunity to offer you all the privileges of being a registered portal administrator, but we will respect your decision. Simply leave this form empty and click Next')"
      />

      <div class="field">
        <div class="p-float-label">
          <InputText
            id="person_name"
            v-model="contact.personName"
            size="30"
            type="text"
          />
          <label
            v-t="'Name'"
            for="person_name"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            id="person_email"
            v-model="contact.personEmail"
            size="30"
            type="email"
          />
          <label
            v-t="'E-mail'"
            for="person_email"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            id="company_name"
            v-model="contact.companyName"
            size="30"
            type="text"
          />
          <label
            v-t="'Your company\'s name'"
            for="company_name"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <Dropdown
            v-model="contact.companyActivity"
            :options="installerData.stepData.activitiesList"
            :placeholder="t('Select one')"
            input-id="company_activity"
          />
          <label
            v-t="'Your company\'s activity'"
            for="company_activity"
          />
        </div>
      </div>
      <div class="field">
        <div class="p-float-label">
          <Dropdown
            v-model="contact.jobRole"
            :options="installerData.stepData.rolesList"
            :placeholder="t('Select one')"
            input-id="person_role"
          />
          <label
            v-t="'Your job\'s description'"
            for="person_role"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <Dropdown
            v-model="contact.companyCountry"
            :filter="true"
            :options="installerData.stepData.countriesList"
            :placeholder="t('Select one')"
            input-id="country"
          />
          <label
            v-t="'Your company\'s home country'"
            for="country"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            id="company_city"
            v-model="contact.companyCity"
            size="30"
            type="text"
          />
          <label
            v-t="'Company city'"
            for="company_city"
          />
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <Dropdown
            v-model="contact.contactLanguage"
            :filter="true"
            :options="installerData.stepData.languagesList"
            :placeholder="t('Select one')"
            input-id="language"
            option-label="1"
            option-value="0"
          />
          <label
            v-t="'Preferred contact language'"
            for="language"
          />
        </div>
      </div>

      <label v-t="'Do you have the power to take financial decisions on behalf of your company?'" />
      <div class="formgroup-inline">
        <div class="field-checkbox">
          <RadioButton
            v-model="contact.financialDecision"
            :value="true"
            input-id="final_decision_yes"
            name="finalcial_decision"
          />
          <label
            v-t="'Yes'"
            for="final_decision_yes"
          />
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="contact.financialDecision"
            :value="false"
            input-id="final_decision_no"
            name="finalcial_decision"
          />
          <label
            v-t="'No'"
            for="final_decision_no"
          />
        </div>
      </div>

      <div class="formgroup-inline">
        <Button
          :label="t('Send information')"
          :loading="sendingContactInformation"
          class="p-button-outlined p-button-plain"
          icon="mdi mdi-send-check"
          type="button"
          @click="sendContactInformation"
        />
      </div>
    </Fieldset>

    <hr>

    <div class="formgroup-inline">
      <div class="field">
        <Button
          :label="t('Previous')"
          class="p-button-plain"
          icon="mdi mdi-page-previous"
          name="step1"
          type="submit"
        />
      </div>
      <Button
        id="license-next"
        ref="btnNext"
        :disabled="!acceptLicence"
        :label="t('Next')"
        class="p-button-success"
        icon="mdi mdi-page-next"
        name="step3"
        type="submit"
      />
      <input
        id="is_executable"
        name="is_executable"
        type="hidden"
        value="-"
      >
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import { inject, reactive, ref } from 'vue';
import axios from 'axios';

import Checkbox from 'primevue/checkbox';
import InlineMessage from 'primevue/inlinemessage';
import Fieldset from 'primevue/fieldset';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import RadioButton from 'primevue/radiobutton';
import Button from 'primevue/button';

const { t } = useI18n();

const installerData = inject('installerData');

const acceptLicence = ref(false);

const contact = reactive({
  personName: null,
  personEmail: null,
  companyName: null,
  companyActivity: null,
  jobRole: null,
  companyCountry: null,
  companyCity: null,
  contactLanguage: null,
  financialDecision: true,
});

const sendingContactInformation = ref(false);

const btnNext = ref(null);

async function sendContactInformation () {
  if (!acceptLicence.value) {
    alert(t('You must accept the licence'));

    return;
  }

  sendingContactInformation.value = true;

  const formData = new FormData();
  formData.append('person_name', contact.personName);
  formData.append('person_email', contact.personEmail);
  formData.append('company_name', contact.companyName);
  formData.append('company_activity', contact.companyActivity);
  formData.append('person_role', contact.jobRole);
  formData.append('company_country', contact.companyCountry);
  formData.append('company_city', contact.companyCity);
  formData.append('language', contact.contactLanguage);
  formData.append('financial_decision', contact.financialDecision * 1 + '');

  const { data } = await axios.post(
    '/main/inc/ajax/install.ajax.php?a=send_contact_information',
    formData,
    {
      headers: { 'content-type': 'application/x-www-form-urlencoded' }
    }
  );

  if ('1' === data + '') {
    alert(
      t('Contact information has been sent')
    );

    btnNext.value.$el.click();

    return;
  } else if ('required_field_error' === data) {
    alert(
      t('The form contains incorrect or incomplete data. Please check your input.')
    );
  } else {
    alert(
      t('Your contact information could not be sent. This is probably due to a temporary network problem. Please try again in a few seconds. If the problem remains, ignore this registration process and simply click the button to go to the next step.')
    );
  }

  sendingContactInformation.value = false;
}
</script>
