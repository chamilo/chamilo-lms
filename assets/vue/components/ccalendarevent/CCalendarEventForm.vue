<template>
  <form>
    <BaseInputText
      v-model="v$.item.title.$model"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$error"
      :label="t('Title')"
    />

    <BaseCalendar
      v-model="dateRange"
      :label="'Date'"
      show-icon
      show-time
      type="range"
    />

    <div class="field">
      <tiny-editor
        v-model="v$.item.content.$model"
        :init="{
          skin_url: '/build/libs/tinymce/skins/ui/oxide',
          content_css: '/build/libs/tinymce/skins/content/default/content.css',
          branding: false,
          relative_urls: false,
          height: 250,
          toolbar_mode: 'sliding',
          file_picker_callback: browser,
          autosave_ask_before_unload: true,
          plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste wordcount emoticons',
          ],
          toolbar:
            'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl',
        }"
        required
      />
    </div>

    <Fieldset
      v-if="agendaCollectiveInvitations"
      :legend="'Invitees'"
      collapsed
      toggleable
    >
      <EditLinks
        :edit-status="false"
        :item="item"
        :links-type="linksType"
        :show-status="false"
        show-share-with-user
      />

      <BaseCheckbox
        id="is_collective"
        v-model="item.collective"
        :label="t('Is it editable by the invitees?')"
        name="is_collective"
      />
    </Fieldset>

    <slot />
  </form>
</template>

<script setup>
import { computed, ref } from "vue"
import { useStore } from "vuex"
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import EditLinks from "../resource_links/EditLinks.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import { usePlatformConfig } from "../../store/platformConfig"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import Fieldset from "primevue/fieldset"

const dateRange = ref()

const store = useStore()
const platformConfigStore = usePlatformConfig()

const agendaCollectiveInvitations = "true" === platformConfigStore.getSetting("agenda.agenda_collective_invitations")

const { t } = useI18n()

// eslint-disable-next-line no-undef
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

const item = computed(() => props.initialValues || props.values)

const rules = computed(() => ({
  item: {
    title: {
      required,
    },
    content: {
      required,
    },
    startDate: {
      required,
    },
    endDate: {
      required,
    },
    collective: {},
  },
}))

const v$ = useVuelidate(rules, { item })

// eslint-disable-next-line no-undef
defineExpose({
  v$,
})

const linksType = ref("users")
const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"])
const isAdmin = computed(() => store.getters["security/isAdmin"])

if (!isAdmin.value) {
  if (isCurrentTeacher.value) {
    linksType.value = "course_students"
  } else {
    linksType.value = "user_rel_users"
  }
}
</script>
