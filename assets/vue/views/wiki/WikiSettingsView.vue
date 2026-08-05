<template>
  <section class="space-y-6">
    <BaseToolbar class="border-b border-gray-25 bg-white">
      <template #start>
        <div class="flex items-center gap-2">
          <BaseButton
            icon="home"
            :label="t('Home')"
            only-icon
            size="large"
            type="primary-text"
            class="!flex !h-12 !w-12 !items-center !justify-center !rounded-xl !p-0 [&_.p-button-icon]:!text-2xl"
            :route="getWikiRoute()"
          />
        </div>
      </template>
    </BaseToolbar>

    <div
      v-if="successMessage"
      class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700"
      role="status"
    >
      {{ successMessage }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-xl border border-gray-20 bg-white p-6 text-center text-sm text-gray-600 shadow-sm"
      role="status"
    >
      {{ t("Loading...") }}
    </div>

    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </div>

    <BaseCard v-else>
      <template #title>
        <div class="flex items-center gap-2">
          <BaseIcon icon="settings" size="small" />
          <span>{{ t("Wiki settings") }}</span>
        </div>
      </template>

      <div class="space-y-6">
        <div
          class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800"
          role="status"
        >
          {{
            t(
              "These settings apply to the whole course, including its sessions and groups.",
            )
          }}
        </div>

        <div
          v-if="form.enabled === '0'"
          class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800"
          role="status"
        >
          {{
            t(
              "Disabling Wiki blocks normal Wiki pages and actions. This settings page remains available to course teachers.",
            )
          }}
        </div>

        <div class="grid gap-5 md:grid-cols-2">
          <BaseSelect
            id="wiki-enabled"
            v-model="form.enabled"
            name="enabled"
            :label="t('Enabled')"
            :options="yesNoOptions"
          />

          <BaseSelect
            id="wiki-categories-enabled"
            v-model="form.categoriesEnabled"
            name="wiki_categories_enabled"
            :label="t('Wiki categories enabled')"
            :options="yesNoOptions"
          />

          <BaseSelect
            id="wiki-html-strict-filtering"
            v-model="form.htmlStrictFiltering"
            name="wiki_html_strict_filtering"
            :label="t('Wiki html strict filtering')"
            :options="yesNoOptions"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex flex-wrap justify-end gap-3">
          <BaseButton
            icon="back"
            :label="t('Cancel')"
            type="secondary"
            :disabled="isSaving"
            @click="goBack"
          />
          <BaseButton
            icon="save"
            :label="t('Save settings')"
            type="success"
            :is-loading="isSaving"
            :disabled="isSaving"
            @click="saveSettings"
          />
        </div>
      </template>
    </BaseCard>
  </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseIcon from "../../components/basecomponents/BaseIcon.vue";
import BaseSelect from "../../components/basecomponents/BaseSelect.vue";
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue";
import wikiService from "../../services/wikiService";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const isLoading = ref(false);
const isSaving = ref(false);
const errorMessage = ref("");
const successMessage = ref("");
const csrfToken = ref("");
const form = reactive({
  enabled: "1",
  categoriesEnabled: "0",
  htmlStrictFiltering: "0",
});

const yesNoOptions = computed(() => [
  { label: t("Yes"), value: "1" },
  { label: t("No"), value: "0" },
]);

function getQueryValue(value) {
  return Array.isArray(value) ? value[0] : value;
}

function getSharedQuery() {
  const query = {
    cid: getQueryValue(route.query.cid),
  };
  const sid = Number(getQueryValue(route.query.sid) || 0);
  const gid = Number(getQueryValue(route.query.gid) || 0);

  if (sid > 0) {
    query.sid = sid;
  }

  if (gid > 0) {
    query.gid = gid;
  }

  if (Object.prototype.hasOwnProperty.call(route.query, "isStudentView")) {
    query.isStudentView = getQueryValue(route.query.isStudentView);
  }

  return query;
}

function getContextParams() {
  return {
    ...getSharedQuery(),
    node: Number(route.params.node || 0),
  };
}

function getWikiRoute() {
  return {
    name: "WikiPage",
    params: { node: route.params.node },
    query: {
      ...getSharedQuery(),
      title: "index",
    },
  };
}

function extractError(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.error ||
    t("An error occurred")
  );
}

async function loadSettings() {
  isLoading.value = true;
  errorMessage.value = "";

  try {
    const response = await wikiService.getSettings(getContextParams());
    const settings = response?.data || response || {};

    form.enabled = settings.enabled ? "1" : "0";
    form.categoriesEnabled = settings.categoriesEnabled ? "1" : "0";
    form.htmlStrictFiltering = settings.htmlStrictFiltering ? "1" : "0";
    csrfToken.value = String(settings.csrfToken || "");
  } catch (error) {
    console.error("Error loading Wiki settings", error);
    errorMessage.value = extractError(error);
  } finally {
    isLoading.value = false;
  }
}

async function saveSettings() {
  isSaving.value = true;
  errorMessage.value = "";
  successMessage.value = "";

  try {
    await wikiService.updateSettings(getContextParams(), {
      enabled: form.enabled === "1",
      categoriesEnabled: form.categoriesEnabled === "1",
      htmlStrictFiltering: form.htmlStrictFiltering === "1",
      csrfToken: csrfToken.value,
    });
    successMessage.value = t("Wiki settings updated");
    await loadSettings();
  } catch (error) {
    console.error("Error saving Wiki settings", error);
    errorMessage.value = extractError(error);
  } finally {
    isSaving.value = false;
  }
}

function goBack() {
  router.push(getWikiRoute());
}

onMounted(loadSettings);
</script>
