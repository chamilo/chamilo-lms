const assert = require("node:assert/strict")
const { readFileSync } = require("node:fs")
const { join } = require("node:path")
const { describe, it, before } = require("node:test")

let mergeAssignmentPage
before(async () => {
  const source = readFileSync(join(__dirname, "../../assets/vue/utils/assignmentListPage.js"), "utf8")
  const helper = await import(`data:text/javascript;base64,${Buffer.from(source).toString("base64")}`)
  mergeAssignmentPage = helper.mergeAssignmentPage
})

const row = (id) => ({ "@id": `/api/c_student_publications/${id}`, title: `Session ${id}` })
const base = (id, title, copied = false, canDuplicate = true) => ({ id, title, copied, canDuplicate })
const originals = [base(1, "Bravo"), base(2, "Alpha"), base(3, "Copied", true), base(4, "Zulu", false, false)]
const page = (sessionItems, sessionTotal, number, pageSize = 2, baseItems = originals, titleOrder = "asc") =>
  mergeAssignmentPage({ sessionItems, sessionTotal, baseItems, page: number, pageSize, titleOrder })

const keys = (result) => result.items.map((item) => item["@id"])

describe("Unified assignment pagination", () => {
  it("shows only the pending originals when a session has no assignments", () => {
    const result = page([], 0, 1)
    assert.equal(result.total, 3)
    assert.deepEqual(keys(result), ["base-assignment:2", "base-assignment:1"])
    assert.equal(result.items[0].isBaseCourseAssignment, true)
    assert.equal(result.items[0].canDuplicate, true)
  })

  it("fills the final session page with originals without skipping or repeating rows", () => {
    const pages = [page([row(10), row(11)], 3, 1), page([row(12)], 3, 2), page([], 3, 3)]
    assert.deepEqual(
      pages.map((result) => result.total),
      [6, 6, 6],
    )
    assert.deepEqual(pages.flatMap(keys), [
      "/api/c_student_publications/10",
      "/api/c_student_publications/11",
      "/api/c_student_publications/12",
      "base-assignment:2",
      "base-assignment:1",
      "base-assignment:4",
    ])
  })

  it("handles an exact session page boundary and retains unsupported originals", () => {
    const result = page([], 2, 2, 2, originals, "desc")
    assert.deepEqual(keys(result), ["base-assignment:4", "base-assignment:1"])
    assert.equal(result.items[0].canDuplicate, false)
    const boundary = page([], 2, 2)
    assert.deepEqual(keys(boundary), ["base-assignment:2", "base-assignment:1"])
  })

  it("replaces the original with its session copy without increasing the combined count", () => {
    const before = page([], 0, 1, 20, [base(7, "Task")])
    const after = page([row(17)], 1, 1, 20, [base(7, "Task", true)])
    assert.equal(before.total, after.total)
    assert.deepEqual(keys(after), ["/api/c_student_publications/17"])
    assert.equal(after.items[0].isBaseCourseAssignment, undefined)
  })

  it("preserves ordinary pagination when there are no base items", () => {
    const result = page([row(3)], 3, 2, 2, [])
    assert.equal(result.total, 3)
    assert.deepEqual(result.items, [row(3)])
  })

  it("sorts pending originals by title within their block and preserves the server order", () => {
    const result = page([row(12), row(10)], 2, 1, 10, originals, "desc")
    assert.deepEqual(keys(result), [
      "/api/c_student_publications/12",
      "/api/c_student_publications/10",
      "base-assignment:4",
      "base-assignment:1",
      "base-assignment:2",
    ])
    assert.equal(originals[0].title, "Bravo")
  })
})
