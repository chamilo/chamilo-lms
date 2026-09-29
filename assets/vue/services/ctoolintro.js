import makeService from "./api"
import baseService from "./baseService"

// CToolIntro's Put operation would crash on the non-nullable courseTool
// relation (only set by the processor's Post branch). makeService()'s
// built-in update() defaults to PUT; override it to PATCH, keeping the
// Response-like `{ json() }` shape store/modules/crud.js expects.
async function update(payload) {
  const data = await baseService.patch(payload["@id"], payload)

  return { json: async () => data }
}

export default makeService("c_tool_intros", { update })
