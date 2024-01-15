<template>
  <q-form>
    <q-input
      id="item_title"
      v-model="item.title"
      :error="v$.item.title.$error"
      :error-message="titleErrors"
      :placeholder="$t('Title')"
      @blur="v$.item.title.$touch()"
      @input="v$.item.title.$touch()"
    />
    <slot></slot>
  </q-form>
</template>

<script>
import has from "lodash/has"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"

export default {
  name: "PersonalFileForm",
  setup() {
    return { v$: useVuelidate() }
  },
  props: {
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
  },
  data() {
    return {
      title: null,
      parentResourceNodeId: null,
    }
  },
  computed: {
    item() {
      return this.initialValues || this.values
    },
    titleErrors() {
      const errors = []
      if (!this.v$.item.title.$dirty) return errors
      has(this.violations, "title") && errors.push(this.violations.title)

      if (this.v$.item.title.required) {
        return this.$t("Field is required")
      }

      return errors
    },
    violations() {
      return this.errors || {}
    },
  },
  validations: {
    item: {
      title: {
        required,
      },
      parentResourceNodeId: {},
    },
  },
}
</script>
