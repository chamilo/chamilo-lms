<template>
  <form>
    <div class="form__field">
      <div class="p-float-label">
        <InputText
          id="item_title"
          v-model="v$.item.title.$model"
          type="text"
          :class="{'p-invalid': v$.item.title.$invalid}"
        />
        <label
          v-t="'Title'"
          for="item_title"
        />
      </div>
      <small
        v-if="v$.item.title.$invalid || v$.item.title.$pending.$response"
        v-t="v$.item.title.required.$message"
        class="p-error"
      />
    </div>

    <div class="flex flex-col md:flex-row gap-5">
      <div class="md:w-1/2 flex flex-col gap-5">
        <div class="form__field">
          <div class="p-float-label">
            <Calendar
              id="start_date"
              v-model="v$.item.startDate.$model"
              :show-icon="true"
              :show-time="true"
              :class="{'p-invalid': v$.item.startDate.$invalid}"
            />
            <label
              v-t="'From'"
              for="start_date"
            />
          </div>
          <small
            v-if="v$.item.startDate.$invalid || v$.item.startDate.$pending.$response"
            v-t="v$.item.startDate.required.$message"
            class="p-error"
          />
        </div>

        <div class="form__field">
          <div class="p-float-label">
            <Calendar
              id="end_date"
              v-model="v$.item.endDate.$model"
              :show-icon="true"
              :show-time="true"
              :class="{'p-invalid': v$.item.endDate.$invalid}"
              :manual-input="false"
            />
            <label
              v-t="'Until'"
              for="end_date"
            />
          </div>
          <small
            v-if="v$.item.endDate.$invalid || v$.item.endDate.$pending.$response"
            v-t="v$.item.endDate.required.$message"
            class="p-error"
          />
        </div>

        <tiny-editor
          v-model="v$.item.content.$model"
          :init="{
            skin_url: '/build/libs/tinymce/skins/ui/oxide',
            content_css: '/build/libs/tinymce/skins/content/default/content.css',
            branding: false,
            relative_urls: false,
            height: 250,
            toolbar_mode: 'sliding',
            file_picker_callback : browser,
            autosave_ask_before_unload: true,
            plugins: [
              'advlist autolink lists link image charmap print preview anchor',
              'searchreplace visualblocks code fullscreen',
              'insertdatetime media table paste wordcount emoticons'
            ],
            toolbar: 'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl',
          }"
          required
        />
      </div>

      <div class="md:w-1/2 flex flex-col gap-5">
        <div
          v-t="'Invitees'"
          class="text-h6"
        />

        <EditLinks
          :edit-status="false"
          :item="item"
          :links-type="linksType"
          :show-status="false"
        />

        <div class="form__field flex-row">
          <Checkbox
            id="is_collective"
            v-model="item.collective"
            :binary="true"
          />
          <label
            v-t="'Is it editable by the invitees?'"
            for="is_collective"
          />
        </div>
      </div>
    </div>

    <slot />
  </form>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useStore } from 'vuex';
import { useVuelidate } from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import InputText from 'primevue/inputtext';
import Calendar from 'primevue/calendar';
import EditLinks from '../resource_links/EditLinks.vue';
import Checkbox from 'primevue/checkbox';

const store = useStore();

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
});

const item = computed(() => props.initialValues || props.values);

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
    collective: {}
  }
}));

const v$ = useVuelidate(rules, { item });

// eslint-disable-next-line no-undef
defineExpose({
  v$,
})

const linksType = ref('users');
const isCurrentTeacher = computed(() => store.getters['security/isCurrentTeacher']);
const isAdmin = computed(() => store.getters['security/isAdmin']);

if (!isAdmin.value) {
  if (isCurrentTeacher.value) {
    linksType.value = 'course_students';
  } else {
    linksType.value = 'user_rel_users';
  }
}
</script>
