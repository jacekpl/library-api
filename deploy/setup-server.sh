#!/usr/bin/env bash
#
# One-time server bootstrap for the production host.
# Installs nginx + certbot, wires the reverse proxy, obtains a TLS certificate
# and starts the application with Docker Compose.
#
# Run it ON the server (as a sudo-capable user), e.g.:
#   DOMAIN=library-api.opcode.me.uk CERTBOT_EMAIL=you@example.com ./deploy/setup-server.sh
#
set -euo pipefail

DOMAIN="${DOMAIN:-library-api.opcode.me.uk}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:?Set CERTBOT_EMAIL to a valid email address for TLS registration}"
APP_DIR="${APP_DIR:-/home/ubuntu/library-api}"
REPO="${REPO:-https://github.com/jacekpl/library-api.git}"

echo ">> Checking Docker"
command -v docker >/dev/null || { echo "Docker is required but not installed"; exit 1; }
docker compose version >/dev/null || { echo "Docker Compose plugin is required"; exit 1; }

echo ">> Installing nginx and certbot"
sudo apt-get update
sudo apt-get install -y nginx certbot python3-certbot-nginx

echo ">> Fetching the application into ${APP_DIR}"
if [ -d "${APP_DIR}/.git" ]; then
    git -C "${APP_DIR}" pull --ff-only
else
    git clone "${REPO}" "${APP_DIR}"
fi

echo ">> Installing the nginx site"
sudo cp "${APP_DIR}/deploy/nginx/${DOMAIN}.conf" "/etc/nginx/sites-available/${DOMAIN}"
sudo ln -sf "/etc/nginx/sites-available/${DOMAIN}" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo nginx -t
sudo systemctl reload nginx

echo ">> Obtaining/renewing the TLS certificate"
sudo certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect

echo ">> Starting the application"
cd "${APP_DIR}"
docker compose up -d --build

echo ">> Done. https://${DOMAIN}/docs should now be live."
