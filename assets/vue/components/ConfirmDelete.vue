<template>
<!--  <b-modal-->
<!--    v-model="show"-->
<!--    persistent-->
<!--    hide-footer-->
<!--  >-->
<!--    <b-card>-->
<!--      <b-card-text>{{ $t('Are you sure you want to delete this item?') }}</b-card-text>-->
<!--      <b-button-->
<!--        color="error darken-1"-->
<!--        @click="handleDelete"-->
<!--      >{{ $t('Yes') }}-->
<!--      </b-button>-->
<!--      <b-button-->
<!--        color="secondary darken-1"-->
<!--        text-->
<!--        @click.stop="show = false"-->
<!--      >{{ $t('No') }}-->
<!--      </b-button>-->
<!--    </b-card>-->
<!--  </b-modal>-->
  <q-dialog :value="show" persistent>
    <q-card>
      <q-card-section class="row items-center">
        <q-avatar icon="warning" color="primary" text-color="white" />
        <span class="q-ml-sm">{{ $t('Are you sure you want to delete this item?') }}</span>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Cancel" color="primary" v-close-popup @click="handleCancel" />
        <q-btn flat label="Delete" color="primary" v-close-popup @click="handleDelete" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
export default {
  name: 'ConfirmDelete',
  props: {
    show: {
      type: Boolean,
      required: true,
    },
    handleDelete: {
      type: Function,
      required: true,
    },
    handleCancel: {
      type: Function,
      required: false,
    },
  },
};
</script>
