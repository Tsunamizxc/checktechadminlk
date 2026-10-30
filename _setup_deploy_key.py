import paramiko

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect("201.24.61.23", username="root", password="s?C#KP4mr-ft.M", timeout=60)


def run(cmd, timeout=120):
    _, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    print(cmd[:120])
    print((out or err)[-2000:])
    return out


run("git config --global --add safe.directory /var/www/chectech_cur_usr/data/deploy/checktechadminlk")
run("mkdir -p /root/.ssh && chmod 700 /root/.ssh")
run("test -f /root/.ssh/lktech_deploy && echo exists || ssh-keygen -t ed25519 -f /root/.ssh/lktech_deploy -N '' -C 'chectechadm-deploy'")
pub = run("cat /root/.ssh/lktech_deploy.pub").strip()
run("""bash -lc 'grep -q lktech_deploy /root/.ssh/config 2>/dev/null || cat >> /root/.ssh/config <<EOF
Host github.com
  HostName github.com
  User git
  IdentityFile /root/.ssh/lktech_deploy
  IdentitiesOnly yes
EOF
chmod 600 /root/.ssh/config'""")
run("cd /var/www/chectech_cur_usr/data/deploy/checktechadminlk && git remote set-url origin git@github.com:Tsunamizxc/checktechadminlk.git && git remote -v")
print("PUBLIC_KEY<<" + pub + ">>")
client.close()
