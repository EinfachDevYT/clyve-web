#!/usr/bin/env bash
# Prepares a local/Codespaces dev environment. Never use on a production server.
set -euo pipefail
cd "$(dirname "$0")/.."

mkdir -p storage

url_for() {
  if [[ -n "${CODESPACE_NAME:-}" ]]; then
    echo "https://${CODESPACE_NAME}-$1.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-app.github.dev}"
  else
    echo "http://127.0.0.1:$1"
  fi
}
SITE_URL=$(url_for 8080)
STAFF_URL=$(url_for 8081)

if [[ ! -f config/config.php ]]; then
  SECRET=$(php -r 'echo bin2hex(random_bytes(32));')
  sed -e "s#'app_env' => 'production'#'app_env' => 'local'#" \
      -e "s#'site_origins'  => \['https://clyve.dev'\]#'site_origins'  => ['${SITE_URL}', 'http://127.0.0.1:8080']#" \
      -e "s#'staff_origins' => \['https://staff.clyve.dev'\]#'staff_origins' => ['${STAFF_URL}', 'http://127.0.0.1:8081']#" \
      -e "s#'CHANGE_ME'#'${SECRET}'#" \
      config/config.example.php > config/config.php
  echo "config/config.php angelegt."
fi

if [[ ! -f storage/DEV-LOGIN.txt ]]; then
  PASSWORD="Clyve-$(php -r 'echo bin2hex(random_bytes(6));')"
  STAFF_PASSWORD="$PASSWORD" php bin/create-staff.php admin@clyve.dev "Admin" admin
  cat > storage/DEV-LOGIN.txt <<EOF
Clyve.dev – Testumgebung

Website:      ${SITE_URL}
Staff-Bereich: ${STAFF_URL}/login

Staff-Login
  E-Mail:     admin@clyve.dev
  Passwort:   ${PASSWORD}

Kundenkonto: auf der Website unter /registrieren selbst anlegen.

Server neu starten:  bash bin/dev-start.sh
EOF
  chmod 600 storage/DEV-LOGIN.txt
fi

echo
cat storage/DEV-LOGIN.txt
