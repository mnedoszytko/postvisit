.PHONY: up down build fresh test migrate seed queue logs shell

# Start all containers
up:
	docker compose up -d

# Stop all containers
down:
	docker compose down

# Build containers
build:
	docker compose build --no-cache

# Fresh install: build, migrate, seed
fresh: build up
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --class=DemoSeeder
	@echo "PostVisit.ai running at http://localhost:8080"

# Run tests
test:
	docker compose exec app php artisan test

# Run migrations
migrate:
	docker compose exec app php artisan migrate

# Seed demo data
seed:
	docker compose exec app php artisan db:seed --class=DemoSeeder

# Watch queue worker logs
queue:
	docker compose logs -f queue

# All container logs
logs:
	docker compose logs -f

# Shell into app container
shell:
	docker compose exec app sh

# Reset everything
reset:
	docker compose down -v
	$(MAKE) fresh
