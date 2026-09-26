# Repository Guidelines

## Project Overview

- Active Court is a PHP web system for sports court booking, administration, payments, reports, touchscreen terminals, displays, and API integrations.
- Application code lives in the repository root. Modules are under `core/modules/`, configuration under `app/config/`, templates under `app/tpl/`, and shared system code under `core/system/`.
- The application uses a custom framework, custom autoloading, Service Locator, modules, engines, device actions, and local routing.
- Entry points cover site, admin, touchscreen, display, street, pay, and `mapi` interfaces.

## Required Reading

- Before changing a feature, read `.docs/README.md`, the relevant documentation in `.docs/`, and the code around the affected module.
- For architecture and core behavior, use `.docs/architecture/README.md`, `.docs/core/README.md`, `.docs/engines/README.md`, `.docs/devices/README.md`, and `.docs/technical/README.md`.
- For module work, start with `.docs/modules/README.md` and then read `.docs/modules/{module}/` when present.
- Before adding or running tests, read `.docs/development/testing.md` and `../tests/README.md`.

## Documentation Rules

- Keep documentation current after functional or architectural changes.
- Follow `.docs/development/documentation.md` for placement, naming, navigation, task registries, plans, reports, and README usage.
- Update an existing document when it already owns the topic; create a new document only when the topic has a distinct purpose or scope.
- README files may be created as section or module overviews and navigation indexes. Do not duplicate detailed documentation in them.
- Documentation filenames in `.docs/` must be lowercase with hyphen-separated words, except standard `README.md` files.
- Keep detailed feature plans with the corresponding module documentation and register active work briefly in `.docs/tasks/README.md`.
- Store completed plans and historical lists in `.docs/archive/tasks/`.
- Put factual work reports in `.docs/archive/reports/` only when a report is requested or already part of the task.
- After structural documentation changes, run `composer docs:check` or `php bin/check-documentation`.

## Code Style

- Follow existing project patterns and local style before introducing new abstractions.
- The server runtime is PHP 8.2. Keep production code compatible with PHP 8.2 and do not use PHP 8.3+ syntax or APIs.
- Use namespaces under `AC\` for main project classes and respect the custom autoloader.
- Use `PascalCase` for classes, `camelCase` for methods and variables, and the project's existing `snake_case` convention for constants.
- Add PHPDoc for classes and public methods when touching or adding them.
- Use Russian for focused comments that explain non-obvious business logic.
- `.editorconfig` defines UTF-8, LF line endings, 2-space indentation, and a maximum line length of 150.

## Language and Messages

- Write internal and system diagnostics in English, including exception, log, assertion, and migration messages.
- Do not hardcode user-facing text. Route it through the existing localization system and display it in the user's selected language.
- Do not expose internal exceptions, diagnostics, secrets, or payment-provider details to users in production.
- Russian text is allowed in focused code comments, project documentation, and Russian localization resources.
- Before creating, translating, or editing German user-facing text in this repository, read
  `.agents/skills/base-active-court-german-editor/SKILL.md` and use its project glossary and context-specific du/Sie rules.
  Apply this skill to German localization work, including new UI strings; preserve existing placeholders and markup.

## Architecture Notes

- Use the existing Service Locator pattern through `Service` and related locators.
- Prefer the established module structure under `core/modules/{module}/`: `controllers/`, `controllers/modComm/`, `models/`, `views/`, and `config/`.
- Reuse existing engines, models, DTOs, validators, helpers, layouts, and service classes before creating new abstractions.
- Device-specific behavior is split across site, admin, touchscreen, display, and mapi actions. Keep changes scoped to the relevant surface unless the shared layer is intentionally changing.
- Validate all request input, check authorization, and use prepared queries or the existing DB/query abstractions.

## Testing

- Unit tests live in the sibling `../tests/` repository, not in this application worktree.
- Do not create ad-hoc test or example files such as `example_usage.php`, `test_advanced.php`, `ExampleController.php`, or demo controllers in this repository.
- Run Codeception commands from `../tests/`.
- To test a specific application worktree, set `AC_APP_ROOT` to its absolute path before running Codeception.
- The canonical capability catalog is `../tests/capabilities.php`; application worktrees list only enabled capabilities in `app/config/TestCapabilitiesConfig.php`.
- When a new or changed test requires a capability, add the catalog entry with `false` when needed and enable it with `true` only in the current application worktree in the same task.
- Add functional tests to the shared tests branch and gate version-specific checks with capabilities. A separate application branch does not imply a separate tests branch.
- Use separate tests branches for test-infrastructure changes, experiments, or a dedicated review workflow. When such work runs in parallel, use a separate worktree or clone; do not switch the branch of the shared tests directory.
- Keep parallel tasks in separate logical commits, staging only their files or hunks and preserving other tasks' working-tree and index changes. Capabilities control test execution, not Git isolation.

Typical commands:

```powershell
cd ..\tests
composer install
php vendor\bin\codecept build
php vendor\bin\codecept run unit
```

## Change Hygiene

- Keep edits scoped to the requested behavior and do not reformat unrelated areas opportunistically.
- Respect existing user changes; do not revert or overwrite unrelated modifications.
- Do not create or rewrite Git commits unless the user explicitly requests that mutation.
- When a commit is requested, follow `.docs/development/git-commit-message.md`.
- Do not commit secrets, local credentials, generated dependency folders, or runtime/upload artifacts.
