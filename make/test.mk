# Testing
test-prepare:
	@echo ">>> [test] 1/5 Проверка .env..."
	@if [ ! -f src/.env ]; then \
		cp src/.env.example src/.env; \
		echo ">>> [test] src/.env создан из .env.example"; \
	fi
	@echo ">>> [test] 2/5 Сборка образа artisan (кэш)..."
	@$(COMPOSE_TEST) build artisan
	@echo ">>> [test] 3/5 composer install (no-op при наличии vendor)..."
	@$(COMPOSE_TEST) run --rm --entrypoint composer artisan install \
		--no-interaction --prefer-dist --no-progress
	@echo ">>> [test] 4/5 Проверка APP_KEY..."
	@if ! grep -qE '^APP_KEY=base64:.+' src/.env; then \
		$(COMPOSE_TEST) run --rm artisan key:generate --force --no-interaction; \
		echo ">>> [test] APP_KEY сгенерирован"; \
	fi
	@echo ">>> [test] 5/5 Миграции..."
	@$(COMPOSE_TEST) run --rm artisan migrate --force --no-interaction
	@echo ">>> [test] Подготовка завершена."

test-init: test-prepare
	@$(MAKE) --no-print-directory test

test-build:
	$(COMPOSE_TEST) build artisan

test:
	$(COMPOSE_TEST) run --rm artisan test

test-clean:
	$(COMPOSE_TEST) down -v
