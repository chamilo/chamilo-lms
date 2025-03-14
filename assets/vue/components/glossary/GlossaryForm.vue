<template>
  <form class="mt-6 flex flex-col gap-2">
    <BaseInputTextWithVuelidate
      id="term-name"
      v-model="formData.title"
      :label="t('Term')"
      :vuelidate-property="v$.title"
    />
    <BaseTextAreaWithVuelidate
      id="term-description"
      v-model="formData.description"
      :label="t('Description')"
      :vuelidate-property="v$.description"
    />

    <LayoutFormButtons>
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="black"
        @click="emit('backPressed')"
      />
      <BaseButton
        :label="t('Save term')"
        icon="send"
        type="success"
        @click="submitGlossaryForm"
      />
    </LayoutFormButtons>
  </form>
</template>

<script setup>
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { onMounted, reactive, ref } from "vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import LayoutFormButtons from "../layout/LayoutFormButtons.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import { required } from "@vuelidate/validators"
import useVuelidate from "@vuelidate/core"
import BaseTextAreaWithVuelidate from "../basecomponents/BaseTextAreaWithVuelidate.vue"
import { useNotification } from "../../composables/notification"
import glossaryService from "../../services/glossaryService"
import { useCidReq } from "../../composables/cidReq"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notification = useNotification()
const { sid, cid } = useCidReq()

const props = defineProps({
  termId: {
    type: Number,
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

const formData = reactive({
  title: "",
  description: "",
})
const rules = {
  title: { required },
  description: { required },
}
const v$ = useVuelidate(rules, formData)

onMounted(() => {
  fetchTerm()
})

const fetchTerm = async () => {
  if (props.termId === null) {
    return
  }
  try {
    const glossary = await glossaryService.getGlossaryTerm(props.termId)
    formData.title = glossary.title
    formData.description = glossary.description
  } catch (error) {
    console.error("Error glossary term:", error)
    notification.showErrorNotification(t("Could not fetch glossary term"))
  }
}

const submitGlossaryForm = async () => {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  const postData = {
    title: formData.title,
    description: formData.description,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
    sid: route.query.sid,
    cid: route.query.cid,
  }

  try {
    if (props.termId) {
      await glossaryService.updateGlossaryTerm(props.termId, postData)
    } else {
      await glossaryService.createGlossaryTerm(postData)
    }

    notification.showSuccessNotification(t("Glossary term saved"))

    await router.push({
      name: "GlossaryList",
      query: route.query,
    })
  } catch (error) {
    console.error("Error updating link:", error)
    notification.showErrorNotification(t("Could not create glossary term"))
  }
}
</script>
