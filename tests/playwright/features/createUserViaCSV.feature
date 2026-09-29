# Ported from tests/behat/features/createUserViaCSV.feature with one drift fix:
#
# - `/main/admin/user_list.php` is the same dead stub createUser.feature already
#   hit (user list lives at the Vue SPA `/admin/user-list`, which still accepts
#   `?keyword=` on mount and searches email among other fields — confirmed in
#   UserListController). The original Behat URL also omitted the leading `/`
#   ("main/admin/..." rather than "/main/admin/..."); fixed while updating.
# - `/main/admin/user_import.php` is still the live legacy FormValidator page
#   (field `import_file`, button label "Import", default file_type=csv /
#   sendMail=0). No two-step "Proceed with the import" for a non-resume import —
#   processUsers() runs on the first POST and redirects with a flash.
# - Example CSV is the repo's own `public/main/admin/example.csv` (UTF-8 BOM,
#   comma-separated; creates "marty" + "emmert"). "Then I should see emmert"
#   checks the username column after searching by that user's email.
@administration
Feature: Users creation via CSV

  Scenario: Import user via CSV
    Given I am a platform administrator
    And I am on "/main/admin/user_import.php"
    And I wait for the page to be loaded
    Then I attach the file "/public/main/admin/example.csv" to "import_file"
    Then I press "Import"
    And wait very long for the page to be loaded
    Then I should not see an error
    Then I am on "/admin/user-list?keyword=drbrown@example.net"
    And wait very long for the page to be loaded
    Then I should see "emmert"
