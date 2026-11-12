<template>
  <div class="install-step">
    <SectionHeader :title="t('Step 4 - Database settings')" />

    <p
      v-if="'update' === installerData.installType"
      v-text="
        t(
          'The upgrade script will recover and update the Chamilo database(s). In order to do this, this script will use the databases and settings defined below. Because our software runs on a wide range of systems and because all of them might not have been tested, we strongly recommend you do a full backup of your databases before you proceed with the upgrade!',
        )
      "
      class="RequirementContent mb-4"
    />
    <p
      v-else
      v-text="
        t(
          'The install script will create (or use) the Chamilo database using the database name given here. Please make sure the user you give has the right to create the database by the name given here. If a database with this name exists, it will be overwritten. Please do not use the root user as the Chamilo database user. This can lead to serious security issues.',
        )
      "
      class="RequirementContent mb-4"
    />

    <BaseInputText
      id="dbHostForm"
      v-model="installerData.stepData.dbHostForm"
      :help-text="t('ex. localhost')"
      :label="t('Database host')"
      maxlength="50"
      name="dbHostForm"
    />

    <BaseInputText
      id="dbPortForm"
      v-model="installerData.stepData.dbPortForm"
      :help-text="t('ex. 3306')"
      :label="t('Port')"
      maxlength="25"
      name="dbPortForm"
      type="number"
    />

    <BaseInputText
      id="dbUsernameForm"
      v-model="installerData.stepData.dbUsernameForm"
      :help-text="t('ex. root')"
      :label="t('Database user')"
      maxlength="25"
      name="dbUsernameForm"
    />

    <div class="field">
      <FloatLabel variant="on">
        <Password
          v-model="installerData.stepData.dbPassForm"
          :feedback="false"
          :input-props="{ maxlength: 255, name: 'dbPassForm' }"
          input-id="dbPassForm"
          toggle-mask
        />
        <label
          v-text="t('Database Password')"
          for="dbPassForm"
        />
      </FloatLabel>
      <small v-text="t('ex. {0}', [installerData.stepData.examplePassword])" />
    </div>

    <BaseInputText
      id="dbNameForm"
      v-model="installerData.stepData.dbNameForm"
      :help-text="
        'update' !== installerData.installType
          ? t(
              'Only letters, digits and underscore (_) are allowed in the database name. Invalid characters will be removed automatically.',
            )
          : ''
      "
      :label="t('Database name')"
      maxlength="25"
      name="dbNameForm"
    />

    <div
      v-if="'update' !== installerData.installType"
      class="formgroup-inline"
    >
      <div class="field">
        <BaseButton
          :label="t('Check database connection')"
          icon="refresh"
          is-submit
          name="step3"
          type="primary-alternative"
          value="step3"
        />
      </div>
    </div>

    <Message
      v-if="installerData.stepData.dbExists"
      :closable="false"
      severity="warn"
      class="mb-2"
    >
      <span
        v-html="
          t(
            'A database with the same name already exists. If it contains tables, they will be deleted; if it is empty, it will be reused.',
          )
        "
      />
    </Message>

    <Message
      v-if="installerData.stepData.connParams"
      id="db_status"
      :closable="false"
      severity="success"
    >
      <table>
        <tbody>
          <tr>
            <td v-text="t('Database host')" />
            <td v-text="installerData.stepData.connParams.host" />
          </tr>
          <tr>
            <td v-text="t('Database port')" />
            <td v-text="installerData.stepData.connParams.port" />
          </tr>
          <tr>
            <td v-text="t('Database driver')" />
            <td v-text="installerData.stepData.connParams.driver" />
          </tr>
        </tbody>
      </table>
      <table v-if="'update' === installerData.installType">
        <tbody>
          <tr>
            <td v-text="t('CREATE TABLE works')" />
            <td v-text="t('OK')" />
          </tr>
          <tr>
            <td v-text="t('ALTER TABLE works')" />
            <td v-text="t('OK')" />
          </tr>
          <tr>
            <td v-text="t('DROP COLUMN works')" />
            <td v-text="t('OK')" />
          </tr>
        </tbody>
      </table>
    </Message>
    <Message
      v-else
      id="db_status"
      :closable="false"
      severity="error"
    >
      {{
        t(
          "The database connection has failed. This is generally due to the wrong user, the wrong password or the wrong database prefix being set above. Please review these settings and try again.",
        )
      }}
      <code v-text="t(installerData.stepData.dbConnError)" />
    </Message>

    <hr />

    <div class="formgroup-inline">
      <div class="field">
        <BaseButton
          :label="t('Previous')"
          icon="back"
          is-submit
          name="step2"
          type="black"
        />
      </div>
      <BaseButton
        :disabled="!installerData.stepData.connParams"
        :label="t('Next')"
        icon="next"
        is-submit
        name="step4"
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
import Message from "primevue/message"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"

const { t } = useI18n()

const installerData = inject("installerData")

// The server sends the port as a number; BaseInputText expects a string.
if (null !== installerData.value.stepData.dbPortForm && undefined !== installerData.value.stepData.dbPortForm) {
  installerData.value.stepData.dbPortForm = String(installerData.value.stepData.dbPortForm)
}

// Normalize database name on the client so it matches backend sanitization.
// We only allow letters, digits and underscore. Other characters are stripped.
if (installerData.value.installType !== "update") {
  const rawName = installerData.value.stepData?.dbNameForm || ""
  installerData.value.stepData.dbNameForm = rawName.replace(/[^a-zA-Z0-9_]/g, "")
}
</script>
