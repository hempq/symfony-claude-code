# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

A Claude Code plugin for Symfony development. 29 skills, 11 AI agents, output styles, hooks, bin shortcuts, MCP and LSP servers.

## Structure

```
skills/<name>/SKILL.md      29 slash command skills with YAML frontmatter
agents/*.md                  11 AI agent personas with YAML frontmatter
output-styles/*.md           Response format styles (terse, tutorial)
hooks.json                   PostToolUse reminders (lint, migration, cache clear)
bin/                         DDEV shortcuts (sf, phptest, phpfix, phpstan)
.mcp.json                    MCP servers (Context7, Playwright)
.lsp.json                    LSP server (Intelephense)
settings.json                Plugin default settings
.claude-plugin/plugin.json   Plugin manifest with component paths and userConfig
```

## How Skills Work

Each `skills/<name>/SKILL.md` has YAML frontmatter (`description`, `model`, `effort`, `context`, `paths`, `allowed-tools`, `argument-hint`) and a markdown prompt body. Skills use `!`command`` syntax for dynamic context injection — they read existing project files before generating code to match conventions. Heavy skills (600+ lines) use `context: fork` to run in isolated context.

## How Agents Work

Each `agents/*.md` has frontmatter (`name`, `description`, `model`, `color`, `tools`, `disallowedTools`, `maxTurns`, `effort`). All agents are Symfony-specific — they reference Doctrine, Messenger, Voters, Profiler, DDEV etc. Claude auto-invokes agents based on the `description` field.

## Target Stack

- PHP 8.2+ with strict types and readonly properties
- Symfony 7.0+ with attribute-based routing and autowiring
- Doctrine ORM with PHP 8 attribute mappings
- DDEV for local container development
- PHPUnit for testing
- Symfony UX (Twig Components, Live Components, Stimulus)
