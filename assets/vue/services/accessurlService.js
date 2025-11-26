import baseService from "./baseService"

/**
 *
 * @param {string} userIri
 * @returns {Promise<Object[]>}
 */
export async function findUserActivePortals(userIri) {
  const { items } = await baseService.getCollection(`${userIri}/access_urls`)

  return items
}

async function getUrl(id) {
  const apiUrl = `/api/access_urls/${encodeURIComponent(id)}`;
  try {
    const resp = await fetch(apiUrl, {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    });
    if (resp.ok) {
      const contentType = resp.headers.get("content-type") || "";
      return contentType.includes("application/json") ? resp.json() : { url: await resp.text() };
    }
    // If API returns 404 or not found, fall through to legacy endpoint
    if (resp.status !== 404) {
      const txt = await resp.text();
      throw new Error(txt || "API fetch failed");
    }
  } catch (err) {
  }
}

async function deleteAccessUrl(id, confirmValue, secToken = "") {
  const url = `/api/access_urls/${encodeURIComponent(id)}`
  const resp = await fetch(url, {
    method: "DELETE",
    credentials: "same-origin",
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...(secToken ? { "X-CSRF-Token": secToken } : {}),
    },
    body: JSON.stringify({ confirm_value: String(confirmValue) }), // certains backends acceptent un body sur DELETE
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
}

export default {
  deleteAccessUrl,
  getUrl,
}
