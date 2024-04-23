import makeService from './api';
import axios from "axios"

export default makeService('social_posts', {
  async addPostAttachment(formData) {
    const endpoint = "/api/social_post_attachments"
    return await axios.post(endpoint, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
  }
});
