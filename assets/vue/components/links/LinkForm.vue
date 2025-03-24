<template>
  <form class="flex flex-col gap-2 mt-6">
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
    />
    <BaseCheckbox
      id="show-link-on-home-page"
      v-model="formData.showOnHomepage"
      :label="t('Show link on course homepage')"
      name="show-link-on-home-page"
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
      option-label="label"
      option-value="value"
    />

    <div v-if="formData.showOnHomepage">
      <div
        v-if="formData.customImageUrl"
        class="mb-4"
      >
        <p class="text-gray-600">{{ t("Current icon") }}</p>
        <img
          :src="formData.customImageUrl"
          alt="Custom Image"
          class="w-24 h-24 object-cover"
        />
        <BaseButton
          :label="t('Remove current icon')"
          icon="trash"
          type="danger"
          @click="removeCurrentImage"
        />
      </div>

      <BaseFileUpload
        id="custom-image"
        :label="t('Custom icon')"
        accept="image"
        size="small"
        @file-selected="selectedFile = $event"
      />
      <p class="text-gray-600">
        {{ t("This icon will show for the link displayed as a tool on the course homepage.") }}
      </p>
      <p class="text-gray-600">{{ t("The icon must be 120x120 pixels.") }}</p>
    </div>

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
import { onMounted, reactive, ref } from "vue"
import { useCidReq } from "../../composables/cidReq"
import BaseButton from "../basecomponents/BaseButton.vue"
import { required, url } from "@vuelidate/validators"
import useVuelidate from "@vuelidate/core"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseTextArea from "../basecomponents/BaseTextArea.vue"
import BaseSelect from "../basecomponents/BaseSelect.vue"
import { useNotification } from "../../composables/notification"
import LayoutFormButtons from "../layout/LayoutFormButtons.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"

const notification = useNotification()
const { t } = useI18n()
const { cid, sid } = useCidReq()
const router = useRouter()
const route = useRoute()
const selectedFile = ref(null)

const props = defineProps({
  linkId: {
    type: [String, Number],
    default: null,
  },
})

const emit = defineEmits(["backPressed"])

const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref(
  JSON.stringify([
    {
      sid,
      cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ]),
)
const categories = ref([])

const formData = reactive({
  url: "http://",
  title: "",
  description: "",
  category: null,
  showOnHomepage: false,
  target: "_blank",
  customImage: null,
  customImageUrl: null,
  removeImage: false,
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

onMounted(() => {
  fetchCategories()
  fetchLink()
})

const fetchCategories = async () => {
  try {
    categories.value = await linkService.getCategories(parentResourceNodeId.value)
  } catch (error) {
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
        formData.category = parseInt(response.category["@id"].split("/").pop())
      }
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
  }
  try {
    if (props.linkId) {
      await linkService.updateLink(props.linkId, postData)

      const formDataImage = new FormData()
      formDataImage.append("removeImage", formData.removeImage ? "true" : "false")

      if (selectedFile.value instanceof File) {
        formDataImage.append("customImage", selectedFile.value)
      }

      await linkService.uploadImage(props.linkId, formDataImage)
    } else {
      const newLink = await linkService.createLink(postData)

      if (selectedFile.value instanceof File) {
        const formDataImage = new FormData()
        formDataImage.append("customImage", selectedFile.value)
        await linkService.uploadImage(newLink.iid, formDataImage)
      }
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
