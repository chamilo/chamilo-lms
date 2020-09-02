<template>
  <v-dialog
    v-model="show"
    persistent
    width="300"
  >
    <b-card>
      <b-card-text>{{ $t('Are you sure you want to delete this item?') }}</b-card-text>
      <b-button
        color="error darken-1"
        @click="handleDelete"
      >
        {{ $t('Delete') }}
      </b-button>
      <b-button
        color="secondary darken-1"
        text
        @click.stop="show = false"
      >
        {{ $t('Cancel') }}
      </b-button>
    </b-card>
  </v-dialog>
</template>

<script>
export default {
  name: 'ConfirmDelete',
  props: {
    visible: {
      type: Boolean,
      required: true,
      default: () => false
    },
    handleDelete: {
      type: Function,
      required: true
    }
  },
  computed: {
    show: {
      get() {
        return this.visible;
      },
      set(value) {
        if (!value) {
          this.$emit('close');
        }
      }
    }
  }
};
</script>
