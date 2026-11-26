COMPOSE   = docker compose
DC_FILE   = -f docker-compose.yaml
PROD_FILE = -f docker-compose.prod.yaml
DEV_FILE  = -f docker-compose.dev.yaml
TEST_FILE = -f docker-compose.test.yaml
UPSTREAM_DIR     := ./docker/nginx/upstreams
ACTIVE_FILE      := $(UPSTREAM_DIR)/active.conf
DEFAULT_UPSTREAM := server php-fpm-blue:9000;
COMPOSE_DEV = $(COMPOSE) $(DC_FILE) $(DEV_FILE)
COMPOSE_PROD = $(COMPOSE) $(DC_FILE) $(PROD_FILE)
COMPOSE_TEST = $(COMPOSE) $(TEST_FILE)
# Compose-файлы для JWT-проверки/генерации (prod-цели передают COMPOSE_PROD)
JWT_COMPOSE ?= $(COMPOSE_DEV)
# Активный blue/green из active.conf (можно переопределить: make deploy ACTIVE=green)
ACTIVE ?= $(shell grep -oE 'blue|green' $(ACTIVE_FILE) 2>/dev/null | head -n1 || echo blue)
STANDBY = $(if $(filter blue,$(ACTIVE)),green,blue)
define app_url
$(shell $(1) config 2>/dev/null | awk '/NGINX_HOST:/{h=$$2} /NGINX_PORT:/{p=$$2} END{print "http://" (h?h:"localhost") ":" (p?p:"8080")}')
endef

# Список команд
.PHONY: help setup check-env init-upstream reset-upstream \
        dev-init dev-setup dev-up dev-down dev-build dev-logs \
        prod-up prod-down prod-build prod-logs prod-create-admin \
        deploy shell-prod \
        shell-dev build \
        test test-init test-build test-prepare test-clean test-shell \
        jwt-check jwt-generate

# Описание команд
help:
	@echo ""
	@echo "                        ОПИСАНИЕ"
	@echo ""
	@echo "  Общие:"
	@echo "    make setup           - Создать .env, src/.env и active.conf (первый запуск)"
	@echo "    make build           - Пересобрать dev- и prod-образы (--no-cache)"
	@echo ""
	@echo "  Blue-green:"
	@echo "    make init-upstream   - Создать active.conf, если его нет (дефолт: blue)"
	@echo "    make reset-upstream  - Сбросить active.conf на дефолт (blue)"
	@echo ""
	@echo "  Dev-окружение:"
	@echo "	   make dev-init 		- Первый запуск приложения в dev"
	@echo "    make dev-setup 		- Инициализировать приложение для первого запуска в dev"
	@echo "    make dev-up          - Запустить dev-контейнеры (активный blue/green)"
	@echo "    make dev-down        - Остановить и удалить dev-контейнеры"
	@echo "    make dev-build       - Пересобрать dev-образы"
	@echo "    make dev-logs        - Показать логи dev (-f --tail=100)"
	@echo ""
	@echo "  Prod-окружение:"
	@echo "    make prod-up         - Запустить prod-контейнеры (активный blue/green)"
	@echo "    make prod-down       - Остановить и удалить prod-контейнеры"
	@echo "    make prod-build      - Пересобрать образ активного prod-контейнера"
	@echo "    make prod-create-admin - Создание администратора"
	@echo "    make prod-logs       - Показать логи prod (-f --tail=100)"
	@echo ""
	@echo "  Blue-green деплой:"
	@echo "    make deploy          - Собрать standby, применить миграции,"
	@echo "                           переключить nginx, остановить active"
	@echo ""
	@echo "  Artisan:"
	@echo "    make shell-prod      - Зайти в shell prod контейнера artisan"
	@echo "    make shell-dev       - Зайти в shell dev  контейнера artisan"
	@echo ""
	@echo "  Application testing:"
	@echo "    make test-init     	- Сборка и запуск тестов"
	@echo "    make test     		- Feature и Unit тесты"
	@echo "    make test-build     	- Сборка образа"
	@echo "    make test-shell  	- Shell в тестовом контейнере (быстрый перезапуск тестов)"
	@echo "	   make test-clean		- Завершение тестирования"
	@echo ""
	@echo "    make jwt-generate    - сгенерировать JWT_SECRET (спросит подтверждение, если уже задан)"
	@echo "	   make jwt-check   	- проверка токена"
