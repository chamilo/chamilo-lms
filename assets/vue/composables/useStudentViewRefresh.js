import { watch } from "vue"
import { usePlatformConfig } from "../store/platformConfig"

/**
 * Re-runs `reload` whenever the student view is toggled.
 *
 * The student view state lives in the server session (the `studentview` key);
 * the only client-side mirror is platformConfig.studentView, written by
 * StudentViewButton from the /toggle_student_view response. Views must refetch
 * instead of recomputing locally, because what changes is server-side: the
 * canManage flag, the per-item canEdit/canDelete flags, and in several tools
 * the item list itself, all resolved by StudentViewHelper-aware providers.
 *
 * Do not replace this with a plain `watch` on the store: keeping every tool on
 * one helper is what makes the coverage auditable, and the reason for the
 * refetch is exactly the thing that got lost and left the button inert.
 *
 * @param {() => unknown | Promise<unknown>} reload
 * @param {Object} [options]
 * @param {() => void} [options.before] Runs synchronously ahead of the reload,
 *   for views that must clear selections, drafts or flash messages first.
 * @returns {void}
 */
export function useStudentViewRefresh(reload, { before } = {}) {
  const platformConfigStore = usePlatformConfig()

  watch(
    () => platformConfigStore.isStudentViewActive,
    async () => {
      before?.()

      await reload()
    },
  )
}
