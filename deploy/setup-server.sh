#!/usr/bin/env bash
#
# One-time server bootstrap for the production host.
# Installs Docker, rsync, nginx and certbot, then wires nginx as a TLS reverse
# proxy in front of the app. It does NOT fetch or start the app itself — the CI
# "Deploy" job ships the code with rsync and runs docker compose.
#
# Run it ON the server (as a sudo-capable user), e.g.:
#   DOMAIN=library-api.opcode.me.uk CERTBOT_EMAIL=you@example.com ./deploy/setup-server.sh
#
set -euo pipefail

DOMAIN="${DOMAIN:-library-api.opcode.me.uk}"
CERTBOT_EMAIL="${CERTBOT_EMAIL:?Set CERTBOT_EMAIL to a valid email address for TLS registration}"
APP_PORT="${APP_PORT:-8088}"

echo ">> Installing curl, rsync, nginx, certbot"
sudo apt-get update
sudo apt-get install -y curl rsync nginx certbot python3-certbot-nginx

echo ">> Installing Docker (if missing)"
if ! command -v docker >/dev/null; then
    curl -fsSL https://get.docker.com | sudo sh
    sudo usermod -aG docker "$USER"
fi

echo ">> Configuring the nginx reverse proxy for ${DOMAIN}"
sudo tee "/etc/nginx/sites-available/${DOMAIN}" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    location / {
        proxy_pass http://127.0.0.1:${APP_PORT};
        proxy_http_version 1.1;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
NGINX
sudo ln -sf "/etc/nginx/sites-available/${DOMAIN}" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo nginx -t
sudo systemctl reload nginx

echo ">> Obtaining the TLS certificate (needs ${DOMAIN} to resolve to this host)"
sudo certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect

echo ">> Server is ready. Trigger the CI 'Deploy' job (or push to main) to ship the app."
echo ">> It will then be served at https://${DOMAIN}/docs"
