import { computed, inject } from 'vue';
import { useStore } from 'vuex';
import { useRoute } from 'vue-router';
import { isEmpty } from 'lodash';
import { useI18n } from 'vue-i18n';

export function useDatatableUpdate (servicePrefix) {
    const moduleName = servicePrefix.toLowerCase();

    const store = useStore();
    const route = useRoute();
    const { t } = useI18n();

    const flashMessageList = inject('flashMessageList');

    const isLoading = computed(() => store.getters[`${moduleName}/isLoading`]);

    async function retrieve () {
        let id = route.params.id;

        if (isEmpty(id)) {
            id = route.query.id;
        }

        if (isEmpty(id)) {
            return;
        }

        await store.dispatch(`${moduleName}/load`, decodeURIComponent(id));
    }

    const retrievedItem = computed(() => {
        let id = route.params.id;

        if (isEmpty(id)) {
            id = route.query.id;
        }

        if (isEmpty(id)) {
            return null;
        }

        return store.getters[`${moduleName}/find`](id);
    });

    async function updateItem (item) {
        await store.dispatch(`${moduleName}/update`, item);
    }

    const updated = computed(() => store.state[moduleName].updated);

    function onUpdated (item) {
        flashMessageList.value.push({
            severity: 'success',
            detail: t('{resource} updated', {
                'resource': item['@id'],
            }),
        });
    }

    return {
        isLoading,
        retrieve,
        retrievedItem,
        updateItem,
        updated,
        onUpdated,
    };
}