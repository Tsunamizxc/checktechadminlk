import paramiko

HOST = "201.24.61.23"
PASS = "s?C#KP4mr-ft.M"
REPO = "https://github.com/Tsunamizxc/checktechadminlk.git"
DEPLOY_DIR = "/var/www/chectech_cur_usr/data/deploy/checktechadminlk"
WP = "/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru"
SECRET = "lktech_deploy_7f3c9a2e1b"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username="root", password=PASS, timeout=60)


def run(cmd, timeout=300):
    print(">>", cmd[:180])
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    text = (out + ("\n" + err if err.strip() else ""))[-3000:]
    print(text)
    return out, err


run("which git rsync || (apt-get update -qq && apt-get install -y -qq git rsync)")
run(f"mkdir -p /var/www/chectech_cur_usr/data/deploy && chown -R chectech_cur_usr:chectech_cur_usr /var/www/chectech_cur_usr/data/deploy")
run(f"if [ -d {DEPLOY_DIR}/.git ]; then cd {DEPLOY_DIR} && git fetch --all && git reset --hard origin/main; else git clone {REPO} {DEPLOY_DIR}; fi")
run(f"chmod +x {DEPLOY_DIR}/deploy/sync.sh")
run(f"REPO_DIR={DEPLOY_DIR} WP_ROOT={WP} bash {DEPLOY_DIR}/deploy/sync.sh")

# public webhook in WP root (and keep secret)
run(f"cp {DEPLOY_DIR}/deploy/hook.php {WP}/deploy-hook.php")
run(f"cp {DEPLOY_DIR}/deploy/sync.sh {WP}/../deploy-sync.sh 2>/dev/null || true")
# place sync next to hook for relative path? hook uses __DIR__/sync.sh — copy both into a deploy folder under site
run(f"mkdir -p {WP}/deploy && cp {DEPLOY_DIR}/deploy/hook.php {WP}/deploy/hook.php && cp {DEPLOY_DIR}/deploy/sync.sh {WP}/deploy/sync.sh && chmod +x {WP}/deploy/sync.sh")
run(f"chown -R chectech_cur_usr:chectech_cur_usr {WP}/deploy {WP}/wp-content/themes/lktech-crm {WP}/wp-content/plugins/lktech-crm")

# cron every minute
cron = f"* * * * * REPO_DIR={DEPLOY_DIR} WP_ROOT={WP} /bin/bash {DEPLOY_DIR}/deploy/sync.sh >> /var/log/lktech-deploy.log 2>&1"
run("(crontab -l 2>/dev/null | grep -v lktech-deploy; echo '%s') | crontab -" % cron)
run("crontab -l | grep lktech || true")

# webhook test
run(f"curl -s -X POST 'https://chectechadm.cursiva-dev.ru/deploy/hook.php' -H 'X-Deploy-Secret: {SECRET}'")
run(f"curl -s 'https://chectechadm.cursiva-dev.ru/wp-json/lktech/v1/health'")
run(f"ls {WP}/wp-content/themes/lktech-crm/assets/css/crm.css {WP}/wp-content/plugins/lktech-crm/includes/api.php")

client.close()
print("SERVER OK")
