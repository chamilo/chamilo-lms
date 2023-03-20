<template>
<!--  auto-width-->
  <q-td slot="body-cell-action" >
    <div class="p-4 flex flex-row gap-1">
      <q-btn
          v-if="handleShow"
          no-caps
          dense
          color="secondary"
          @click="handleShow"
          label="Show"
      />
      <q-btn
          v-if="handleEdit"
          no-caps
             dense
             color="secondary"
             @click="handleEdit"
          label="Edit"
      />
      <q-btn
          v-if="handleDelete"
          no-caps
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
    </div>
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
