.PHONY: parse help coverage coverage-check

# Seuil minimal de couverture de lignes (surchargeable : make coverage-check COVERAGE_MIN=70)
COVERAGE_MIN ?= 75

help: ## Affiche les cibles disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2}'

parse: ## Parse les factures (data/*) via app:parse
	docker-compose run --rm app php -d memory_limit=1024M bin/console app:parse

coverage: ## Génère le rapport de couverture (texte + HTML dans var/coverage)
	docker-compose run --rm app php vendor/bin/phpunit --coverage-text --coverage-html var/coverage --coverage-clover var/coverage/clover.xml

coverage-check: ## Échoue si la couverture de lignes < COVERAGE_MIN
	docker-compose run --rm app sh -c "php vendor/bin/phpunit --coverage-clover var/coverage/clover.xml && php ci/coverage-check.php var/coverage/clover.xml $(COVERAGE_MIN)"

test:
	docker-compose exec app php vendor/bin/phpunit