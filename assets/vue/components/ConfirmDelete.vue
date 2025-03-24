<template>
  <q-dialog
    :model-value="show"
    persistent
  >
    <v-card>
      <q-card-section class="row items-center">
        <q-avatar
          color="primary"
          icon="warning"
          text-color="white"
        />
        <span class="q-ml-sm">{{ $t("Are you sure you want to delete this item?") }}</span>
      </q-card-section>

      <v-card-actions>
        <q-btn
          v-close-popup
          color="primary"
          flat
          label="Cancel"
          @click="handleCancel"
        />
        <q-btn
          v-close-popup
          color="primary"
          flat
          label="Delete"
          @click="handleDelete"
        />
      </v-card-actions>
    </v-card>
  </q-dialog>
</template>

<script>
export default {
  name: "ConfirmDelete",
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
}
</script>
