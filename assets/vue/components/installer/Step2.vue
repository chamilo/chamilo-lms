<template>
  <div class="install-step">
    <SectionHeader :title="t('Step 2 - Requirements')" />

    <p class="RequirementText mb-4">
      <strong v-text="t('Please read the following requirements thoroughly.')" />
    </p>

    <i18n-t
      class="mb-4"
      keypath="For more details {0}"
      tag="p"
    >
      <a
        v-text="t('Read the installation guide')"
        href="../../documentation/installation_guide.html"
        target="_blank"
        rel="noopener noreferrer"
      />
    </i18n-t>

    <p
      v-if="'update' === installerData.installType"
      class="mb-4"
    >
      {{ t("If you plan to upgrade from an older version of Chamilo, you might want to") }}
      <a
        href="../../documentation/changelog.html"
        target="_blank"
        v-text="t('have a look at the changelog')"
      />
      {{ t("to know what's new and what has been changed.") }}
    </p>

    <h3
      class="install-subtitle mt-8 mb-4"
      v-text="t('Server requirements')"
    />

    <Message
      v-if="!installerData.stepData.timezone"
      :closable="false"
      severity="warn"
    >
      {{
        t(
          "We have detected that your PHP installation does not define the date.timezone setting. This is a requirement of Chamilo. Please make sure it is configured by checking your php.ini configuration, otherwise you will run into problems. We warned you!",
        )
      }}
    </Message>

    <div class="text-center mb-4">
      <p class="text-body-2 font-semibold mb-2">{{ t("PHP version") }} >= {{ installerData.phpRequiredVersion }}</p>
      <p
        v-if="installerData.stepData.isVersionPassed"
        class="text-success text-body-1 font-semibold"
      >
        <span
          aria-hidden="true"
          class="mdi mdi-check"
        />
        {{ t("Your PHP version matches the minimum requirement:") }}
        {{ installerData.stepData.phpVersion }}
      </p>
      <p
        v-else
        class="text-error text-body-1 font-semibold"
        v-text="
          t(
            'Your PHP version does not match the requirements for this software. Please check you have the latest version, then try again.',
          )
        "
      />
    </div>

    <div
      class="grid grid-flow-row-dense grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-3 gap-y-4 place-items-center mb-4"
    >
      <p
        v-for="(extension, i) in installerData.stepData.extensions"
        :key="i"
        class="text-center"
      >
        <a
          :href="extension.url"
          class="block"
          v-text="extension.title"
        />
        <BaseTag
          :icon="SEVERITY_ICONS[extension.status.severity]"
          :label="extension.status.message"
          :type="extension.status.severity"
        />
      </p>
    </div>

    <h3
      class="install-subtitle mt-8 mb-4"
      v-text="t('Recommended settings')"
    />
    <p
      class="install-requirement mb-4"
      v-text="
        t(
          'Recommended settings for your server configuration. These settings are set in the php.ini configuration file on your server.',
        )
      "
    />
    <div class="table-responsive">
      <table class="requirements-list">
        <thead>
          <tr>
            <th
              class="requirements-item"
              v-text="t('Setting')"
            />
            <th
              class="requirements-recommended"
              v-text="t('Recommended settings')"
            />
            <th
              class="requirements-value"
              v-text="t('Currently')"
            />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="({ title, recommended, current }, i) in installerData.stepData.phpIni"
            :key="i"
          >
            <td
              class="requirements-item"
              v-text="title"
            />
            <td class="requirements-recommended">
              <BaseTag
                :label="String(recommended)"
                type="success"
              />
            </td>
            <td class="requirements-value">
              <BaseTag
                :label="String(current.value)"
                :type="current.severity"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <h3
      class="install-subtitle mt-8 mb-4"
      v-text="t('Directory and files permissions')"
    />
    <p
      v-text="
        t(
          'Some directories and the files they include must be writable by the web server in order for Chamilo to run (user uploaded files, homepage html files, ...). This might imply a manual change on your server (outside of this interface).',
        )
      "
      class="mb-4"
    />

    <div class="table-responsive">
      <table class="requirements-list">
        <tbody>
          <tr
            v-for="({ item, status }, i) in installerData.stepData.pathPermissions"
            :key="i"
          >
            <td v-text="item" />
            <td>
              <BaseTag
                v-if="true === status"
                :label="t('Writable')"
                type="success"
              />
              <BaseTag
                v-else-if="false === status"
                :label="t('Not writable')"
                type="danger"
              />
              <BaseTag
                v-else
                :label="String(status)"
                type="info"
              />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="
        'update' === installerData.installType &&
        (!installerData.updatePath || installerData.badUpdatePath || installerData.upgradeNotAuthorised)
      "
    >
      <br />

      <Message
        v-if="installerData.badUpdatePath"
        :closable="false"
        severity="warn"
      >
        <strong v-text="t('Error')" /><br />
        Chamilo {{ installerData.upgradeFromVersion[0].split(".").slice(0, 2).join(".") }}
        {{ t("has not been found in that directory") }}
      </Message>

      <Message
        v-if="installerData.upgradeNotAuthorised"
        :closable="false"
        severity="warn"
      >
        <strong v-text="t('Error')" /><br />
        {{
          t(
            "The installer has no login of its own, so an upgrade must be authorised on the server. Create an empty file named {0} in the project root, next to .env, then continue.",
            [installerData.upgradeFlagFile],
          )
        }}
      </Message>

      <br />

      <!-- form inputs for old version path -->
      <BaseInputText
        id="updatePath"
        v-model="updatePathInput"
        :label="t('Old version\'s root path')"
        autocomplete="off"
      />
      <input
        :value="updatePathInput"
        name="updatePath"
        type="hidden"
      />

      <div class="formgroup-inline">
        <div class="field">
          <BaseButton
            :label="t('Back')"
            icon="back"
            is-submit
            name="step1"
            type="black"
          />
        </div>
        <BaseButton
          :label="t('Next')"
          :name="installerData.stepData.step2_update_6 ? 'step2_update_6' : 'step2_update_8'"
          icon="next"
          is-submit
          type="secondary"
        />
        <input
          id="is_executable"
          name="is_executable"
          type="hidden"
          value="-"
        />
      </div>
    </div>
    <div v-else>
      <div v-if="installerData.stepData.notWritable.length > 0">
        <strong
          v-text="t('Warning!')"
          class="text-error"
        />
        <p class="text-error">
          {{
            t(
              "Some files or folders don't have writing permission. To be able to install Chamilo you should first change their permissions (using CHMOD). Please read the",
            )
          }}
          <a
            href="../../documentation/installation_guide.html"
            target="_blank"
            rel="noopener noreferrer"
            v-text="t('Installation guide')"
          />
        </p>
        <ul class="list-disc list-inside">
          <li
            v-for="(notWritable, i) in installerData.stepData.notWritable"
            :key="i"
            class="text-error"
            v-text="notWritable"
          />
        </ul>
      </div>

      <div v-else-if="installerData.stepData.existsConfigurationFile">
        <!-- Check wether a Chamilo configuration file already exists -->
        <Message
          :closable="false"
          severity="warn"
        >
          {{ t("Warning! The installer has detected an existing Chamilo platform on your system") }}
        </Message>
      </div>

      <div v-if="installerData.stepData.deprecatedToRemove.length > 0">
        <p
          class="text-error"
          v-html="
            t(
              'Because the <code>newscorm</code> and <code>exercice</code> directories were renamed to <code>lp</code> and <code>exercise</code> respectively, is necessary to delete or rename to <code>newscorm_old</code> and <code>exercice_old</code>',
            )
          "
        />
        <ul class="list-disc list-inside">
          <li
            v-for="(deprecatedToRemove, i) in installerData.stepData.deprecatedToRemove"
            :key="i"
            class="text-error"
            v-text="deprecatedToRemove"
          />
        </ul>
      </div>

      <hr />

      <div class="formgroup-inline">
        <!-- And now display the choice buttons (go back or install) -->
        <div class="field">
          <BaseButton
            :label="t('Previous')"
            icon="back"
            name="step1"
            type="black"
            @click.prevent="goToIndex"
          />
        </div>
        <div class="field">
          <BaseButton
            :disabled="Boolean(installerData.stepData.installError || installerData.isUpdateAvailable)"
            :label="t('New installation')"
            icon="next"
            is-submit
            name="step2_install"
            type="success"
          />
        </div>
        <BaseButton
          :label="t('Upgrade Chamilo LMS version')"
          icon="next"
          name="step2_update_8"
          type="secondary"
          @click.prevent="goToUpgrade"
        />
        <input
          id="is_executable"
          name="is_executable"
          type="hidden"
          value="-"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
import { inject, ref, watch } from "vue"

import Message from "primevue/message"
import SectionHeader from "../layout/SectionHeader.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import BaseTag from "../basecomponents/BaseTag.vue"

const { t } = useI18n()

const installerData = inject("installerData")

// Icon of each extension status, keyed by the severity the server sends.
const SEVERITY_ICONS = {
  success: "mdi mdi-check",
  warning: "mdi mdi-alert",
  danger: "mdi mdi-close",
}
const updatePathInput = ref(installerData.value?.updatePath || "")

watch(
  () => installerData.value?.updatePath,
  (value) => {
    updatePathInput.value = value || ""
  },
)

function goToUpgrade() {
  window.location = `/main/install/index.php?running=1&installType=${installerData.installType || "update"}&step=step2_update_8`
}

function goToIndex() {
  window.location = "index.php"
}
</script>
