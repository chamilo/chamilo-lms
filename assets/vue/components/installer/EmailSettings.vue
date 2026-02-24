<template>
  <div class="mt-8">
    <h3
      class="mb-4"
      v-text="t('Email Settings')"
    ></h3>

    <BaseInputText
      v-model="installerData.stepData.mailerDsn"
      id="mailerDsn"
      :help-text="
        t(
          'The DSN fully includes all parameters needed to connect to the mail service. You can learn more at {0}. The default value is null://null and will disable e-mail sending. Use native://default to use the default PHP configuration on your server. Here are a few examples of supported DSN syntaxes: {1}.',
          [
            'https://symfony.com/doc/6.4/mailer.html#using-built-in-transports',
            'https://symfony.com/doc/6.4/mailer.html#using-a-3rd-party-transport',
          ],
        )
      "
      :label="t('Mail DSN')"
    />

    <BaseInputText
      id="mailerFromEmail"
      v-model="installerData.stepData.mailerFromEmail"
      :help-text="
        t(
          'The e-mail address from which e-mails will be sent when the platform sends an e-mail, also used as \'reply-to\' header. We recommend using a \'no-reply\' e-mail address here, to avoid excessive filling of an e-mail box. The support e-mail address defined in the Platform section should be used to contact you, but replying to automatic notifications should not be encouraged.',
        )
      "
      :label="t('Mail: \'From\' address')"
    />

    <BaseInputText
      id="mailerFromName"
      v-model="installerData.stepData.mailerFromName"
      :help-text="
        t('The name that appears as the sender (next to the From e-mail address) when the platform sends an e-mail.')
      "
      :label="t('Mail: \'From\' name')"
    />

    <div class="field">
      <InputGroup>
        <InputText
          v-model="mailerTestDestination"
          :placeholder="t('Destination of test e-mail')"
        />
        <InputGroupAddon>
          <BaseButton
            type="info"
            icon="send"
            :label="t('Test e-mail sending')"
            :is-loading="isTesting"
            :disabled="!mailerTestDestination || isTesting"
            @click="testSmtp"
          />
        </InputGroupAddon>
      </InputGroup>
    </div>
  </div>
</template>
<script setup>
import { inject, ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import InputText from "primevue/inputtext"
import InputGroup from "primevue/inputgroup"
import InputGroupAddon from "primevue/inputgroupaddon"
import axios from "axios"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseButton from "../basecomponents/BaseButton.vue"

const { t } = useI18n()
const installerData = inject("installerData", ref({}))
const isTesting = ref(false)

const mailerTestDestination = ref("")

async function testSmtp() {
  isTesting.value = true
  try {
    if (!installerData?.value?.stepData) {
      alert(t("Installer data is missing."))

      return
    }

    const formData = new FormData()
    formData.append("mailerDsn", installerData.value.stepData.mailerDsn)
    formData.append("mailer_dsn", installerData.value.stepData.mailerDsn)
    formData.append("mailerFromEmail", installerData.value.stepData.mailerFromEmail)
    formData.append("mailerFromName", installerData.value.stepData.mailerFromName)
    formData.append("mailerTestDestination", mailerTestDestination.value)

    const { data } = await axios.post("/main/inc/ajax/install.ajax.php?a=test_mailer", formData)

    if (data.success) {
      alert(t("Test email sent successfully"))
    } else {
      alert(t("Test email failed: ") + (data.message || "Unknown error"))
    }
  } catch (e) {
    alert(t("Error during test email: ") + e.message)
  } finally {
    isTesting.value = false
  }
}

onMounted(() => {
  if (installerData.value.installType === "new" && installerData.value.stepData.smtpUniqueReplyTo === undefined) {
    installerData.value.stepData.smtpUniqueReplyTo = true
  }
})
</script>
