# API biblioteki

[English](README.md) | **Polski**

Proste REST API dla systemu informatycznego biblioteki. Pracownicy biblioteki mogą
rejestrować posiadane książki, usuwać je, pobierać ich listę oraz śledzić, czy dana
książka jest aktualnie wypożyczona (i przez kogo).

Zbudowane w oparciu o **Symfony 7.4**, **Doctrine ORM 3**, **PostgreSQL 16** oraz
**FrankenPHP**, wraz z konfiguracją `docker compose`, która uruchamia działającą
aplikację jednym poleceniem.

> **Live:** https://library-api.opcode.me.uk — interaktywna dokumentacja pod
> [/docs](https://library-api.opcode.me.uk/docs).

## Wymagania

- Docker z wtyczką Compose (`docker compose`).

Nic więcej nie jest potrzebne na maszynie hosta — PHP, Composer i baza danych
działają w kontenerach.

## Szybki start

```bash
docker compose up --build
```

Spowoduje to:

1. uruchomienie PostgreSQL i oczekiwanie, aż baza będzie gotowa,
2. zbudowanie obrazu aplikacji (instalacja zależności, rozgrzanie cache),
3. automatyczne uruchomienie migracji bazy danych przy starcie,
4. wystawienie API.

API jest następnie dostępne pod adresem:

- **http://localhost:8088** — gdy używany jest dołączony plik
  `docker-compose.override.yml` (domyślnie, gdy uruchamiasz `docker compose up`
  z tego katalogu).
- **http://localhost:8080** — gdy używany jest wyłącznie `docker-compose.yml`
  (np. `docker compose -f docker-compose.yml up`).

Szybkie sprawdzenie:

```bash
curl http://localhost:8088/api/books
```

### Porty i lokalny override

`docker-compose.yml` to przenośna definicja, która wystawia aplikację na porcie
`8080` (baza danych pozostaje wyłącznie w sieci wewnętrznej).

`docker-compose.override.yml` jest ładowany automatycznie przez Compose. Przemapowuje
aplikację na `127.0.0.1:8088` (dzięki tagowi `!override`) oraz dodatkowo wystawia
PostgreSQL na `127.0.0.1:55432`, tak aby stos nie kolidował z innymi usługami już
działającymi na hoście. Porty można dowolnie zmienić. Przy korzystaniu z samego pliku
bazowego port aplikacji ustawia się zmienną `APP_HTTP_PORT` (baza pozostaje wtedy
wewnętrzna).

## API

Ścieżka bazowa: `/api/books`. Wszystkie żądania i odpowiedzi używają formatu JSON.

| Metoda   | Ścieżka                             | Opis                              | Sukces |
|----------|-------------------------------------|-----------------------------------|--------|
| `GET`    | `/api/books`                        | Lista wszystkich książek          | `200`  |
| `GET`    | `/api/books/{serialNumber}`         | Pobranie jednej książki           | `200`  |
| `GET`    | `/api/books/{serialNumber}/history` | Historia wypożyczeń i zwrotów     | `200`  |
| `POST`   | `/api/books`                        | Dodanie nowej książki             | `201`  |
| `DELETE` | `/api/books/{serialNumber}`         | Usunięcie książki                 | `204`  |
| `POST`   | `/api/books/{serialNumber}/borrow`  | Oznaczenie książki jako wypożyczonej | `200` |
| `POST`   | `/api/books/{serialNumber}/return`  | Oznaczenie książki jako dostępnej | `200`  |

`serialNumber` oraz `cardNumber` to sześciocyfrowe ciągi znaków (wiodące zera są
znaczące, np. `000042`).

### Reprezentacja książki

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

### Przykłady

Dodanie książki:

```bash
curl -X POST http://localhost:8088/api/books \
  -H 'Content-Type: application/json' \
  -d '{"serialNumber":"123456","title":"Clean Code","author":"Robert C. Martin"}'
```

Wypożyczenie:

```bash
curl -X POST http://localhost:8088/api/books/123456/borrow \
  -H 'Content-Type: application/json' \
  -d '{"cardNumber":"654321"}'
```

Zwrot:

```bash
curl -X POST http://localhost:8088/api/books/123456/return
```

Usunięcie:

```bash
curl -X DELETE http://localhost:8088/api/books/123456
```

### Błędy

Błędy zwracane są w spójnej strukturze wraz ze znaczącym kodem statusu:

```json
{ "error": { "code": "book_already_borrowed", "message": "..." } }
```

Błędy walidacji (`422`) dodatkowo wypisują niepoprawne pola:

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

| Status | Kiedy                                                                          |
|--------|--------------------------------------------------------------------------------|
| `404`  | Książka nie istnieje                                                            |
| `409`  | Zduplikowany numer seryjny / wypożyczenie wypożyczonej / zwrot dostępnej książki |
| `422`  | Niepoprawne dane (błędny numer seryjny/karty, brak tytułu/autora)              |

## Dokumentacja interaktywna

Wraz z aplikacją serwowana jest specyfikacja OpenAPI 3.0:

- **Swagger UI:** [`/docs`](https://library-api.opcode.me.uk/docs) — przeglądaj
  i testuj endpointy. Użyj listy **Servers**, aby przełączać się między produkcją
  a lokalnym środowiskiem.
- **Surowa specyfikacja:** `/openapi.yaml` (również w `public/openapi.yaml`).

Dołączona jest **kolekcja Postman**:
[`postman/library-api.postman_collection.json`](postman/library-api.postman_collection.json).
Zaimportuj ją razem z jednym ze środowisk, aby ustawić `baseUrl` na właściwy host:

- [`postman/library-api.local.postman_environment.json`](postman/library-api.local.postman_environment.json) — `http://localhost:8088`
- [`postman/library-api.production.postman_environment.json`](postman/library-api.production.postman_environment.json) — `https://library-api.opcode.me.uk`

## Testy

Projekt jest rozwijany w podejściu test-first. Uruchomienie całego zestawu testów
(jednostkowych i funkcjonalnych):

```bash
make test
```

lub bezpośrednio:

```bash
docker compose run --rm -e APP_ENV=test app sh -lc "\
  php bin/console doctrine:database:create --if-not-exists && \
  php bin/console doctrine:migrations:migrate --no-interaction && \
  php vendor/bin/phpunit"
```

- **Testy jednostkowe** (`tests/Unit`) pokrywają logikę domenową `Book`
  (reguły wypożyczania/zwrotu) bez żadnej infrastruktury.
- **Testy funkcjonalne** (`tests/Functional`) sprawdzają API HTTP na prawdziwej
  bazie PostgreSQL. Każdy test działa w transakcji wycofywanej po jego zakończeniu
  (dzięki `dama/doctrine-test-bundle`), więc są izolowane i szybkie.

## Jakość kodu i CI

Analiza statyczna uruchamiana jest w CI przy każdym pushu i pull requeście
(`.github/workflows/ci.yml`), obok zestawu testów:

- **PHP CS Fixer** — zestaw reguł `@Symfony` + strict types (`.php-cs-fixer.dist.php`).
  `make cs` sprawdza, `make cs-fix` naprawia.
- **PHPStan** — poziom 8 z rozszerzeniami Doctrine/Symfony (`phpstan.dist.neon`).
  `make phpstan`.

## Wdrożenie

Działa pod adresem **https://library-api.opcode.me.uk**, za **nginx** (TLS przez
**certbot**) pełniącym rolę reverse proxy do stosu Docker Compose. Zadanie `deploy`
w pipelinie CI kopiuje kod na host przez `rsync` i uruchamia
`docker compose up -d --build` przy każdym zielonym pushu do `main`. Konfiguracja
serwera, wymagane sekrety oraz higiena klucza SSH są opisane w
[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

## Decyzje projektowe

- **Bogaty model domenowy.** `Book` pilnuje swoich niezmienników: książki nie można
  wypożyczyć dwa razy ani zwrócić, gdy jest już dostępna — te reguły żyją w encji,
  a nie w kontrolerze.
- **Warstwy.** Kontrolery są cienkie: mapują HTTP na `BookService`, który
  orkiestruje domenę i persystencję. Dane wejściowe to walidowane DTO
  (`#[MapRequestPayload]`); kontrolery przekazują encje wprost do `$this->json()`,
  a dedykowany `BookNormalizer` kształtuje JSON, więc format odpowiedzi jest
  zdefiniowany w jednym miejscu, a encja nie zajmuje się serializacją.
- **Persystencja za interfejsem.** `BookService` zależy od
  `BookRepositoryInterface`, a nie od Doctrine bezpośrednio. Produkcyjnym adapterem
  jest `BookRepository` (Doctrine); testy jednostkowe używają drobnego
  `InMemoryBookRepository`, więc zachowanie serwisu jest testowane zupełnie bez bazy.
- **Obsługa błędów.** Wyjątki domenowe niosą swój status HTTP poprzez mały interfejs
  `ApiException`; pojedynczy `ApiExceptionListener` zamienia je (oraz błędy walidacji)
  na powyższą strukturę JSON, trzymając domenę z dala od spraw HTTP.
- **Czas jest wstrzykiwany** przez `ClockInterface` z Symfony, co czyni znacznik
  czasu wypożyczenia deterministycznym w testach.
- **Współbieżność.** `Book` posiada wersję optymistycznej blokady, więc dwa żądania
  wypożyczenia tego samego egzemplarza nie mogą oba się powieść: przegrywający zapis
  zamieniany jest na `409 Conflict`, zamiast po cichu wypożyczyć książkę dwa razy.
- **Historia.** Pola `borrowedBy`/`borrowedAt` to jedynie *bieżący* stan. Pełny zapis
  żyje w dopisywanym (append-only) dzienniku `BookEvent`: `Book::borrow()` oraz
  `returnToShelf()` zapisują niemodyfikowalne zdarzenie (typ, numer karty, znacznik
  czasu) do kolekcji `OneToMany` kaskadowanej przy persystencji, więc zmiana stanu
  i zdarzenie zapisują się w jednej transakcji. Zdarzenia nie mają setterów i nigdy
  nie są modyfikowane. Historia jest częścią agregatu `Book`, więc usunięcie książki
  usuwa też jej historię (`ON DELETE CASCADE`); gdyby dziennik miał przetrwać
  usunięcie książki, stałby się osobną encją kluczowaną numerem seryjnym.
- **Numer seryjny** to identyfikator biznesowy używany w ścieżkach API. Kluczem
  głównym jest osobny, wewnętrzny UUID (v7, uporządkowany czasowo dla lokalności
  indeksu) nadawany w konstruktorze — dzięki temu encja ma niepusty identyfikator
  już w momencie utworzenia, zanim trafi do bazy. Numer seryjny ma własne
  ograniczenie unikalności na poziomie bazy danych.

### Struktura projektu

```
src/
├── Controller/
│   ├── BookController.php             endpointy HTTP
│   └── DocumentationController.php    serwuje Swagger UI pod /docs
├── Dto/                               walidowane dane wejściowe
├── Entity/
│   ├── Book.php                       agregat: stan + wypożyczenie/zwrot + historia
│   ├── BookEvent.php                  niemodyfikowalne zdarzenie wypożyczenia/zwrotu
│   └── BookEventType.php              borrowed | returned
├── EventListener/ApiExceptionListener.php
├── Exception/                         wyjątki domenowe (ApiException)
├── Serializer/                        BookNormalizer + BookEventNormalizer
├── Repository/
│   ├── BookRepositoryInterface.php    port persystencji
│   └── BookRepository.php             adapter Doctrine
└── Service/
    ├── BookServiceInterface.php       port przypadków użycia (zależą od niego kontrolery)
    └── BookService.php                przypadki użycia
tests/
├── Unit/Entity/BookTest.php
├── Unit/Service/BookServiceTest.php   serwis testowany na podwójniaku in-memory
├── Unit/Serializer/BookNormalizerTest.php
├── Double/InMemoryBookRepository.php
└── Functional/                        pełne testy HTTP + bazy danych
migrations/                            schemat (tabele book + book_event)
public/
├── openapi.yaml                       specyfikacja OpenAPI 3.0
└── docs/index.html                    Swagger UI
postman/                               kolekcja Postman + środowiska local/production
docker/frankenphp/                     Dockerfile + entrypoint
deploy/setup-server.sh                  jednorazowy bootstrap hosta (Docker, nginx, certbot)
docs/DEPLOYMENT.md                      przewodnik wdrożeniowy
.php-cs-fixer.dist.php                  konfiguracja standardów kodowania
phpstan.dist.neon                       konfiguracja analizy statycznej
.github/workflows/ci.yml                pipeline CI (cs-fixer, phpstan, phpunit, deploy)
```
