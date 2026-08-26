<template>
  <div class="flex flex-col gap-8">
    <div class="flex items-center justify-between">
      <SectionHeader :title="t('Edit user information')" />
      <div class="flex gap-1">
        <BaseButton
          :label="t('Information')"
          icon="information"
          only-icon
          size="small"
          :to-url="`/main/admin/user_information.php?user_id=${userId}`"
          type="primary-text"
        />
        <BaseButton
          :disabled="!data.canLoginAs"
          icon="account-key"
          :label="t('Login as')"
          only-icon
          size="small"
          :to-url="
            data.canLoginAs ? `/admin/user-list-login-as?user_id=${userId}&sec_token=${data.loginAsToken}` : null
          "
          type="primary-text"
        />
      </div>
    </div>

    <div
      v-if="conflictMessages.length > 0"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-body-2 text-red-700"
    >
      <p class="font-semibold">{{ errorMessage }}</p>
      <ul class="mt-2 list-disc pl-5">
        <li
          v-for="(conflict, index) in conflictMessages"
          :key="index"
        >
          {{ conflict }}
        </li>
      </ul>
    </div>
    <div
      v-else-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 p-4 text-body-2 text-red-700"
    >
      {{ errorMessage }}
    </div>

    <form
      class="flex flex-col gap-8"
      @submit.prevent="submit"
    >
      <Fieldset :legend="t('Personal data')">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <template v-if="data.westernNameOrder">
            <BaseInputText
              id="firstname"
              v-model="form.firstname"
              :label="t('First name')"
              name="firstname"
              required
            />
            <BaseInputText
              id="lastname"
              v-model="form.lastname"
              :label="t('Last name')"
              name="lastname"
              required
            />
          </template>
          <template v-else>
            <BaseInputText
              id="lastname"
              v-model="form.lastname"
              :label="t('Last name')"
              name="lastname"
              required
            />
            <BaseInputText
              id="firstname"
              v-model="form.firstname"
              :label="t('First name')"
              name="firstname"
              required
            />
          </template>

          <BaseInputText
            id="official_code"
            v-model="form.officialCode"
            :label="t('Official code')"
            name="official_code"
          />
          <BaseInputText
            id="email"
            v-model="form.email"
            :label="t('E-mail')"
            :required="data.emailRequired"
            name="email"
          />
        </div>

        <p
          v-if="data.user.creator"
          class="mt-4 text-body-2 text-gray-70"
        >
          {{ t("Created by {0} on {1}", [data.user.creator.username, formattedCreatedAt]) }}
        </p>
      </Fieldset>

      <Fieldset :legend="t('Authentication')">
        <div class="flex flex-col gap-4">
          <BaseInputText
            v-if="!data.loginIsEmail"
            id="username"
            v-model="form.username"
            :label="t('Username')"
            name="username"
            required
          />

          <p
            v-if="authSourceOptions.length <= 1"
            class="text-body-2 text-gray-70"
          >
            {{ t("Authentication methods") }}: {{ authSourceOptions[0]?.label }}
          </p>
          <BaseMultiSelect
            v-else
            v-model="form.authSource"
            :label="t('Authentication methods')"
            :options="authSourceOptions"
            input-id="auth_source"
            option-label="label"
            option-value="value"
          />

          <div
            v-if="hasPlatformAuth"
            class="rounded-xl border border-gray-25 bg-gray-5 p-4"
          >
            <p class="mb-3 text-body-1 font-semibold text-gray-90">{{ t("Password") }}</p>
            <div class="flex flex-col gap-3">
              <BaseRadioButtons
                v-model="form.resetPassword"
                name="reset_password"
                :options="resetPasswordOptions"
              />
              <div
                v-if="'manual' === form.resetPassword"
                class="relative mb-5 [&>.field]:mb-0"
              >
                <BaseInputText
                  id="password"
                  v-model="form.password"
                  :label="t('Password')"
                  autocomplete="new-password"
                  name="password"
                  :type="showPassword ? 'text' : 'password'"
                />
                <button
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                  :title="showPassword ? t('Hide password') : t('Show password')"
                  type="button"
                  @click="showPassword = !showPassword"
                >
                  <BaseIcon :icon="showPassword ? 'eye-off' : 'eye-on'" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </Fieldset>

      <Fieldset :legend="t('Account')">
        <div class="flex flex-col gap-4">
          <BaseMultiSelect
            v-model="form.roles"
            :label="t('Roles')"
            :options="data.roleOptions"
            input-id="roles"
            option-label="label"
            option-value="value"
          />

          <BaseSelect
            id="locale"
            v-model="form.locale"
            :label="t('Language')"
            :options="languageOptions"
            option-label="label"
            option-value="value"
          />

          <BaseRadioButtons
            v-model="form.sendMail"
            :options="sendMailOptions"
            :title="t('Send mail to new user')"
            name="send_mail"
          />

          <template v-if="!data.hideFields">
            <div v-if="data.hideNeverExpireOption">
              <BaseCalendar
                id="expiration_date"
                v-model="form.expirationDate"
                :label="t('Expiration date')"
                show-time
              />
            </div>
            <div
              v-else
              class="flex flex-col gap-2"
            >
              <BaseRadioButtons
                v-model="form.hasExpirationDate"
                :options="expirationOptions"
                :title="t('Expiration date')"
                name="radio_expiration_date"
              />
              <BaseCalendar
                v-if="form.hasExpirationDate"
                id="expiration_date"
                v-model="form.expirationDate"
                :label="t('Expiration date')"
                show-time
              />
            </div>

            <BaseRadioButtons
              v-model="form.active"
              :options="activeOptions"
              :title="t('Account')"
              name="active"
            />
          </template>
        </div>
      </Fieldset>

      <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
        <div class="flex flex-col gap-6">
          <BaseInputText
            id="phone"
            v-model="form.phone"
            :label="t('Phone number')"
            name="phone"
          />

          <div class="rounded-lg border border-gray-25 bg-gray-10 p-4">
            <div class="mb-4 flex items-center justify-between gap-2">
              <div class="flex items-center gap-2 font-semibold text-gray-800">
                <BaseIcon icon="account-circle" />
                <span>{{ t("Add image") }}</span>
              </div>
              <BaseCheckbox
                v-if="data.user.hasPicture"
                id="delete_picture"
                v-model="form.deletePicture"
                :label="t('Remove picture')"
                name="delete_picture"
              />
            </div>

            <div class="grid gap-5 lg:grid-cols-[420px_160px] lg:justify-between">
              <Dashboard
                :plugins="['ImageEditor']"
                :props="{
                  proudlyDisplayPoweredByUppy: false,
                  hideUploadButton: true,
                  // Uppy's own ImageEditor CSS caps the crop image at
                  // max-height: 400px regardless of this prop -- a shorter
                  // height here doesn't shrink the image, it just lets the
                  // editor overflow past this container's own bottom edge,
                  // where it gets silently painted over by the next form
                  // field (confirmed live: the image's bottom half was
                  // unreachable no matter how far the page scrolled).
                  // 460 gives the 400px image + its header bar room to fit
                  // without overflowing. The narrower fixed-width column
                  // (was 1fr) also matches the square output shape --  a
                  // wide landscape-shaped editor for a square avatar made
                  // portrait/square source photos render as a thin sliver.
                  height: 460,
                  restrictions: pictureRestrictions,
                }"
                :uppy="uppy"
              />

              <div class="rounded-lg border border-gray-25 bg-white p-3">
                <div class="mb-3 flex items-center justify-between gap-2">
                  <span class="font-semibold text-gray-800">{{ t("Preview") }}</span>
                  <BaseButton
                    v-if="stagedPreviewUrl"
                    icon="delete"
                    :label="t('Remove picture')"
                    only-icon
                    size="small"
                    type="danger-text"
                    @click="removeStagedPicture"
                  />
                </div>

                <img
                  v-if="previewUrl"
                  :alt="t('Add image')"
                  class="aspect-square w-full rounded-full border border-gray-25 object-cover"
                  :src="previewUrl"
                />
                <div
                  v-else
                  class="flex aspect-square items-center justify-center rounded-full border border-dashed border-gray-40 bg-gray-10 text-gray-500"
                >
                  <BaseIcon
                    icon="account-circle"
                    size="large"
                  />
                </div>
              </div>
            </div>
          </div>

          <BaseMultiSelect
            v-model="form.studentBoss"
            :label="t('Superior (n+1)')"
            :options="data.studentBossOptions"
            input-id="student_boss"
            option-label="label"
            option-value="value"
          />

          <div
            v-for="field in data.extraFields"
            :key="field.variable"
          >
            <ExtraFieldInput
              v-model="extraValues[field.variable]"
              :disabled="frozenExtraFields.has(field.variable)"
              :error-text="extraFieldErrors[field.variable] || ''"
              :field="field"
              :is-invalid="Boolean(extraFieldErrors[field.variable])"
            />
          </div>

          <div
            v-for="templateType in data.emailTemplateTypes"
            :key="templateType.type"
            class="flex flex-col gap-2"
          >
            <BaseSelect
              :id="`email_template_${templateType.type}`"
              v-model="emailTemplateSelection[templateType.type]"
              :label="t('Preview')"
              :options="templateType.options"
              allow-clear
            />
            <BaseTextArea
              v-if="emailTemplateSelection[templateType.type]"
              :id="`email_template_preview_${templateType.type}`"
              :model-value="templateType.previewById[emailTemplateSelection[templateType.type]] || ''"
              disabled
              label="Preview"
              rows="5"
            />
          </div>
        </div>
      </BaseAdvancedSettingsButton>

      <div class="flex justify-end gap-4">
        <BaseButton
          icon="content-save"
          :is-loading="isSaving"
          :label="t('Save')"
          is-submit
          type="secondary"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, shallowRef } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import ImageEditor from "@uppy/image-editor"
import Fieldset from "primevue/fieldset"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseRadioButtons from "../../components/basecomponents/BaseRadioButtons.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import ExtraFieldInput from "../../components/admin/ExtraFieldInput.vue"
import baseService from "../../services/baseService"
import { useNotification } from "../../composables/notification"
import { useUppyLocale } from "../../composables/uppyLocale"

import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { uppyLocale } = useUppyLocale()

const userId = Number(route.params.userId)

const data = ref({
  westernNameOrder: true,
  loginIsEmail: false,
  emailRequired: false,
  hideNeverExpireOption: false,
  adminsCanSetUsersPass: false,
  hideFields: false,
  authSources: [],
  roleOptions: [],
  extraFields: [],
  extraValues: {},
  frozenExtraFieldVariables: [],
  studentBossOptions: [],
  loginAsToken: "",
  canLoginAs: false,
  emailTemplateTypes: [],
  user: {
    creator: null,
    hasPicture: false,
    pictureUrl: null,
  },
})

const isSaving = ref(false)
const showPassword = ref(false)
const showAdvancedSettings = ref(false)
const extraValues = reactive({})
const extraFieldErrors = reactive({})
const emailTemplateSelection = reactive({})
const errorMessage = ref("")
const conflictMessages = ref([])

const form = reactive({
  firstname: "",
  lastname: "",
  officialCode: "",
  email: "",
  username: "",
  authSource: [],
  resetPassword: "none",
  password: "",
  roles: [],
  locale: "en",
  sendMail: 0,
  hasExpirationDate: false,
  expirationDate: null,
  active: 1,
  phone: "",
  deletePicture: false,
  studentBoss: [],
})

const authSourceOptions = computed(() => data.value.authSources)
const hasPlatformAuth = computed(() => form.authSource.includes("platform"))
const frozenExtraFields = computed(() => new Set(data.value.frozenExtraFieldVariables))

const resetPasswordOptions = computed(() => {
  const options = [
    { label: t("Don't reset password"), value: "none" },
    { label: t("Automatically generate a new password"), value: "auto" },
  ]
  if (data.value.adminsCanSetUsersPass) {
    options.push({ label: t("Set password manually"), value: "manual" })
  }

  return options
})
const sendMailOptions = computed(() => [
  { label: t("Yes"), value: 1 },
  { label: t("No"), value: 0 },
])
const expirationOptions = computed(() => [
  { label: t("Never expires"), value: false },
  { label: t("Enabled"), value: true },
])
const activeOptions = computed(() => [
  { label: t("active"), value: 1 },
  { label: t("inactive"), value: 0 },
])

// Mirrors ResourceLanguageSelector.vue's own window.languages -> options logic
// (same source, same alphabetical-by-native-name order the legacy SelectLanguage
// element used: `ORDER BY original_name ASC`) instead of a separate, unsorted
// /api/languages fetch.
const languageOptions = computed(() => {
  const languages = Array.isArray(window.languages) ? window.languages : []

  return languages
    .filter((language) => language?.available ?? true)
    .map((language) => ({
      value: String(language.isocode || "").trim(),
      label: String(language.originalName || language.isocode || "").trim(),
    }))
    .filter((language) => language.value && language.label)
    .sort((first, second) => first.label.localeCompare(second.label))
})

const formattedCreatedAt = computed(() =>
  data.value.user.createdAt ? new Date(data.value.user.createdAt).toLocaleString() : "",
)

const pictureRestrictions = {
  allowedFileTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
  maxNumberOfFiles: 1,
  maxFileSize: 10 * 1024 * 1024,
}

const stagedPreviewUrl = ref("")
const previewUrl = computed(() => stagedPreviewUrl.value || (form.deletePicture ? "" : data.value.user.pictureUrl))

const uppy = shallowRef(
  new Uppy({
    autoProceed: false,
    locale: uppyLocale.value,
    restrictions: pictureRestrictions,
  })
    .use(ImageEditor, {
      cropperOptions: {
        viewMode: 1,
        background: false,
        autoCropArea: 1,
        responsive: true,
        // User pictures are displayed as a circular avatar everywhere in the app
        // (BaseUserAvatar's default shape) -- lock the ratio so the upload is
        // always square regardless of what the admin drags.
        aspectRatio: 1,
      },
      actions: {
        revert: true,
        rotate: true,
        granularRotate: true,
        flip: true,
        zoomIn: true,
        zoomOut: true,
        cropSquare: true,
        cropWidescreen: false,
        cropWidescreenVertical: false,
      },
    })
    // A blob URL of the raw selected file is enough for a 160px preview tile
    // (CSS already scales it down via object-cover) -- no need for
    // @uppy/thumbnail-generator's canvas-based resizing just for this.
    .on("file-added", (file) => {
      revokeStagedPreview()
      stagedPreviewUrl.value = URL.createObjectURL(file.data)
    })
    .on("file-removed", () => {
      revokeStagedPreview()
    }),
)

function revokeStagedPreview() {
  if (stagedPreviewUrl.value) {
    URL.revokeObjectURL(stagedPreviewUrl.value)
  }
  stagedPreviewUrl.value = ""
}

function removeStagedPicture() {
  const files = uppy.value.getFiles()
  files.forEach((file) => uppy.value.removeFile(file.id))
  revokeStagedPreview()
}

onBeforeUnmount(() => {
  if ("function" === typeof uppy.value?.close) {
    uppy.value.close({ reason: "unmount" })
  }
  revokeStagedPreview()
})

function defaultExtraValue(field) {
  if ([5, 13].includes(field.valueType)) {
    return field.defaultValue ? [field.defaultValue] : []
  }
  if ([8, 24, 25, 26, 27].includes(field.valueType)) {
    return ["", "", ""]
  }

  return field.defaultValue || ""
}

async function loadData() {
  const response = await baseService.get("/admin/user-edit-data", { user_id: userId })
  data.value = response

  const user = response.user

  form.firstname = user.firstname
  form.lastname = user.lastname
  form.officialCode = user.officialCode || ""
  form.email = user.email || ""
  form.username = user.username
  form.authSource = [...user.authSources]
  form.roles = [...user.roles]
  form.locale = user.locale
  form.phone = user.phone || ""
  form.active = user.active
  form.hasExpirationDate = user.hasExpirationDate
  form.expirationDate = user.expirationDate ? new Date(user.expirationDate) : null
  form.studentBoss = [...user.studentBoss]

  for (const field of response.extraFields) {
    const value = response.extraValues[field.variable]
    extraValues[field.variable] = undefined === value ? defaultExtraValue(field) : value
  }
  for (const templateType of response.emailTemplateTypes) {
    emailTemplateSelection[templateType.type] = templateType.defaultId ?? null
  }
}

function buildFormData() {
  const payload = new FormData()
  payload.set("user_id", String(userId))
  payload.set("firstname", form.firstname)
  payload.set("lastname", form.lastname)
  payload.set("officialCode", form.officialCode)
  payload.set("email", form.email)
  if (!data.value.loginIsEmail) {
    payload.set("username", form.username)
  }
  form.authSource.forEach((value) => payload.append("authSource[]", value))
  form.roles.forEach((value) => payload.append("roles[]", value))
  payload.set("locale", form.locale)
  payload.set("sendMail", String(form.sendMail))
  payload.set("phone", form.phone)

  if (!data.value.hideFields) {
    payload.set("active", String(form.active))
    payload.set("hasExpirationDate", form.hasExpirationDate ? "1" : "0")
    if (form.hasExpirationDate && form.expirationDate) {
      payload.set("expirationDate", new Date(form.expirationDate).toISOString())
    }
  }

  const resetPasswordMap = { none: "0", auto: "1", manual: "2" }
  payload.set("resetPassword", resetPasswordMap[form.resetPassword] ?? "0")
  if ("manual" === form.resetPassword) {
    payload.set("password", form.password)
  }

  payload.set("deletePicture", form.deletePicture ? "1" : "0")
  form.studentBoss.forEach((value) => payload.append("studentBoss[]", value))

  for (const field of data.value.extraFields) {
    if (frozenExtraFields.value.has(field.variable)) {
      continue
    }
    const value = extraValues[field.variable]
    const key = `extra_${field.variable}`

    if (9 === field.valueType) {
      continue
    }

    if ([24, 25].includes(field.valueType)) {
      const [address, coordinates] = Array.isArray(value) ? value : [value || "", ""]
      if (address) {
        payload.set(key, address)
      }
      if (coordinates) {
        payload.set(`${key}_coordinates`, coordinates)
      }
      continue
    }

    if ([8, 26, 27].includes(field.valueType)) {
      const [first, second, third] = Array.isArray(value) ? value : ["", "", ""]
      if (first || second || third) {
        payload.set(`${key}[${key}]`, first || "")
        payload.set(`${key}[${key}_second]`, second || "")
        if (27 === field.valueType) {
          payload.set(`${key}[${key}_third]`, third || "")
        }
      }
      continue
    }

    if ([16, 18].includes(field.valueType)) {
      if (value instanceof File) {
        payload.set(key, value, value.name)
      } else if (value?.remove) {
        payload.set(`${key}_remove`, "1")
      }
      continue
    }

    if (Array.isArray(value)) {
      value.forEach((v) => payload.append(`${key}[]`, v))
    } else if (value) {
      payload.set(key, value)
    }
  }

  for (const templateType of data.value.emailTemplateTypes) {
    const selected = emailTemplateSelection[templateType.type]
    if (selected) {
      payload.set(`emailTemplateOption[${templateType.type}]`, String(selected))
    }
  }

  return payload
}

async function uploadPictureIfAny() {
  const files = uppy.value.getFiles()
  if (0 === files.length) {
    return
  }

  const pictureData = new FormData()
  pictureData.set("user_id", String(userId))
  pictureData.set("file", files[0].data, files[0].name)

  try {
    await baseService.post("/admin/user-edit-picture", pictureData)
  } catch {
    showErrorNotification(t("The picture could not be uploaded"))
  }
}

async function submit() {
  isSaving.value = true
  errorMessage.value = ""
  conflictMessages.value = []
  Object.keys(extraFieldErrors).forEach((key) => delete extraFieldErrors[key])
  try {
    await baseService.post("/admin/user-edit-action", buildFormData())
    await uploadPictureIfAny()

    showSuccessNotification(t("User updated"))
    router.push({ name: "AdminUserList" })
  } catch (e) {
    conflictMessages.value = e?.response?.data?.conflicts || []
    errorMessage.value = e?.response?.data?.error || t("Error")
    const fieldErrors = e?.response?.data?.fieldErrors
    if (fieldErrors && "object" === typeof fieldErrors) {
      Object.assign(extraFieldErrors, fieldErrors)
    }
    showErrorNotification(errorMessage.value)
  } finally {
    isSaving.value = false
  }
}

onMounted(async () => {
  await loadData()
})
</script>
