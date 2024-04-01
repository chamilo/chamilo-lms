<template>
  <form>
    <BaseInputText
      v-model="item.title"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$invalid"
      :label="t('Title')"
    />

    <BaseCalendar
      v-model="dateRange"
      :is-invalid="v$.item.startDate.$invalid || v$.item.endDate.$invalid"
      :label="t('Date')"
      show-icon
      show-time
      type="range"
    />

    <div class="field">
      <tiny-editor
        v-model="item.content"
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
    <CalendarInvitations v-model="item" />

    <slot />
  </form>
</template>

<script setup>
import { computed, ref, watch } from "vue"
import { useVuelidate } from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import CalendarInvitations from "./CalendarInvitations.vue"

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
  },
}))

const v$ = useVuelidate(rules, { item })

// eslint-disable-next-line no-undef
defineExpose({
  v$,
})

const dateRange = ref()

if (item.value?.startDate || item.value?.endDate) {
  dateRange.value = [item.value?.startDate, item.value?.endDate]
}

watch(dateRange, (newValue) => {
  item.value.startDate = newValue[0]
  item.value.endDate = newValue[1]
})
</script>
