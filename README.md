# checktechadminlk

CRM личный кабинет LK Tech (WordPress theme + plugin).

## Production

https://chectechadm.cursiva-dev.ru/

## Структура

- `wp-content/themes/lktech-crm` — тема кабинета
- `wp-content/plugins/lktech-crm` — плагин заявок/API
- `deploy/` — автодеплой на хостинг

## Деплой

После `git push` GitHub webhook (или cron) вызывает `deploy/hook.php` на сервере — `git pull` и синхронизация theme/plugin в WordPress.
