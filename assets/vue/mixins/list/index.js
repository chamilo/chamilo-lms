import { useStore } from 'vuex';
import { computed, ref } from 'vue';
import { useRoute } from 'vue-router';
import { isEmpty } from 'lodash';
import { useCidReq } from '../../composables/cidReq';

export function useList (moduleName) {
    const store = useStore();
    const route = useRoute();
    const { cid, sid, gid } = useCidReq();

    const pagination = ref({
        sortBy: 'resourceNode.title',
        descending: false,
        page: 1, // page to be displayed
        rowsPerPage: 10, // maximum displayed rows
        rowsNumber: 10, // max number of rows
    });

    const nextPage = ref(null);

    const filters = ref({});

    const filtration = ref({});

    const expandedFilter = ref(false);

    const options = ref({
        sortBy: [],
        sortDesc: [],
        page: 1,
        itemsPerPage: 10
    });

    const resetList = computed(() => store.state[`${moduleName}/resetList`]);

    function onRequest (props) {
        console.log('onRequest');
        console.log(props);

        const { page, rowsPerPage: itemsPerPage, sortBy, descending } = props.pagination;

        nextPage.value = page;

        if (isEmpty(nextPage)) {
            nextPage.value = 1;
        }

        let params = {};

        if (itemsPerPage > 0) {
            params = { ...params, itemsPerPage, page };
        }

        if (sortBy) {
            params[`order[${sortBy}]`] = descending ? 'desc' : 'asc';
        }

        if (route.params.node) {
            params[`resourceNode.parent`] = route.params.node;
        }

        resetList.value = true;

        store.dispatch(`${moduleName}/fetchAll`, params)
            .then(() => {
                pagination.value.sortBy = sortBy;
                pagination.value.descending = descending;
                pagination.value.rowsPerPage = itemsPerPage;
            });
    }

    function onUpdateOptions ({ page, itemsPerPage, sortBy, sortDesc, totalItems }) {
        console.log('ListMixin.js: onUpdateOptions');

        resetList.value = true;

        let params = { ...filters.value };

        console.log(params);

        if (1 === filters.value['loadNode']) {
            params['resourceNode.parent'] = route.params.node;
        }

        if (itemsPerPage > 0) {
            params = { ...params, itemsPerPage, page };
        }

        if (!isEmpty(sortBy)) {
            params[`order[${sortBy}]`] = sortDesc ? 'desc' : 'asc';
        }

        let type = route.query.type;

        params = { ...params, cid, sid, gid, type };

        store.dispatch(`${moduleName}/fetchAll`, params)
            .then(() => options.value = { sortBy, sortDesc, itemsPerPage, totalItems });
    }

    return {
        pagination,
        nextPage,
        filters,
        filtration,
        expandedFilter,
        options,
        onRequest,
        onUpdateOptions,
    };
}
