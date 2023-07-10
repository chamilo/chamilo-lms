import { useRoute } from "vue-router"

export function useCidReq() {
  const route = useRoute()

  return {
    cid: parseInt(route.query?.cid ?? 0),
    sid: parseInt(route.query?.sid ?? 0),
    gid: parseInt(route.query?.gid ?? 0),
  }
}
