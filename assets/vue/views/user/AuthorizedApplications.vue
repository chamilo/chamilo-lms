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

      <hr class="-mt-2 mb-4 -mx-4" />

      <div class="space-y-4">
        <p>
          {{
            t(
              "These applications can access Chamilo as you, using your existing account permissions and nothing more. Revoke any you no longer use or recognize.",
            )
          }}
        </p>

        <div
          v-if="isLoading"
          class="text-sm text-gray-50"
        >
          {{ t("Loading...") }}
        </div>

        <div
          v-else-if="apps.length === 0"
          class="rounded-xl border border-gray-25 bg-gray-10 p-4 text-sm text-gray-50"
        >
          {{ t("No applications are currently connected to your account.") }}
        </div>

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
                {{ t("Connected on") }}: {{ formatDate(app.connectedAt) }}
                <template v-if="app.lastUsedAt"> · {{ t("Last used") }}: {{ formatDate(app.lastUsedAt) }}</template>
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
import { useConfirmation } from "../../composables/useConfirmation"
import { useNotification } from "../../composables/notification"
import oauthConnectedAppService from "../../services/oauthConnectedAppService"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()
const notifications = useNotification()

const isLoading = ref(false)
const isRevoking = ref(null)
const apps = ref([])

function getErrorMessage(error) {
  return (
    error?.response?.data?.detail ||
    error?.response?.data?.["hydra:description"] ||
    error?.response?.data?.message ||
    t("The operation could not be completed.")
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

async function loadApps() {
  isLoading.value = true

  try {
    apps.value = await oauthConnectedAppService.list()
  } catch (error) {
    console.error("Error loading authorized applications", error)
    notifications.showErrorNotification(getErrorMessage(error))
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
    notifications.showErrorNotification(getErrorMessage(error))
  } finally {
    isRevoking.value = null
  }
}

onMounted(loadApps)
</script>
