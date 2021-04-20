<template>
  <div>
    <Toolbar
      v-if="item"
      :handle-edit="editHandler"
      :handle-delete="del"
    >
      <template slot="left">
<!--        <v-toolbar-title v-if="item">-->
<!--          {{-->
<!--            `${$options.servicePrefix} ${item['@id']}`-->
<!--          }}-->
<!--        </v-toolbar-title>-->
      </template>
    </Toolbar>
    <div
      v-if="item"
      class="table-documents-show"
    >
      <h5>
        {{ item['title'] }}
      </h5>
      <div v-if="item['resourceLinkListFromEntity']">
        <ul>
          <li
              v-for="link in item['resourceLinkListFromEntity']"
          >
            {{ $t('Status') }}: {{ link.visibilityName }}
            <div v-if="link['course']">
              {{ $t('Course') }}: {{ link.course.resourceNode.title }}
            </div>
            <div v-if="link['session']">
              {{ $t('Session') }}: {{ link.session.name }}
            </div>
            <div v-if="link['group']">
              {{ $t('Group') }}: {{ link.group.resourceNode.title }}
            </div>
          </li>
        </ul>
      </div>
      <q-markup-table>
          <tbody>
            <tr>
              <td><strong>{{ $t('Author') }}</strong></td>
              <td>
                {{ item['resourceNode'].creator.username }}
              </td>
              <td></td>
              <td />
            </tr>
            <tr>
              <td><strong>{{ $t('Comment') }}</strong></td>
              <td>
                {{ item['comment'] }}
              </td>
            </tr>
            <tr>
              <td><strong>{{ $t('Created at') }}</strong></td>
              <td>
                {{ item['resourceNode'] ? moment(item['resourceNode'].createdAt).fromNow() : ''}}
              </td>
              <td />
            </tr>
            <tr>
              <td><strong>{{ $t('Updated at') }}</strong></td>
              <td>
                {{ item['resourceNode'] ? moment(item['resourceNode'].updatedAt).fromNow() : ''}}
              </td>
              <td />
            </tr>
            <tr v-if="item['resourceNode']['resourceFile']">
              <td><strong>{{ $t('File') }}</strong></td>
              <td>
                <div>
                  <q-img
                    v-if="item['resourceNode']['resourceFile']['image']"
                    :src="item['contentUrl'] + '?w=300'"
                  />
                  <span v-else-if="item['resourceNode']['resourceFile']['video']">
                    <video controls>
                      <source :src="item['contentUrl']" />
                    </video>
                  </span>
                  <span v-else>
                    <q-btn
                      variant="primary"
                      :href="item['downloadUrl']"
                    >
                      {{ $t('Download file') }}
                    </q-btn>
                  </span>
                </div>
              </td>
              <td />
            </tr>
          </tbody>
      </q-markup-table>
    </div>
    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import ShowMixin from '../../mixins/ShowMixin';
import Toolbar from '../../components/Toolbar.vue';
import moment from 'moment'
const servicePrefix = 'Documents';

export default {
  name: 'DocumentsShow',
  servicePrefix,
  components: {
      Loading,
      Toolbar
  },
  created: function () {
    this.moment = moment;
  },
  mixins: [ShowMixin],
  computed: {
    ...mapFields('documents', {
      isLoading: 'isLoading'
    }),
    ...mapGetters('documents', ['find']),
  },
  methods: {
    ...mapActions('documents', {
      deleteItem: 'del',
      reset: 'resetShow',
      retrieve: 'load'
    }),
  }
};
</script>
