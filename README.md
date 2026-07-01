# Library API

A small REST API for a library information system. Library staff can register the
books the library owns, remove them, list them, and track whether each book is
currently borrowed (and by whom).

Built with **Symfony 7.4**, **Doctrine ORM 3**, **PostgreSQL 16** and **FrankenPHP**,
and shipped with a `docker compose` setup that boots a working application with a
single command.

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

## Design notes

- **Rich domain model.** `Book` owns its invariants: a book cannot be borrowed
  twice or returned when it is already available — those rules live in the entity,
  not in the controller.
- **Layers.** Controllers stay thin: they map HTTP to a `BookService`, which
  orchestrates the domain and persistence. Request payloads are validated DTOs
  (`#[MapRequestPayload]`); responses go through a `BookResponse` view so the API
  contract is explicit and decoupled from the entity.
- **Error handling.** Domain exceptions carry their own HTTP status via a small
  `ApiException` interface; a single `ApiExceptionListener` turns them (and
  validation failures) into the JSON envelope above, keeping the domain free of
  HTTP concerns.
- **Time is injected** through Symfony's `ClockInterface`, which keeps the
  borrow timestamp deterministic in tests.
- **Concurrency.** `Book` carries an optimistic-lock version, so two borrow
  requests racing on the same copy cannot both succeed: the losing flush is
  turned into a `409 Conflict` instead of silently double-lending.
- **Serial number** is the business identifier used in the API routes; an internal
  auto-increment id is used as the primary key, with a unique constraint on the
  serial number enforced at the database level.

### Project structure

```
src/
├── Controller/BookController.php      HTTP endpoints
├── Dto/                               request/response payloads
├── Entity/Book.php                    domain model + Doctrine mapping
├── EventListener/ApiExceptionListener.php
├── Exception/                         domain exceptions (ApiException)
├── Repository/BookRepository.php
└── Service/BookService.php            use cases
tests/
├── Unit/Entity/BookTest.php
└── Functional/BookApiTest.php
migrations/                            schema (book table)
docker/frankenphp/                     Dockerfile + entrypoint
```
