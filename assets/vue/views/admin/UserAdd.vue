<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Add a user')" />

    <form
      class="flex flex-col gap-8"
      @submit.prevent="submit('add')"
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
            <div
              v-if="data.adminsCanSetUsersPass"
              class="flex flex-col gap-3"
            >
              <BaseRadioButtons
                v-model="form.passwordMode"
                name="password_mode"
                :options="passwordModeOptions"
              />
              <div
                v-if="'manual' === form.passwordMode"
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
            <p
              v-else
              class="text-body-2 text-gray-70"
            >
              {{ t("Automatically generate a new password") }}
            </p>
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
            <div class="mb-4 flex items-center gap-2 font-semibold text-gray-800">
              <BaseIcon icon="account-circle" />
              <span>{{ t("Add image") }}</span>
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
                    v-if="picturePreviewUrl"
                    icon="delete"
                    :label="t('Remove picture')"
                    only-icon
                    size="small"
                    type="danger-text"
                    @click="removeStagedPicture"
                  />
                </div>

                <img
                  v-if="picturePreviewUrl"
                  :alt="t('Add image')"
                  class="aspect-square w-full rounded-full border border-gray-25 object-cover"
                  :src="picturePreviewUrl"
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

          <div
            v-for="field in data.extraFields"
            :key="field.variable"
          >
            <ExtraFieldInput
              v-model="extraValues[field.variable]"
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
          :label="t('Add')"
          :is-loading="isSaving"
          icon="plus"
          is-submit
          type="success"
        />
        <BaseButton
          :label="`${t('Add')}+`"
          :is-loading="isSaving"
          icon="plus"
          type="success"
          @click="submit('add_plus')"
        />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, shallowRef } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import Uppy from "@uppy/core"
import { Dashboard } from "@uppy/vue"
import ImageEditor from "@uppy/image-editor"
import Fieldset from "primevue/fieldset"
import BaseAdvancedSettingsButton from "../../components/basecomponents/BaseAdvancedSettingsButton.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
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
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()
const { uppyLocale } = useUppyLocale()

const data = ref({
  westernNameOrder: true,
  loginIsEmail: false,
  emailRequired: false,
  hideNeverExpireOption: false,
  adminsCanSetUsersPass: false,
  defaultExpirationDate: null,
  defaultLocale: "en",
  redirectToAddAnotherAfterCreate: false,
  authSources: [],
  roleOptions: [],
  extraFields: [],
  emailTemplateTypes: [],
})

const isSaving = ref(false)
const showPassword = ref(false)
const showAdvancedSettings = ref(false)
const extraValues = reactive({})
const extraFieldErrors = reactive({})
const emailTemplateSelection = reactive({})

const form = reactive({
  firstname: "",
  lastname: "",
  officialCode: "",
  email: "",
  username: "",
  authSource: [],
  passwordMode: "auto",
  password: "",
  roles: [],
  locale: "en",
  sendMail: 1,
  hasExpirationDate: false,
  expirationDate: null,
  active: 1,
  phone: "",
})

const authSourceOptions = computed(() => data.value.authSources)
const hasPlatformAuth = computed(() => form.authSource.includes("platform"))

const passwordModeOptions = computed(() => [
  { label: t("Automatically generate a new password"), value: "auto" },
  { label: t("Set password manually"), value: "manual" },
])
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

const pictureRestrictions = {
  allowedFileTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
  maxNumberOfFiles: 1,
  maxFileSize: 10 * 1024 * 1024,
}

const picturePreviewUrl = ref("")

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
        // User pictures are displayed as a circular avatar everywhere in the
        // app (BaseUserAvatar's default shape) and the only two existing
        // Glide filters for them (user_picture_small/profile) are square —
        // unlike the course picture, which is intentionally landscape.
        // Locking the ratio (not just offering a "square" toolbar button,
        // which the admin could still ignore) guarantees the upload is
        // always square regardless of what they drag.
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
        // ImageEditor's own defaultOptions has both of these as `true` and
        // merges the passed-in actions object OVER them — omitting a key
        // here does NOT disable it, it silently keeps the plugin's own
        // default (confirmed by inspecting the built vendor chunk directly:
        // both buttons still rendered despite never being set here).
        // Explicit `false` is required.
        cropWidescreen: false,
        cropWidescreenVertical: false,
      },
    })
    // A blob URL of the raw selected file is enough for a 160px preview tile
    // (CSS already scales it down via object-cover) -- no need for
    // @uppy/thumbnail-generator's canvas-based resizing just for this.
    .on("file-added", (file) => {
      revokeStagedPreview()
      picturePreviewUrl.value = URL.createObjectURL(file.data)
    })
    .on("file-removed", () => {
      revokeStagedPreview()
    }),
)

function revokeStagedPreview() {
  if (picturePreviewUrl.value) {
    URL.revokeObjectURL(picturePreviewUrl.value)
  }
  picturePreviewUrl.value = ""
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

async function loadData() {
  const response = await baseService.get("/admin/user-add-data")
  data.value = response

  form.authSource = response.authSources.length > 0 ? [response.authSources[0].value] : []
  form.locale = response.defaultLocale
  form.hasExpirationDate = response.hideNeverExpireOption
  form.expirationDate = response.defaultExpirationDate ? new Date(response.defaultExpirationDate) : null

  for (const field of response.extraFields) {
    extraValues[field.variable] = defaultExtraValue(field)
  }
  for (const templateType of response.emailTemplateTypes) {
    emailTemplateSelection[templateType.type] = templateType.defaultId ?? null
  }
}

function defaultExtraValue(field) {
  if ([5, 13].includes(field.valueType)) {
    return field.defaultValue ? [field.defaultValue] : []
  }
  if ([8, 24, 25, 26, 27].includes(field.valueType)) {
    return ["", "", ""]
  }

  return field.defaultValue || ""
}

function buildFormData() {
  const payload = new FormData()
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
  payload.set("active", String(form.active))
  payload.set("phone", form.phone)
  payload.set("passwordMode", form.passwordMode)
  if ("manual" === form.passwordMode) {
    payload.set("password", form.password)
  }
  payload.set("hasExpirationDate", form.hasExpirationDate ? "1" : "0")
  if (form.hasExpirationDate && form.expirationDate) {
    payload.set("expirationDate", new Date(form.expirationDate).toISOString())
  }

  for (const field of data.value.extraFields) {
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

async function uploadPictureIfAny(userId) {
  const files = uppy.value.getFiles()
  if (0 === files.length) {
    return
  }

  const pictureData = new FormData()
  pictureData.set("user_id", String(userId))
  pictureData.set("file", files[0].data, files[0].name)

  try {
    await baseService.post("/admin/user-add-picture", pictureData)
  } catch {
    showErrorNotification(t("The picture could not be uploaded"))
  }
}

async function submit(mode) {
  isSaving.value = true
  Object.keys(extraFieldErrors).forEach((key) => delete extraFieldErrors[key])
  try {
    const response = await baseService.post("/admin/user-add-action", buildFormData())

    await uploadPictureIfAny(response.userId)

    showSuccessNotification(t("The user has been added"))

    const shouldAddAnother = "add_plus" === mode || data.value.redirectToAddAnotherAfterCreate
    if (shouldAddAnother) {
      resetForm()
    } else {
      router.push({ name: "AdminUserList" })
    }
  } catch (e) {
    const fieldErrors = e?.response?.data?.fieldErrors
    if (fieldErrors && "object" === typeof fieldErrors) {
      Object.assign(extraFieldErrors, fieldErrors)
    }
    showErrorNotification(e)
  } finally {
    isSaving.value = false
  }
}

function resetForm() {
  form.firstname = ""
  form.lastname = ""
  form.officialCode = ""
  form.email = ""
  form.username = ""
  form.password = ""
  form.passwordMode = "auto"
  form.roles = []
  uppy.value.cancelAll()
  picturePreviewUrl.value = ""
}

onMounted(async () => {
  await loadData()
})
</script>
