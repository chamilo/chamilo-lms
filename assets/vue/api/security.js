import axios from "axios"

export default {
  login({ login, password, _remember_me, token }) {
    return axios.post("/login_json", {
      username: login,
      password,
      _remember_me,
      csrf_token: token,
    })
  },
  logout() {
    return axios.get("/logout")
  },
}
