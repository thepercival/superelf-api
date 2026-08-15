# SuperElf Workspace Guidelines

## Repository Boundaries

- This workspace contains multiple PHP packages plus `superelf-api` and `superelf-frontend`. Run commands from the owning project directory.
- Prefer existing domain services, repositories, Doctrine mappings, and application commands over direct SQL. Do not write or execute SQL for application-level reads or mutations unless the user explicitly requests it.
- The local `superelf` MariaDB database runs as a host system service, not a Docker container. Commands needing it may require access outside the terminal sandbox.

## SuperElf CLI Tools

Treat the commands registered in `config/commands.php` and implemented in `app/Commands/` as workspace tools. Run them from `superelf-api` using:

```bash
XDEBUG_MODE=off php bin/console.php <command> [arguments] [options]
```

Use `php bin/console.php app:help` to inspect registered arguments and options. If command discovery fails while constructing an unrelated command, read `config/commands.php` and the relevant class under `app/Commands/`.

Preferred tools:

- `app:get`: inspect internal domain data such as sports, seasons, competitions, teams, structure, and games.
- `app:get-external`: inspect SofaScore data without importing it.
- `app:import`: import SofaScore entities, games, lineups, events, and missing players.
- `app:import:image`: import team or player images.
- `app:sync`: synchronize SuperElf data for a season or game-round range.
- `app:validate-*`: validate competition configuration, game participations, team players, and points.
- Use the administrative and migration commands registered in `config/commands.php` for their corresponding workflows.

Supported external import object types include `sports`, `associations`, `seasons`, `leagues`, `competitions`, `structure`, `teams`, `teamcompetitors`, `games-basics`, `games-complete`, `game`, `players`, and `transfers`.

Use season names as `YYYY/YYYY` and game-round ranges as `N-N`. Omit `--loglevel` unless needed; prefer named Monolog levels such as `Info` over legacy numeric values.

### Single-Game SofaScore Workflow

Use application commands to identify and test a specific game:

```bash
php bin/console.php app:get games-basics \
  --league=Eredivisie --season=2025/2026 --sport=football \
  --gameRoundRange=34-34

php bin/console.php app:get-external sofascore game \
  --league=Eredivisie --season=2025/2026 --sport=football \
  --internal-id=<internal-game-id>

php bin/console.php app:import sofascore game \
  --league=Eredivisie --season=2025/2026 --sport=football \
  --id=<external-game-id> --no-events

php bin/console.php app:sync \
  --league=Eredivisie --season=2025/2026 \
  --id=<internal-game-id>
```

- Always run `app:sync` after a SofaScore import. The import stores source sports data; sync converts that imported data into the corresponding SuperElf data.
- Use `--no-events` for focused local import tests unless queue publication is explicitly part of the test.
- Use `--no-game-cache` only when a fresh SofaScore response is required.
- Validate persisted output through repositories, existing console commands, or API endpoints rather than direct table queries.

## Package Integration

After changing `php-sports-import`, copy it into the API before integration validation or release preparation:

```bash
cd ../php-sports-import
composer run copy-sup
```

Then validate both source and consumer with Psalm:

```bash
cd ../php-sports-import && composer run psalm
cd ../superelf-api && composer run psalm
```

Use Psalm, not PHPStan, for static analysis in this workspace.

Database schema changes belong in Doctrine mappings. `superelf-api` runs `composer run doctrine-update` from Composer's post-install workflow, so verify schema changes with `composer install` rather than handwritten migration SQL.