<template>
  <div>
    <Toolbar
      :handle-submit="onSendForm"
      :handle-reset="resetForm"
      :handle-delete="del"
    />

    <DocumentsForm
      ref="updateForm"
      v-if="item"
      :values="item"
      :errors="violations"
    />

    <ResourceLinkForm
            ref="resourceLinkForm"
            v-if="item"
            :values="item"
    />

    <div v-if="item">
      <div v-if="item['resourceLinkList']">
        <ul>
          <li
                  v-for="link in item['resourceLinkList']"
          >
            <div v-if="link['course']">
              Course: {{ link.course.resourceNode.title }}
            </div>

            <div v-if="link['session']">
              Session: {{ link.session.resourceNode.title }}
            </div>

            <v-select
                    :items="visibilityList"
                    v-model="link.visibility"
                    label="Status"
                    persistent-hint
            ></v-select>
          </li>
        </ul>
      </div>
    </div>

    <Loading :visible="isLoading || deleteLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import DocumentsForm from '../../components/documents/FormNewDocument';
import Loading from '../../components/Loading';
import Toolbar from '../../components/Toolbar';
import UpdateMixin from '../../mixins/UpdateMixin';

const servicePrefix = 'Documents';

export default {
  name: 'DocumentsUpdate',
  servicePrefix,
  mixins: [UpdateMixin],
  components: {
    Loading,
    Toolbar,
    DocumentsForm
  }

  ,
  data() {
    return {
      // See ResourceLink entity constants.
      visibilityList: [
        {value:2, text: 'Published'},
        {value:0, text: 'Invisible'},
      ],
    };
  },
  computed: {
    ...mapFields('documents', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('documents', ['find'])
  },

  methods: {
    ...mapActions('documents', {
      createReset: 'resetCreate',
      deleteItem: 'del',
      delReset: 'resetDelete',
      retrieve: 'load',
      update: 'update',
      updateReset: 'resetUpdate'
    })
  }
};
</script>
