<laravel-boost-guidelines>
# Laravel Application

Laravel Boost is installed. Read `AGENTS.md` in this directory for the curated guidelines
for this application, and follow the skills in `.claude/skills/` for the domain you are
working in.

## Running this application

PHP is not installed on the host — everything runs through Laravel Sail (Docker):

```sh
docker compose up -d
docker compose exec -u sail laravel.test php artisan ...
docker compose exec -u sail laravel.test vendor/bin/pint --format agent
docker compose exec -u sail laravel.test php artisan test --compact
```

Always pass `-u sail`; running as root leaves root-owned files in the bind-mounted project.
</laravel-boost-guidelines>
