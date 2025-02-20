<template>
  <form>
    <BaseInputText
      v-model="item.title"
      :error-text="v$.item.title.$errors.map((error) => error.$message).join('<br>')"
      :is-invalid="v$.item.title.$invalid"
      :label="t('Title')"
    />

    <BaseCalendar
      id="calendar-id"
      v-model="dateRange"
      :initial-value="[item.startDate, item.endDate]"
      :is-invalid="v$.item.startDate.$invalid || v$.item.endDate.$invalid"
      :label="t('Date')"
      class="max-w-sm w-full"
      show-icon
      show-time
      type="range"
    />

    <BaseTinyEditor
      v-model="item.content"
      :required="false"
      editor-id="calendar-event-content"
    />
    <CalendarInvitations v-model="item" />

    <CalendarRemindersEditor
      v-if="!isGlobal"
      v-model="item"
    />

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
import CalendarRemindersEditor from "./CalendarRemindersEditor.vue"
import BaseTinyEditor from "../basecomponents/BaseTinyEditor.vue"

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
  isGlobal: Boolean,
})

const item = computed(() => props.initialValues || props.values)

const rules = computed(() => ({
  item: {
    title: {
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
