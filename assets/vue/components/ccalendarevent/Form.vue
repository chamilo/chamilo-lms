<template>
    <q-form>
      <v-container>
        <v-row>
          <v-col>
            <q-input
                id="item_title"
                v-model="item.title"
                :error="v$.item.title.$error"
                :error-message="titleErrors"
                :placeholder="$t('Title')"
                @blur="v$.item.title.$touch()"
                @input="v$.item.title.$touch()"
            />

            <q-input v-model="item.startDate" filled>
              <template v-slot:prepend>
                <q-icon class="cursor-pointer" name="event">
                  <q-popup-proxy transition-hide="scale" transition-show="scale">
                    <q-date v-model="item.startDate" mask="YYYY-MM-DD HH:mm">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup color="primary" flat label="Close"/>
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>

              <template v-slot:append>
                <q-icon class="cursor-pointer" name="access_time">
                  <q-popup-proxy transition-hide="scale" transition-show="scale">
                    <q-time v-model="item.startDate" format24h mask="YYYY-MM-DD HH:mm">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup color="primary" flat label="Close"/>
                      </div>
                    </q-time>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <q-input v-model="item.endDate" filled>
              <template v-slot:prepend>
                <q-icon class="cursor-pointer" name="event">
                  <q-popup-proxy transition-hide="scale" transition-show="scale">
                    <q-date v-model="item.endDate" mask="YYYY-MM-DD HH:mm">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup color="primary" flat label="Close"/>
                      </div>
                    </q-date>
                  </q-popup-proxy>
                </q-icon>
              </template>

              <template v-slot:append>
                <q-icon class="cursor-pointer" name="access_time">
                  <q-popup-proxy transition-hide="scale" transition-show="scale">
                    <q-time v-model="item.endDate" format24h mask="YYYY-MM-DD HH:mm">
                      <div class="row items-center justify-end">
                        <q-btn v-close-popup color="primary" flat label="Close"/>
                      </div>
                    </q-time>
                  </q-popup-proxy>
                </q-icon>
              </template>
            </q-input>

            <!--                <q-input-->
            <!--                    v-model="item.content"-->
            <!--                    :error="v$.item.content.$error"-->
            <!--                    :error-message="contentErrors"-->
            <!--                    :placeholder="$t('Content')"-->
            <!--                    type="textarea"-->
            <!--                    @blur="v$.item.content.$touch()"-->
            <!--                    @input="v$.item.content.$touch()"-->
            <!--                />-->

            <TinyEditor
                v-model="item.content"
                required
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
            />
          </v-col>

          <v-col>
            <div
                v-text="$t('Invitees')"
                class="text-h6"
            >
            </div>

            <EditLinks
                :item="item"
                :show-status="false"
                :edit-status="false"
                :links-type="linksType"
            />
            <q-checkbox
                v-model="item.collective"
                :label="$t('Is it editable by the invitees?')"
            />
          </v-col>

          <slot></slot>
        </v-row>
      </v-container>
    </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import {required} from '@vuelidate/validators';
import EditLinks from "../resource_links/EditLinks.vue";
import {computed, ref} from "vue";
import {useStore} from "vuex";

export default {
  name: 'CCalendarEventForm',
  components: {
    EditLinks
  },
  setup() {
    const store = useStore();

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

    return {
      v$: useVuelidate(),
      linksType
    }
  },
  props: {
    values: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => {
      }
    },
    initialValues: {
      type: Object,
      default: () => {
      }
    },
  },
  data() {
    return {
      title: null,
      content: null,
      parentResourceNodeId: null,
      collective: null,
    };
  },
  computed: {
    item() {
      return this.initialValues || this.values;
    },
    titleErrors() {
      const errors = [];
      if (!this.v$.item.title.$dirty) return errors;
      has(this.violations, 'title') && errors.push(this.violations.title);

      if (this.v$.item.title.required) {
        return this.$t('Field is required')
      }

      return errors;
    },

    violations() {
      return this.errors || {};
    }
  },
  validations: {
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
    }
  }
};
</script>
