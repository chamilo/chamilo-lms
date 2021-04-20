<template>
<!--  <div>-->
<!--    <b-button-toolbar>-->
<!--      <b-button-->
<!--        v-if="handleShow"-->
<!--        variant="info"-->
<!--        size="sm"-->
<!--        class="mr-2"-->
<!--        @click="handleShow"-->
<!--      >{{ $t('Info') }}-->
<!--      </b-button>-->
<!--      <b-button-->
<!--        v-if="handleEdit"-->
<!--        size="sm"-->
<!--        class="mr-2"-->
<!--        @click="handleEdit"-->
<!--      >{{ $t('Edit') }}-->
<!--      </b-button>-->
<!--      <b-button-->
<!--        v-if="handleDelete"-->
<!--        variant="danger"-->
<!--        size="sm"-->
<!--        @click="confirmDelete = true"-->
<!--      >{{ $t('Delete') }}-->
<!--      </b-button>-->
<!--    </b-button-toolbar>-->

<!--    <ConfirmDelete-->
<!--      v-if="handleDelete"-->
<!--      :visible="confirmDelete"-->
<!--      :handle-delete="handleDelete"-->
<!--      @close="confirmDelete = false"-->
<!--    />-->
<!--  </div>-->

  <q-td slot="body-cell-action" auto-width>
    <q-btn
        v-if="handleShow"
        flat
        round
        dense
        color="secondary"
        @click="handleShow"
        icon="format_align_justify"
    />
    <q-btn v-if="handleEdit" flat round dense color="secondary" @click="handleEdit" icon="edit" />
    <q-btn
        v-if="handleDelete"
        icon="delete"
        flat
        round
        dense
        color="secondary"
        @click="confirmDelete = true"
    />
    <ConfirmDelete
        v-if="handleDelete"
        :show="confirmDelete"
        :handle-delete="handleDelete"
        :handle-cancel="() => (confirmDelete = false)"
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
      confirmDelete: false
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
