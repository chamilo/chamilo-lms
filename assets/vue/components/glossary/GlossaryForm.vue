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

    <!-- AI-assisted toggle (raw extrafield) -->
    <div
      v-if="canShowAiToggle"
      class="mt-2 flex items-center gap-2"
    >
      <input
        id="ai-assisted-flag"
        v-model="formData.ai_assisted_raw"
        type="checkbox"
      />
      <label
        for="ai-assisted-flag"
        class="text-sm"
      >
        AI-assisted
      </label>
    </div>

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
import { computed, onMounted, reactive, ref } from "vue"
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
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notification = useNotification()
const { sid, cid } = useCidReq()
const securityStore = useSecurityStore()

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

const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })

const canShowAiToggle = computed(() => {
  const isAdmin = !!(securityStore.isAdmin || securityStore.isPlatformAdmin || securityStore.isSuperAdmin)
  const isTeacher = !!(securityStore.isCurrentTeacher || securityStore.isTeacher)
  return isAdmin || isTeacher || isAllowedToEdit.value
})

const formData = reactive({
  title: "",
  description: "",
  ai_assisted_raw: false,
})
const rules = {
  title: { required },
  description: { required },
}
const v$ = useVuelidate(rules, formData)

onMounted(async () => {
  await fetchTerm()
})

function normalizeBoolean(value) {
  const s = String(value ?? "")
    .trim()
    .toLowerCase()
  return value === true || value === 1 || s === "1" || s === "true" || s === "yes" || s === "on"
}

const fetchTerm = async () => {
  if (!props.termId) {
    return
  }
  try {
    const glossary = await glossaryService.getGlossaryTerm(props.termId)

    formData.title = glossary.title ?? ""
    formData.description = glossary.description ?? ""

    // Prefer stored raw value
    if (typeof glossary.ai_assisted_raw !== "undefined") {
      formData.ai_assisted_raw = normalizeBoolean(glossary.ai_assisted_raw)
    } else if (typeof glossary.ai_assisted !== "undefined") {
      formData.ai_assisted_raw = normalizeBoolean(glossary.ai_assisted)
    } else {
      formData.ai_assisted_raw = false
    }
  } catch (error) {
    console.error("[GlossaryForm] Failed to fetch glossary term:", error)
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
    ai_assisted_raw: formData.ai_assisted_raw ? 1 : 0,
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
    console.error("[GlossaryForm] Failed to save glossary term:", error)
    notification.showErrorNotification(t("Could not create glossary term"))
  }
}
</script>
