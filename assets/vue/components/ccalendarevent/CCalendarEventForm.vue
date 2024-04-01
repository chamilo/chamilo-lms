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

    <div v-if="agendaRemindersEnabled" class="field mt-2">
      <BaseButton
        label="Add Notification"
        @click="addNotification"
        icon="time"
        type="button"
        class="mb-2"
      />
      <div v-for="(notification, index) in notifications" :key="index" class="flex items-center gap-2">
        <input
          v-model="notification.count"
          type="number"
          min="0"
          placeholder="Count"
        />
        <select v-model="notification.period">
          <option value="i">Minutes</option>
          <option value="h">Hours</option>
          <option value="d">Days</option>
        </select>
        <BaseButton
          icon="delete"
          @click="removeNotification(index)"
          type="button"/>
      </div>
    </div>
    <slot />
  </form>
</template>

<script setup>
import { computed, onMounted, ref, watch } from "vue"
import { useVuelidate } from "@vuelidate/core"
import { required, minValue } from "@vuelidate/validators"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import { useI18n } from "vue-i18n"
import BaseCalendar from "../basecomponents/BaseCalendar.vue"
import CalendarInvitations from "./CalendarInvitations.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { usePlatformConfig } from "../../store/platformConfig"

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
  notificationsData: Array,
})

const item = computed(() => props.initialValues || props.values)
const notifications = ref(props.notificationsData || [])

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
  notifications: {
    $each: {
      count: { required, minVal: minValue(1) },
      period: { required },
    },
  },
}))

const v$ = useVuelidate(rules, { item, notifications })

// eslint-disable-next-line no-undef
defineExpose({
  v$,
  notifications
})

const platformConfigStore = usePlatformConfig()
const agendaRemindersEnabled = computed(() => {
  return platformConfigStore.getSetting("agenda.agenda_reminders") === "true"
})

const dateRange = ref()

if (item.value?.startDate || item.value?.endDate) {
  dateRange.value = [item.value?.startDate, item.value?.endDate]
}

watch(dateRange, (newValue) => {
  item.value.startDate = newValue[0]
  item.value.endDate = newValue[1]
})

onMounted(() => {
  notifications.value = props.notificationsData.map(notification => ({
    count: notification.count,
    period: notification.period,
  }))
})

watch(() => props.notificationsData, (newVal) => {
  notifications.value = newVal || []
})

function addNotification() {
  notifications.value.push({ count: 1, period: 'i' })
}

function removeNotification(index) {
  notifications.value.splice(index, 1)
}
</script>
