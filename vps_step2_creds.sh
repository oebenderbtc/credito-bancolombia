#!/bin/bash
# PASO 2: Asegurar password usuario MySQL u423799403_eldemon777@'%' = 777Eldemon
# 0 cambios en estructura/tablas/datos — solo credenciales
set +e

NEWPASS='777Eldemon'
U='u423799403_eldemon777'
DB='u423799403_eldemon777'

echo "=== SHOW USERS (antes) ==="
mysql -uroot -e "SELECT User,Host FROM mysql.user WHERE User = '${U}' ORDER BY Host;" 2>&1

echo "=== Habilitamos CREATE USER IF NOT EXISTS y GRANTs (sin cambiar nada si ya está) ==="
mysql -uroot -e "
CREATE USER IF NOT EXISTS '${U}'@'%' IDENTIFIED BY '${NEWPASS}';
" 2>&1

echo "=== FORZAMOS password via ALTER USER para asegurar 777Eldemon ==="
mysql -uroot -e "
ALTER USER '${U}'@'%' IDENTIFIED BY '${NEWPASS}';
FLUSH PRIVILEGES;
" 2>&1

echo "=== GRANTs ==="
mysql -uroot -e "
GRANT ALL PRIVILEGES ON ${DB}.* TO '${U}'@'%';
FLUSH PRIVILEGES;
SHOW GRANTS FOR '${U}'@'%';
" 2>&1

echo "=== Mostramos SHOW CREATE TABLE solicitudes (solo lectura) ==="
mysql -uroot -D"${DB}" -e "SHOW CREATE TABLE solicitudes\G" 2>&1 | head -n 60

echo "=== TEST CONEXION DESDE EL VPS MISMO con el usuario (SELECT solo) ==="
mysql -h 127.0.0.1 -P 3306 -u"${U}" -p"${NEWPASS}" -e "SELECT COUNT(*) AS total_solicitudes FROM ${DB}.solicitudes;" 2>&1

echo "DONE_STEP2_VPS"
