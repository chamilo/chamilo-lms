import axios from "axios"

export async function checkIsAllowedToEdit(tutor = false, coach = false, sessionCoach = false, checkStudentView = true) {
  try {
    const { data } = await axios.get(window.location.origin + '/permissions/is_allowed_to_edit', {
      params: {
        tutor,
        coach,
        sessioncoach: sessionCoach,
        checkstudentview: checkStudentView,
      },
    })

    return data.isAllowedToEdit
  } catch (e) {
    console.log(e)
  }

  return false
}
