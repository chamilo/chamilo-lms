import axios from "axios";

export default {
    login(login, password, token) {
        return axios.post("/login_json", {
            username: login,
            password: password,
            csrf_token: token
        });
    },
    logout() {
        return axios.get("/logout");
    }
}