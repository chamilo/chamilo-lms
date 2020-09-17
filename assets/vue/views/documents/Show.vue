<template>
  <div>
    <Toolbar
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
    <br>
    <div
      v-if="item"
      class="table-documents-show"
    >
      <div v-if="item['resourceLinkList']">
        <ul>
          <li
            v-for="link in item['resourceLinkList']"
          >
            Status: {{ link.visibilityName }}
            <div v-if="link['course']">
              Course: {{ link.course.resourceNode.title }}
            </div>
            <div v-if="link['session']">
              Session: {{ link.session.resourceNode.title }}
            </div>
          </li>
        </ul>
      </div>

      <h2>
        {{ item['title'] }}
      </h2>

      <b-table-simple>
        <template slot="default">
          <tbody>
            <tr>
              <td><strong>{{ $t('Author') }}</strong></td>
              <td>
                {{ item['resourceNode'].creator.username }}
              </td>
              <td><strong /></td>
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
                {{ item['resourceNode'] && item['resourceNode'].createdAt | moment("from", "now") }}
              </td>
              <td />
            </tr>

            <tr>
              <td><strong>{{ $t('Updated at') }}</strong></td>
              <td>
                {{ item['resourceNode'] && item['resourceNode'].updatedAt | moment("from", "now") }}
              </td>
              <td />
            </tr>

            <tr v-if="item['resourceNode']['resourceFile']">
              <td><strong>{{ $t('File') }}</strong></td>
              <td>
                <div>
                  <b-img
                    v-if="item['resourceNode']['resourceFile']['image']"
                    :src="item['contentUrl'] + '?w=300'"
                  />
                  <span v-else-if="item['resourceNode']['resourceFile']['video']">
                    <video controls>
                      <source :src="item['contentUrl']" />
                    </video>
                  </span>
                  <span v-else>
                    <b-btn
                      variant="primary"
                      :href="item['downloadUrl']"
                    >
                      {{ $t('Download file') }}
                    </b-btn>
                  </span>
                </div>
              </td>
              <td />
            </tr>
          </tbody>
        </template>
      </b-table-simple>
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
