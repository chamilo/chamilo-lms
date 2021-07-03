<template>
    <q-dialog :model-value="show" persistent>
      <v-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="primary" text-color="white" />
          <span class="q-ml-sm">{{ $t('Are you sure you want to delete this item?') }}</span>
        </q-card-section>

        <v-card-actions>
          <q-btn flat label="Cancel" color="primary" v-close-popup @click="handleCancel" />
          <q-btn flat label="Delete" color="primary" v-close-popup @click="handleDelete" />
        </v-card-actions>
      </v-card>
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
