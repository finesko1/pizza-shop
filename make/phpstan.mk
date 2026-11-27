# PHPStan + Larastan (конфиг — src/phpstan.neon.dist)
stan:
	@echo ">>> [stan] Статический анализ..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan stan

stan-baseline:
	@echo ">>> [stan] Генерация baseline..."
	$(COMPOSE_DEV) --profile tools run --rm --no-deps --entrypoint composer artisan stan:baseline