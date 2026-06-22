.PHONY: parse help

help: ## Affiche les cibles disponibles
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

parse: ## Parse les factures (data/*) via app:parse
	docker-compose run --rm app php bin/console app:parse
