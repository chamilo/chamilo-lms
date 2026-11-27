import { expect, type Page } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Given, When, Then } = createBdd()
const sameOrigin = { "sec-fetch-site": "same-origin", Accept: "application/json" }
let courseId: number | null = null
const cards = (page: Page) => page.locator('[data-type="course_description"]')
const card = (page: Page, title: string) => cards(page).filter({ has: page.getByText(title, { exact: true }) })

function requireCourseId() {
  if (!courseId) throw new Error("The isolated description course has not been created")
  return courseId
}

Given("I create an isolated course for description ordering", async ({ page }) => {
  const code = `CDORDER${Date.now()}`
  const created = await page.request.post("/course/create", {
    headers: sameOrigin,
    data: { name: code, code, language: "en_US", fillDemoContent: false },
  })
  expect(created.ok(), await created.text()).toBeTruthy()
  const result = await created.json()
  expect(result.success).toBe(true)
  courseId = Number(result.courseId)
  expect(courseId).toBeGreaterThan(0)
  for (const title of ["QA Description A", "QA Description B"]) {
    const response = await page.request.post(`/api/course-description?cid=${courseId}`, {
      headers: sameOrigin,
      data: { descriptionType: 8, title, content: `<p>Content ${title}</p>`, progress: 0, language: "", enableSearch: false },
    })
    expect(response.status(), await response.text()).toBe(201)
  }
})

Given("I open the isolated course description", async ({ page }) => {
  await page.goto(`/main/course_description/index.php?cid=${requireCourseId()}&sid=0&gid=0`)
  await expect(cards(page).first()).toBeVisible()
})

When("I reload the isolated course description", async ({ page }) => {
  await page.reload()
  await expect(cards(page).first()).toBeVisible()
})

async function fillDescription(page: Page, title: string) {
  await page.locator("#course_description_title").fill(title)
  await expect(page.locator("#course_description_content_ifr")).toBeVisible()
  await page.evaluate((newTitle) => {
    const editor = (window as any).tinymce?.get("course_description_content")
    if (!editor) throw new Error("Course description editor is not ready")
    editor.setContent(`<p>Content ${newTitle}</p>`)
    editor.fire("change")
  }, title)
  await page.locator('[name="save"]').click()
  await expect(card(page, title)).toBeVisible()
}

When("I add a custom description named {string}", async ({ page }, title: string) => {
  await page.getByRole("link", { name: "Other", exact: true }).click()
  await fillDescription(page, title)
})

When("I edit the description {string} to {string}", async ({ page }, oldTitle: string, title: string) => {
  await card(page, oldTitle).getByRole("link", { name: "Edit", exact: true }).click()
  await expect(page.locator("#course_description_title")).toHaveValue(oldTitle)
  await fillDescription(page, title)
})

When("I move description {string} up", async ({ page }, title: string) => {
  const response = page.waitForResponse((r) => r.url().includes("/api/course-description/reorder") && r.request().method() === "POST")
  await card(page, title).locator('[name="course-description-move-up"]').click()
  expect((await response).ok()).toBeTruthy()
  await expect(card(page, title)).toBeVisible()
})

Then("the isolated descriptions appear in order {string}", async ({ page }, order: string) => {
  const titles = order.split("|")
  await expect(cards(page)).toHaveCount(titles.length)
  for (const [index, title] of titles.entries()) {
    await expect(cards(page).nth(index).getByText(title, { exact: true })).toBeVisible()
  }
})

When("I remove the description {string}", async ({ page }, title: string) => {
  await card(page, title).getByRole("button", { name: "Delete", exact: true }).click()
  await page.getByRole("button", { name: "Yes", exact: true }).click()
  await expect(card(page, title)).toHaveCount(0)
})

Given("I enroll the teacher and learner in the isolated description course", async ({ page }) => {
  const course = `/api/courses/${requireCourseId()}`
  for (const [username, status] of [["mmosquera", 1], ["acostea", 5]] as const) {
    const lookup = await page.request.get(`/api/users?username=${encodeURIComponent(username)}`, {
      headers: { Accept: "application/ld+json" },
    })
    expect(lookup.ok(), await lookup.text()).toBeTruthy()
    const result = await lookup.json()
    const members = result["hydra:member"] ?? result.member ?? []
    const user = members.find((item: { username: string; id: number }) => item.username === username)
    expect(user, `Missing test user ${username}`).toBeTruthy()
    const response = await page.request.post("/api/course_rel_users", {
      headers: sameOrigin,
      data: { user: `/api/users/${user.id}`, course, relationType: 0, status },
    })
    expect(response.status(), await response.text()).toBe(201)
  }
})

Then("I cannot reorder isolated course descriptions", async ({ page }) => {
  await expect(page.locator('[name="course-description-move-up"], [name="course-description-move-down"]')).toHaveCount(0)
})

Then("I remove the isolated course for description ordering", async ({ page }) => {
  const response = await page.request.delete(`/api/courses/${requireCourseId()}`, { headers: sameOrigin })
  expect(response.status(), await response.text()).toBe(204)
  courseId = null
})
