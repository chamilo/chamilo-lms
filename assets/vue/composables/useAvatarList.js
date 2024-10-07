import { computed } from "vue"
import { useI18n } from "vue-i18n"

export function useAvatarList(props) {
  const { t } = useI18n()

  const several = computed(() => props.users.length > props.countSeveral && props.shortSeveral)

  const userList = computed(() => (several.value ? props.users.slice(0, props.countSeveral) : props.users))

  const plusText = computed(() => {
    let diff = props.users.length - props.countSeveral

    if (diff) {
      return t("+%d", [diff])
    }

    return ""
  })

  return {
    several,
    userList,
    plusText,
  }
}
