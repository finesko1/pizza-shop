# Artisan for production
shell-prod:
	@echo ">>> [artisan] Открывается shell в контейнере artisan (exit для выхода)..."
	$(COMPOSE_PROD) --profile tools run --rm --entrypoint sh artisan

# Artisan for development
shell-dev:
	@echo ">>> [artisan] Открывается shell в контейнере artisan (exit для выхода)..."
	$(COMPOSE_DEV) --profile tools run --rm --entrypoint sh artisan

# Интерактивный shell: php artisan test / php artisan test --filter=TestName
shell-test:
	$(COMPOSE_TEST) run --rm --entrypoint sh artisan