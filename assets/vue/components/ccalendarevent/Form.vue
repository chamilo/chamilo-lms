<template>
    <q-form>
      <div class="row q-col-gutter-md">
        <div class="col-7 q-gutter-md">
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

          <q-input
              v-model="item.content"
              :error="v$.item.content.$error"
              :error-message="contentErrors"
              :placeholder="$t('Content')"
              type="textarea"
              @blur="v$.item.content.$touch()"
              @input="v$.item.content.$touch()"
          />
        </div>

        <div class="col-5 q-gutter-md">
          <h6 class="text-xl">Invitees</h6>

          <EditLinks :item="item" :show-status="false"/>

          <q-checkbox v-model="item.collective" label="Is it editable by the invitees?"/>
        </div>
      </div>

      <slot></slot>
    </q-form>
</template>

<script>
import has from 'lodash/has';
import useVuelidate from '@vuelidate/core';
import {required} from '@vuelidate/validators';
import EditLinks from "../resource_links/EditLinks.vue";

export default {
  name: 'CCalendarEventForm',
  components: {
    EditLinks
  },
  setup() {
    return {v$: useVuelidate()}
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
