<template>
  <form class="mt-6 flex flex-col gap-2">
    <BaseInputTextWithVuelidate
      v-model="formData.url"
      :label="t('URL')"
      :vuelidate-property="v$.url"
    />
    <BaseInputTextWithVuelidate
      v-model="formData.title"
      :label="t('Link name')"
      :vuelidate-property="v$.title"
    />
    <BaseTextArea
      v-model="formData.description"
      :label="t('Description')"
      class="min-h-[120px] w-full"
      rows="6"
    />

    <BaseAdvancedSettingsButton v-model="showAdvancedSettings">
      <div class="flex flex-col gap-4">
        <ResourceLanguageSelector
          id="link-language"
          v-model="formData.language"
        />

        <BaseSelect
          v-model="formData.category"
          :label="t('Select a category')"
          :options="categories"
          hast-empty-value
          option-label="title"
          option-value="iid"
        />

        <BaseSelect
          v-model="formData.target"
          :label="t('Link\'s target')"
          :options="[
            { label: 'Open self', value: '_self' },
            { label: 'Open blank', value: '_blank' },
            { label: 'Open parent', value: '_parent' },
            { label: 'Open top', value: '_top' },
          ]"
        />

        <BaseCheckbox
          id="show-link-on-home-page"
          v-model="formData.showOnHomepage"
          :label="t('Show link on course homepage')"
          name="show-link-on-home-page"
        />

        <div
          v-if="formData.showOnHomepage"
          class="space-y-4"
        >
          <div v-if="currentPreviewImage">
            <p class="font-semibold text-gray-600">{{ t("Current icon") }}</p>
            <img
              :src="currentPreviewImage"
              alt="Custom Image"
              class="h-24 w-24 rounded-xl border object-cover shadow"
            />
            <BaseButton
              class="mt-2"
              :label="t('Remove current icon')"
              icon="trash"
              type="danger"
              size="small"
              @click="removeCurrentImage"
            />
          </div>

          <Dashboard
            class="w-full max-w-3xl"
            :uppy="uppy"
            :props="{
              proudlyDisplayPoweredByUppy: false,
              height: 350,
              hideUploadButton: true,
              autoOpenFileEditor: true,
              note: t('Click the image to crop it (1:1 ratio, 120x120 px recommended).'),
            }"
          />

          <p class="text-sm text-gray-600">
            {{ t("This icon will show for the link displayed as a tool on the course homepage.") }}
          </p>
          <p class="text-sm text-gray-600">
            {{ t("Use the crop tool to select a 1:1 region. Recommended size: 120x120 pixels.") }}
          </p>
        </div>
      </div>
    </BaseAdvancedSettingsButton>

    <LayoutFormButtons>
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="black"
        @click="emit('backPressed')"
      />
      <BaseButton
        :label="t('Save link')"
        icon="send"
        type="success"
        @click="submitForm"
      />
    </LayoutFormButtons>
  </form>
</template>

<script setup>
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import linkService from "../../services/linkService"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { computed, onMounted, reactive, ref, watch } from "vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { required, url } from "@vuelidate/validators"
import useVuelidate from "@vuelidate/core"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import BaseAdvancedSettingsButton from "../basecomponents/BaseAdvancedSettingsButton.vue"
import ResourceLanguageSelector from "../resources/ResourceLanguageSelector.vue"
import { useNotification } from "../../composables/notification"
import LayoutFormButtons from "../layout/LayoutFormButtons.vue"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import "@uppy/image-editor/dist/style.css"
import Uppy from "@uppy/core"
import ImageEditor from "@uppy/image-editor"
import { Dashboard } from "@uppy/vue"

const notification = useNotification()
const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const selectedFile = ref(null)
const showAdvancedSettings = ref(false)

const objectUrl = ref(null)

const currentPreviewImage = computed(() => {
  if (selectedFile.value) {
    if (objectUrl.value) window.URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = window.URL.createObjectURL(selectedFile.value)
    return objectUrl.value
  }
  return formData.customImageUrl
})

const uppy = new Uppy({
  restrictions: { maxNumberOfFiles: 1, allowedFileTypes: ["image/*"] },
  autoProceed: false,
  debug: false,
})
  .use(ImageEditor, {
    actions: {
      revert: true,
      rotate: true,
      cropSquare: true,
      zoomIn: true,
      zoomOut: true,
    },
    quality: 1,
    cropperOptions: {
      aspectRatio: 1,
      croppedCanvasOptions: {
        width: 120,
        height: 120,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: "high",
      },
    },
  })
  .on("file-added", async (file) => {
    formData.removeImage = false

    const editor = uppy.getPlugin("ImageEditor")
    selectedFile.value = file.data

    if (editor?.openEditor) await editor.openEditor(file.id)
  })
  .on("file-editor:complete", (updatedFile) => {
    if (updatedFile?.data) {
      const uniqueName = `customicon-${Date.now()}.png`
      selectedFile.value = new File([updatedFile.data], uniqueName, {
        type: updatedFile.type || "image/png",
      })
    }
  })
  .on("file-removed", () => {
    selectedFile.value = null
    formData.removeImage = true
  })

const props = defineProps({
  linkId: {
    type: [String, Number],
    default: null,
  },
})

const emit = defineEmits(["backPressed"])

const parentResourceNodeId = ref(Number(route.params.node))
// Course context derived server-side from the gated session course.
const resourceLinkList = ref(JSON.stringify([{ visibility: RESOURCE_LINK_PUBLISHED }]))
const categories = ref([])

const courseContextParams = computed(() => {
  const params = {}
  const cid = Number(route.query.cid || 0)
  const sid = Number(route.query.sid || 0)
  const gid = Number(route.query.gid || 0)

  if (cid > 0) {
    params.cid = cid
  }

  if (sid > 0) {
    params.sid = sid
  }

  if (gid > 0) {
    params.gid = gid
  }

  return params
})

const formData = reactive({
  url: "https://",
  title: "",
  description: "",
  category: null,
  showOnHomepage: false,
  target: "_blank",
  customImage: null,
  customImageUrl: null,
  removeImage: false,
  language: "",
})
const rules = {
  url: { required, url },
  title: { required },
  description: {},
  category: {},
  showOnHomepage: {},
  target: {},
}
const v$ = useVuelidate(rules, formData)

watch(selectedFile, (file, oldFile) => {
  if (!file && objectUrl.value) {
    window.URL.revokeObjectURL(objectUrl.value)
    objectUrl.value = null
  }
})

onMounted(() => {
  fetchCategories()
  fetchLink()
})

const fetchCategories = async () => {
  const params = {
    cid: courseContextParams.value.cid || null,
    sid: courseContextParams.value.sid || null,
  }

  try {
    const data = await linkService.getLinks(params)
    categories.value = Object.values(data.categories || {})
      .map((category) => {
        const info = category.info || category
        const id = Number(info.iid || info.id || 0)

        if (!id) {
          return null
        }

        return {
          ...info,
          iid: id,
          title: info.title || "",
        }
      })
      .filter(Boolean)
  } catch (error) {
    categories.value = []
    console.error("Error fetching categories:", error)
  }
}

const fetchLink = async () => {
  if (props.linkId) {
    try {
      const response = await linkService.getLink(props.linkId)
      formData.url = response.url
      formData.title = response.title
      formData.description = response.description
      formData.showOnHomepage = response.onHomepage
      formData.target = response.target
      formData.parentResourceNodeId = response.parentResourceNodeId
      formData.resourceLinkList = response.resourceLinkList

      if (response.customImageUrl) {
        formData.customImageUrl = response.customImageUrl
      }

      if (response.category) {
        formData.category = response.category
      }

      formData.language = String(response.language || "").trim()
    } catch (error) {
      console.error("Error fetching link:", error)
    }
  }
}

const removeCurrentImage = () => {
  formData.customImageUrl = null
  formData.removeImage = true
}

const submitForm = async () => {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  let category = 0
  if (formData.category !== null) {
    category = formData.category
  }

  const postData = {
    url: formData.url,
    title: formData.title,
    description: formData.description,
    category: category,
    showOnHomepage: formData.showOnHomepage,
    target: formData.target,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
    language: formData.language || "",
  }
  try {
    let linkId = props.linkId

    if (props.linkId) {
      await linkService.updateLink(props.linkId, postData)
    } else {
      const newLink = await linkService.createLink(postData)
      linkId = newLink.iid
    }

    if (formData.showOnHomepage && (formData.removeImage || selectedFile.value)) {
      const formDataImage = new FormData()

      formDataImage.append("removeImage", formData.removeImage ? "true" : "false")

      if (selectedFile.value) {
        formDataImage.append("customImage", selectedFile.value)
      }

      await linkService.uploadImage(linkId, formDataImage)
    }

    notification.showSuccessNotification(t("Link saved"))

    await router.push({
      name: "LinksList",
      query: route.query,
    })
  } catch (error) {
    console.error("Error updating link:", error)
    notification.showErrorNotification(t("Error saving the link"))
  }
}
</script>
