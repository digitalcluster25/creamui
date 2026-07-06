# CreamUI Repository

Один репозиторий для двух контуров:

- `frontend/` — боевой Next.js фронтенд для HWS
- `UIkit/` — статическая среда для блоков, примитивов и сборочных страниц
- `wp-plugins/` — WordPress плагины, связанные с фронтендом
- `backups/` — локальные снимки прод-сервера HWS, в git не идут
- `artifacts/` — временные визуальные артефакты, в git не идут

## Быстрый старт

Боевой фронтенд локально:

```bash
npm run frontend:dev
```

UI kit локально:

```bash
npm run uikit:serve
```

## Порты

- `http://localhost:3001` — Next.js frontend
- `http://localhost:4173` — UI kit

## Деплой на HWS

Ручной деплой:

```bash
npm run hws:deploy
```

Локальный backup с HWS:

```bash
npm run hws:backup
```

Автодеплой идет из GitHub Actions после пуша в `main`, если изменился `frontend/**` или deploy-конфиг.

### GitHub Secrets for deploy

- `HWS_HOST`
- `HWS_USER`
- `HWS_SSH_KEY`
- `HWS_PORT` (optional, default `22`)
- `HWS_REMOTE_DIR` (optional, default `/opt/hws-frontend`)
- `HWS_PM2_APP` (optional, default `hws-frontend`)

## Что не редактировать на сервере руками

- `/opt/hws-frontend/app`
- `/opt/hws-frontend/components`
- `/opt/hws-frontend/lib`
- `/opt/hws-frontend/package.json`

Сервер должен быть только deploy-целью. Source of truth — этот репозиторий.
