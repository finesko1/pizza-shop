# Development
dev-setup: setup
	@set -e; \
	echo ">>> [dev-setup] Установка Composer-зависимостей..."; \
	$(COMPOSE_DEV) --profile tools run --rm --entrypoint composer artisan install --no-interaction --prefer-dist; \
	echo ">>> [dev-setup] Выполнение миграций Laravel..."; \
	$(COMPOSE_DEV) --profile tools run --rm artisan migrate --force; \
	echo ">>> [dev-setup] Первоначальная настройка завершена."

dev-up: setup
	@echo ">>> [dev] Проверка JWT-настроек..."
	@$(MAKE) --no-print-directory jwt-check || (echo ">>> [dev] JWT_SECRET отсутствует или некорректен. Выполните: make jwt-generate"; exit 1)
	@echo ">>> [dev] Активный контейнер: $(ACTIVE)"
	@echo ">>> [dev] 1/2 Запуск php-fpm-$(ACTIVE) и зависимостей..."
	@$(COMPOSE_DEV) --progress quiet --profile $(ACTIVE) up -d php-fpm-$(ACTIVE)
	@echo ">>> [dev] 2/2 Запуск nginx..."
	@$(COMPOSE_DEV) --progress quiet up -d nginx
	@echo ">>> [dev] Запуск завершён."
	@echo ">>> [dev] Приложение доступно: $(call app_url,$(COMPOSE_DEV))"

dev-init: dev-setup
	@if ! grep -qE '^JWT_SECRET=.+' src/.env; then \
		echo ">>> [dev-init] JWT_SECRET не задан — генерируется..."; \
		$(MAKE) --no-print-directory jwt-generate; \
	fi
	@$(MAKE) --no-print-directory dev-up
	@echo ">>> [dev-init] Готово. Приложение запущено."

dev-build:
	@echo ">>> [dev] Активный контейнер: $(ACTIVE)"
	@echo ">>> [dev] Собираются dev-образы..."
	@$(COMPOSE_DEV) --progress quiet --profile $(ACTIVE) build
	@echo ">>> [dev] Сборка завершена."

dev-down:
	@echo ">>> [dev] Останавливаются и удаляются все dev-контейнеры (включая профили)..."
	$(COMPOSE_DEV) --progress quiet --profile "*" down
	@echo ">>> [dev] Остановка завершена."

dev-logs:
	@echo ">>> [dev] Логи dev (follow, tail=100). Ctrl+C для выхода."
	$(COMPOSE_DEV) logs -f --tail=100
