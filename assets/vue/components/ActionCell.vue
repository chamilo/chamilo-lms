<template>
  <div>
    <b-row>
      <b-button
        v-if="handleShow"
        size="sm"
        class="mr-2"
        @click="handleShow"
      >
        Info
      </b-button>
      <b-button
        v-if="handleEdit"
        size="sm"
        class="mr-2"
        @click="handleEdit"
      >
        Edit
      </b-button>
      <b-button
        v-if="handleDelete"
        size="sm"
        @click="confirmDelete = true"
      >
        Delete
      </b-button>
    </b-row>
    <ConfirmDelete
      v-if="handleDelete"
      :visible="confirmDelete"
      :handle-delete="handleDelete"
      @close="confirmDelete = false"
    />
  </div>
</template>

<script>
import ConfirmDelete from './ConfirmDelete';

export default {
  name: 'ActionCell',
  components: {
    ConfirmDelete
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
  },
  data() {
    return {
      confirmDelete: false
    };
  }
};
</script>
