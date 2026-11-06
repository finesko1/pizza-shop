COMPOSE   = docker compose
DC_FILE   = -f docker-compose.yaml
DEV_FILE  = -f docker-compose.dev.yaml
PROD_FILE = -f docker-compose.prod.yaml
UPSTREAM_DIR     := ./docker/nginx/upstreams
ACTIVE_FILE      := $(UPSTREAM_DIR)/active.conf
DEFAULT_UPSTREAM := server php-fpm-blue:9000;
COMPOSE_DEV = $(COMPOSE) $(DC_FILE) $(DEV_FILE)
COMPOSE_PROD = $(COMPOSE) $(DC_FILE) $(PROD_FILE)

# Список команд
.PHONY: help setup init-upstream reset-upstream \
        dev-up dev-down dev-build dev-logs \
        prod-up prod-down prod-build prod-logs \
        deploy shell-prod migrate-prod rollback-prod art-prod \
        build

# Описание команд
help:
	@echo ""
	@echo "                        ОПИСАНИЕ"
	@echo ""
	@echo "  Общие:"
	@echo "    make setup           - Создать .env, src/.env и active.conf (первый запуск)"
	@echo "    make build           - Пересобрать все образы (--no-cache)"
	@echo ""
	@echo "  Blue-green:"
	@echo "    make init-upstream   - Создать active.conf, если его нет (дефолт: blue)"
	@echo "    make reset-upstream  - Сбросить active.conf на дефолт (blue)"
	@echo ""
	@echo "  Dev-окружение:"
	@echo "    make dev-up          - Запустить dev-контейнеры (активный blue/green)"
	@echo "    make dev-down        - Остановить и удалить dev-контейнеры"
	@echo "    make dev-build       - Пересобрать dev-образы"
	@echo "    make dev-logs        - Показать логи dev (-f --tail=100)"
	@echo ""
	@echo "  Prod-окружение:"
	@echo "    make prod-up         - Запустить prod-контейнеры (активный blue/green)"
	@echo "    make prod-down       - Остановить и удалить prod-контейнеры"
	@echo "    make prod-build      - Пересобрать образ активного prod-контейнера"
	@echo "    make prod-logs       - Показать логи prod (-f --tail=100)"
	@echo ""
	@echo "  Blue-green деплой:"
	@echo "    make deploy          - Собрать standby, применить миграции,"
	@echo "                           переключить nginx, остановить active"
	@echo ""
	@echo "  Artisan for production:"
	@echo "    make migrate-prod    - Выполнить migrate --force"
	@echo "    make rollback-prod   - Выполнить migrate:rollback --force"
	@echo "    make shell-prod      - Зайти в shell контейнера artisan"
	@echo "    make art-prod <cmd>  - Произвольная artisan-команда, например:"
	@echo "                           make art-prod migrate:status"

# Setup
setup:
	@echo ">>> [setup] Настройка локального окружения..."
	@$(MAKE) --no-print-directory init-upstream
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ">>> [setup] Создан .env из .env.example"; \
	else \
		echo ">>> [setup] .env уже существует"; \
	fi
	@if [ ! -f src/.env ]; then \
		cp src/.env.example src/.env; \
		echo ">>> [setup] Создан src/.env из src/.env.example"; \
	else \
		echo ">>> [setup] src/.env уже существует"; \
	fi
	@echo ">>> [setup] Готово. Проверьте .env и src/.env (DB_HOST=db для compose, DB_HOST=127.0.0.1 для project)."

init-upstream:
	@mkdir -p $(UPSTREAM_DIR)
	@if [ ! -f $(ACTIVE_FILE) ]; then \
		echo ">>> [init] $(ACTIVE_FILE) не найден, создаётся дефолт: blue"; \
		echo "$(DEFAULT_UPSTREAM)" > $(ACTIVE_FILE); \
	else \
		echo ">>> [init] $(ACTIVE_FILE): $$(cat $(ACTIVE_FILE))"; \
	fi

reset-upstream:
	@mkdir -p $(UPSTREAM_DIR)
	@echo ">>> [init] Сброс $(ACTIVE_FILE) на дефолт: blue"
	@echo "$(DEFAULT_UPSTREAM)" > $(ACTIVE_FILE)
	@cat $(ACTIVE_FILE)

# Production
prod-up: init-upstream
	@if [ ! -f .env ]; then echo ">>> [prod] .env не найден. Создайте его из .env.example и заполните секреты."; exit 1; fi
	@if [ ! -f src/.env ]; then echo ">>> [prod] src/.env не найден."; exit 1; fi
	@ACTIVE=$$(grep -oE 'blue|green' $(UPSTREAM_DIR)/active.conf 2>/dev/null | head -n1 || echo blue); \
	echo ">>> [prod] Активный контейнер: $$ACTIVE"; \
	echo ">>> [prod] Запускается: php-fpm-$$ACTIVE + зависимости..."; \
	$(COMPOSE_PROD) --progress quiet --profile $$ACTIVE up -d; \
	echo ">>> [prod] Запуск завершён."
	echo ">>> [prod] Приложение доступно: http://$${NGINX_HOST:-localhost}:$${NGINX_PORT:-8080}"

prod-build: init-upstream
	@ACTIVE=$$(grep -oE 'blue|green' $(UPSTREAM_DIR)/active.conf 2>/dev/null | head -n1 || echo blue); \
	echo ">>> [prod] Активный контейнер: $$ACTIVE"; \
	echo ">>> [prod] Собирается образ: php-fpm-$$ACTIVE..."; \
	$(COMPOSE_PROD) --progress quiet --profile $$ACTIVE build php-fpm-$$ACTIVE; \
	echo ">>> [prod] Образ php-fpm-$$ACTIVE собран."

prod-down:
	@echo ">>> [prod] Останавливаются и удаляются все prod-контейнеры (включая профили)..."
	@$(COMPOSE_PROD) --progress quiet --profile "*" down
	@echo ">>> [prod] Остановка завершена."

prod-logs:
	@echo ">>> [prod] Логи prod (follow, tail=100). Ctrl+C для выхода."
	$(COMPOSE_PROD) logs -f --tail=100

# Development
dev-up: setup
	@ACTIVE=$$(grep -oE 'blue|green' $(UPSTREAM_DIR)/active.conf 2>/dev/null | head -n1 || echo blue); \
	echo ">>> [dev] Активный контейнер: $$ACTIVE"; \
	echo ">>> [dev] Запускается: php-fpm-$$ACTIVE + зависимости..."; \
	$(COMPOSE_DEV) --progress quiet --profile $$ACTIVE up -d; \
	echo ">>> [dev] Запуск завершён."
	echo ">>> [dev] Приложение доступно: http://$${NGINX_HOST:-localhost}:$${NGINX_PORT:-8080}"

dev-build:
	@echo ">>> [dev] Собираются dev-образы..."
	$(COMPOSE_DEV) --progress quiet build
	@echo ">>> [dev] Сборка завершена."

dev-down:
	@echo ">>> [dev] Останавливаются и удаляются все dev-контейнеры (включая профили)..."
	$(COMPOSE_DEV) --progress quiet --profile "*" down
	@echo ">>> [dev] Остановка завершена."

dev-logs:
	@echo ">>> [dev] Логи dev (follow, tail=100). Ctrl+C для выхода."
	$(COMPOSE_DEV) logs -f --tail=100

# Blue-green deploy
deploy: init-upstream
	@if [ ! -f .env ]; then echo ">>> [prod] .env не найден. Создайте его из .env.example и заполните секреты."; exit 1; fi
	@if [ ! -f src/.env ]; then echo ">>> [prod] src/.env не найден."; exit 1; fi
	@ACTIVE=$$(grep -oE 'blue|green' $(UPSTREAM_DIR)/active.conf 2>/dev/null | head -n1 || true); \
	ACTIVE=$${ACTIVE:-blue}; \
	STANDBY=$$([ "$$ACTIVE" = "blue" ] && echo green || echo blue); \
	echo ">>> [deploy] Активный контейнер: $$ACTIVE"; \
	echo ">>> [deploy] Целевой (standby) контейнер: $$STANDBY"; \
	echo ">>> [deploy] 1/7 Сборка образа: php-fpm-$$STANDBY..."; \
	$(COMPOSE_PROD) --progress quiet --profile $$STANDBY build php-fpm-$$STANDBY || exit 1; \
	echo ">>> [deploy] 2/7 Миграции БД: artisan migrate --force..."; \
	$(COMPOSE_PROD) --progress quiet --profile tools run --rm artisan migrate --force || exit 1; \
	echo ">>> [deploy] 3/7 Запуск php-fpm-$$STANDBY с ожиданием healthy..."; \
	$(COMPOSE_PROD) --progress quiet --profile $$STANDBY up -d --wait php-fpm-$$STANDBY || exit 1; \
	echo ">>> [deploy] 4/7 Переключение upstream на php-fpm-$$STANDBY..."; \
	echo "server php-fpm-$$STANDBY:9000;" > $(UPSTREAM_DIR)/active.conf; \
	echo ">>> [deploy] 5/7 Запуск/проверка nginx..."; \
	$(COMPOSE_PROD) --progress quiet up -d --wait nginx || exit 1; \
	echo ">>> [deploy] 6/7 Проверка конфига и reload nginx..."; \
	$(COMPOSE_PROD) --progress quiet exec -T nginx nginx -t || exit 1; \
	$(COMPOSE_PROD) --progress quiet exec -T nginx nginx -s reload || exit 1; \
	echo ">>> [deploy] 7/7 Остановка php-fpm-$$ACTIVE..."; \
	$(COMPOSE_PROD) --progress quiet --profile $$ACTIVE stop php-fpm-$$ACTIVE || exit 1; \
	echo ">>> [deploy] Переключено на $$STANDBY. Деплой завершён."

# Artisan for production
migrate-prod:
	@echo ">>> [artisan] Применяются миграции: migrate --force..."
	$(COMPOSE_PROD) --progress quiet --profile tools run --rm artisan migrate --force
	@echo ">>> [artisan] Миграции применены."

rollback-prod:
	@echo ">>> [artisan] Откат миграций: migrate:rollback --force..."
	$(COMPOSE_PROD) --progress quiet --profile tools run --rm artisan migrate:rollback --force
	@echo ">>> [artisan] Откат выполнен."

shell-prod:
	@echo ">>> [artisan] Открывается shell в контейнере artisan (exit для выхода)..."
	$(COMPOSE_PROD) --profile tools run --rm --entrypoint sh artisan

# Произвольная artisan-команда
art-prod:
	@echo ">>> [artisan] Выполняется: $(filter-out $@,$(MAKECMDGOALS))"
	@$(COMPOSE_PROD) --progress quiet --profile tools run --rm artisan $(filter-out $@,$(MAKECMDGOALS))

# Общее
build:
	@echo ">>> Сборка всех образов (--no-cache)..."
	$(COMPOSE) --progress quiet $(DC_FILE) build --no-cache
	@echo ">>> Сборка завершена."

# Поглотитель для произвольных аргументов art-prod
%:
	@: