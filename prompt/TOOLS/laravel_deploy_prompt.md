# Prompt for Other Agent: Laravel + Hostinger Deploy (Different Domain)

**Goal:** Set up GitHub Actions CI/CD for a Laravel project on Hostinger (same server, different domain). Use SSH deploy + GitHub deploy key method that worked on the Iceland Beach project. Also keep WhatsApp notification system (CallMeBot) for contact form if needed.

## Context
- Hosting: Hostinger (SSH)
- Deployment: GitHub Actions + appleboy/ssh-action
- Method: Use a server-side SSH deploy key for GitHub access, then `git fetch` + `git reset --hard` on the server.
- Avoid SSH auth errors by adding the server’s public key as a **Deploy Key** in the new repo.

## What Worked in Iceland Beach
- Use `appleboy/ssh-action@master`.
- Set secrets: `HOSTINGER_SSH_HOST`, `HOSTINGER_SSH_USER`, `HOSTINGER_SSH_PORT`, `HOSTINGER_SSH_DEPLOY_PATH`, `HOSTINGER_SSH_PRIVATE_KEY`.
- On server, use `ssh-agent` and `ssh-add` the deploy key file.
- Add GitHub to `known_hosts` with `ssh-keyscan`.
- Use SSH remote: `git@github.com:${{ github.repository }}.git`.

## Deploy Workflow (Laravel)
Use this as the base:

```yml
name: Deploy Laravel to Hostinger via SSH

on:
  push:
    branches:
      - main

permissions:
  contents: write

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
        with:
          fetch-depth: 0
          persist-credentials: true

      - name: Deploy via SSH
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.HOSTINGER_SSH_HOST }}
          username: ${{ secrets.HOSTINGER_SSH_USER }}
          key: ${{ secrets.HOSTINGER_SSH_PRIVATE_KEY }}
          port: ${{ secrets.HOSTINGER_SSH_PORT }}
          script: |
            set -e
            cd "${{ secrets.HOSTINGER_SSH_DEPLOY_PATH }}"

            # 1) SSH Agent for GitHub
            eval $(ssh-agent -s)
            ssh-add ~/.ssh/id_ed25519_laravel 2>/dev/null || ssh-add ~/.ssh/id_ed25519 2>/dev/null || ssh-add ~/.ssh/id_rsa 2>/dev/null || true

            # 2) Trust GitHub
            mkdir -p ~/.ssh
            ssh-keyscan -H github.com >> ~/.ssh/known_hosts 2>/dev/null

            # 3) Sync repo
            git remote set-url origin git@github.com:${{ github.repository }}.git
            git fetch origin main
            git reset --hard origin/main

            # 4) Laravel commands
            /opt/alt/php82/usr/bin/php /usr/local/bin/composer install --no-dev --prefer-dist --no-interaction --ignore-platform-reqs
            /opt/alt/php82/usr/bin/php artisan migrate --force
            /opt/alt/php82/usr/bin/php artisan config:clear
            /opt/alt/php82/usr/bin/php artisan route:clear
            /opt/alt/php82/usr/bin/php artisan view:clear
            /opt/alt/php82/usr/bin/php artisan optimize
```

## Deploy Key Setup (server)
On Hostinger server:
1. Generate new key for this repo:
   ```bash
   ssh-keygen -t ed25519 -C "laravel-deploy" -f ~/.ssh/id_ed25519_laravel -N ""
   ```
2. Add public key to GitHub repo as Deploy Key (allow write access):
   ```bash
   cat ~/.ssh/id_ed25519_laravel.pub
   ```

## Required GitHub Secrets (new repo)
- `HOSTINGER_SSH_HOST`
- `HOSTINGER_SSH_USER`
- `HOSTINGER_SSH_PORT`
- `HOSTINGER_SSH_DEPLOY_PATH`
- `HOSTINGER_SSH_PRIVATE_KEY` (the server SSH private key used to log into Hostinger)

## Notes
- If Action fails with **Repository not found**, deploy key is missing or attached to another repo.
- If Action fails with **ssh handshake failed**, re-check `HOSTINGER_SSH_PRIVATE_KEY` formatting.
- Make sure `.env` exists on server for Laravel and is not overwritten by git.

## Optional: WhatsApp Notification (CallMeBot)
- Create `core/config/whatsapp.php` (ignored by git), use `callmebot_phone` and `callmebot_key`.
- Trigger WhatsApp after successful mail send.
