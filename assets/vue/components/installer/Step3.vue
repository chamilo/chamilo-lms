<template>
  <div class="install-step">
    <SectionHeader :title="t('Step 3 - License')" />

    <p
      v-text="t('Chamilo is free software distributed under the GNU General Public licence (GPL).')"
      class="RequirementHeading mb-4"
    />

    <a
      v-text="t('Printable version')"
      class="mb-4"
      href="../../documentation/license.html"
      target="_blank"
    />

    <div class="field">
      <pre
        class="bg-gray-15 py-3 px-6 h-80 overflow-y-auto text-sm border border-solid border-gray-25 rounded-md"
        v-html="installerData.stepData.license"
      />
    </div>

    <BaseCheckbox
      id="accept_licence"
      v-model="acceptLicence"
      :label="t('I accept')"
      name="accept"
    />

    <hr />

    <Fieldset
      :legend="t('Contact information')"
      :toggleable="true"
      class="mt-4"
    >
      <div class="space-y-3 mb-3">
        <p v-text="t('Dear user')" />
        <p
          v-text="
            t(
              'You are about to start using one of the best Open Source e-learning platforms on the market. Like many other Open Source projects, Chamilo is backed up by a large community of students, teachers, developers, content creators and businesses who would like to promote the project better.',
            )
          "
        />
        <p
          v-text="
            t(
              'By knowing a little bit more about you, a platform administrator, one of the most important user type for us, who will manage this e-learning system, we will be able to let people know, statistically, how our software is used while respecting your privacy. We will also be able to let you know when we organize events that might be relevant to you or offer additional services that might be of value to your organisation.',
            )
          "
        />
        <p
          v-html="
            t(
              'By filling this form, you agree that the editor of Chamilo, the {0} company, registered in Belgium, and/or members of its network of partners (exclusively Chamilo services providers) might occasionally send you information by e-mail about important events or updates in the Chamilo software or community. This will help the community grow as an organized entity where information flows, with a thorough respect of your time and your privacy.',
              [
                'BeezNest Belgium'
              ]
            )
          "
        />
        <p
          v-html="
            t(
              'Please note that you are NOT REQUIRED to fill this form. If you want to remain anonymous, we will lose the opportunity to offer you all the privileges of being a registered portal administrator, but we will respect your decision. Simply leave this form empty and click Next.'
            )
          "
        />
      </div>

      <BaseInputText
        id="person_name"
        v-model="contact.personName"
        :label="t('Name')"
      />

      <BaseInputText
        id="person_email"
        v-model="contact.personEmail"
        :label="t('E-mail')"
      />

      <BaseInputText
        id="company_name"
        v-model="contact.companyName"
        :label="t('Your company\'s name')"
      />

      <BaseSelect
        v-model="contact.companyActivity"
        :label="t('Your company\'s activity')"
        :options="installerData.stepData.activitiesList"
        id="company_activity"
        name="company_activity"
        option-label="0"
        option-value="0"
      />

      <BaseSelect
        v-model="contact.jobRole"
        :label="t('Your job\'s description')"
        :options="installerData.stepData.rolesList"
        id="person_role"
        name="person_role"
        option-label="0"
        option-value="0"
      />

      <BaseSelect
        v-model="contact.companyCountry"
        :label="t('Your company\'s home country')"
        :options="installerData.stepData.countriesList"
        id="country"
        name="country"
        option-label="0"
        option-value="0"
      />

      <BaseInputText
        id="company_city"
        v-model="contact.companyCity"
        :label="t('Company city')"
      />

      <BaseSelect
        v-model="contact.contactLanguage"
        :label="t('Preferred contact language')"
        :options="installerData.stepData.languagesList"
        id="language"
        name="country"
        option-label="1"
        option-value="0"
      />

      <BaseRadioButtons
        v-model="contact.financialDecision"
        :options="[
          { label: t('Yes'), value: 1 },
          { label: t('No'), value: 0 },
        ]"
        :title="t('Do you have the power to take financial decisions on behalf of your company?')"
        class="mb-4"
        name="finalcial_decision"
      />

      <div class="formgroup-inline">
        <BaseButton
          :is-loading="sendingContactInformation"
          :label="t('Send information')"
          icon="send"
          type="black"
          @click="sendContactInformation"
        />
      </div>
    </Fieldset>

    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <BaseButton
          :label="t('Previous')"
          icon="back"
          is-submit
          name="step1"
          type="black"
        />
      </div>
      <BaseButton
        id="license-next"
        ref="btnNext"
        :disabled="!acceptLicence"
        :label="t('Next')"
        icon="next"
        is-submit
        name="step3"
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
import { useI18n } from "vue-i18n"
import { inject, reactive, ref } from "vue"
import installerService from "../../services/installerService"

import Fieldset from "primevue/fieldset"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseRadioButtons from "../basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import SectionHeader from "../layout/SectionHeader.vue"

const { t } = useI18n()

const installerData = inject("installerData")

const acceptLicence = ref(false)

const contact = reactive({
  personName: null,
  personEmail: null,
  companyName: null,
  companyActivity: null,
  jobRole: null,
  companyCountry: null,
  companyCity: null,
  contactLanguage: null,
  financialDecision: 1,
})

const sendingContactInformation = ref(false)

const btnNext = ref(null)

async function sendContactInformation() {
  if (!acceptLicence.value) {
    alert(t("You must accept the licence"))

    return
  }

  sendingContactInformation.value = true

  const formData = new FormData()
  formData.append("person_name", contact.personName)
  formData.append("person_email", contact.personEmail)
  formData.append("company_name", contact.companyName)
  formData.append("company_activity", contact.companyActivity)
  formData.append("person_role", contact.jobRole)
  formData.append("company_country", contact.companyCountry)
  formData.append("company_city", contact.companyCity)
  formData.append("language", contact.contactLanguage)
  formData.append("financial_decision", contact.financialDecision * 1 + "")

  const data = await installerService.sendContactInformation(formData)

  if ("1" === data + "") {
    alert(t("Contact information has been sent"))

    btnNext.value.$el.click()

    return
  } else if ("required_field_error" === data) {
    alert(t("The form contains incorrect or incomplete data. Please check your input."))
  } else {
    alert(
      t(
        "Your contact information could not be sent. This is probably due to a temporary network problem. Please try again in a few seconds. If the problem remains, ignore this registration process and simply click the button to go to the next step.",
      ),
    )
  }

  sendingContactInformation.value = false
}
</script>
