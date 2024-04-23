import { storeToRefs } from "pinia"
import { useCidReqStore } from "../store/cidReq"
import api from "../config/api"

/**
 * @param {boolean} tutor
 * @param {boolean} coach
 * @param {boolean} sessionCoach
 * @param {boolean} checkStudentView
 * @returns {Promise<boolean>}
 */
export async function checkIsAllowedToEdit(
  tutor = false,
  coach = false,
  sessionCoach = false,
  checkStudentView = true,
) {
  const cidReqStore = useCidReqStore()
  const { course, session } = storeToRefs(cidReqStore)

  try {
    const { data } = await api.get("/permissions/is_allowed_to_edit", {
      params: {
        tutor,
        coach,
        sessioncoach: sessionCoach,
        checkstudentview: checkStudentView,
        cid: course.value?.id,
        sid: session.value?.id,
      },
    })

    return data.isAllowedToEdit
  } catch (e) {
    console.log(e)
  }

  return false
}
