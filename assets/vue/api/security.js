import axios from "axios";

export default {
    login(login, password, token) {
        console.log(login);
        console.log(password);
        console.log(token);

        return axios.post("/login_json", {
            username: login,
            password: password,
            csrf_token: token
        });
    }
}