---
description: Prepare a tagged release — bump version everywhere, replace PLUGIN_SINCE, update the changelog, commit, and create the git tag (stops before pushing).
argument-hint: <version> (e.g. 1.2.0, no leading "v")
allowed-tools: Bash(git status:*), Bash(git add:*), Bash(git commit:*), Bash(git tag:*), Bash(git log:*), Bash(git diff:*), Bash(git describe:*), Bash(grep:*), Bash(npm run version:*), Bash(pnpm make:pot:*), Bash(pnpm build:*), Edit, Read
---

Prepare release **$1** for this WordPress plugin (Debug Suite — slug `debug-suite`).

Releases are driven by pushing a `vX.Y.Z` git tag. `.github/workflows/release.yml`
triggers on tags matching **`v*.*.*`** and (1) builds the plugin
(`composer install` → mozart generates `override/`, then `composer install --no-dev -o`,
then `pnpm install && pnpm run build`), (2) deploys to the WordPress.org SVN repo
(slug `debug-suite`) via `10up/action-wordpress-plugin-deploy`, and (3) creates a
GitHub release with the generated zip. This command prepares everything locally and
**stops before pushing** — the user pushes the tag themselves to fire the live
public release.

## Preconditions — verify first, abort with a clear message if any fail

1. A version argument was given in `$1`. If empty, stop and ask for one.
2. `$1` is a valid semver `X.Y.Z` with **no** leading `v`. If it has a `v`, strip it.
   The workflow only matches `v*.*.*`, so a 2-part version will never deploy.
3. The working tree is clean (`git status --porcelain` is empty). If not, stop and
   show what's dirty — do not bundle unrelated changes into a release commit.
4. The tag `v$1` does not already exist (`git tag -l v$1` is empty).
5. `$1` is greater than the current version (read from the `Version:` header in
   `debug-suite.php`). If it is lower or equal, warn and ask to confirm.

## Steps

1. Bump the version by hand in **all four** locations (Edit each, exact replacement
   — `bin/version.js` does **not** touch any of these):
   - `debug-suite.php` → the `* Version:` plugin header
   - `debug-suite.php` → `public string $version = '...';` on the `DebugSuite` class
     (this feeds the `DEBUG_SUITE_VERSION` constant — there is no separate `define()`
     with a literal to edit)
   - `package.json` → the top-level `"version"` field (must be bumped **before** step 3)
   - `README.txt` → the `Stable tag:` header (the readme is **uppercase** `README.txt`
     in this repo)

   `composer.json` also carries a `"version"` field, but it has drifted (still `1.0.0`)
   and nothing reads it — leave it alone unless the user asks.

2. Update the changelog in `README.txt`. Draft a new entry directly below
   `== Changelog ==`, matching the existing style — **no `v` prefix, no date**:

   ```
   = $1 =
   * <bullet>
   ```

   Build the bullet list from `git log <last-tag>..HEAD` (last tag = `git describe
   --tags --abbrev=0`). Existing entries use conventional-commit-ish prefixes
   (`feat:`, `fix:`, `refactor:`, `chore:`) — follow that, but rewrite raw commit
   subjects into user-facing prose. **Show the draft to the user for confirmation
   before committing** — release notes are public on WordPress.org.

   Also check whether `Tested up to:` should be bumped. It is currently **out of
   sync**: `README.txt` says 6.9, `debug-suite.php` says 6.8. Flag it and keep both
   in lockstep.

3. Replace the `PLUGIN_SINCE` placeholders in source: run `npm run version`
   (**not** `pnpm version` — that resolves to pnpm's built-in version command;
   `npm run version` or `node bin/version.js` runs the script). It reads `version`
   from `package.json` (bumped in step 1) and rewrites every `@since PLUGIN_SINCE` /
   `@deprecated PLUGIN_SINCE` docblock across `includes/`, `src/`, `tests/`,
   `debug-suite.php`, and `uninstall.php` to `$1`. These are permanent — they get
   committed as part of the release.

4. Regenerate translations: run `pnpm make:pot` (updates `languages/debug-suite.pot`).
   It scans `assets/js` — which is gitignored build output — so run `pnpm build`
   first if `assets/js` is stale or missing. Skip only if `wp` CLI is unavailable —
   if so, tell the user.

5. Confirm the bumps with
   `grep -n "Version:\|\$version" debug-suite.php`,
   `grep -n '"version"' package.json`, and `grep -n "Stable tag" README.txt` — all
   must read `$1`. Also `grep -rn "PLUGIN_SINCE" includes src debug-suite.php uninstall.php`
   must come back empty. Run `git status` and review `git diff` so nothing unexpected
   (build output, `override/`, `vendor/`) is staged.

6. Commit: `git commit -am "chore(release): v$1"`.

7. Create an annotated tag: `git tag -a v$1 -m "Release v$1"`.

8. **Stop.** Do not push. Print the exact command for the user to run when ready,
   and remind them it triggers the **public WordPress.org deploy + GitHub release**:

   ```
   git push origin develop && git push origin v$1
   ```

   (Default branch here is `develop` — adjust if on another branch.)

## Notes

- Keep the four version strings in lockstep — a mismatch between the plugin header,
  the `$version` property, `package.json`, and `Stable tag` is the most common
  release bug here.
- Do **not** rewrite existing `@since X.Y.Z` docblocks that already have a real
  number — `bin/version.js` only touches the literal `PLUGIN_SINCE` placeholder.
- `override/` (mozart-scoped `league/container` + `psy/psysh`) and `vendor/` are
  gitignored build output. CI regenerates them; never commit them.
- The CI workflow builds the zip itself; you do **not** need `pnpm build` or
  `pnpm zip` locally to release. `pnpm release` / `pnpm release:org` (which runs
  `npm run version` first) exist only for producing a local artifact
  `debug-suite-v<version>.zip`.
- What ships to WP.org is controlled by `.distignore` (10up action), not by
  `bin/zip.js`. If you add a new top-level dev file, add it to `.distignore` too.
