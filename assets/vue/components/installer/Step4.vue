<template>
  <div class="install-step">
    <h2
      v-t="'Step 4 - Database settings'"
      class="RequirementHeading mb-8"
    />

    <p
      v-if="'update' === installerData.installType"
      v-t="'The upgrade script will recover and update the Chamilo database(s). In order to do this, this script will use the databases and settings defined below. Because our software runs on a wide range of systems and because all of them might not have been tested, we strongly recommend you do a full backup of your databases before you proceed with the upgrade!'"
      class="RequirementContent mb-4"
    />
    <p
      v-else
      v-t="'The install script will create (or use) the Chamilo database using the database name given here. Please make sure the user you give has the right to create the database by the name given here. If a database with this name exists, it will be overwritten. Please do not use the root user as the Chamilo database user. This can lead to serious security issues.'"
      class="RequirementContent mb-4"
    />

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.dbHostForm"
          :readonly="'update' === installerData.installType"
          input-id="dbHostForm"
          maxlength="50"
          name="dbHostForm"
          type="text"
        />
        <label
          v-t="'Database host'"
          for="dbHostForm"
        />
      </div>
      <small v-t="'ex. localhost'" />
    </div>

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.dbPortForm"
          :readonly="'update' === installerData.installType"
          input-id="dbPortForm"
          maxlength="25"
          name="dbPortForm"
          type="number"
        />
        <label
          v-t="'Port'"
          for="dbPortForm"
        />
      </div>
      <small v-t="'ex. 3306'" />
    </div>

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.dbUsernameForm"
          :readonly="'update' === installerData.installType"
          input-id="dbUsernameForm"
          maxlength="25"
          name="dbUsernameForm"
          type="text"
        />
        <label
          v-t="'Database login'"
          for="dbUsernameForm"
        />
      </div>
      <small v-t="'ex. root'" />
    </div>

    <div class="field">
      <div class="p-float-label">
        <Password
          v-model="installerData.stepData.dbPassForm"
          :feedback="false"
          :input-props="{ maxlength: 25, name: 'dbPassForm' }"
          :readonly="'update' === installerData.installType"
          input-id="dbPassForm"
          toggle-mask
        />
        <label
          v-t="'Database password'"
          for="dbPassForm"
        />
      </div>
      <small v-t="{ path: 'ex. {examplePassword}', args: { examplePassword: installerData.stepData.examplePassword } }" />
    </div>

    <div class="field">
      <div class="p-float-label">
        <InputText
          v-model="installerData.stepData.dbNameForm"
          :readonly="'update' === installerData.installType"
          input-id="dbNameForm"
          maxlength="25"
          name="dbNameForm"
          type="text"
        />
        <label
          v-t="'Database name'"
          for="dbNameForm"
        />
      </div>
    </div>

    <div
      v-if="'update' !== installerData.installType"
      class="formgroup-inline"
    >
      <div class="field">
        <Button
          :label="t('Check database connection')"
          class="p-button-outlined"
          icon="mdi mdi-database-sync"
          name="step3"
          type="submit"
          value="step3"
        />
      </div>
    </div>

    <Message
      v-if="installerData.stepData.dbExists"
      :closable="false"
      severity="warn"
    >
      <i18n-t keypath="A database with the name {0}. It will be {1}.">
        <b>already exists</b>
        <b>deleted</b>
      </i18n-t>
    </Message>

    <Message
      v-if="installerData.stepData.connParams"
      id="db_status"
      :closable="false"
      severity="success"
    >
      <table>
        <tr>
          <td v-t="'Database host'" />
          <td v-text="installerData.stepData.connParams.host" />
        </tr>
        <tr>
          <td v-t="'Database port'" />
          <td v-text="installerData.stepData.connParams.port" />
        </tr>
        <tr>
          <td v-t="'Database driver'" />
          <td v-text="installerData.stepData.connParams.driver" />
        </tr>
      </table>
      <table v-if="'update' === installerData.installType">
        <tr>
          <td v-t="'CREATE TABLE works'" />
          <td v-t="'OK'" />
        </tr>
        <tr>
          <td v-t="'ALTER TABLE works'" />
          <td v-t="'OK'" />
        </tr>
        <tr>
          <td v-t="'DROP COLUMN works'" />
          <td v-t="'OK'" />
        </tr>
      </table>
    </Message>
    <Message
      v-else
      id="db_status"
      :closable="false"
      severity="error"
    >
      {{ t('The database connection has failed. This is generally due to the wrong user, the wrong password or the wrong database prefix being set above. Please review these settings and try again.') }}
      <code v-t="installerData.stepData.dbConnError" />
    </Message>

    <hr>

    <div class="formgroup-inline">
      <div class="field">
        <Button
          :label="t('Previous')"
          class="p-button-secondary"
          icon="mdi mdi-page-previous"
          name="step2"
          type="submit"
        />
      </div>
      <Button
        :disabled="!installerData.stepData.connParams"
        :label="t('Next')"
        class="p-button-success"
        icon="mdi mdi-page-next"
        name="step4"
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
import { inject } from 'vue';
import { useI18n } from 'vue-i18n';

import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const { t } = useI18n();

const installerData = inject('installerData');

// Database Name fix replace weird chars
if ('update' !== installerData.value.installType) {
  installerData.value.dbNameForm = installerData.value.dbNameForm.replace(/[-*$ .]/g, '');
}
</script>
