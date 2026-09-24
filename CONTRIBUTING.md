# Contributing

Thanks for considering a contribution. PRs, issue reports, and reproduction
cases are all welcome.

## Setup

```bash
composer install
npm install
```

The PHPUnit suite runs against a real WordPress install — see
[README.md → Tests](README.md#tests) for the one-time WP test-suite setup.

## Quality bar

CI runs the full quality suite on every push and PR. Run them locally before
opening a PR:

```bash
composer phpstan         # PHPStan level 10 (must be clean)
composer format          # mago — formats PHP
composer format:check    # mago — formatting check (CI runs this)
composer test            # PHPUnit (needs WP test suite installed once)

npm run format           # oxfmt — formats JS / CSS / JSON
npm run lint             # oxlint — JS lint
npm run check            # format:check + lint, run together
```

PHP is pinned to 8.2+ via `composer.json`'s `config.platform`. Don't bump that
without intent — it changes resolved versions of dev dependencies.

## What makes a good PR

- **Solve a problem.** Bug fixes and missing-feature gaps are easier to review
  than speculative refactors.
- **Add a test.** The PHPUnit suite exercises real WP behaviour; new behaviour
  should land with a test that pins it down. See `tests/TestPlugin.php` for the
  pattern (filters auto-cleared by `tearDown()`).
- **Update the docs.** If you change a public API or a filter contract,
  update README and CHANGELOG in the same PR.
- **Keep PRs focused.** Smaller PRs review faster than one that refactors
  three subsystems at once.

## Coding style

PHP is formatted by [mago](https://github.com/carthage-software/mago) (see
`mago.toml`); JS / CSS / JSON by [oxfmt](https://oxc.rs/docs/guide/usage/formatter.html).
Both are run from `composer format` / `npm run format`. Don't fight the
formatter — if a rule is wrong, raise it as a separate discussion.

PHPStan runs at level 10 (its highest). New code must analyse clean — no
`@phpstan-ignore` lines without a comment explaining why.
