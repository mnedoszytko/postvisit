# Deployment Guide (Internal)

## Servers

| Server | Domain | IP | Region | Provider |
|--------|--------|----|--------|----------|
| Production EU | postvisit.ai | 159.69.91.159 | Nuremberg | Hetzner |
| Production US | app.postvisit.ai | 178.156.228.160 | Ashburn | Hetzner |

Both servers: Ubuntu 24.04, PHP 8.4, PostgreSQL 17, Bun, Forge-managed.

## Quick Deploy (SSH)

### app.postvisit.ai (US)

```bash
ssh forge@178.156.228.160
cd /home/forge/app.postvisit.ai
git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
bun install
bun run build
sudo /usr/sbin/service php8.4-fpm reload
```

### postvisit.ai (EU)

```bash
ssh forge@159.69.91.159
cd /home/forge/postvisit.ai
git pull origin main
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm install
npm run build
sudo -S service php8.4-fpm reload
```

## Deploy via Forge API

```bash
export FORGE_TOKEN="<token from postvisit-agent4/.env>"

# US server (1157745, site 3041870)
curl -s -X POST "https://forge.laravel.com/api/v1/servers/1157745/sites/3041870/deployment/deploy" \
  -H "Authorization: Bearer $FORGE_TOKEN" -H "Accept: application/json"

# EU server (1157023, site 3039654)
curl -s -X POST "https://forge.laravel.com/api/v1/servers/1157023/sites/3039654/deployment/deploy" \
  -H "Authorization: Bearer $FORGE_TOKEN" -H "Accept: application/json"
```

## Deploy Both Servers at Once

```bash
export FORGE_TOKEN="<token>"
for SITE in "1157745/sites/3041870" "1157023/sites/3039654"; do
  curl -s -X POST "https://forge.laravel.com/api/v1/servers/$SITE/deployment/deploy" \
    -H "Authorization: Bearer $FORGE_TOKEN" -H "Accept: application/json" &
done
wait
echo "Both deployments triggered"
```

## IP Whitelist (nginx)

Both servers restrict access to whitelisted IPs only:

```nginx
allow 80.201.242.171;   # Nedo home
allow 31.4.178.242;     # Whitelist 2
allow 127.0.0.1;
allow ::1;
deny all;
```

To update: edit nginx config via Forge API or directly in `/etc/nginx/sites-available/<domain>`, then reload:

```bash
sudo /usr/sbin/service nginx reload
```

## .env Differences

| Key | EU (postvisit.ai) | US (app.postvisit.ai) |
|-----|--------------------|-----------------------|
| APP_URL | https://postvisit.ai | https://app.postvisit.ai |
| SANCTUM_STATEFUL_DOMAINS | postvisit.ai | app.postvisit.ai |
| SESSION_DOMAIN | postvisit.ai | .postvisit.ai |
| FILESYSTEM_DISK | local | s3 |
| AWS_* | not set | DigitalOcean Spaces (sfo3) |

## Credentials

Stored in Craft (PostVisit.ai folder): "Server Credentials - app.postvisit.ai (US)"

- **Forge API token**: in `postvisit-agent4/.env` (`FORGE_API_TOKEN`)
- **Hetzner API token**: in `postvisit-agent3/.env` (`HETZNER_API_TOKEN`)

## Troubleshooting

### DemoSeeder fails on re-deploy
Expected — seeder has unique constraints. Deploy script uses `|| true` to ignore.

### nginx won't reload after config change via Forge API
Forge API updates the config file but doesn't always reload. SSH in and run:
```bash
sudo /usr/sbin/service nginx configtest
sudo /usr/sbin/service nginx reload
```

### Check logs
```bash
ssh forge@178.156.228.160 "tail -50 /home/forge/app.postvisit.ai/storage/logs/laravel.log"
```
