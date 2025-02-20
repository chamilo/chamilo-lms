<template>
  <!--  auto-width-->
  <q-td slot="body-cell-action">
    <div class="p-4 flex flex-row gap-1">
      <q-btn
        v-if="handleShow"
        color="secondary"
        dense
        label="Show"
        no-caps
        @click="handleShow"
      />
      <q-btn
        v-if="handleEdit"
        color="secondary"
        dense
        label="Edit"
        no-caps
        @click="handleEdit"
      />
      <q-btn
        v-if="handleDelete"
        color="red"
        dense
        label="Delete"
        no-caps
        @click="confirmDeleteClick = true"
      />
      <ConfirmDelete
        v-if="handleDelete"
        :handle-cancel="() => (confirmDeleteClick = false)"
        :handle-delete="handleDelete"
        :show="confirmDeleteClick"
      />
    </div>
  </q-td>
</template>

<script>
import ConfirmDelete from "./ConfirmDelete.vue"

export default {
  name: "ActionCell",
  components: {
    ConfirmDelete,
  },
  data() {
    return {
      confirmDeleteClick: false,
    }
  },
  props: {
    handleShow: {
      type: Function,
      required: false,
    },
    handleEdit: {
      type: Function,
      required: false,
    },
    handleDelete: {
      type: Function,
      required: false,
    },
  },
}
</script>
