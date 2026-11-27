# Pizza Shop — единая точка входа.
#
#   make/common.mk    — переменные, help, .PHONY
#   make/setup.mk     — .env и active.conf
#   make/dev.mk       — dev-окружение
#   make/prod.mk      — prod-окружение и blue-green deploy
#   make/artisan.mk   — shell-доступ (dev/prod/test)
#   make/test.mk      — тесты
#   make/jwt.mk       — JWT-секрет и проверка
#   make/misc.mk      — build
#   make/cs-fixer.mk  — PHP CS Fixer
#   make/phpstan.mk   — PHPStan + Larastan
#   make/rector.mk    — Rector

MAKE_DIR := make

include $(MAKE_DIR)/common.mk
include $(MAKE_DIR)/setup.mk
include $(MAKE_DIR)/dev.mk
include $(MAKE_DIR)/prod.mk
include $(MAKE_DIR)/artisan.mk
include $(MAKE_DIR)/test.mk
include $(MAKE_DIR)/jwt.mk
include $(MAKE_DIR)/misc.mk
include $(MAKE_DIR)/cs-fixer.mk
include $(MAKE_DIR)/phpstan.mk
include $(MAKE_DIR)/rector.mk
