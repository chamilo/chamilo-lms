<template>
  <form class="flex flex-col gap-2 mt-6">
    <BaseInputTextWithVuelidate
      id="category_category_title"
      v-model="formData.title"
      :vuelidate-property="v$.title"
      :label="t('Category name')"
    />
    <BaseTextArea
      id="description"
      v-model="formData.description"
      :label="t('Description')"
    />

    <div class="flex gap-4">
      <BaseButton
        :label="t('Back')"
        type="black"
        icon="back"
        @click="emit('backPressed')"
      />
      <BaseButton
        :label="t('Save category')"
        type="success"
        icon="send"
        @click="submitCategoryForm"
      />
    </div>
  </form>
</template>

<script setup>
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from "vue-i18n";
import {ref, onMounted, reactive} from "vue";
import linkService from "../../services/linkService";
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue";
import BaseTextArea from "../basecomponents/BaseTextArea.vue";
import BaseButton from "../basecomponents/BaseButton.vue";
import useVuelidate from "@vuelidate/core";
import {required} from "@vuelidate/validators";
import {useNotification} from "../../composables/notification";
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"

const notification = useNotification();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();


const props = defineProps({
  categoryId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['backPressed'])

const parentResourceNodeId = ref(Number(route.params.node))
const resourceLinkList = ref(
  JSON.stringify([
    {
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    },
  ])
)

const formData = reactive({
  title: '',
  description: '',
})
const rules = {
  title: {required},
  description: {}
}
const v$ = useVuelidate(rules, formData)

onMounted(() => {
  fetchCategory();
})

const fetchCategory = async () => {
  if (props.categoryId) {
    try {
      let category = await linkService.getCategory(props.categoryId)
      formData.title = category.title
      formData.description = category.description
    } catch (error) {
      console.error('Error fetching category:', error)
    }
  }
}

const submitCategoryForm = async () => {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  const postData = {
    category_title: formData.title,
    description: formData.description,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }

  try {
    if (props.categoryId) {
      await linkService.updateCategory(props.categoryId, postData)
    } else {
      await linkService.createCategory(postData)
    }

    notification.showSuccessNotification(t('Category saved'))

    await router.push({
      name: "LinksList",
      query: route.query,
    })
  } catch (error) {
    console.error('Error updating link:', error)
  }
}
</script>
