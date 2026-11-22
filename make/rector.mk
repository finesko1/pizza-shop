# Rector (конфиг — src/rector.php, скрипты — src/composer.json)
rector:
	@echo ">>> [rector] Проверка рефакторинга (dry-run)..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan rector

rector-fix:
	@echo ">>> [rector] Применение рефакторинга..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan rector:fix
