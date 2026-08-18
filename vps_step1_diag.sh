#!/bin/bash
# DIAGNOSTICO SOLO-LECTURA VPS 2.25.79.121
# Ningun comando modifica estructura ni datos (solo read)
set +e

echo "=== 1 HOSTNAME & DATE ==="
hostname
date

echo "=== 2 OS ==="
cat /etc/os-release 2>/dev/null | head -n 6

echo "=== 3 MYSQL SERVICE STATUS ==="
(systemctl is-active mariadb 2>/dev/null || true)
(systemctl is-active mysql 2>/dev/null || true)
(systemctl status mariadb --no-pager 2>&1 | head -n 25 || systemctl status mysql --no-pager 2>&1 | head -n 25 || true)

echo "=== 4 MYSQL PROCESS ==="
ps aux 2>/dev/null | grep -iE 'mysqld|mariadbd' | grep -v grep | head -n 5 || true

echo "=== 5 PORT 3306 LISTEN ==="
(ss -tlnp 2>/dev/null || netstat -tlnp 2>/dev/null) | grep -E ':3306' || echo '3306 not in listen list'

echo "=== 6 ALL LISTENING PORTS (first 30) ==="
(ss -tlnp 2>/dev/null || netstat -tlnp 2>/dev/null) | head -n 30 || true

echo "=== 7 MYSQL CONFIG BIND/SKIP/PORT ==="
grep -RinE 'bind-address|skip-networking|^port\s*=|\[mysqld\]|\[mariadb\]' /etc/mysql/ /etc/my.cnf 2>/dev/null | head -n 80 || true

echo "=== 8 MY.CNF LOCATIONS ==="
ls -la /etc/my.cnf /etc/mysql/ /etc/mysql/mariadb.conf.d/ /etc/mysql/mysql.conf.d/ 2>&1 | head -n 60 || true

echo "=== 9 UFW STATUS ==="
(which ufw >/dev/null 2>&1 && (ufw status numbered 2>&1 | head -n 60)) || echo 'ufw not present'

echo "=== 10 IPTABLES INPUT (first 60) ==="
(iptables -S INPUT 2>/dev/null | head -n 60) || echo 'iptables not readable'

echo "=== 11 MYSQL VERSION & BINARY ==="
which mysql mysqld mariadb mariadbd 2>&1 || true
(mysql --version 2>&1 || true)
(mysqld --version 2>&1 || mariadbd --version 2>&1 || true)

echo "=== 12 TRY MYSQL ROOT (NO PASSWORD FIRST) ==="
if command -v mysql >/dev/null 2>&1; then
  mysql -uroot -e "SELECT 1 AS test_connection;" 2>&1 && \
  mysql -uroot -e "SHOW DATABASES;" 2>&1 | head -n 30 && \
  mysql -uroot -e "SELECT User,Host FROM mysql.user ORDER BY User,Host;" 2>&1 | head -n 50 && \
  mysql -uroot -e "USE u423799403_eldemon777; SHOW TABLES;" 2>&1 | head -n 30 && \
  mysql -uroot -e "USE u423799403_eldemon777; DESCRIBE solicitudes;" 2>&1 | head -n 80 && \
  mysql -uroot -e "SHOW VARIABLES LIKE 'bind_address'; SHOW VARIABLES LIKE 'port'; SHOW VARIABLES LIKE 'skip_networking';" 2>&1 || \
  echo "--- root-without-pw failed, trying root@localhost with empty pw flag ---"
fi

echo "=== 13 TRY MYSQL ROOT with possible common empty + check for socket ==="
ls -la /var/run/mysqld/ /var/lib/mysql/ 2>&1 | head -n 20 || true
find / -maxdepth 4 -type f -iname '*.cnf' 2>/dev/null | head -n 20 || true

echo "DONE_DIAG_VPS"
