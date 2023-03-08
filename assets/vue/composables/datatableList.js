import { useStore } from 'vuex'
import { inject, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { isEmpty } from 'lodash'

import { useCidReq } from './cidReq'
import { useI18n } from 'vue-i18n';

export function useDatatableList (servicePrefix) {
  const moduleName = servicePrefix.toLowerCase()

  const store = useStore()
  const router = useRouter()
  const route = useRoute()
  const { t } = useI18n()

  const { cid, sid, gid } = useCidReq()

  const flashMessageList = inject('flashMessageList')

  const filters = ref({})

  const expandedFilter = ref(false)

  const options = ref({
    sortBy: [],
    sortDesc: false,
    page: 1,
    itemsPerPage: 5,
  })

  function onUpdateOptions ({ page, itemsPerPage, sortBy, sortDesc }) {
    page = page || options.value.page

    let params = { ...filters.value }

    if (1 === filters.value.loadNode) {
      console.log('params', route.params)
      params['resourceNode.parent'] = route.params.node
    }

    if (itemsPerPage > 0) {
      params = { ...params, itemsPerPage, page }
    }

    if (!isEmpty(sortBy)) {
      params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc'
    }

    let type = route.query.type

    params = { ...params, cid, sid, gid, type, page }

    store.dispatch(`${moduleName}/fetchAll`, params)
      .then(() => options.value = { sortBy, sortDesc, itemsPerPage, page })
  }

  function goToAddItem () {
    console.log('addHandler');

    let folderParams = route.query;

    router.push({
      name: `${servicePrefix}Create`,
      query: folderParams,
    });
  }

  function goToEditItem (item) {
    let folderParams = route.query;
    folderParams['id'] = item['@id'];

    if ('folder' === item.filetype || isEmpty(item.filetype)) {
      router.push({
        name: `${servicePrefix}Update`,
        params: { id: item['@id'] },
        query: folderParams
      });
    }

    if ('file' === item.filetype) {
      folderParams['getFile'] = true;
      if (item.resourceNode.resourceFile &&
          item.resourceNode.resourceFile.mimeType &&
          'text/html' === item.resourceNode.resourceFile.mimeType
      ) {
        //folderParams['getFile'] = true;
      }

      this.$router.push({
        name: `${servicePrefix}UpdateFile`,
        params: { id: item['@id'] },
        query: folderParams
      });
    }
  }

  function onShowItem (item) {
    console.log('listmixin showHandler', item);

    let folderParams = route.query;

    if (item) {
      folderParams['id'] = item['@id'];
    }

    router.push({
      name: `${servicePrefix}Show`,
      params: folderParams,
      query: folderParams,
    });
  }

  async function deleteItem (item) {
    await store.dispatch(`${moduleName}/del`, item.value)

    onUpdateOptions(options.value);

    flashMessageList.value.push({
      severity: 'success',
      detail: t('Deleted')
    })
  }

  return {
    filters,
    expandedFilter,
    options,
    onUpdateOptions,
    goToAddItem,
    onShowItem,
    goToEditItem,
    deleteItem,
  }
}
