# Design: Unify `BaseModel` Query Concept on `QueryBuilder`

**Date:** 2026-07-21
**Status:** Approved (design locked)
**Scope:** `includes/Models/BaseModel.php`, `includes/Models/QueryBuilder.php`, in-plugin callers, tests
**Backward compatibility:** Not required — in-plugin callers may be rewritten.

## Problem

`BaseModel` currently ships **two parallel query engines**:

1. **Legacy raw-SQL finders** — `find`, `find_many`, `all`, `where`, `first`, `latest`, `oldest`, `count`. Each hand-builds SQL inside `BaseModel`. `where()` takes a `['col' => val]` array and supports equality only.
2. **`QueryBuilder`** (via `query()`) — fluent, chainable, rich operators (`where('col','>=',v)`, `where_any`, `where_in`, `where_raw`, `order_by`, `distinct`, `select_raw`, …). Added in 1.3.0.

Consequences:

- Duplicated SQL-building logic and two divergent `where()` semantics (array-of-conditions vs `(column, operator, value)`).
- The legacy finders are effectively dead in live code — only `find`, `create`, `save`, `delete`, `destroy`, `truncate`, and `query()` have live callers. `all`, `first`, `latest`, `oldest`, `first_or_create`, `update_or_create`, `find_many`, and array-`where` have **zero** live/test callers.
- No `BaseModelTest` exists, so the legacy paths are untested.

## Goal

Make `QueryBuilder` the single SQL engine. `BaseModel` retains attribute/cast/dirty-tracking machinery plus `save()` persistence, and exposes finders as **thin delegating wrappers**. Adopt an Eloquent-lite call model.

## Design

### Two kinds of static call

- **Starters** — return a `QueryBuilder` for chaining. All builder methods are reachable this way:
  `where`, `where_any`, `where_in`, `where_not_empty`, `where_raw`, `order_by`, `limit`, `offset`, `distinct`, `select`, `select_raw`, `group_by`.
- **Terminals** — execute and return a result:
  `find`, `find_many`, `all`, `first`, `count`, `latest`, `oldest`, `create`, `first_or_create`, `update_or_create`, `destroy`, `truncate`.

### Mechanism

- `query(): QueryBuilder` becomes **public** — the explicit query path.
- Terminals are explicit methods on `BaseModel`, each delegating to `query()`.
- `__call` gains one rule: a method that is **not** defined on the model is **forwarded to `query()->$method(...)`**. This makes every `QueryBuilder` method available as a model starter without hand-writing forwarders.
- `method_exists( $this, $method )` is checked first, so an explicit terminal (`count()`, `first()`) always wins over the builder's same-named method. Both return the same type, so there is no observable collision.
- `__callStatic` is unchanged: `Model::foo(...)` → `(new static())->foo(...)`, which then hits either a terminal or the `__call` forwarder.

### Signature changes (no backward compatibility)

Conditions are **never** passed as arrays to terminals — they always go through the builder:

```php
EmailLog::find( 1 );                               // ?static
EmailLog::all();                                   // array, ordered by pk DESC
EmailLog::count();                                 // int, total rows
EmailLog::where( 'status', 'success' )->get();     // builder → array
EmailLog::where( 'status', 'success' )->first();   // builder → ?static
EmailLog::where( 'status', 'success' )->count();   // builder → int
EmailLog::where_in( 'id', $ids )->delete();        // builder → int
```

The old array form `where( [ 'status' => 'x' ] )` is **removed**. Only a docblock example referenced it; there is no live caller.

### `QueryBuilder` additions

Only new capability — write execution:

| Method | Behavior |
|---|---|
| `delete(): int` | Executes `DELETE FROM {table} WHERE {conditions}` using the accumulated `where` clauses; returns affected-row count. **Throws `RuntimeException` if no `where` clause is set** — prevents accidental full-table deletion. Use `truncate()` to intentionally clear a table. |
| `truncate(): bool` | Executes `TRUNCATE TABLE {table}`. |

All read methods are unchanged and already sufficient for delegation.

### `BaseModel` delegation map

| Terminal | Delegates to |
|---|---|
| `find( $id )` | `query()->where( pk, $id )->first()` |
| `find_many( $ids )` | `query()->where_in( pk, $ids )->get()` |
| `all()` | `query()->order_by( pk, 'DESC' )->get()` |
| `first()` | `query()->first()` |
| `count()` | `query()->count()` |
| `latest( $col = 'created_at' )` | validate `$col` against `fillable + pk`; `query()->order_by( $col, 'DESC' )->first()`; return `null` if invalid |
| `oldest( $col = 'created_at' )` | validate `$col`; `query()->order_by( $col, 'ASC' )->first()`; return `null` if invalid |
| `destroy( $ids )` | `query()->where_in( pk, $ids )->delete()` |
| `truncate()` | `query()->truncate()` |
| `create( $attributes )` | `new static( $attributes )` + `save()` (unchanged) |
| `first_or_create( $conditions, $attributes = [] )` | build `query()` from `$conditions` keys, `first()`; else `create()` |
| `update_or_create( $conditions, $attributes )` | build `query()` from `$conditions`, `first()` + `save()`; else `create()` |
| `save()` (instance) | unchanged — `$wpdb->insert / update` |
| `delete()` (instance) | unchanged — `$wpdb->delete` |

`create`, `first_or_create`, and `update_or_create` still accept a `$conditions`/`$attributes` array because those are inherent to their contract; their lookups build the query via `query()` + a `->where()` loop.

### Identifier safety

- Values are always bound through `$wpdb->prepare` (`%s`), unchanged.
- Column/table identifiers are treated as developer-controlled. User-facing sort columns are already allow-listed in the service layer (`EmailLogService` / `sanitize_sql_orderby`), and that contract stays.
- `latest()` / `oldest()` keep their `fillable + pk` column check and return `null` on an unknown column, preserving today's defense-in-depth.
- No security regression: every path that previously validated identifiers still does, and no new user-controlled identifier path is introduced.

### Callers to update

- `EmailLog` internals already use the builder form `$query->where( ... )` — unaffected.
- `EmailLogService` uses `EmailLog::find()` and `EmailLog::truncate()` (terminals) — unaffected.
- Net caller churn is near zero. The only removed surface (array-`where`) is referenced solely by a docblock, which will be updated.

## Testing

- Extend `tests/Unit/Models/QueryBuilderTest.php` (backed by the existing `QbFixture` model) with:
  - `delete()` after `where` / `where_in` returns the affected count and removes only matching rows.
  - `delete()` with no `where` clause throws `RuntimeException`.
  - `truncate()` empties the table.
- Add `tests/Unit/Models/BaseModelTest.php` (none exists today), using `QbFixture`, covering:
  - Terminals: `find`, `find_many`, `all`, `first`, `count`, `latest`, `oldest`, `create`, `destroy`, `truncate`.
  - `latest`/`oldest` return `null` for an invalid column.
  - Starter forwarding: `QbFixture::where(...)->get()` and other builder methods reachable as statics.
  - `first_or_create` / `update_or_create` create-vs-find branches.

Both files run against the throwaway `debug_suite_qb_fixtures` table (created/dropped in `set_up`/`tear_down`), so no shipped feature model is involved.

## Out of scope (YAGNI)

- No `update()` / `insert()` on `QueryBuilder` — instance `save()` covers writes.
- No joins, scopes, `paginate()`, `chunk()`, or `having()`.
- No change to the `Model` interface (instance-level contract) or the `__callStatic` proxy shape.

## Risks

- **Return-type shift for `where()`** — now yields a `QueryBuilder` instead of an array. Mitigated: no live caller uses the array form.
- **`delete()` guard** — throwing on an unbounded delete is a deliberate safety choice; callers that truly want a full wipe must use `truncate()`.
- **`all()` drops the implicit `LIMIT 100`** — old `all()` defaulted to `LIMIT 100 OFFSET 0`; new `all()` returns every row (Eloquent `all()` semantics). Deliberate; callers needing bounds use `query()->limit()->offset()->get()`. No live caller relied on the old default.
