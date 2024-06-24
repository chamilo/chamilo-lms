<template>
  <form>
    <BaseTinyEditor
      v-model="item.introText"
      editor-id="introText"
      :full-page="false"
      required
    />
    <!-- For extra content-->
    <slot></slot>
  </form>
</template>

<script setup>
import useVuelidate from "@vuelidate/core"
import { computed, ref } from "vue"
import { usePlatformConfig } from "../../store/platformConfig"
import { useRoute } from "vue-router"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

const props = defineProps({
  values: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => {},
  },
  initialValues: {
    type: Object,
    default: () => {},
  },
})

const route = useRoute()
const introText = ref(null)
const parentResourceNodeId = ref(route.query.parentResourceNodeId)
const resourceNode = ref(null)

const item = computed(() => {
  return props.initialValues || props.values
})

const violations = computed(() => {
  return props.errors || {}
})

const extraPlugins = ref("")

const platformConfigStore = usePlatformConfig()

if ("true" === platformConfigStore.getSetting("editor.translate_html")) {
  extraPlugins.value = "translatehtml"
}

const validations = {
  item: {
    introText: {
      //required,
    },
    parentResourceNodeId: {},
    resourceNode: {},
  },
}

const v$ = useVuelidate(validations, { item })

defineExpose({ v$: v$ })
</script>
