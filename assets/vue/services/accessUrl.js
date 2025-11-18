export default {
  async getUrl(id) {
    const resp = await fetch(
      `/main/admin/access_urls.php?action=fetch_url&url_id=${encodeURIComponent(id)}`,
      {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      },
    )
    if (!resp.ok) {
      const txt = await resp.text()
      throw new Error(txt || "Fetch failed")
    }
    return resp.json()
  },


  async deleteAccessUrl(id, confirmValue, secToken = "") {
    const params = new URLSearchParams({
      action: "delete_url",
      url_id: String(id),
      confirm_value: String(confirmValue),
    })
    if (secToken) params.append("sec_token", secToken)


    const url = `/main/admin/access_urls.php?${params.toString()}`


    const resp = await fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })


    if (!resp.ok) {
      const txt = await resp.text()
      throw new Error(txt || "Delete failed")
    }


    const contentType = resp.headers.get("content-type") || ""
    if (contentType.includes("application/json")) {
      return resp.json()
    }


    return { message: await resp.text(), redirectUrl: "/main/admin/access_urls.php" }
  },
}
