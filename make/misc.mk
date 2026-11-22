# Общее
build:
	@echo ">>> Сборка dev-образов (--no-cache)..."
	$(COMPOSE_DEV) --progress quiet --profile "*" build --no-cache
	@echo ">>> Сборка prod-образов (--no-cache)..."
	$(COMPOSE_PROD) --progress quiet --profile "*" build --no-cache php-fpm-blue php-fpm-green
	$(COMPOSE_PROD) --progress quiet --profile "*" build --no-cache nginx
	@echo ">>> Сборка завершена."
