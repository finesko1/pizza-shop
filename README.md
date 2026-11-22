# Pizza Shop

Laravel-сервис пиццерии: REST API на JWT-аутентификации, PostgreSQL и nginx.
Всё окружение — Docker, управление — через Make. Для production реализован blue-green деплой
через `nginx upstream`.

**Стек:** PHP 8.4 · Laravel 13 · PostgreSQL 17 · nginx 1.30 · JWT (`tymon/jwt-auth`) · Docker Compose.

---

## Требования

- Docker + Docker Compose v2 (`docker compose version`);
- GNU Make (Linux/macOS, WSL или Git Bash на Windows);
- свободные порты **8080** (nginx) и **54321** (PostgreSQL на хосте).

PHP, Composer и база на хосте не нужны — всё внутри контейнеров.

---

## Быстрый старт (первый запуск)

```bash
git clone https://github.com/finesko1/pizza-shop pizza-shop
cd pizza-shop
make dev-init
```

`make dev-init` последовательно:

1. создаёт `.env`, `src/.env` и `docker/nginx/upstreams/active.conf` (если их нет);
2. ставит Composer-зависимости;
3. применяет миграции;
4. генерирует `JWT_SECRET`, если он ещё не задан;
5. поднимает активный слот `php-fpm-blue`, `nginx` и `db`.

Приложение: **http://127.0.0.1:8080**

Полезно знать:

- код из `src/` монтируется в контейнер — правки подхватываются без пересборки;
- база разработки хранится в `src/storage/database`, прямой доступ — `127.0.0.1:54321`;
- `APP_KEY` создаётся автоматически при старте контейнера;
- порт можно поменять в корневом `.env` (`NGINX_PORT`), тогда сайт будет на `http://127.0.0.1:<порт>`.

---

## Команды на каждый день

| Команда | Что делает |
|---|---|
| `make dev-up` | Запустить активный слот + nginx + db |
| `make dev-down` | Остановить и удалить dev-контейнеры |
| `make dev-logs` | Логи dev-контейнеров (`-f --tail=100`) |
| `make dev-build` | Пересобрать dev-образы |
| `make dev-init` | Полный первый запуск dev (setup + зависимости + миграции + JWT + запуск) |
| `make shell-dev` | Shell (`sh`) в контейнере с artisan: `php artisan ...` |
| `make help` | Список всех команд |

Внутри `make shell-dev` доступны привычные команды:

```bash
php artisan migrate
php artisan migrate:status
php artisan db:seed
php artisan tinker
```

---

## Тестирование

Тесты идут в отдельном compose-проекте **`pizza-shop-test`** с собственной БД
(`pizza_shop_test`, `tmpfs`) — dev-база не затрагивается.

```bash
make test-init    # первый раз: подготовка (composer, APP_KEY, миграции) + запуск тестов
make test         # обычный запуск всех тестов
make test-shell   # интерактивный shell для быстрых перезапусков
make test-clean   # удалить тестовый проект вместе с volume
```

Внутри `make test-shell`:

```bash
php artisan test
php artisan test --filter=ProductControllerTest
php artisan test --testsuite=Feature
php artisan test --stop-on-failure
```

Дополнительно:

| Команда | Что делает |
|---|---|
| `make test-prepare` | Только подготовка окружения тестов |
| `make test-build` | Пересобрать образ для тестов |

> Тестовое окружение задаётся в `docker-compose.test.yaml` (`DB_HOST=db-test`, `pizza_shop_test`),
> а PHPUnit-настройки — в `src/phpunit.xml`.

---

## JWT

Секрет хранится в `src/.env` (`JWT_SECRET`), время жизни — `JWT_TTL`, refresh — `JWT_REFRESH_TTL`.

```bash
make jwt-check      # .env существует, секрет задан, подпись/разбор токена работают
make jwt-generate   # сгенерировать новый JWT_SECRET
```

`make jwt-generate`:

- если текущий секрет корректен — спросит подтверждение `[y/N]`, по умолчанию отменит;
- если секрет пустой или нерабочий — сгенерирует без вопроса;
- `make jwt-generate FORCE=1` — перегенерировать без подтверждения.

После смены секрета **все ранее выданные токены становятся недействительными**.

`jwt-check` автоматически выполняется в `make dev-up`, `make prod-up` и `make deploy`.

---

## Как это устроено

- **dev** (`docker-compose.dev.yaml`): исходники монтируются, включены Xdebug и `APP_DEBUG`;
- **prod** (`docker-compose.prod.yaml`): multi-stage сборка, только production-зависимости;
- два одинаковых слота приложения: `php-fpm-blue` и `php-fpm-green`;
- активный слот определяется файлом `docker/nginx/upstreams/active.conf`
  (в git не хранится, создаётся автоматически со значением `blue`).

### Blue-green деплой

```bash
make deploy
```

Шаги:

1. сборка образов standby-слота и nginx;
2. проверка JWT-настроек;
3. миграции БД (`migrate --force`) до переключения трафика;
4. запуск standby и ожидание готовности;
5. переключение `active.conf` на standby;
6. запуск/проверка nginx, `nginx -t` и `nginx -s reload`;
7. остановка бывшего активного слота.

При ошибке на любом шаге деплой прерывается, трафик остаётся на текущем слоте.
Сбросить состояние на `blue`: `make reset-upstream`.

---

## Production

Перед запуском подготовьте `.env` и `src/.env` (в частности `DB_PASSWORD`),
`APP_KEY` сгенерируется при старте контейнера.

```bash
make prod-build   # собрать образы (нужно при первом запуске/обновлении кода)
make prod-up      # запустить активный слот + nginx + db
make prod-logs    # логи
make prod-down    # остановить и удалить prod-контейнеры
make deploy       # выкатить новую версию через standby
```

Миграции применяет `make deploy`. Для ручных artisan-команд:

```bash
make shell-prod
php artisan migrate:status
php artisan config:cache
```

> `make prod-up` намеренно требует подготовленные `.env` и `src/.env` —
> чтобы production не стартовал со значениями по умолчанию.

---

## API кратко

Публичные:

| Метод | Путь | Тело |
|---|---|---|
| `POST` | `/api/register` | `name`, `email`, `password`, `password_confirmation` |
| `POST` | `/api/login` | `email`, `password` |

Защищённые (`Authorization: Bearer <token>`):

| Метод | Путь |
|---|---|
| `GET` | `/api/auth/me` |
| `POST` | `/api/auth/logout` |
| `POST` | `/api/auth/refresh` |
| `GET/POST` | `/api/products` |
| `GET/PUT/DELETE` | `/api/products/{id}` |

Пример:

```bash
curl -X POST http://127.0.0.1:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'
```

Ответ содержит `access_token`, `token_type`, `expires_in` и данные пользователя.

---

## Переменные окружения

### Корневой `.env` (Docker Compose)

| Переменная | По умолчанию | Назначение |
|---|---|---|
| `NGINX_HOST` | `127.0.0.1` | хост в выводимом URL |
| `NGINX_PORT` | `8080` | порт nginx на хосте |
| `PHP_VERSION` | `8.4` | версия PHP в образе |
| `NGINX_VERSION` | `1.30` | версия nginx |
| `POSTGRES_VERSION` | `17` | версия PostgreSQL |
| `DB_PORT` | `54321` | порт PostgreSQL на хосте (dev) |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | `laravel`, `root`, `currentPassword` | доступ к БД |

### `src/.env` (Laravel)

| Переменная | Значение для compose | Назначение |
|---|---|---|
| `APP_ENV` | `local` / `production` | окружение |
| `DB_HOST` | `db` | имя сервиса БД в сети Docker |
| `DB_PORT` | `5432` | порт БД внутри сети |
| `DB_DATABASE` | `laravel` | имя БД |
| `JWT_SECRET` | генерируется | подпись JWT |
| `JWT_TTL` | `60` | время жизни access-токена, мин |
| `JWT_REFRESH_TTL` | `20160` | время жизни refresh-токена, мин |

---

## Структура проекта

```text
.
├── Makefile                  # единая точка входа, подключает make/*.mk
├── make/                     # модули Makefile
│   ├── common.mk             # переменные, help, .PHONY
│   ├── setup.mk              # .env, active.conf, check-env
│   ├── dev.mk                # dev-команды
│   ├── prod.mk               # prod-команды и deploy
│   ├── artisan.mk            # shell-dev / shell-prod
│   ├── test.mk               # тесты
│   ├── jwt.mk                # jwt-check / jwt-generate
│   └── misc.mk               # build
├── docker-compose.yaml       # база: php-fpm-blue/green, nginx, db
├── docker-compose.dev.yaml   # dev: mount src/, Xdebug
├── docker-compose.prod.yaml  # prod: multi-stage build
├── docker-compose.test.yaml  # изолированный тестовый проект
├── docker/
│   ├── php/                  # Dockerfile, entrypoint, php.ini, php-fpm pools
│   └── nginx/                # конфиг и upstreams/active.conf
├── .env.example              # переменные compose
└── src/                      # Laravel
    ├── app/                  # контроллеры, модели, requests, resources
    ├── routes/api.php        # API-маршруты
    ├── tests/
    ├── .env.example          # переменные Laravel
    └── storage/database/     # данные dev-БД (bind mount)
```

---

## Если что-то не работает

- **Порт занят** — измените `NGINX_PORT`/`DB_PORT` в корневом `.env` и выполните `make dev-up`.
- **`jwt-check` падает** — выполните `make jwt-generate`; проверьте, что контейнеры запущены (`make dev-up`).
- **Нужны логи** — `make dev-logs` или `make prod-logs`.
- **Сбросить тесты** — `make test-clean`, затем снова `make test-init`.
- **Полная пересборка образов** — `make build` (dev и prod, `--no-cache`, занимает время).

Больше команд — `make help`.
