import api from "../config/api"

async function toogleStudentView() {
  const { data } = await api.get("/toggle_student_view")

  return data
}

export default {
  toogleStudentView,
}
