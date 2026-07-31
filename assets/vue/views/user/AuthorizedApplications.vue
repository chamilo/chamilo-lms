<template>
  <div class="flex flex-col gap-4">
    <BaseCard
      class="bg-white"
      plain
    >
      <template #header>
        <div class="px-4 py-2 -mb-2 bg-gray-15">
          <h2 class="text-h5">{{ t("Authorized applications") }}</h2>
        </div>
      </template>

      <div class="space-y-4">
        <p>
          {{
            t(
              "These applications can access Chamilo as you, using your existing account permissions and nothing more. Revoke any you no longer use or recognize.",
            )
          }}
        </p>

        <div class="rounded-xl border border-gray-25 bg-gray-10 p-4 text-sm space-y-2">
          <p>
            {{ t("You can also connect an MCP client directly by pointing it to the following endpoint:") }}
          </p>
          <div class="flex flex-col gap-2 md:flex-row">
            <input
              name="mcp_endpoint"
              class="min-w-0 flex-1 rounded-lg border border-gray-25 bg-white px-3 py-2 font-mono text-sm"
              :value="mcpEndpoint"
              readonly
              type="text"
              @focus="$event.target.select()"
            />
            <BaseButton
              :label="t('Copy')"
              icon="copy"
              type="primary"
              @click="copyMcpEndpoint"
            />
          </div>
        </div>

        <div
          v-if="isLoading"
          class="text-sm text-gray-50"
        >
          {{ t("Loading...") }}
        </div>

        <Message
          v-else-if="apps.length === 0"
          severity="info"
        >
          {{ t("No applications are currently connected to your account.") }}
        </Message>

        <div
          v-else
          class="space-y-3"
        >
          <div
            v-for="app in apps"
            :key="app.id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-25 p-4"
          >
            <div class="min-w-0">
              <p class="font-semibold text-gray-90">{{ app.clientName }}</p>
              <p class="text-sm text-gray-50">
                {{ t("Connected on") }}: {{ abbreviatedDatetime(app.connectedAt) }}
                <template v-if="app.lastUsedAt">
                  · {{ t("Last used") }}: {{ abbreviatedDatetime(app.lastUsedAt) }}</template
                >
              </p>
            </div>

            <BaseButton
              :disabled="isRevoking === app.id"
              :is-loading="isRevoking === app.id"
              :label="t('Revoke')"
              icon="delete"
              type="danger"
              @click="confirmRevoke(app)"
            />
          </div>
        </div>
      </div>
    </BaseCard>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import Message from "primevue/message"
import { useConfirmation } from "../../composables/useConfirmation"
import { useFormatDate } from "../../composables/formatDate"
import { useNotification } from "../../composables/notification"
import oauthConnectedAppService from "../../services/oauthConnectedAppService"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const notifications = useNotification()
const { abbreviatedDatetime } = useFormatDate()

const isLoading = ref(true)
const isRevoking = ref(null)
const apps = ref([])
const mcpEndpoint = `${window.location.origin}/mcp`

async function loadApps() {
  isLoading.value = true

  try {
    apps.value = await oauthConnectedAppService.list()
  } catch (error) {
    console.error("Error loading authorized applications", error)
    notifications.showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

function confirmRevoke(app) {
  requireConfirmation({
    title: t("Revoke"),
    message: t("%s will no longer be able to access your Chamilo account. Continue?", [app.clientName]),
    accept: () => revokeApp(app),
  })
}

async function revokeApp(app) {
  isRevoking.value = app.id

  try {
    await oauthConnectedAppService.revoke(app.id)
    apps.value = apps.value.filter((item) => item.id !== app.id)
    notifications.showSuccessNotification(t("Application revoked"))
  } catch (error) {
    console.error("Error revoking authorized application", error)
    notifications.showErrorNotification(error)
  } finally {
    isRevoking.value = null
  }
}

async function copyMcpEndpoint() {
  try {
    await navigator.clipboard.writeText(mcpEndpoint)
    notifications.showSuccessNotification(t("Copied to clipboard"))
  } catch (error) {
    console.error("Error copying MCP endpoint", error)
    notifications.showErrorNotification(t("Could not copy the MCP endpoint"))
  }
}

onMounted(loadApps)
</script>
