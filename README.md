# DoFit

A small, self-hosted fitness tracker: log your training sessions, the sets you actually did, and how your body weight moves over time. No account on someone else's server, no subscription, no analytics — one container, your database, your data.

DoFit is a personal project, deliberately kept simple. It does a handful of things and tries to do them without ceremony.

## What it does

**Trainings and sets.** A training holds activities (one exercise each), and an activity holds sequences — the actual sets, with weight and repetitions. That is the whole logging model.

**Programs.** Build a routine once, then start a session from it in a tap. The dashboard tells you which one is due and roughly how long it takes.

**An exercise library.** Around 900 exercises with muscles, equipment and instructions, optionally extended to 1300+ multilingual entries. You can add your own, and pin the ones you train to keep them one tap away.

**Body metrics.** Record your weight, see the curve, and read your BMI next to it once you have entered your height.

**A dashboard.** Sessions and tonnage for the month, a weekly strip, your recent personal records, and the weight curve.

**Installable on a phone.** It ships a web app manifest and an offline page, so it can be added to a home screen and opened like a native app. The interface is available in French and English.

## Quick start with Docker Compose

Published images live at `ghcr.io/gmarineau/dofit`. Use `latest` for the last release, or pin a version tag.

### 1. Generate an application key

```bash
docker run --rm --entrypoint php ghcr.io/gmarineau/dofit:latest artisan key:generate --show
```

### 2. Write your `.env`

Next to your `compose.yaml`:

```dotenv
APP_NAME=DoFit
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:paste-the-key-from-step-1
APP_URL=https://dofit.example.com

# fr or en. The fallback is what a missing translation falls back to.
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=database
DB_PORT=3306
DB_DATABASE=dofit
DB_USERNAME=dofit
DB_PASSWORD=change-me

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=database
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Where exercise illustrations are stored. See "Media storage" below.
MEDIA_DISK=public
```

### 3. Write your `compose.yaml`

```yaml
services:
  app:
    image: ghcr.io/gmarineau/dofit:latest
    restart: unless-stopped
    env_file: .env
    ports:
      - "8080:80"
    volumes:
      - storage:/app/storage
    depends_on:
      database:
        condition: service_healthy
      redis:
        condition: service_started

  # Drains the queue. Nothing heavy runs on it today, but password reset
  # mails and anything you queue later go through here.
  queue:
    image: ghcr.io/gmarineau/dofit:latest
    restart: unless-stopped
    env_file: .env
    environment:
      CONTAINER_ROLE: queue
    volumes:
      - storage:/app/storage
    depends_on:
      database:
        condition: service_healthy

  # Runs the database migrations on start, then the task scheduler. Keep it:
  # this is the container that brings the schema up to date on every deploy.
  scheduler:
    image: ghcr.io/gmarineau/dofit:latest
    restart: unless-stopped
    env_file: .env
    environment:
      CONTAINER_ROLE: scheduler
    volumes:
      - storage:/app/storage
    depends_on:
      database:
        condition: service_healthy

  database:
    image: mariadb:11
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: dofit
      MARIADB_USER: dofit
      MARIADB_PASSWORD: change-me
      MARIADB_ROOT_PASSWORD: change-me-too
    volumes:
      - database:/var/lib/mysql
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s
      timeout: 5s
      retries: 10

  redis:
    image: redis:8-alpine
    restart: unless-stopped
    volumes:
      - redis:/data

volumes:
  storage:
  database:
  redis:
```

### 4. Start it

```bash
docker compose up -d
```

The schema is migrated by the `scheduler` container on start, so give it a few seconds on the first run. Then open `http://localhost:8080` (or whatever you put behind your reverse proxy) and create your account.

## After the first start

**Registration is open.** Anyone who can reach the instance can create an account. There is no invite system and no admin role — put DoFit behind a reverse proxy with authentication, a VPN, or your own network if it is not meant to be public.

**The exercise library is empty until you import it.** Two importers are available, and both write into the same table:

```bash
# ~900 English exercises from free-exercise-db (public domain data).
docker compose exec app php artisan dofit:import-exercises --without-images

# ~1300 exercises in 10 languages from exercises-dataset (MIT data).
docker compose exec app php artisan dofit:import-exercises-dataset
```

Both are safe to re-run: they update what they already imported rather than duplicating it.

**Illustrations are opt-in, and not ours to redistribute.** Passing `--with-images` (first importer) or `--with-media` (second) downloads the pictures and animations that go with the exercises. Those files belong to [Gym Visual](https://gymvisual.com/) and are not covered by the datasets' licenses — keep them to your own instance and keep the attribution.

## Configuration

Everything is driven by environment variables, the standard Laravel way. The ones that matter here:

| Variable | Default | What it does |
| --- | --- | --- |
| `APP_KEY` | — | Required. Encryption key, generated once (step 1 above). |
| `APP_URL` | `http://localhost` | Public URL. Also used to build media links. |
| `APP_LOCALE` | `fr` | Interface language, `fr` or `en`. Each user can pick their own. |
| `DB_CONNECTION` | `sqlite` | `mysql` for the compose above. SQLite works for a single-user instance. |
| `MEDIA_DISK` | `public` | Disk holding exercise illustrations: `public` (local) or `media` (S3). |
| `QUEUE_CONNECTION` | `database` | `redis` when you run a Redis, as in the compose above. |
| `SCOUT_DRIVER` | `collection` | `meilisearch` to get typo-tolerant search. See below. |
| `CONTAINER_ROLE` | `app` | Per-container: `app` (web), `queue` (worker), `scheduler` (migrations + cron). |

Configuration is cached when the container starts, so a changed variable needs a container restart, not just a new `.env`.

### Media storage

Illustrations go to the disk named by `MEDIA_DISK`:

- `public` — `storage/app/public`, served through the `public/storage` symlink, which the container creates on start. Keep `/app/storage` on a volume so the files survive a container replacement.
- `media` — a public prefix on an S3-compatible bucket. Set the usual `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET` and, importantly, `AWS_URL` so the links point at your endpoint. If your bucket has ACLs disabled (the default on recent AWS buckets, on Cloudflare R2 and on Scaleway), drop the `visibility` line from the `media` disk in `config/filesystems.php` and open the prefix with a bucket policy instead.

### Search

Search runs in memory by default, which is fine and needs nothing. Point `SCOUT_DRIVER=meilisearch` at a Meilisearch instance to get typo tolerance and the French synonyms, then build the index:

```bash
docker compose exec app php artisan dofit:sync-exercise-search
```

Re-run that command after every import, since the importers write in bulk and never fire the indexing events.

## Updating

```bash
docker compose pull
docker compose up -d
```

The `scheduler` container migrates the database on start, so there is no separate migration step.

## Local development

Requires PHP 8.4, Composer and Node 22.

```bash
composer setup
```

That installs both dependency sets, creates `.env`, generates the key, migrates, and builds the assets. Then:

```bash
composer dev
```

which runs the PHP server, the queue listener and Vite together.

The test suite is Pest:

```bash
php artisan test --compact
```

Before committing, format with Pint and check the types:

```bash
vendor/bin/pint && composer types:check
```

## Built with

Laravel 13, Livewire 4 (single-file page components), Tailwind CSS 4, and FrankenPHP in the published image. Search goes through Laravel Scout, media through spatie/laravel-medialibrary.

## Credits and license

The application is MIT licensed.

Exercise data comes from [free-exercise-db](https://github.com/yuhonas/free-exercise-db) (public domain) and [exercises-dataset](https://github.com/hasaneyldrm/exercises-dataset) (MIT). The illustrations that go with them belong to [Gym Visual](https://gymvisual.com/) and are downloaded only if you ask for them.
