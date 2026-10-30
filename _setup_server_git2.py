import os
import tarfile
import paramiko

HOST = "201.24.61.23"
PASS = "s?C#KP4mr-ft.M"
LOCAL = r"C:\Users\it13\Desktop\lktechsite"
TAR = r"C:\Users\it13\Desktop\lktechsite\_repo_upload.tar"
DEPLOY_DIR = "/var/www/chectech_cur_usr/data/deploy/checktechadminlk"
WP = "/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru"
SECRET = "lktech_deploy_7f3c9a2e1b"

# pack repo without junk
with tarfile.open(TAR, "w") as tar:
    for root, dirs, files in os.walk(LOCAL):
        dirs[:] = [d for d in dirs if d not in {".idea", "node_modules"} and not d.startswith("_")]
        for name in files:
            if name.startswith("_") or name.endswith((".tar", ".bundle", ".sql")):
                continue
            path = os.path.join(root, name)
            arc = os.path.relpath(path, LOCAL).replace("\\", "/")
            tar.add(path, arcname=arc)

print("tar", os.path.getsize(TAR))

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username="root", password=PASS, timeout=60)


def run(cmd, timeout=300):
    print(">>", cmd[:160])
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print((out + ("\nERR:" + err if err.strip() else ""))[-2500:])
    return out


run(f"rm -rf {DEPLOY_DIR} && mkdir -p {DEPLOY_DIR}")
sftp = client.open_sftp()
sftp.put(TAR, "/tmp/checktechadminlk.tar")
sftp.close()
run(f"tar -xf /tmp/checktechadminlk.tar -C {DEPLOY_DIR}")
run(f"cd {DEPLOY_DIR} && git status -sb && git remote -v | head")
run(f"chmod +x {DEPLOY_DIR}/deploy/sync.sh")

# update sync.sh to soft-fail pull for private repos, then rsync
sync = f"""#!/usr/bin/env bash
set -euo pipefail
REPO_DIR="${{REPO_DIR:-{DEPLOY_DIR}}}"
WP_ROOT="${{WP_ROOT:-{WP}}}"
cd "$REPO_DIR"
if [ -d .git ]; then
  git fetch --all || true
  git reset --hard origin/main || git reset --hard main || true
fi
rsync -a --delete "$REPO_DIR/wp-content/themes/lktech-crm/" "$WP_ROOT/wp-content/themes/lktech-crm/"
rsync -a --delete "$REPO_DIR/wp-content/plugins/lktech-crm/" "$WP_ROOT/wp-content/plugins/lktech-crm/"
mkdir -p "$WP_ROOT/deploy"
cp -f "$REPO_DIR/deploy/hook.php" "$WP_ROOT/deploy/hook.php"
cp -f "$REPO_DIR/deploy/sync.sh" "$WP_ROOT/deploy/sync.sh"
chmod +x "$WP_ROOT/deploy/sync.sh" "$REPO_DIR/deploy/sync.sh"
chown -R chectech_cur_usr:chectech_cur_usr \\
  "$WP_ROOT/wp-content/themes/lktech-crm" \\
  "$WP_ROOT/wp-content/plugins/lktech-crm" \\
  "$WP_ROOT/deploy"
echo "synced $(date -u +%Y-%m-%dT%H:%M:%SZ)"
"""
import base64
b64 = base64.b64encode(sync.encode()).decode()
run(f"echo {b64} | base64 -d > {DEPLOY_DIR}/deploy/sync.sh && chmod +x {DEPLOY_DIR}/deploy/sync.sh")
run(f"REPO_DIR={DEPLOY_DIR} WP_ROOT={WP} bash {DEPLOY_DIR}/deploy/sync.sh")

# check if github zip is public for future pulls
run("curl -sI https://github.com/Tsunamizxc/checktechadminlk/archive/refs/heads/main.zip | head -15")

# improve sync to also try zip if public
run(f"curl -s -X POST 'https://chectechadm.cursiva-dev.ru/deploy/hook.php' -H 'X-Deploy-Secret: {SECRET}'")
run("grep -n feed /var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru/wp-content/plugins/lktech-crm/includes/api.php | head")
run("grep -n 'lk-app' /var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru/wp-content/themes/lktech-crm/templates/dashboard.php | head")
run("curl -s https://chectechadm.cursiva-dev.ru/wp-content/themes/lktech-crm/assets/css/crm.css | head -c 200")

client.close()
print("DONE")
