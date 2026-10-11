# NPF-familjen

<img src="resources/images/logo.png" alt="NPF-familjen" width="240">

Ett hjälpmedel för barn och familjer med NPF-problematik (t.ex. ADHD och autism): uppgifter, rutiner och packlistor med bildstöd, veckomatsedel, skolmat och en hemskärm som visar familjens dag.

## Dokumentation

- [Arkitektur](docs/ARCHITECTURE.md): stack, datamodell och regler
- [Roadmap](docs/ROADMAP.md): milestones i byggordning
- [Stilguide](docs/STYLEGUIDE.md): färger, typsnitt och komponenter, med [tokens](docs/design/tokens.json) och [utkast av hemskärmen](docs/mockups/)

## AI-stöd

Projektets regler för AI-verktyg ligger i [.ai/guidelines/](.ai/guidelines/). Laravel Boost tar med dem i `CLAUDE.md` (Claude Code) och `AGENTS.md` (Copilot). Kör `php artisan boost:update` efter att reglerna ändrats.

## Utveckling

- Appen körs lokalt med Laravel Herd på http://adhd.test
- Databas: MariaDB 12.3 (`npf_familjen` och `npf_familjen_test`)
- Mejl fångas av Mailpit: http://localhost:8025
- Kontroller som körs på GitHub: `composer ci:check`
