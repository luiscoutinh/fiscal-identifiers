# Contributing

The package is in its infrastructure phase. No validator or public validation API
exists yet. Discuss the scope of a first validator before building a broad API.

## Development

Use PHP 8.3, 8.4 or 8.5 and Composer 2. Development tools require the usual
PHP CLI extensions, including DOM, XML, XMLWriter and mbstring. Xdebug or PCOV is
required only for coverage.

```sh
git clone https://github.com/luiscoutinh/fiscal-identifiers.git
cd fiscal-identifiers
composer install
composer check
composer format
```

For coverage with Xdebug (POSIX shells):

```sh
XDEBUG_MODE=coverage composer test:coverage
```

In PowerShell, set `$env:XDEBUG_MODE = 'coverage'` before running
`composer test:coverage`. Reports are written to `build/coverage/` (HTML and
Clover XML). PCOV can also be used when enabled for `src/`.

## Conventions

- Keep runtime code framework-agnostic and compatible with PHP 8.3.
- Use `FiscalIdentifiers\` under `src/` and strict types in every PHP file.
- Run Pest, PHPStan at maximum level for `src/`, and PHP-CS-Fixer before opening a PR.
- Add meaningful tests for behavior, malformed input and boundaries. Tests must be
  deterministic and must not call live tax registries or VIES.
- Source jurisdiction rules from official publications and include references.
  Record relevant dates and distinguish mathematical plausibility from registration.
- Keep identifiers as strings. Do not infer country or type from digits alone.
- Avoid real personal identifiers in fixtures, logs, screenshots and issues.
- Document normalization choices; silently stripping arbitrary characters is unsafe.

The temporary `Internal\Package` stub and its test exist only to exercise autoloading,
Pest and coverage. Replace them with the first functional implementation. The current
100% coverage gate covers only that stub; it makes no claim about fiscal validation.
Uncovered files in `src/` are included in the coverage denominator.

## Dependencies and CI

As a library, this repository intentionally does not commit `composer.lock`.
`composer install` resolves dependencies on a fresh checkout and creates a local
lock file; use `composer update` to refresh it. CI resolves highest dependencies
on PHP 8.3–8.5 and lowest supported dependencies on PHP 8.3. This checks compatibility
at both ends of the declared ranges. The runtime has no framework or third-party
dependency; development tooling is isolated in `require-dev`.

CI also runs strict Composer validation, static analysis, style checks and coverage.
Coverage reports are attached to the workflow run for 14 days. No external coverage
account or secret is needed. Dependabot checks Composer and GitHub Actions weekly.

Submit changes on a topic branch with a focused pull request. Keep the README honest
about implemented support. Release notes will live in GitHub Releases, following
Semantic Versioning; no manually maintained changelog is required at this stage.
