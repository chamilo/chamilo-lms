.PHONY: test test-all install update clean dev bower load assets

cs:
	./vendor/bin/php-cs-fixer fix --verbose

cs_dry_run:
	./vendor/bin/php-cs-fixer fix --verbose --dry-run

test:
	phpunit
	cd docs && sphinx-build -W -b html -d _build/doctrees . _build/html
