# checktechadminlk

CRM личный кабинет LK Tech (WordPress theme + plugin).

## Production

https://chectechadm.cursiva-dev.ru/

## Структура

- `wp-content/themes/lktech-crm` — тема кабинета
- `wp-content/plugins/lktech-crm` — плагин заявок/API
- `deploy/` — автодеплой на хостинг

## Деплой

### Вариант A — GitHub Actions (рекомендуется)
В Secrets репозитория добавьте:
- `SERVER_HOST` = `201.24.61.23`
- `SERVER_SSH_KEY` = приватный SSH-ключ сервера (для root)

Каждый `git push` в `main` сам обновит тему/плагин на https://chectechadm.cursiva-dev.ru/

### Вариант B — локальная публикация
```powershell
$env:LKTECH_SSH_PASS="..."
py -3 deploy/publish.py
```

### Вариант C — webhook
`POST https://chectechadm.cursiva-dev.ru/deploy/hook.php`  
Header: `X-Deploy-Secret: lktech_deploy_7f3c9a2e1b`

Для `git pull` на сервере добавьте Deploy Key (read-only) в GitHub:

`ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIOcXGt5VCOd2hxUbSYiqDSSQeJT/d/zg6kH09Iy0b1Ob chectechadm-deploy`
