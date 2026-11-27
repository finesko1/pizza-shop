# PHP CS Fixer (конфиг — src/.php-cs-fixer.dist.php, скрипты — src/composer.json)
cs:
	@echo ">>> [cs] Проверка стиля кода..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan cs

cs-fix:
	@echo ">>> [cs] Исправление стиля кода..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan cs:fix
