<template>
  <section class="w-full min-w-0 space-y-4 px-4 pb-8 sm:px-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-2xl font-semibold">{{ t("Confidential report") }}</h1>
      <BaseButton
        icon="arrow-left"
        :label="t('Back')"
        type="secondary"
        :route="{ name: 'WelfareList', query: { view: detail?.isOwner ? 'mine' : 'investigations' } }"
      />
    </div>

    <p
      v-if="errorMessage"
      role="alert"
      class="rounded-lg border border-red-200 p-3 text-red-700"
    >
      {{ errorMessage }}
    </p>

    <div
      v-if="detail"
      class="grid items-start gap-4 lg:grid-cols-12"
    >
      <div class="min-w-0 space-y-4 lg:col-span-8 xl:col-span-9">
        <section class="min-w-0 space-y-3 rounded-xl border border-gray-25 bg-white p-4 sm:p-5">
          <h2 class="break-words text-xl font-semibold">{{ detail.subject }}</h2>
          <p class="whitespace-pre-wrap break-words leading-relaxed">{{ detail.body }}</p>
        </section>

        <section class="min-w-0 rounded-xl border border-gray-25 bg-white p-4 sm:p-5">
          <h2 class="mb-2 text-lg font-semibold">{{ t("Confidential conversation") }}</h2>
          <p class="mb-4 text-sm text-gray-600">
            {{
              t(
                "Investigators can communicate through this conversation without seeing the reporter's identity unless contact sharing is enabled.",
              )
            }}
          </p>

          <div class="space-y-3">
            <article
              v-for="message in detail.messages"
              :key="message.id"
              class="min-w-0 rounded-lg border border-gray-25 p-3"
              :class="{ 'bg-gray-15': message.authorKind === 'investigator' }"
            >
              <div class="mb-1 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                <strong>{{ message.authorKind === "reporter" ? t("Reporter") : t("Investigator") }}</strong>
                <span class="break-all">{{ message.createdAt }}</span>
              </div>
              <p class="whitespace-pre-wrap break-words">{{ message.body }}</p>
            </article>
          </div>

          <form
            class="mt-4 space-y-3 border-t border-gray-25 pt-4"
            @submit.prevent="reply"
          >
            <label
              for="welfare-reply"
              class="block font-semibold"
              >{{ t("Write a message") }}</label
            >
            <textarea
              id="welfare-reply"
              v-model="replyText"
              name="reply"
              maxlength="10000"
              rows="3"
              class="form-control w-full"
            />
            <label
              v-if="detail.attachmentsEnabled"
              for="welfare-reply-files"
              class="block text-sm font-semibold"
            >
              {{ t("Additional evidence (optional)") }}
            </label>
            <input
              v-if="detail.attachmentsEnabled"
              id="welfare-reply-files"
              name="attachments[]"
              type="file"
              multiple
              accept="image/jpeg,image/png,image/webp,audio/mpeg,audio/ogg,audio/wav,video/mp4,video/webm"
              class="form-control w-full"
              @change="updateFiles"
            />
            <p
              v-if="detail.attachmentsEnabled"
              class="text-sm text-gray-600"
            >
              {{ t("Up to three files, maximum 8 MiB each; ten files per report.") }}
            </p>
            <div class="flex justify-end">
              <BaseButton
                icon="send"
                :label="t('Send message')"
                type="primary"
                is-submit
                :is-loading="sending"
              />
            </div>
          </form>
        </section>
      </div>

      <aside class="min-w-0 space-y-4 lg:sticky lg:top-4 lg:col-span-4 lg:self-start xl:col-span-3">
        <section class="min-w-0 space-y-3 rounded-xl border border-gray-25 bg-white p-4">
          <h2 class="text-lg font-semibold">{{ t("Details") }}</h2>
          <dl class="space-y-3 text-sm">
            <div>
              <dt class="font-semibold text-gray-600">{{ t("Reported user") }}</dt>
              <dd class="mt-1 break-words">{{ detail.targetName }}</dd>
            </div>
            <div>
              <dt class="font-semibold text-gray-600">{{ t("Created") }}</dt>
              <dd class="mt-1 break-words">{{ detail.createdAt }}</dd>
            </div>
          </dl>

          <div
            v-if="detail.contact"
            class="space-y-1 border-t border-gray-25 pt-3 text-sm"
          >
            <h3 class="font-semibold">{{ t("Contact shared voluntarily") }}</h3>
            <p class="break-words">{{ detail.contact.name }}</p>
            <p class="break-all">{{ detail.contact.email }}</p>
          </div>

          <div
            v-if="detail.isOwner"
            class="space-y-2 border-t border-gray-25 pt-3"
          >
            <label class="flex items-start gap-2 text-sm">
              <input
                v-model="shareContact"
                name="share_contact"
                type="checkbox"
              />
              <span>{{ t("Allow investigators to see my name and email for this report") }}</span>
            </label>
            <BaseButton
              icon="save"
              :label="t('Save')"
              type="secondary"
              :is-loading="savingContact"
              @click="saveContact"
            />
          </div>
        </section>

        <section
          v-if="detail.attachments.length"
          class="min-w-0 rounded-xl border border-gray-25 bg-white p-4"
        >
          <h2 class="mb-3 text-lg font-semibold">{{ t("Evidence") }}</h2>
          <ul class="space-y-2">
            <li
              v-for="attachment in detail.attachments"
              :key="attachment.id"
              class="min-w-0"
            >
              <button
                type="button"
                class="w-full break-all rounded-lg border border-gray-25 px-3 py-2 text-left text-sm text-primary underline disabled:opacity-50"
                :title="attachment.filename"
                :disabled="downloadingEvidenceId === attachment.id"
                @click="downloadEvidence(attachment)"
              >
                {{ attachment.filename }} ({{ Math.ceil(attachment.size / 1024) }} KB)
              </button>
            </li>
          </ul>
        </section>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import welfareService from "../../services/welfareService"

const { t } = useI18n()
const route = useRoute()
const reference = computed(() => String(route.params.reference || ""))
const detail = ref(null)
const replyText = ref("")
const files = ref([])
const shareContact = ref(false)
const errorMessage = ref("")
const sending = ref(false)
const savingContact = ref(false)
const downloadingEvidenceId = ref(null)

async function load() {
  errorMessage.value = ""
  try {
    detail.value = await welfareService.detail(reference.value)
    shareContact.value = detail.value.shareContact === true
  } catch {
    detail.value = null
    errorMessage.value = t("This confidential report is not available.")
  }
}

function updateFiles(event) {
  files.value = Array.from(event.target.files || [])
}

async function reply() {
  if (sending.value || (!replyText.value.trim() && !files.value.length)) return
  if (files.value.length > 3 || files.value.some((file) => file.size > 8388608)) {
    errorMessage.value = t("Up to three files, maximum 8 MiB each.")
    return
  }
  const payload = new FormData()
  payload.append("body", replyText.value)
  files.value.forEach((file) => payload.append("attachments[]", file))
  sending.value = true
  errorMessage.value = ""
  try {
    await welfareService.reply(reference.value, payload)
    replyText.value = ""
    files.value = []
    await load()
  } catch {
    errorMessage.value = t("Your message could not be saved.")
  } finally {
    sending.value = false
  }
}

async function saveContact() {
  savingContact.value = true
  errorMessage.value = ""
  try {
    await welfareService.changeContact(reference.value, shareContact.value)
    await load()
  } catch {
    errorMessage.value = t("Contact sharing could not be updated.")
  } finally {
    savingContact.value = false
  }
}

async function downloadEvidence(attachment) {
  if (downloadingEvidenceId.value !== null) return
  downloadingEvidenceId.value = attachment.id
  errorMessage.value = ""
  try {
    const response = await welfareService.downloadEvidence(reference.value, attachment.id)
    const blob = response.data instanceof Blob ? response.data : new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.href = url
    link.download = attachment.filename || "confidential-evidence"
    document.body.appendChild(link)
    link.click()
    link.remove()
    // Let the browser open the file-save dialog before dropping the object URL.
    window.setTimeout(() => URL.revokeObjectURL(url), 30000)
  } catch {
    errorMessage.value = t("Evidence could not be downloaded.")
  } finally {
    downloadingEvidenceId.value = null
  }
}

onMounted(load)
</script>
