#!/usr/bin/env bash
# Starts both dev servers in the background (local/Codespaces only).
set -euo pipefail
cd "$(dirname "$0")/.."

mkdir -p storage/logs
pkill -f "php -S 0.0.0.0:808[01]" 2>/dev/null || true

nohup php -S 0.0.0.0:8080 -t public bin/dev-router.php > storage/logs/site.log 2>&1 &
nohup php -S 0.0.0.0:8081 -t staff bin/dev-router.php > storage/logs/team.log 2>&1 &
sleep 1

echo "Website:     Port 8080"
echo "Teambereich: Port 8081"
echo "Logs:        storage/logs/"
