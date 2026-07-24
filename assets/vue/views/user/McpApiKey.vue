<template>
  <div class="flex flex-col gap-4">
    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("MCP API key") }}</h2>
        </div>
      </template>

      <hr class="-mt-2 mb-4 -mx-4" />

      <div class="space-y-4">
        <p>
          {{
            t(
              "Use this personal key to connect a remote MCP client to Chamilo with your own account and permissions.",
            )
          }}
        </p>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
          {{
            t(
              "The complete key is displayed only once. Store it securely. Generating a new key immediately invalidates the previous one.",
            )
          }}
        </div>

        <div
          v-if="isLoading"
          class="text-sm text-gray-50"
        >
          {{ t("Loading...") }}
        </div>

        <template v-else>
          <dl class="grid grid-cols-1 gap-3 md:grid-cols-[12rem_1fr]">
            <dt class="font-semibold">{{ t("Status") }}</dt>
            <dd>
              <span
                v-if="apiKey.active"
                class="inline-flex rounded-full bg-success px-3 py-1 text-sm text-success-button-text"
              >
                {{ t("Active") }}
              </span>
              <span
                v-else
                class="inline-flex rounded-full bg-gray-20 px-3 py-1 text-sm text-gray-90"
              >
                {{ t("Inactive") }}
              </span>
            </dd>

            <dt class="font-semibold">{{ t("MCP endpoint") }}</dt>
            <dd class="break-all font-mono text-sm">{{ apiKey.endpoint || "-" }}</dd>

            <template v-if="apiKey.maskedKey">
              <dt class="font-semibold">{{ t("API key") }}</dt>
              <dd class="break-all font-mono text-sm">{{ apiKey.maskedKey }}</dd>
            </template>

            <template v-if="apiKey.createdAt">
              <dt class="font-semibold">{{ t("Created at") }}</dt>
              <dd>{{ formatDate(apiKey.createdAt) }}</dd>
            </template>

            <template v-if="apiKey.lastUsedAt">
              <dt class="font-semibold">{{ t("Last used at") }}</dt>
              <dd>{{ formatDate(apiKey.lastUsedAt) }}</dd>
            </template>
          </dl>

          <div
            v-if="plainKey"
            class="rounded-xl border border-success bg-support-2 p-4"
          >
            <p class="mb-3 font-semibold">
              {{ t("Copy this key now. It will not be shown again.") }}
            </p>

            <div class="flex flex-col gap-2 md:flex-row">
              <input
                name="mcp_api_key"
                class="min-w-0 flex-1 rounded-lg border border-gray-25 bg-white px-3 py-2 font-mono text-sm"
                :value="plainKey"
                readonly
                type="text"
                @focus="$event.target.select()"
              />
              <BaseButton
                :label="t('Copy')"
                icon="copy"
                type="primary"
                @click="copyKey"
              />
            </div>
          </div>

          <div class="flex flex-wrap gap-2">
            <BaseButton
              :disabled="isSaving"
              :is-loading="isSaving"
              :label="apiKey.active ? t('Rotate API key') : t('Generate API key')"
              icon="refresh"
              type="primary"
              @click="confirmGenerate"
            />
            <BaseButton
              v-if="apiKey.active"
              :disabled="isSaving"
              :label="t('Revoke API key')"
              icon="delete"
              type="danger"
              @click="confirmRevoke"
            />
          </div>
        </template>
      </div>
    </BaseCard>

    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Remote MCP connection") }}</h2>
        </div>
      </template>

      <hr class="-mt-2 mb-4 -mx-4" />

      <div class="space-y-3 text-sm">
        <p>{{ t("Configure your MCP client with the following values:") }}</p>
        <pre class="overflow-x-auto rounded-xl bg-gray-90 p-4 text-white">URL: {{ apiKey.endpoint || "https://your-chamilo.example/mcp" }}
Authorization: Bearer &lt;your MCP API key&gt;</pre>
        <p>
          {{
            t(
              "The key authenticates the client as your Chamilo account. It does not grant permissions that your account does not already have.",
            )
          }}
        </p>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import mcpApiKeyService from "../../services/mcpApiKeyService"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const notifications = useNotification()

const isLoading = ref(false)
const isSaving = ref(false)
const plainKey = ref("")
const apiKey = reactive(createEmptyApiKey())

function createEmptyApiKey() {
  return {
    active: false,
    maskedKey: null,
    endpoint: "",
    createdAt: null,
    lastUsedAt: null,
    revokedAt: null,
  }
}

function applyApiKey(data = {}) {
  Object.assign(apiKey, createEmptyApiKey(), data)
}

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.message ||
    t("The MCP API key operation could not be completed.")
  )
}

function formatDate(value) {
  if (!value) {
    return "-"
  }

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" }).format(date)
}

async function loadApiKey() {
  isLoading.value = true

  try {
    applyApiKey(await mcpApiKeyService.getCurrent())
  } catch (error) {
    console.error("Error loading MCP API key metadata", error)
    notifications.showErrorNotification(getErrorMessage(error))
  } finally {
    isLoading.value = false
  }
}

function confirmGenerate() {
  requireConfirmation({
    title: apiKey.active ? t("Rotate API key") : t("Generate API key"),
    message: apiKey.active
      ? t("The current key will stop working immediately. Continue?")
      : t("A new personal MCP API key will be generated. Continue?"),
    accept: generateKey,
  })
}

async function generateKey() {
  isSaving.value = true
  plainKey.value = ""

  try {
    const response = await mcpApiKeyService.generate()
    applyApiKey(response)
    plainKey.value = response.plainKey || ""
    notifications.showSuccessNotification(t("MCP API key generated"))
  } catch (error) {
    console.error("Error generating MCP API key", error)
    notifications.showErrorNotification(getErrorMessage(error))
  } finally {
    isSaving.value = false
  }
}

function confirmRevoke() {
  requireConfirmation({
    title: t("Revoke API key"),
    message: t("Connected MCP clients using this key will stop working immediately. Continue?"),
    accept: revokeKey,
  })
}

async function revokeKey() {
  isSaving.value = true
  plainKey.value = ""

  try {
    await mcpApiKeyService.revoke()
    await loadApiKey()
    notifications.showSuccessNotification(t("MCP API key revoked"))
  } catch (error) {
    console.error("Error revoking MCP API key", error)
    notifications.showErrorNotification(getErrorMessage(error))
  } finally {
    isSaving.value = false
  }
}

async function copyKey() {
  if (!plainKey.value) {
    return
  }

  try {
    await navigator.clipboard.writeText(plainKey.value)
    notifications.showSuccessNotification(t("Copied"))
  } catch (error) {
    console.error("Error copying MCP API key", error)
    notifications.showErrorNotification(t("Could not copy the API key"))
  }
}

onMounted(loadApiKey)
</script>
