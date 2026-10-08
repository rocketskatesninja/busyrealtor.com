#!/usr/bin/env bash
#
# Deploy busyrealtor.
#
#   ./scripts/deploy.sh              pull origin/main, then deploy
#   ./scripts/deploy.sh --no-pull    deploy whatever is already checked out
#
# THE ORDER IS THE POINT. resources/css/app.css lists storage/framework/views as a
# @source, so Tailwind scans the COMPILED views as well as the raw .blade.php files.
# Seven classes come only from there -- Laravel's pagination view uses -mt-px, ml-12 and
# leading-7, which appear in no template of ours. Build the CSS before recompiling the
# views and Tailwind reads the PREVIOUS deploy's compiled output, so the stylesheet is
# built against stale markup and silently ships without those rules. Since the
# stylesheet is inlined into every response, there is no cached copy to fall back on.
#
# So: view:cache first, npm run build second. Everything else follows from that.

set -euo pipefail

cd "$(dirname "$0")/.."

PHP=${PHP:-php}
FPM=${FPM:-php8.5-fpm}
QUEUE=${QUEUE:-busyrealtor-queue.service}
PULL=1
[ "${1:-}" = "--no-pull" ] && PULL=0

step() { printf '\n\033[1m== %s\033[0m\n' "$1"; }

if [ "$PULL" = 1 ]; then
    step "pull"
    git pull --ff-only origin main
fi
echo "at $(git log --oneline -1)"

# Maintenance mode only when the schema is actually changing. A view-only deploy does
# not need the site down, and taking it down is not free.
step "pending migrations"
PENDING=$("$PHP" artisan migrate:status 2>/dev/null | grep -c 'Pending' || true)
echo "${PENDING} pending"
DOWN=0
if [ "$PENDING" -gt 0 ]; then
    DOWN=1
    "$PHP" artisan down --retry=60
fi

step "composer"
# --no-dev only in production. Stripping dev dependencies on staging removes PHPUnit,
# and the next `artisan test` there fails for a reason that looks nothing like the cause.
ENV=$(grep -E '^APP_ENV=' .env | cut -d= -f2- | tr -d '"'"'"'"' || true)
if [ "$ENV" = "production" ]; then
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
else
    composer install --optimize-autoloader --no-interaction --quiet
fi
echo "done (APP_ENV=${ENV:-unset})"

# Only when the lockfile moved; npm ci wipes and reinstalls node_modules otherwise.
if ! git diff --quiet HEAD@{1} HEAD -- package-lock.json 2>/dev/null; then
    step "npm ci (lockfile changed)"
    npm ci --silent
fi

if [ "$PENDING" -gt 0 ]; then
    step "migrate"
    "$PHP" artisan migrate --force
fi

step "storage"
# Two users write backups: the web process when someone uses the console, and whoever the
# scheduler runs as. Whichever creates this first owns it, so make it explicitly shared --
# otherwise the first click of "Back up now" fails with a bare mkdir permission error.
mkdir -p storage/app/backups
chgrp -R www-data storage/app/backups 2>/dev/null || sudo -n chgrp -R www-data storage/app/backups
chmod -R g+w storage/app/backups 2>/dev/null || sudo -n chmod -R g+w storage/app/backups
ls -ld storage/app/backups

step "views"
"$PHP" artisan view:clear -q
"$PHP" artisan view:cache -q
echo "compiled $(ls storage/framework/views/*.php 2>/dev/null | wc -l) views"

step "assets"            # after the views, deliberately -- see the note at the top
npm run build

step "caches"
# config:cache only in production. A cached config file takes precedence over the env
# vars phpunit.xml sets, so caching it on the box that runs the suite points every test
# at the real database and fails all of them for a reason that looks like a code fault.
# Route and event caches are harmless either way.
if [ "$ENV" = "production" ]; then
    "$PHP" artisan config:cache -q
    echo "config cached"
else
    "$PHP" artisan config:clear -q
    echo "config NOT cached (APP_ENV=${ENV:-unset}); the test suite needs its own env"
fi
"$PHP" artisan route:cache -q
"$PHP" artisan event:cache -q
echo "route, event cached"

step "services"
sudo -n systemctl reload "$FPM" && echo "reloaded $FPM" || echo "!! could not reload $FPM -- do it by hand"
if systemctl list-units --type=service --all 2>/dev/null | grep -q "$QUEUE"; then
    sudo -n systemctl restart "$QUEUE" && echo "restarted $QUEUE" || echo "!! could not restart $QUEUE"
fi

[ "$DOWN" = 1 ] && "$PHP" artisan up

step "done"
