import { expect } from "@playwright/test"
import { createBdd } from "playwright-bdd"

const { Given } = createBdd()

// Some user extra fields are created by the installer itself (src/CoreBundle/DataFixtures/ExtraFieldFixtures.php,
// e.g. terms_ville, hidden by default). Creating one of them again through the "add" form is silently refused because
// its variable is taken, and the field stays as the installer left it. Open the edit page of the existing field instead:
// the id is looked up by variable, as it differs between installations.
Given("I am on the edit page of the user extra field {string}", async ({ page }, variable: string) => {
  const response = await page.request.get(`/api/extra_fields?variable=${encodeURIComponent(variable)}&itemType=1`, {
    headers: { Accept: "application/ld+json" },
  })
  expect(response.ok(), `GET /api/extra_fields for "${variable}" failed with ${response.status()}`).toBeTruthy()
  const members = (await response.json())["hydra:member"] ?? []
  const field = members.find((item: { variable?: string }) => item.variable === variable)
  expect(field?.id, `No user extra field with the variable "${variable}"`).toBeTruthy()
  await page.goto(`/main/admin/extra_fields.php?type=user&action=edit&id=${field.id}`)
})
