# Deployment

The application is deployed to a single host, served behind **nginx** (TLS via
**Let's Encrypt / certbot**) which reverse-proxies to the Docker Compose stack.

- Live: **https://library-api.opcode.me.uk** (docs at `/docs`)
- Runtime: `docker compose` on the host (`/home/ubuntu/library-api`), app bound to
  `127.0.0.1:8088` by `docker-compose.override.yml`; nginx proxies `:443` → that port.
- Delivery: the `deploy` job in `.github/workflows/ci.yml` runs after the
  `quality` and `tests` jobs pass on `main`, connects over SSH, and runs
  `git pull` + `docker compose up -d --build` (migrations run on container start).

## ⚠️ SSH key hygiene

Never paste a private key into a chat, an issue, or a commit. A key that has been
exposed must be rotated:

```bash
ssh-keygen -t ed25519 -C "library-api-deploy" -f deploy_key   # new keypair
# add deploy_key.pub to the server's ~/.ssh/authorized_keys, remove the old key
```

The **private** key belongs only in the GitHub Actions secret store, added by you:

```bash
gh secret set DEPLOY_SSH_KEY < deploy_key          # the NEW private key
gh secret set DEPLOY_HOST   --body "51.68.136.139" # server IP
gh secret set DEPLOY_USER   --body "ubuntu"
gh variable set DEPLOY_ENABLED --body "true"        # opt-in switch for the deploy job
```

`DEPLOY_ENABLED` gates the deploy job, so the pipeline stays green until you turn
it on.

## First-time server setup

Docker + the Compose plugin must already be installed. Then, on the server:

```bash
git clone https://github.com/jacekpl/library-api.git /home/ubuntu/library-api
cd /home/ubuntu/library-api
DOMAIN=library-api.opcode.me.uk CERTBOT_EMAIL=you@example.com ./deploy/setup-server.sh
```

`deploy/setup-server.sh` installs nginx + certbot, installs
`deploy/nginx/library-api.opcode.me.uk.conf`, obtains the certificate (upgrading
the site to HTTPS with an HTTP→HTTPS redirect), and starts the stack.

## Continuous deployment

Once the secrets and `DEPLOY_ENABLED=true` are in place, every push to `main`
that passes CI deploys automatically. The `deploy` job:

1. writes the SSH key from the secret to the runner,
2. connects to `DEPLOY_USER@DEPLOY_HOST`,
3. `git pull --ff-only && docker compose up -d --build && docker image prune -f`.

## Notes

- For a real production deployment, set a strong `APP_SECRET` on the host
  (e.g. export it before `docker compose up`, or via a compose env file) instead
  of the demo default in `docker-compose.yml`.
- nginx forwards `X-Forwarded-*` headers; if you later generate absolute URLs,
  configure Symfony `framework.trusted_proxies` accordingly.
