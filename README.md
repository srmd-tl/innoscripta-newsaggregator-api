# News Aggregator API

A Laravel 13 backend that aggregates articles from **NewsAPI.org**, **The Guardian**, and
**The New York Times**, stores them locally, keeps them updated on a schedule, and exposes a
REST API for search, filtering, and per-user personalization.

## Architecture

The design centers on a **provider abstraction** so adding a news source means writing one
class and registering it — nothing else changes (Open/Closed).

```
Contracts\NewsProvider              interface: key(), isConfigured(), fetch()
DataTransferObjects\ArticleData     readonly normalized article every provider maps into
Services\News\Providers\*           NewsApiProvider, GuardianProvider, NytProvider (one API each)
Services\News\NewsProviderRegistry  resolves/filters providers (container-tagged)
Services\News\ArticleImporter       persists + dedupes on url_hash via upsert
Jobs\FetchProviderArticles          one queued job per provider (isolated failure)
Console\Commands\FetchNewsCommand   news:fetch — dispatches jobs / runs inline
```

**SOLID:** each provider has one responsibility (SRP); a 4th source is one class + one tag
entry (OCP); providers are interchangeable behind `NewsProvider` (LSP); the interface exposes
only what consumers need (ISP); the command/jobs depend on the registry + interface, resolved
via container tags (DIP). Controllers stay thin — validation lives in Form Requests, query
building in `ArticleFilter` (reused by `/articles` and `/feed`, DRY), output in API Resources.

**Deduplication:** every article is keyed by `url_hash = sha256(url)` (UNIQUE). Fetches use
`Article::upsert(..., ['url_hash'], ...)`, so re-running never creates duplicates.

## Setup

```bash
composer install
cp .env.example .env          # if not already present
php artisan key:generate
php artisan migrate

# Add your API keys to .env (the app runs with any subset — missing keys are skipped):
# NEWSAPI_KEY=...     https://newsapi.org/register
# GUARDIAN_KEY=...    https://open-platform.theguardian.com/access/
# NYT_KEY=...         https://developer.nytimes.com/get-started

php artisan serve
```

## Fetching articles

```bash
php artisan news:fetch          # dispatch one queued job per configured provider
php artisan news:fetch --sync   # run inline (no queue worker needed)
php artisan news:fetch --provider=guardian #dispatch provider by name
php artisan news:fetch --provider=guardian --sync #to run inline for specific provider
```

The scheduler (in `routes/console.php`) runs `news:fetch` **hourly**. Enable it locally with:

```bash
php artisan schedule:work       # or a cron entry: * * * * * php artisan schedule:run
php artisan queue:work          # to process the dispatched jobs
```

## API endpoints (`/api/v1`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | – | Register, returns a token |
| POST | `/auth/login` | – | Login, returns a token |
| POST | `/auth/logout` | ✓ | Revoke current token |
| GET | `/articles` | – | Search + filter + paginate |
| GET | `/articles/{id}` | – | Single article |
| GET | `/sources` | – | List sources |
| GET | `/categories` | – | List categories |
| GET | `/preferences` | ✓ | Current user's preferences |
| PUT | `/preferences` | ✓ | Set preferred sources/categories/authors |
| GET | `/feed` | ✓ | Personalized feed from preferences |

**Article filters** (query params on `/articles` and `/feed`): `q` (keyword),
`source`, `category`, `author` (slug or id), `date_from`, `date_to`, `sort` (`asc|desc`),
`per_page` (max 100).

Authenticate protected routes with the token: `Authorization: Bearer <token>`.

### Example

```bash
curl "http://localhost:8000/api/v1/articles?q=climate&source=the-guardian&per_page=5"

# register -> capture token -> set preferences -> read personalized feed
curl -X PUT http://localhost:8000/api/v1/preferences \
  -H "Authorization: Bearer <token>" -H "Accept: application/json" \
  -d "sources[]=1&categories[]=2"

curl http://localhost:8000/api/v1/feed -H "Authorization: Bearer <token>"
```

### API client collection

An importable collection (`insomnia_collection.json`) is provided at the project root so you can
test every endpoint from your REST client. Import it into Insomnia (**Import → From File**), then
run **Register** or **Login** and paste the returned token into the environment's `token` variable —
all protected requests use it automatically. Adjust the `base_url` variable if your host/port differ.

## Testing

```bash
php artisan test
```

Tests use an in-memory SQLite database and `Http::fake()` — no real API keys required. Coverage
includes provider normalization, fetch idempotency (no duplicates on re-run), skipping
unconfigured providers, article search/filter/pagination, auth, preferences validation, and the
personalized feed.

## Tech

Laravel 13 · PHP 8.4 · Sanctum (token auth) · SQLite · PHPUnit · Pint.
