"""Sync theme/plugin from this repo to production over SSH/SFTP."""
from __future__ import annotations

import os
import posixpath
from pathlib import Path

import paramiko

HOST = os.environ.get("LKTECH_SSH_HOST", "201.24.61.23")
USER = os.environ.get("LKTECH_SSH_USER", "root")
PASSWORD = os.environ.get("LKTECH_SSH_PASS", "")
if not PASSWORD:
    raise SystemExit("Set LKTECH_SSH_PASS env var before publish")
REMOTE_WP = "/var/www/chectech_cur_usr/data/www/chectechadm.cursiva-dev.ru"
REMOTE_REPO = "/var/www/chectech_cur_usr/data/deploy/checktechadminlk"
ROOT = Path(__file__).resolve().parents[1]

PATHS = [
    "wp-content/themes/lktech-crm",
    "wp-content/plugins/lktech-crm",
    "deploy",
]


def upload_dir(sftp: paramiko.SFTPClient, local: Path, remote: str) -> None:
    try:
        sftp.stat(remote)
    except FileNotFoundError:
        # create nested
        parts = remote.strip("/").split("/")
        cur = ""
        for p in parts:
            cur += "/" + p
            try:
                sftp.stat(cur)
            except FileNotFoundError:
                sftp.mkdir(cur)

    for root, dirs, files in os.walk(local):
        rel = os.path.relpath(root, local).replace("\\", "/")
        rdir = remote if rel == "." else posixpath.join(remote, rel)
        try:
            sftp.stat(rdir)
        except FileNotFoundError:
            sftp.mkdir(rdir)
        for name in files:
            lp = Path(root) / name
            rp = posixpath.join(rdir, name)
            sftp.put(str(lp), rp)


def main() -> None:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(HOST, username=USER, password=PASSWORD, timeout=60)
    sftp = client.open_sftp()
    for rel in PATHS:
        local = ROOT / rel
        print("upload", rel)
        upload_dir(sftp, local, posixpath.join(REMOTE_REPO, rel.replace("\\", "/")))
        if rel.startswith("wp-content/"):
            upload_dir(sftp, local, posixpath.join(REMOTE_WP, rel.replace("\\", "/")))
        if rel == "deploy":
            upload_dir(sftp, local, posixpath.join(REMOTE_WP, "deploy"))
    sftp.close()
    cmds = [
        f"chmod +x {REMOTE_REPO}/deploy/sync.sh {REMOTE_WP}/deploy/sync.sh || true",
        f"chown -R chectech_cur_usr:chectech_cur_usr {REMOTE_WP}/wp-content/themes/lktech-crm {REMOTE_WP}/wp-content/plugins/lktech-crm {REMOTE_WP}/deploy",
        f"git -C {REMOTE_REPO} config --global --add safe.directory {REMOTE_REPO} || true",
    ]
    for cmd in cmds:
        _, out, err = client.exec_command(cmd, timeout=60)
        print(out.read().decode("utf-8", "replace"), err.read().decode("utf-8", "replace"))
    client.close()
    print("published")


if __name__ == "__main__":
    main()
