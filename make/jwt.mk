# JWT
jwt-check:
	@if [ ! -f src/.env ]; then \
		echo ">>> [jwt-check] src/.env отсутствует. Сначала выполните: make setup"; \
		exit 1; \
	fi
	@SECRET=$$(grep -E '^JWT_SECRET=' src/.env | tail -n1 | cut -d '=' -f2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$$//' -e "s/^'//" -e "s/'$$//"); \
	if [ -z "$$SECRET" ]; then \
		echo ">>> [jwt-check] JWT_SECRET не задан в src/.env. Выполните: make jwt-generate"; \
		exit 1; \
	fi; \
	echo ">>> [jwt-check] JWT_SECRET найден (длина: $${#SECRET} символов)."; \
	echo ">>> [jwt-check] Проверка токена: подпись и разбор (Laravel bootstrap)..."; \
	RESULT=$$($(JWT_COMPOSE) --progress quiet --profile tools run --rm --no-deps --entrypoint php artisan -r 'require "vendor/autoload.php"; $$app = require "bootstrap/app.php"; $$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap(); try { $$provider = $$app->make("tymon.jwt.provider.jwt"); $$token = $$provider->encode(["sub" => "jwt-check", "iat" => time(), "exp" => time() + 60]); $$payload = (array) $$provider->decode($$token); echo (($$payload["sub"] ?? null) === "jwt-check") ? "valid" : "invalid"; } catch (\Throwable $$e) { echo "invalid"; }' 2>/dev/null | tr -d '\r' | tail -n1); \
	if [ "$$RESULT" = "valid" ]; then \
		echo ">>> [jwt-check] Токен корректен: JWT_SECRET подписывает и проверяет токены."; \
	else \
		echo ">>> [jwt-check] ОШИБКА: не удалось подтвердить корректность JWT_SECRET (контейнеры запущены? make dev-up)."; \
		exit 1; \
	fi

jwt-generate:
	@echo ">>> [jwt] Проверка текущего JWT-секрета..."
	@if [ ! -f src/.env ]; then \
		echo ">>> [jwt] src/.env отсутствует. Сначала выполните: make setup"; \
		exit 1; \
	fi; \
	SECRET=$$(grep -E '^JWT_SECRET=' src/.env | tail -n1 | cut -d '=' -f2- | tr -d '\r' | sed -e 's/^"//' -e 's/"$$//' -e "s/^'//" -e "s/'$$//"); \
	if [ -n "$$SECRET" ]; then \
		if [ "$(FORCE)" = "1" ]; then \
			echo ">>> [jwt] FORCE=1: перегенерация существующего JWT_SECRET без подтверждения."; \
		else \
			echo ">>> [jwt] В src/.env уже задан JWT_SECRET."; \
			echo ">>> [jwt] Перегенерация сделает все ранее выданные токены недействительными."; \
			printf ">>> [jwt] Перегенерировать? [y/N]: "; \
			read -r ANSWER || ANSWER=""; \
			case "$$ANSWER" in \
				y|Y|yes|YES|Yes) echo ">>> [jwt] Подтверждено." ;; \
				*) echo ">>> [jwt] Отменено: JWT_SECRET не изменён."; exit 0 ;; \
			esac; \
		fi; \
	else \
		echo ">>> [jwt] JWT_SECRET не задан — генерируем новый без подтверждения."; \
	fi; \
	echo ">>> [jwt] Генерация JWT_SECRET..."; \
	$(COMPOSE_DEV) --progress quiet --profile tools run --rm artisan jwt:secret --force; \
	STATUS=$$?; \
	if [ $$STATUS -ne 0 ]; then \
		echo ">>> [jwt] ОШИБКА: не удалось сгенерировать JWT_SECRET."; \
		exit $$STATUS; \
	fi; \
	echo ">>> [jwt] JWT_SECRET записан в src/.env."; \
	echo ">>> [jwt] Внимание: ранее выпущенные токены станут недействительными."
