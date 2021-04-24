<template>
  <q-td slot="body-cell-action" auto-width>
    <q-btn
        v-if="handleShow"
        dense
        color="secondary"
        @click="handleShow"
        label="Show"
    />
    <q-btn
        v-if="handleEdit"
           dense
           color="secondary"
           @click="handleEdit"
        label="Edit"
    />
    <q-btn
        v-if="handleDelete"
        label="Delete"
        dense
        color="red"
        @click="confirmDeleteClick = true"
    />
    <ConfirmDelete
        v-if="handleDelete"
        :show="confirmDeleteClick"
        :handle-delete="handleDelete"
        :handle-cancel="() => (confirmDeleteClick = false)"
    />
  </q-td>
</template>

<script>
import ConfirmDelete from './ConfirmDelete.vue';

export default {
  name: 'ActionCell',
  components: {
    ConfirmDelete
  },
  data() {
    return {
      confirmDeleteClick: false
    };
  },
  props: {
    handleShow: {
      type: Function,
      required: false
    },
    handleEdit: {
      type: Function,
      required: false
    },
    handleDelete: {
      type: Function,
      required: false
    }
  }
};
</script>
