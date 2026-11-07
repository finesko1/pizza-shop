# Pizza Shop

Laravel-приложение с blue-green деплоем через nginx upstream. Всё окружение — Docker: dev для локальной разработки, 
prod для production-сборки. Управление — через Makefile.

# Быстрый старт
```bash
# 1. Клонировать
git clone https://github.com/finesko1/pizza-shop pizza-shop
cd pizza-shop

# 2. Установить зависимости для первого запуска
make dev-setup

# 3. Запустить приложение
# Приложение доступно по адресу из вывода, по умолчанию: http://localhost:8080
make dev-up
```

**Важно**:
Команда `make prod-up` требует подготовленных `.env/src/` и `.env` — это сделано намеренно, чтобы prod не запускался с дефолтным DB_PASSWORD=secret.

### Структура
```text
.
├── Makefile                     # все команды (см. таблицу ниже)
├── docker-compose.yaml          # база: php-fpm-blue/green, nginx, db
├── docker-compose.dev.yaml      # dev: mount src/, APP_DEBUG=true, xdebug
├── docker-compose.prod.yaml     # prod: multi-stage build, artisan tools
├── .env.example                 # переменные compose (PHP_VERSION, NGINX_PORT, DB_*)
├── docker/
│   ├── php/
│   │   ├── Dockerfile           # multi-stage: base / development / builder / production
│   │   ├── entrypoint.sh        # config:cache в runtime (prod)
│   │   ├── conf.d/              # php.ini, opcache, xdebug
│   │   └── php-fpm.d/           # pool-конфиги dev/prod
│   └── nginx/
│       ├── default.conf.template
│       └── upstreams/
│           ├── blue.conf        # server php-fpm-blue:9000;
│           ├── green.conf       # server php-fpm-green:9000;
│           └── active.conf      # генерируется, НЕ в git
└── src/                         # Laravel
├── .env.example             
└── .gitignore
```

### Blue-green development for production
В проде два одинаковых слота: php-fpm-blue и php-fpm-green. Один из них активный — на него указывает docker/nginx/upstreams/active.conf.

Команда `make deploy`:
- собирает образ standby-слота;
- применяет миграции (artisan migrate --force) — до переключения трафика;
- поднимает standby и ждёт healthy;
- перезаписывает active.conf на standby;
- проверяет конфиг (nginx -t) и делает nginx -s reload;
- останавливает бывший active.

Если любой шаг падает — деплой прерывается (|| exit 1), трафик остаётся на старом слоте.

active.conf — локальное состояние каждого окружения, в git не хранится. Создаётся через make setup / make init-upstream с дефолтом blue. Сбросить: make reset-upstream.

## Переменные окружения
### Корневой .env (для compose)

| Переменная            | 	По умолчанию    |	Назначение|
|-----------------------|------------------| - |
| PHP_VERSION           | 8.4              |	версия PHP в образе
| NGINX_VERSION         | 	1.30            |	версия nginx
| NGINX_HOST            | 	127.0.0.1       |	хост для URL в выводе up
| NGINX_PORT            | 	8080            |	порт на хосте
| POSTGRES_VERSION      | 	17              |	Версия PostgreSQL
| DB_HOST               | 	127.0.0.1       |   хост Postgres (dev)
| DB_PORT               | 	54321           |	порт Postgres на хосте (dev)
| DB_DATABASE           | 	laravel         |	имя БД
| DB_USERNAME           | 	root            |	пользователь БД
| DB_PASSWORD           | 	currentPassword |	обязательно задать в prod

### src/.env (для Laravel)
|Переменная| 	По умолчанию   |	Назначение|
| - |-----------------| - |
|DB_HOST| db              | имя сервиса в docker network
|DB_PORT| 5432            | порт для доступа к базе данных в docker network 
|DB_DATABASE| laravel         | название базы данных
|DB_USERNAME| root            | имя пользователя 
|DB_PASSWORD| currentPassword | пароль 

## Dev: типовые сценарии
```bash
make dev-up                 # запустить активный слот + nginx + db
make dev-logs               # смотреть логи всех dev-контейнеров
make dev-down               # остановить и удалить (включая профили)
make dev-build              # пересобрать dev-образы
```
## Prod: типовые сценарии
Перед началом: проверьте файлы `.env` И `src/.env`
```bash
# Собрать и запустить активный слот
make prod-up

# Задеплоить новую версию в standby и переключить
make deploy # сам определит active/standby по active.conf, соберёт standby, применит миграции, переключит nginx и остановит старый слот.
# Логи
make prod-logs
# Остановить контейнеры
make prod-down
```
Artisan в prod — одноразовый контейнер из prod-образа (профиль tools):
```bash
make migrate-prod                 # migrate --force
make rollback-prod                # migrate:rollback --force
make art-prod migrate:status      # произвольная команда
make art-prod config:cache
make art-prod queue:restart
make shell-prod                   # sh внутри образа artisan
```