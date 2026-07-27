#!/usr/bin/env bash
# Run Chamilo's test tiers inside the stack.
#
#   docker/test.sh phpunit [args...]          PHPUnit (resets chamilo_test first)
#   docker/test.sh phpunit --no-reset [args]  PHPUnit without rebuilding the DB
#   docker/test.sh behat [feature...]         Behat; no args = the CI feature list
#   docker/test.sh phpstan                    PHPStan
#   docker/test.sh psalm                      Psalm
#   docker/test.sh ecs                        Coding standards (dry run)
#   docker/test.sh all                        phpunit + phpstan + ecs
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

. docker/lib.sh

"${DC[@]}" ps --status running --services 2>/dev/null | grep -qx web \
    || die "the web container is not running — start it with docker/setup.sh"

reset_test_db() {
    bold "Resetting chamilo_test"
    "${EXEC[@]}" php bin/console --env=test doctrine:database:create --if-not-exists
    "${EXEC[@]}" php bin/console --env=test doctrine:schema:drop --force --full-database
    "${EXEC[@]}" php bin/console --env=test doctrine:schema:create
    "${EXEC[@]}" php bin/console --env=test doctrine:fixtures:load --no-interaction
}

# The CI list from .github/workflows/behat.yml. Features commented out there
# (companyReports, registration, toolExercise) are left out here too.
BEHAT_CI_FEATURES=(
    actionUserLogin adminFillUsers adminSettings career class course
    course_user_registration courseCategory createUser createUserViaCSV
    extraFieldUser profile promotion sessionAccess sessionManagement skill
    socialGroup systemAnnouncements ticket toolAgenda toolAnnouncement
    toolAttendance toolDocument toolForum toolGroup toolLink toolLp
    toolThematic toolWork
)

cmd="${1:-}"
shift || true

case "$cmd" in
    phpunit)
        if [[ "${1:-}" == "--no-reset" ]]; then
            shift
        else
            reset_test_db
        fi
        bold "PHPUnit"
        "${DC[@]}" exec -T web php bin/phpunit --testdox "$@"
        ;;

    behat)
        if [[ $# -gt 0 ]]; then
            targets=("$@")
        else
            targets=()
            for f in "${BEHAT_CI_FEATURES[@]}"; do targets+=("features/${f}.feature"); done
        fi

        failed=()
        for t in "${targets[@]}"; do
            bold "Behat: ${t}"
            # Fresh grid per feature — see reset_selenium() in docker/lib.sh.
            reset_selenium
            if ! behat_run "$t" -v; then
                failed+=("$t")
            fi
        done

        if [[ ${#failed[@]} -gt 0 ]]; then
            printf '\n\033[1;31mFailed features (%d):\033[0m\n' "${#failed[@]}"
            printf '  %s\n' "${failed[@]}"
            exit 1
        fi
        bold "All Behat features passed"
        ;;

    phpstan)
        # phpstan.neon reads the compiled dev container
        # (var/cache/dev/Chamilo_KernelDevDebugContainer.xml) for the Symfony
        # extension, so it has to exist before the analysis runs.
        bold "Warming the dev container for PHPStan"
        "${EXEC[@]}" php bin/console cache:warmup --env=dev
        bold "PHPStan"
        "${DC[@]}" exec -T web vendor/bin/phpstan analyse --memory-limit=-1 "$@"
        ;;

    psalm)
        bold "Psalm"
        "${DC[@]}" exec -T web vendor/bin/psalm --no-cache "$@"
        ;;

    ecs)
        bold "Easy Coding Standard (dry run)"
        "${DC[@]}" exec -T web vendor/bin/ecs check --ansi "$@"
        ;;

    all)
        reset_test_db
        bold "PHPUnit"
        "${DC[@]}" exec -T web php bin/phpunit --testdox
        "${EXEC[@]}" php bin/console cache:warmup --env=dev
        bold "PHPStan"
        "${DC[@]}" exec -T web vendor/bin/phpstan analyse --memory-limit=-1 || true
        bold "Easy Coding Standard"
        "${DC[@]}" exec -T web vendor/bin/ecs check --ansi || true
        ;;

    ""|-h|--help)
        sed -n '2,11p' "$0" | sed 's/^# \{0,1\}//'
        ;;

    *)
        die "unknown command: ${cmd} (try: docker/test.sh --help)"
        ;;
esac
