# Library API

**English** | [Polski](README.pl.md)

A small REST API for a library information system. Library staff can register the
books the library owns, remove them, list them, and track whether each book is
currently borrowed (and by whom).

Built with **Symfony 7.4**, **Doctrine ORM 3**, **PostgreSQL 16** and **FrankenPHP**,
and shipped with a `docker compose` setup that boots a working application with a
single command.

> **Live:** https://library-api.opcode.me.uk — interactive docs at
> [/docs](https://library-api.opcode.me.uk/docs).

## Requirements

- Docker with the Compose plugin (`docker compose`).

Nothing else is needed on the host — PHP, Composer and the database all run in
containers.

## Quick start

```bash
docker compose up --build
```

This will:

1. start PostgreSQL and wait until it is healthy,
2. build the application image (installs dependencies, warms the cache),
3. run the database migrations automatically on startup,
4. serve the API.

The API is then available at:

- **http://localhost:8088** — when the bundled `docker-compose.override.yml` is used
  (the default when you run `docker compose up` from this directory).
- **http://localhost:8080** — when only `docker-compose.yml` is used
  (e.g. `docker compose -f docker-compose.yml up`).

Quick check:

```bash
curl http://localhost:8088/api/books
```

### Ports and the local override

`docker-compose.yml` is the portable definition and exposes the app on `8080`
(the database stays on the internal network only).

`docker-compose.override.yml` is loaded automatically by Compose. It remaps the app
to `127.0.0.1:8088` (via the `!override` tag) and additionally exposes PostgreSQL on
`127.0.0.1:55432`, so the stack does not collide with other services already running
on the host. Adjust those ports to taste. When using the base file on its own, the
app port can be set with `APP_HTTP_PORT` (the database stays internal there).

## API

Base path: `/api/books`. All requests and responses use JSON.

| Method   | Path                            | Description                    | Success |
|----------|---------------------------------|--------------------------------|---------|
| `GET`    | `/api/books`                    | List all books                 | `200`   |
| `GET`    | `/api/books/{serialNumber}`     | Get a single book              | `200`   |
| `GET`    | `/api/books/{serialNumber}/history` | Get the borrow/return history | `200` |
| `POST`   | `/api/books`                    | Add a new book                 | `201`   |
| `DELETE` | `/api/books/{serialNumber}`     | Delete a book                  | `204`   |
| `POST`   | `/api/books/{serialNumber}/borrow` | Mark a book as borrowed     | `200`   |
| `POST`   | `/api/books/{serialNumber}/return` | Mark a book as available    | `200`   |

`serialNumber` and `cardNumber` are six-digit strings (leading zeros are
significant, e.g. `000042`).

### Book representation

```json
{
  "serialNumber": "123456",
  "title": "Clean Code",
  "author": "Robert C. Martin",
  "borrowed": true,
  "borrowedBy": "654321",
  "borrowedAt": "2026-07-01T20:27:23+00:00"
}
```

### Examples

Add a book:

```bash
curl -X POST http://localhost:8088/api/books \
  -H 'Content-Type: application/json' \
  -d '{"serialNumber":"123456","title":"Clean Code","author":"Robert C. Martin"}'
```

Borrow it:

```bash
curl -X POST http://localhost:8088/api/books/123456/borrow \
  -H 'Content-Type: application/json' \
  -d '{"cardNumber":"654321"}'
```

Return it:

```bash
curl -X POST http://localhost:8088/api/books/123456/return
```

Delete it:

```bash
curl -X DELETE http://localhost:8088/api/books/123456
```

### Errors

Errors are returned with a consistent envelope and a meaningful status code:

```json
{ "error": { "code": "book_already_borrowed", "message": "..." } }
```

Validation errors (`422`) additionally list the offending fields:

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The submitted data is invalid.",
    "violations": [
      { "field": "serialNumber", "message": "The serial number must be a six-digit number." }
    ]
  }
}
```

| Status | When                                                              |
|--------|-------------------------------------------------------------------|
| `404`  | The book does not exist                                           |
| `409`  | Duplicate serial number / borrowing a borrowed book / returning an available one |
| `422`  | Invalid payload (bad serial/card number, missing title/author)   |

## Interactive documentation

An OpenAPI 3.0 specification is served with the application:

- **Swagger UI:** [`/docs`](https://library-api.opcode.me.uk/docs) — browse and try
  the endpoints. Use the **Servers** dropdown to switch between production and local.
- **Raw spec:** `/openapi.yaml` (also in `public/openapi.yaml`).

A **Postman collection** is provided at
[`postman/library-api.postman_collection.json`](postman/library-api.postman_collection.json).
Import it together with one of the environments to point `baseUrl` at the right host:

- [`postman/library-api.local.postman_environment.json`](postman/library-api.local.postman_environment.json) — `http://localhost:8088`
- [`postman/library-api.production.postman_environment.json`](postman/library-api.production.postman_environment.json) — `https://library-api.opcode.me.uk`

## Tests

The project is developed test-first. Run the full suite (unit + functional) with:

```bash
make test
```

or directly:

```bash
docker compose run --rm -e APP_ENV=test app sh -lc "\
  php bin/console doctrine:database:create --if-not-exists && \
  php bin/console doctrine:migrations:migrate --no-interaction && \
  php vendor/bin/phpunit"
```

- **Unit tests** (`tests/Unit`) cover the `Book` domain behaviour (borrow/return
  rules) with no infrastructure.
- **Functional tests** (`tests/Functional`) exercise the HTTP API against a real
  PostgreSQL database. Each test runs inside a transaction that is rolled back
  afterwards (via `dama/doctrine-test-bundle`), so they are isolated and fast.

## Code quality & CI

Static checks run in CI on every push and pull request
(`.github/workflows/ci.yml`), next to the test suite:

- **PHP CS Fixer** — `@Symfony` ruleset + strict types (`.php-cs-fixer.dist.php`).
  Run `make cs` to check, `make cs-fix` to apply.
- **PHPStan** — level 8 with the Doctrine/Symfony extensions (`phpstan.dist.neon`).
  Run `make phpstan`.

## Deployment

Live at **https://library-api.opcode.me.uk**, behind **nginx** (TLS via **certbot**)
proxying to the Docker Compose stack. The `deploy` job in the CI pipeline
`rsync`s the code to the host and runs `docker compose up -d --build` on every
green push to `main`. Server setup, the required secrets, and SSH-key hygiene are
documented in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Design notes

- **Rich domain model.** `Book` owns its invariants: a book cannot be borrowed
  twice or returned when it is already available — those rules live in the entity,
  not in the controller.
- **Layers.** Controllers stay thin: they map HTTP to a `BookService`, which
  orchestrates the domain and persistence. Request payloads are validated DTOs
  (`#[MapRequestPayload]`); controllers hand entities straight to `$this->json()`
  and a dedicated `BookNormalizer` shapes the JSON, so the wire format is defined
  in one place and the entity stays free of serialization concerns.
- **Persistence behind an interface.** `BookService` depends on
  `BookRepositoryInterface`, not on Doctrine directly. The production adapter is
  `BookRepository` (Doctrine); unit tests use a tiny `InMemoryBookRepository`, so
  the service's behaviour is tested with no database at all.
- **Error handling.** Domain exceptions carry their own HTTP status via a small
  `ApiException` interface; a single `ApiExceptionListener` turns them (and
  validation failures) into the JSON envelope above, keeping the domain free of
  HTTP concerns.
- **Time is injected** through Symfony's `ClockInterface`, which keeps the
  borrow timestamp deterministic in tests.
- **Concurrency.** `Book` carries an optimistic-lock version, so two borrow
  requests racing on the same copy cannot both succeed: the losing flush is
  turned into a `409 Conflict` instead of silently double-lending.
- **History.** The `borrowedBy`/`borrowedAt` fields are only the *current* state.
  The full record lives in an append-only `BookEvent` log: `Book::borrow()` and
  `returnToShelf()` record an immutable event (type, card number, timestamp) into
  a `OneToMany` collection that cascades on persist, so the state change and the
  event are written in a single transaction. Events have no setters and are never
  modified. History is part of the `Book` aggregate, so deleting a book removes
  its history too (`ON DELETE CASCADE`); if the log had to outlive the book, it
  would become a standalone entity keyed by serial number.
- **Serial number** is the business identifier used in the API routes. The primary
  key is a separate, internal UUID (v7, time-ordered for index locality) assigned
  in the constructor — so an entity has a non-null identity the moment it is
  created, before it ever reaches the database. The serial number has its own
  unique constraint at the database level.

### Project structure

```
src/
├── Controller/
│   ├── BookController.php             HTTP endpoints
│   └── DocumentationController.php    serves Swagger UI at /docs
├── Dto/                               validated request payloads
├── Entity/
│   ├── Book.php                       aggregate: state + borrow/return + history
│   ├── BookEvent.php                  immutable borrow/return event
│   └── BookEventType.php              borrowed | returned
├── EventListener/ApiExceptionListener.php
├── Exception/                         domain exceptions (ApiException)
├── Serializer/                        BookNormalizer + BookEventNormalizer
├── Repository/
│   ├── BookRepositoryInterface.php    persistence port
│   └── BookRepository.php             Doctrine adapter
└── Service/
    ├── BookServiceInterface.php       use-case port (controllers depend on this)
    └── BookService.php                use cases
tests/
├── Unit/Entity/BookTest.php
├── Unit/Service/BookServiceTest.php   service tested against the in-memory double
├── Unit/Serializer/BookNormalizerTest.php
├── Double/InMemoryBookRepository.php
└── Functional/                        full HTTP + database tests
migrations/                            schema (book + book_event tables)
public/
├── openapi.yaml                       OpenAPI 3.0 specification
└── docs/index.html                    Swagger UI
postman/                               Postman collection + local/production environments
docker/frankenphp/                     Dockerfile + entrypoint
deploy/setup-server.sh                  one-time host bootstrap (Docker, nginx, certbot)
docs/DEPLOYMENT.md                      deployment guide
.php-cs-fixer.dist.php                  coding standards config
phpstan.dist.neon                       static analysis config
.github/workflows/ci.yml                CI pipeline (cs-fixer, phpstan, phpunit, deploy)
```
