import { useRoute } from 'vue-router';

export function useCidReq () {
    const route = useRoute();

    return {
        'cid': parseInt(route.query?.cid),
        'sid': parseInt(route.query?.sid),
        'gid': parseInt(route.query?.gid)
    };
}
