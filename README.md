# aol-demo

Real-world demos for the [`php-aol/php-aol`](https://packagist.org/packages/php-aol/php-aol) library.

Three scripts that each hit a real public endpoint and use a different
attribute-driven surface of AOL.

## Run

```bash
composer install
php bin/github.php   # 4 parallel HTTP requests
php bin/wiki.php     # Wikipedia recent-changes SSE
php bin/crypto.php   # Binance live trades over WebSocket
```

## What each one shows

| Script | Feature shown |
|---|---|
| `bin/github.php` | Declarative HTTP interface (`#[Get]`, `#[Path]`, `#[Query]`) + auto-graph parallelism. 4 GitHub repos fetched concurrently, decoded into typed `GithubRepo` value objects via `Aol\Support\Cast`. |
| `bin/wiki.php` | Imperative SSE — `Http::sse()` consuming Wikimedia EventStream. Filters on enwiki + ≥50-byte edits with a deadline-driven scope. |
| `bin/crypto.php` | Declarative WebSocket — `#[WebSocket(url)]` + `#[OnOpen]`/`#[OnMessage]`/`#[OnClose]` + `#[OnTick(every: 1.0)]`. Subscribes to Binance combined trade stream for BTC/ETH/SOL. |

## Layout

```
src/
  GithubApi.php       # Retrofit-style HTTP interface
  GithubRepo.php      # readonly value object, fromArray via Cast::pick
  WikiEdit.php        # readonly value object, Cast + Arr decoding
  BinanceTrade.php    # readonly value object + side() method + notionalUsd()
  CryptoTicker.php    # declarative WebSocket worker class
bin/
  github.php
  wiki.php
  crypto.php
```

## Conventions

- PHP 8.4 `final readonly class` for every value type
- `Aol\Support\Cast::pick()` / `Aol\Support\Arr::from()` for raw-payload decoding — never `??`
- Strict types declared everywhere
- All async work runs inside `Aol::scope()`, which owns connection lifetime
