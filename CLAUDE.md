# CLAUDE.md

Source Web Console is a Symfony 7.4 web application (PHP 8.2+, Doctrine, Twig, Webpack Encore, TypeScript) that manages Source engine game servers over the Source Query and RCON protocols. The code is hosted on a private GitLab instance; GitHub is a mirror.

## Environment

- Local PHP is Windows PHP 8.5 with no coverage driver (no Xdebug, no PCOV). Docker is not installed. GitLab CI (`.gitlab/.gitlab-ci.yml`) runs PHP 8.2 with Xdebug and is the reference for coverage.
- The `test` environment uses SQLite (`var/data.db`) and loads `.env` only, never `.env.local`.
- Never run `composer update` or `composer require` without `--no-plugins`: Symfony Flex then applies recipes and creates or rewrites files (`compose.yaml`, `.env.*`, `config/bundles.php`, `src/Schedule.php`, `<env>` variables in `phpunit.xml`, auto-scripts in `composer.json`...). To refresh only the lock hash, run `composer update --lock --no-plugins`.

## Quality checks

The Husky hooks run these on every commit; run them before committing and fix every error:

```
vendor/bin/phpcs -s
vendor/bin/phpstan analyse --memory-limit=-1
vendor/bin/phpmd src ansi phpmd.xml
vendor/bin/phpunit
npm run check-spell -- --no-must-find-files
```

PhpStorm also runs EA Extended and SonarLint inspections. Code must be free of their warnings: unused imports, redundant `static` in a `final` class, polymorphic calls on an interface, non-complementary `??` operands.

## Tests

- Tests never touch the network. In the `test` environment, `config/services.yaml` and `config/packages/framework.yaml` replace these services with doubles from `tests/Mock/`:
  - `FakeSourceQuery`, for Source Query and RCON. Addresses starting with `123.123.123.` are unreachable. Any other address answers, and RCON commands need a password.
  - `HttpClientResponseFactory`, for Steam, GitHub and Have I Been Pwned. For Steam, the server address picks the scenario: `10.0.0.1` to `10.0.0.4` are the error cases.
  - `FakeMailer` and `FakeStorageManager`.
- Each double keeps its state in static properties. Call `resetFakeServices()` from `tests/TestHelperTrait.php` in `setUp()`.
- `TestHelperTrait` also loads the fixtures, creates and selects servers, and generates CSRF tokens bound to the client session.
- After reloading the fixtures, log the user in again: the IDs change and the old token is invalidated.
- Assertions are called as `self::assertX()`. Mock builders stay on `$this` (`$this->once()`, `$this->exactly()`, `$this->createMock()`). In a `final` class, use `self::` instead of `static::`.
- The target is 100% line coverage of `src/` (`Kernel.php` and `DataFixtures/` are excluded). A line is excluded with `@codeCoverageIgnore` only when it requires real infrastructure (FTP or SFTP server), with a comment giving the reason.

## Code style

- PSR-12 with Allman braces (opening brace on its own line). Follow `.editorconfig`: indentation with 4 spaces (no tabs), LF line endings, a final newline and lines of 120 characters at most. Older files still use tabs: convert every file you modify. Use type casts (`(int)`) rather than `intval()`.
- Comments and doc blocks are written in French, following the existing style. Do not add comments that repeat the code or narrate a change ("now", "used to"): the git history owns that.
- `use` statements are ordered as a staircase by line length, shortest first. Equal lengths keep their order. The same rule applies to the extension list in `composer.json`.
- Write the straight apostrophe `'` and three dots `...`, never `’` or `…`.
- Code identifiers are in English.
- A suppression comment (`phpcs:disable`, `@SuppressWarnings`) is only allowed when a third-party parent class imposes the name, as in `FakeSourceQuery`.

## Commits

- Conventional Commits, checked by commitlint: `type(scope): description`, in English and lowercase, with no trailing period.
- Commit only when asked, and never push without an explicit request.
- Split large work into separate commits by batch (for example tests, source fixes, dependencies).
- End the message with the `Co-Authored-By` line of the model in use.
