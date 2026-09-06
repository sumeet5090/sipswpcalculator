# ADR 0005: Atomic Symlink Deployments and SQLite WAL Concurrency

## Status
Accepted

## Context
Deploying code directly into active document roots (`public_html`) causes mid-transfer fatal errors, OPcache file descriptor races, and broken asset 404s. Furthermore, standard SQLite operations lock the entire database file during writes, creating contention during concurrent traffic surges.

## Decision
1. **Zero-Downtime Atomic Symlink Pipeline:** GitHub Actions builds assets in CI, rsyncs the release into timestamped directories (`releases/YYYYMMDDHHMMSS/`), and performs an atomic symlink swap of `public_html`.
2. **Persistent Shared Assets:** The SQLite database and storage logs reside in `shared/` and are auto-discovered by `CoreServiceProvider`.
3. **SQLite WAL Mode:** All SQLite connections enforce Write-Ahead Logging (`PRAGMA journal_mode = WAL; PRAGMA busy_timeout = 5000; PRAGMA synchronous = NORMAL;`), allowing simultaneous concurrent reads during writes.
4. **CLI-Only Migrations:** Migrations run strictly through `php bin/migrate` during the deployment step; no web migration endpoints exist.

## Consequences
- **Positive:** Zero user downtime during deployments, instant rollback capability (last 3 releases retained), high database read concurrency without locking.
- **Negative:** Requires shared hosting SSH key access and symlink resolution configuration (`SymLinksIfOwnerMatch`).
