import { useStore } from 'vuex'
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { isEmpty } from 'lodash'

import { useCidReq } from './cidReq'

export function useDatatableList (servicePrefix) {
  const moduleName = servicePrefix.toLowerCase()

  const store = useStore()
  const route = useRoute()

  const { cid, sid, gid } = useCidReq()

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

  return {
    filters,
    expandedFilter,
    options,
    onUpdateOptions,
  }
}
