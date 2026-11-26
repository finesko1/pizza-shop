# Production
prod-up: check-env init-upstream
	@echo ">>> [prod] Проверка JWT-настроек..."
	@$(MAKE) --no-print-directory jwt-check JWT_COMPOSE='$(COMPOSE_PROD)' || (echo ">>> [prod] JWT_SECRET отсутствует или некорректен. Проверьте src/.env (make jwt-generate)."; exit 1)
	@echo ">>> [prod] Активный контейнер: $(ACTIVE)"
	@echo ">>> [prod] Запускается: php-fpm-$(ACTIVE) + зависимости..."
	@$(COMPOSE_PROD) --progress quiet --profile $(ACTIVE) up -d
	@echo ">>> [prod] Запуск завершён."
	@echo ">>> [prod] Приложение доступно: $(call app_url,$(COMPOSE_PROD))"

prod-build: init-upstream
	@echo ">>> [prod] Активный контейнер: $(ACTIVE)"
	@echo ">>> [prod] Собирается образ: php-fpm-$(ACTIVE)..."
	@$(COMPOSE_PROD) --progress quiet --profile $(ACTIVE) build php-fpm-$(ACTIVE)
	@echo ">>> [prod] Собирается образ nginx (со свежим public)..."
	@$(COMPOSE_PROD) --progress quiet build nginx
	@echo ">>> [prod] Образы php-fpm-$(ACTIVE) и nginx собраны."

prod-down:
	@echo ">>> [prod] Останавливаются и удаляются все prod-контейнеры (включая профили)..."
	@$(COMPOSE_PROD) --progress quiet --profile "*" down
	@echo ">>> [prod] Остановка завершена."

prod-logs:
	@echo ">>> [prod] Логи prod (follow, tail=100). Ctrl+C для выхода."
	$(COMPOSE_PROD) logs -f --tail=100

prod-create-admin:
	@echo ">>> [prod] Создание администратора..."
	@$(COMPOSE_PROD) --profile tools run --rm artisan app:create-admin

# Blue-green deploy
deploy: check-env init-upstream
	@echo ">>> [deploy] Активный контейнер: $(ACTIVE)"
	@echo ">>> [deploy] Целевой (standby) контейнер: $(STANDBY)"
	@echo ">>> [deploy] 1/8 Сборка образов: php-fpm-$(STANDBY) + nginx..."
	@$(COMPOSE_PROD) --progress quiet --profile $(STANDBY) build php-fpm-$(STANDBY)
	@$(COMPOSE_PROD) --progress quiet build nginx
	@echo ">>> [deploy] 2/8 Проверка JWT-настроек..."
	@$(MAKE) --no-print-directory jwt-check JWT_COMPOSE='$(COMPOSE_PROD)'
	@echo ">>> [deploy] 3/8 Миграции БД: artisan migrate --force..."
	@$(COMPOSE_PROD) --progress quiet --profile tools run --rm artisan migrate --force
	@echo ">>> [deploy] 4/8 Запуск php-fpm-$(STANDBY) с ожиданием healthy..."
	@$(COMPOSE_PROD) --progress quiet --profile $(STANDBY) up -d --wait php-fpm-$(STANDBY)
	@echo ">>> [deploy] 5/8 Переключение upstream на php-fpm-$(STANDBY)..."
	@echo "server php-fpm-$(STANDBY):9000;" > $(ACTIVE_FILE)
	@echo ">>> [deploy] 6/8 Запуск/проверка nginx..."
	@$(COMPOSE_PROD) --progress quiet up -d --wait nginx
	@echo ">>> [deploy] 7/8 Проверка конфига и reload nginx..."
	@$(COMPOSE_PROD) --progress quiet exec -T nginx nginx -t
	@$(COMPOSE_PROD) --progress quiet exec -T nginx nginx -s reload
	@echo ">>> [deploy] 8/8 Остановка php-fpm-$(ACTIVE)..."
	@$(COMPOSE_PROD) --progress quiet --profile $(ACTIVE) stop php-fpm-$(ACTIVE)
	@echo ">>> [deploy] Переключено на $(STANDBY). Деплой завершён."
