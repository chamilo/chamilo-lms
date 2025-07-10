<template>
  <div class="mt-8">
    <h3
      class="mb-4"
      v-t="'Email Settings'"
    ></h3>

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.fromEmail"
          input-id="fromEmail"
          type="email"
        />
        <label
          for="fromEmail"
          v-t="'From email address'"
        ></label>
      </div>
    </div>

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.fromName"
          input-id="fromName"
          type="text"
        />
        <label
          for="fromName"
          v-t="'From name'"
        ></label>
      </div>
    </div>

    <div class="field">
      <div class="p-float-label">
        <Select
          v-model="installerData.stepData.mailer"
          :options="mailerOptions"
          input-id="mailer"
          option-label="label"
          option-value="value"
        />
        <label
          for="mailer"
          v-t="'Mailer'"
        ></label>
      </div>
    </div>

    <div v-if="installerData.stepData.mailer === 'smtp'">
      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.smtpHost"
            input-id="smtpHost"
            type="text"
          />
          <label
            for="smtpHost"
            v-t="'SMTP Host'"
          ></label>
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.smtpPort"
            input-id="smtpPort"
            type="number"
          />
          <label
            for="smtpPort"
            v-t="'SMTP Port'"
          ></label>
        </div>
      </div>

      <label v-t="'SMTP Authentication'"></label>
      <div class="formgroup-inline mb-3">
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpAuth"
            input-id="smtpAuthYes"
            :value="true"
          />
          <label
            for="smtpAuthYes"
            v-t="'Yes'"
          ></label>
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpAuth"
            input-id="smtpAuthNo"
            :value="false"
          />
          <label
            for="smtpAuthNo"
            v-t="'No'"
          ></label>
        </div>
      </div>

      <div v-if="installerData.stepData.smtpAuth">
        <div class="field">
          <div class="p-float-label">
            <InputText
              v-model="installerData.stepData.smtpUser"
              input-id="smtpUser"
              type="text"
            />
            <label
              for="smtpUser"
              v-t="'SMTP Username'"
            ></label>
          </div>
        </div>

        <div class="field">
          <div class="p-float-label">
            <Password
              v-model="installerData.stepData.smtpPass"
              input-id="smtpPass"
              toggle-mask
            />
            <label
              for="smtpPass"
              v-t="'SMTP Password'"
            ></label>
          </div>
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <Select
            v-model="installerData.stepData.smtpSecure"
            :options="[
              { label: 'tls', value: 'tls' },
              { label: 'ssl', value: 'ssl' },
            ]"
            input-id="smtpSecure"
            option-label="label"
            option-value="value"
          />
          <label
            for="smtpSecure"
            v-t="'Encryption'"
          ></label>
        </div>
      </div>

      <div class="field">
        <div class="p-float-label">
          <InputText
            v-model="installerData.stepData.smtpCharset"
            input-id="smtpCharset"
            type="text"
          />
          <label
            for="smtpCharset"
            v-t="'Charset'"
          ></label>
        </div>
      </div>

      <label v-t="'Unique Reply-To'"></label>
      <div class="formgroup-inline mb-3">
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpUniqueReplyTo"
            input-id="smtpUniqueReplyToYes"
            :value="true"
          />
          <label
            for="smtpUniqueReplyToYes"
            v-t="'Yes'"
          ></label>
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpUniqueReplyTo"
            input-id="smtpUniqueReplyToNo"
            :value="false"
          />
          <label
            for="smtpUniqueReplyToNo"
            v-t="'No'"
          ></label>
        </div>
      </div>

      <label v-t="'Enable SMTP debug'"></label>
      <div class="formgroup-inline mb-4">
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpDebug"
            input-id="smtpDebugYes"
            :value="true"
          />
          <label
            for="smtpDebugYes"
            v-t="'Yes'"
          ></label>
        </div>
        <div class="field-checkbox">
          <RadioButton
            v-model="installerData.stepData.smtpDebug"
            input-id="smtpDebugNo"
            :value="false"
          />
          <label
            for="smtpDebugNo"
            v-t="'No'"
          ></label>
        </div>
      </div>

      <div class="field">
        <Button
          :label="t('Test send e-mail')"
          class="p-button-success"
          icon="mdi mdi-email-send"
          :loading="isTesting"
          :disabled="isTesting"
          @click="testSmtp"
        />
      </div>
    </div>
  </div>
</template>
<script setup>
import { inject, ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import InputText from "primevue/inputtext"
import Password from "primevue/password"
import Select from "primevue/select"
import RadioButton from "primevue/radiobutton"
import Button from "primevue/button"
import axios from "axios"

const { t } = useI18n()
const installerData = inject("installerData", ref({}))
const isTesting = ref(false)

const mailerOptions = [
  { label: "mail", value: "mail" },
  { label: "sendmail", value: "sendmail" },
  { label: "smtp", value: "smtp" },
]

async function testSmtp() {
  isTesting.value = true
  try {
    if (!installerData?.value?.stepData) {
      alert(t("Installer data is missing."))
      return
    }

    const smtpFields = [
      "fromEmail",
      "fromName",
      "mailer",
      "smtpHost",
      "smtpPort",
      "smtpAuth",
      "smtpUser",
      "smtpPass",
      "smtpSecure",
      "smtpCharset",
      "smtpUniqueReplyTo",
      "smtpDebug",
    ]

    const payload = {}
    for (const field of smtpFields) {
      payload[field] = installerData.value.stepData[field]
    }

    const formData = new FormData()
    for (const [key, value] of Object.entries(payload)) {
      if (value !== undefined) {
        formData.append(key, value)
      }
    }

    const { data } = await axios.post("/main/inc/ajax/install.ajax.php?a=test_smtp", formData)

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
