<template>
  <div>
    <Toolbar :handle-edit="editHandler"  :handle-delete="del">
      <template slot="left">
        <v-toolbar-title v-if="item">{{
          `${$options.servicePrefix} ${item['@id']}`
        }}</v-toolbar-title>
      </template>
    </Toolbar>

    <br />

    <div v-if="item" class="table-documents-show">
      <v-simple-table>
        <template slot="default">
          <thead>
            <tr>
              <th>Field</th>
              <th>Value</th>
              <th>Field</th>
              <th>Value</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>{{ $t('title') }}</strong></td>
              <td>
                {{ item['title'] }}
              </td>

              <td><strong>{{ $t('comment') }}</strong></td>
              <td>
                {{ item['comment'] }}
              </td>

            </tr>

            <tr>
              <td><strong>{{ $t('resourceNode') }}</strong></td>
              <td>
                  {{ item['resourceNode'] && item['resourceNode'].name }}
              </td>
              <td></td>
            </tr>
            <tr>
              <td><strong>{{ $t('file') }}</strong></td>
              <td>
                <div v-if="item['resourceNode']['resourceFile']">
<!--                  <img v-bind:src=" item['resourceNode']['resourceFile']['file'] " />-->
                    <img v-bind:src=" item['contentUrl'] " />
                </div>
                <div v-else>
                  -
                </div>
              </td>
              <td></td>
            </tr>
          </tbody>
        </template>
      </v-simple-table>
    </div>

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar';

const servicePrefix = 'Documents';

export default {
  name: 'DocumentsShow',
  servicePrefix,
  components: {
      Loading,
      Toolbar
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('documents', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('documents', ['find'])
  },
  methods: {
    ...mapActions('documents', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'load'
    })
  }
};
</script>
