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
| `make cs` / `make cs-fix` | Проверить / исправить стиль кода (PHP CS Fixer) |
| `make stan` | Статический анализ (PHPStan + Larastan) |
| `make rector` / `make rector-fix` | Проверить / применить рефакторинг (Rector) |
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
make shell-test   # интерактивный shell для быстрых перезапусков
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

## Стиль кода

Код проверяется **PHP CS Fixer** со стандартом **PHPyhCodingStandard**
(конфиг — `src/.php-cs-fixer.dist.php`). Команды выполняются в dev-контейнере:

```bash
make cs       # проверить стиль (ничего не меняет)
make cs-fix   # автоматически исправить
```

Скрипты продублированы в `src/composer.json` (`composer cs` / `composer cs:fix`),
поэтому их можно запускать и напрямую на хосте, если установлены PHP и Composer.

---

## Статический анализ

Код проверяется **PHPStan** (level 6) вместе с **Larastan** — расширением,
которое объясняет PHPStan магию Laravel (Eloquent-модели, фасады, контейнер,
`FormRequest`, ресурсы). Конфиг — `src/phpstan.neon.dist`.

```bash
make stan       # запустить анализ
make stan-baseline  # зафиксировать текущие ошибки в baseline
```

Анализируются `app`, `bootstrap`, `database`, `routes` и `tests`. Larastan
поднимает приложение, поэтому нужен `src/.env` с `APP_KEY`, но **база данных
не требуется** — миграции читаются с диска.

Полезные параметры конфига:

| Параметр | Зачем |
|---|---|
| `checkModelProperties: true` | проверять ключи, передаваемые в `create()`/`update()` |
| `parseModelCastsMethod: true` | выводить типы из тела `casts()` (enum-касты и т.п.) |
| `tmpDir` | кэш PHPStan в `var/phpstan` |

Скрипты продублированы в `src/composer.json` (`composer stan` / `composer stan:baseline`).

Кеши всех quality-инструментов (CS Fixer, PHPStan, Rector, PHPUnit) лежат в `src/var/`
и не попадают в Git и Docker-контекст.

### Baseline

Сейчас проект проходит анализ **без ошибок**, поэтому `phpstan-baseline.neon`
не нужен. Если появятся ошибки, которые нельзя исправить сразу (например,
из-за магии сторонней библиотеки), можно зафиксировать их:

```bash
make stan-baseline
```

Команда создаст `src/phpstan-baseline.neon`; его нужно закоммитить и подключить
в `src/phpstan.neon.dist`:

```neon
includes:
    - phpstan-baseline.neon
    - vendor/larastan/larastan/extension.neon
```

Baseline скрывает только уже известные ошибки: новый код проверяется полностью,
а исправленные записи помечаются как лишние — их следует удалять.

---

## Автоматический рефакторинг

Для массовых изменений кода используется **Rector** (`src/rector.php`).
Сейчас подключено одно правило — **AddNamedArgumentsRector**: оно расставляет
именованные аргументы там, где это безопасно.

```bash
make rector       # dry-run: показать дифф, файлы не меняет
make rector-fix   # применить изменения
```

Правило не трогает `vendor`, вызовы с распаковкой (`...$args`), методы,
которые не удаётся разрешить через рефлексию (магия Eloquent/фасадов),
и классы/методы с `@no-named-arguments`. Синтетические имена для variadic
(`values1`, `values2`) отключены.

Рекомендуемый порядок после рефакторинга:

```bash
make rector-fix
make cs-fix
make stan
make test
```

> **Важно:** named arguments зависят от имён параметров вызываемого кода.
> Перед применением просмотрите `make rector`, а нестабильные вызовы сторонних
> пакетов добавьте в `SKIP_CALLS` в `src/rector.php`. Первый запуск намеренно
> сделан в режиме dry-run.

Скрипты продублированы в `src/composer.json` (`composer rector` / `composer rector:fix`).

---

## CI (GitLab)

Pipeline описан в `.gitlab-ci.yml` и запускается на **каждый push и merge request**.

| Stage | Job | Что делает |
|---|---|---|
| build | `build:prod` | собирает production-образ из `docker/php/Dockerfile` (target `production`) |
| build | `build:ci` | собирает development-образ для проверок (Composer + PHP-расширения) |
| code-quality | `test` | полный PHPUnit suite на PostgreSQL |
| code-quality | `php-cs-fixer` | `composer cs` (check) |
| code-quality | `phpstan` | `composer stan` (level 6 + Larastan) |
| code-quality | `rector` | `composer rector` (dry-run) |

Все quality-jobs независимы: результат каждой проверки виден в одном pipeline,
падение любой делает pipeline красным. Для merge-request включите
**Settings → Merge requests → Merge checks → Pipelines must succeed**.

Требования к окружению GitLab: включённый **Container Registry** и runner
с поддержкой Docker-in-Docker (privileged). Секреты в `.env` не коммитятся:
CI создаёт `src/.env` из `src/.env.example` и генерирует `APP_KEY`.

Результат первого прогона предоставлен ниже

---

## JWT

Секрет хранится в `src/.env` (`JWT_SECRET`), время жизни — `JWT_TTL`, refresh — `JWT_REFRESH_TTL`.

```bash
make jwt-check      # .env существует, секрет задан, подпись/разбор токена работают
make jwt-generate   # сгенерировать новый JWT_SECRET
```

`make jwt-generate`:

- если `JWT_SECRET` уже задан — спросит подтверждение `[y/N]`, по умолчанию отменит;
- если `JWT_SECRET` пустой — сгенерирует без вопроса;
- `make jwt-generate FORCE=1` — перегенерировать без подтверждения.

Подтверждение требуется и тогда, когда `jwt-check` не смог проверить секрет (например, контейнеры не запущены): наличие секрета определяется по `src/.env`, а не по результату проверки.

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

| Метод | Путь                 | Тело |
|---|----------------------|---|
| `POST` | `/api/auth/register` | `name`, `email`, `password`, `password_confirmation` |
| `POST` | `/api/auth/login`    | `email`, `password` |

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
│   ├── misc.mk               # build
│   ├── cs-fixer.mk           # make cs / cs-fix
│   ├── phpstan.mk            # make stan / stan-baseline
│   └── rector.mk             # make rector / rector-fix
├── .gitlab-ci.yml            # CI: build + test + quality-проверки
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
    ├── .php-cs-fixer.dist.php # конфиг PHP CS Fixer
    ├── phpstan.neon.dist     # конфиг PHPStan + Larastan
    ├── rector.php            # конфиг Rector
    ├── .env.example          # переменные Laravel
    └── storage/database/     # данные dev-БД (bind mount)
```

---

## Если что-то не работает

- **Порт занят** — измените `NGINX_PORT`/`DB_PORT` в корневом `.env` и выполните `make dev-up`.
- **`jwt-check` падает** — проверьте, что контейнеры запущены (`make dev-up`). Перегенерация: `make jwt-generate` (спросит подтверждение) или `make jwt-generate FORCE=1`.
- **Нужны логи** — `make dev-logs` или `make prod-logs`.
- **Сбросить тесты** — `make test-clean`, затем снова `make test-init`.
- **Полная пересборка образов** — `make build` (dev и prod, `--no-cache`, занимает время).

Больше команд — `make help`.
