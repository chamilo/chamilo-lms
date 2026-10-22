<template>
  <section class="mx-auto max-w-3xl space-y-5 pb-8">
    <h1 class="text-2xl font-semibold">{{ t("New confidential report") }}</h1>
    <p class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900">
      {{
        t(
          "Your identity is protected from investigators by default, but avoid including identifying details in the report text or attachments. This is not an emergency service.",
        )
      }}
    </p>
    <p
      v-if="errorMessage"
      role="alert"
      class="rounded-lg border border-red-200 p-3 text-red-700"
    >
      {{ errorMessage }}
    </p>

    <form
      class="space-y-4 rounded-xl border border-gray-25 bg-white p-5"
      @submit.prevent="submit"
    >
      <div>
        <label
          for="welfare-target-search"
          class="mb-1 block font-semibold"
          >{{ t("Find the user involved") }}</label
        >
        <div class="flex gap-2">
          <input
            id="welfare-target-search"
            v-model="searchTerm"
            name="user_search"
            type="search"
            minlength="3"
            maxlength="100"
            class="form-control w-full"
            :placeholder="t('Name or username')"
          />
          <BaseButton
            icon="search"
            :label="t('Search')"
            :is-loading="searching"
            @click="searchTargets"
          />
        </div>
        <select
          id="welfare-target"
          v-model.number="targetUserId"
          name="target_user_id"
          required
          class="form-control mt-2 w-full"
        >
          <option :value="0">{{ t("Select a user") }}</option>
          <option
            v-for="user in targets"
            :key="user.id"
            :value="user.id"
          >
            {{ user.label }}
          </option>
        </select>
      </div>

      <div>
        <label
          for="welfare-subject"
          class="mb-1 block font-semibold"
          >{{ t("Subject") }}</label
        >
        <input
          id="welfare-subject"
          v-model="subject"
          name="subject"
          required
          minlength="3"
          maxlength="160"
          class="form-control w-full"
        />
      </div>
      <div>
        <label
          for="welfare-body"
          class="mb-1 block font-semibold"
          >{{ t("Describe the situation") }}</label
        >
        <textarea
          id="welfare-body"
          v-model="body"
          name="body"
          required
          minlength="10"
          maxlength="10000"
          rows="8"
          class="form-control w-full"
        />
      </div>
      <div v-if="attachmentsEnabled">
        <label
          for="welfare-files"
          class="mb-1 block font-semibold"
          >{{ t("Evidence (optional)") }}</label
        >
        <input
          id="welfare-files"
          name="attachments[]"
          type="file"
          accept="image/jpeg,image/png,image/webp,audio/mpeg,audio/ogg,audio/wav,video/mp4,video/webm"
          multiple
          class="form-control w-full"
          @change="updateFiles"
        />
        <p class="mt-1 text-sm text-gray-600">
          {{ t("Up to three files, maximum 8 MiB each. Images, audio or short videos.") }}
        </p>
      </div>
      <label class="flex items-start gap-2 text-sm">
        <input
          v-model="shareContact"
          name="share_contact"
          type="checkbox"
          class="mt-1"
        />
        <span>{{
          t(
            "I agree that authorized investigators may see my name and email for this specific report. I can withdraw this permission later.",
          )
        }}</span>
      </label>
      <div class="flex justify-end gap-2">
        <BaseButton
          icon="arrow-left"
          type="secondary"
          :label="t('Back')"
          :route="{ name: 'WelfareList' }"
        />
        <BaseButton
          icon="send"
          :is-loading="saving"
          :label="t('Send confidential report')"
          is-submit
          type="primary"
        />
      </div>
    </form>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import welfareService from "../../services/welfareService"

const { t } = useI18n()
const router = useRouter()
const searchTerm = ref("")
const targets = ref([])
const targetUserId = ref(0)
const subject = ref("")
const body = ref("")
const shareContact = ref(false)
const files = ref([])
const attachmentsEnabled = ref(false)
const searching = ref(false)
const saving = ref(false)
const errorMessage = ref("")

async function searchTargets() {
  if (searchTerm.value.trim().length < 3) return
  searching.value = true
  errorMessage.value = ""
  targetUserId.value = 0
  try {
    targets.value = (await welfareService.targets(searchTerm.value.trim())).items || []
  } catch {
    errorMessage.value = t("Could not search users")
  } finally {
    searching.value = false
  }
}

function updateFiles(event) {
  files.value = Array.from(event.target.files || [])
}

async function submit() {
  if (saving.value || !targetUserId.value) return
  errorMessage.value = ""
  if (files.value.length > 3 || files.value.some((file) => file.size > 8388608)) {
    errorMessage.value = t("Up to three files, maximum 8 MiB each.")
    return
  }
  const payload = new FormData()
  payload.append("targetUserId", String(targetUserId.value))
  payload.append("subject", subject.value)
  payload.append("body", body.value)
  payload.append("shareContact", shareContact.value ? "1" : "0")
  files.value.forEach((file) => payload.append("attachments[]", file))
  saving.value = true
  try {
    const result = await welfareService.create(payload)
    await router.replace({ name: "WelfareDetail", params: { reference: result.reference } })
  } catch {
    errorMessage.value = t("The confidential report could not be saved.")
  } finally {
    saving.value = false
  }
}
onMounted(async () => {
  try {
    const config = await welfareService.list()
    attachmentsEnabled.value = config.attachmentsEnabled === true
  } catch {
    attachmentsEnabled.value = false
  }
})
</script>
