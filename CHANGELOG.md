# Changelog

## [2.0.0] - 2026-04-10

### Breaking Changes
- Migrated from `commands/` to `skills/` format (skills take precedence if both exist)
- Plugin now requires Claude Code 2.1+ for skills support

### Added
- **5 new skills**: `/migration`, `/fixture-new`, `/command-new`, `/middleware-new`, `/twig-extension-new`
- **Output styles**: `terse` (code-focused) and `tutorial` (educational) via `/output-style`
- **Bin shortcuts**: `sf`, `phptest`, `phpfix`, `phpstan` added to PATH while plugin is active
- **Hooks**: Automated reminders after editing PHP files and entities
- **userConfig**: Configurable DDEV prefix and PHP version at plugin enable time
- **Supporting files**: Lint skill bundles template configs for PHP CS Fixer, PHPStan, and Rector
- **Cross-references**: Skills now suggest related next steps (e.g., entity-new suggests /migration, /form-new)
- `argument-hint` on 5 high-usage skills for UI placeholder text
- `effort` levels on all skills (high/medium/low)
- `tools`, `maxTurns`, `effort` on all 11 agents
- `disallowedTools` on research-only agents to prevent unintended file edits

### Changed
- All skills use `model: claude-sonnet-4-6` (was claude-sonnet-4-5)
- All agents have rich descriptions with concrete trigger examples for auto-invocation
- All agents have `model: sonnet` and `color` for visual differentiation
- Removed invalid `category` field from all agents
- Removed stale "Context Framework Note" from security-engineer agent
- Removed outdated Symfony 6.x version qualifiers (target is 7.0+)
- Removed undocumented Supabase section from MCP-SERVERS.md

### Fixed
- `lint.md` was entirely ESLint/TypeScript — rewritten for PHP CS Fixer, PHPStan, Rector
- `code-explain.md` was entirely Python — rewritten for PHP/Symfony patterns

## [1.5.0] - 2026-04-10

### Changed
- Migrated to new plugin standard: `commands/` and `agents/` at plugin root (was `.claude/commands/`, `.claude/agents/`)
- Updated `plugin.json` with full metadata (homepage, repository, license, keywords, component paths)
- Updated `marketplace.json` with `$schema`, `category`, `strict`, component paths
- Added `.mcp.json` at plugin root for MCP server definitions
- Added `settings.json` at plugin root
- Removed `.claude/settings.template.json`
- Updated all internal path references

## [1.4.0] - Initial release

- 21 slash commands for Symfony development
- 11 specialized AI agents
- DDEV integration throughout
