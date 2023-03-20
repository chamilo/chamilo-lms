import { inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import { useStore } from 'vuex';

export function useDatatableCreate (servicePrefix) {
    const moduleName = servicePrefix.toLowerCase();

    const store = useStore();
    const router = useRouter();
    const route = useRoute();
    const { t } = useI18n();

    const flashMessageList = inject('flashMessageList');

    function onCreated (item) {
        flashMessageList.value.push({
            severity: 'success',
            detail: t('{resource} created', {
                'resource': item['resourceNode'] ? item['resourceNode'].title : item.title,
            }),
        });

        let folderParams = route.query;

        router.push({
            name: `${servicePrefix}List`,
            params: {id: item['@id']},
            query: folderParams,
        });
    }

    async function createItem (item) {
        await store.dispatch(`${moduleName}/create`, item)
    }

    return {
        createItem,
        onCreated,
    };
}