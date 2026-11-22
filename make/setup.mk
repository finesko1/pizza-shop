# Setup
setup: init-upstream
	@echo ">>> [setup] Настройка локального окружения..."
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

# Проверка обязательных env-файлов (prod)
check-env:
	@if [ ! -f .env ]; then echo ">>> [prod] .env не найден. Создайте его из .env.example и заполните секреты."; exit 1; fi
	@if [ ! -f src/.env ]; then echo ">>> [prod] src/.env не найден."; exit 1; fi

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
