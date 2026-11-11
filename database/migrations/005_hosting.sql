-- 005: Gehostete Dienste (z. B. Discord-Bots) mit Laufzeit und Lizenzschlüssel
CREATE TABLE IF NOT EXISTS service_plans (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  name        TEXT    NOT NULL,
  description TEXT    NOT NULL DEFAULT '',
  price_cents INTEGER NOT NULL DEFAULT 0 CHECK (price_cents >= 0),
  term_months INTEGER NOT NULL DEFAULT 1 CHECK (term_months BETWEEN 0 AND 36),
  active      INTEGER NOT NULL DEFAULT 1,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS services (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id      INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
  plan_id          INTEGER REFERENCES service_plans(id) ON DELETE SET NULL,
  request_id       INTEGER REFERENCES requests(id) ON DELETE SET NULL,
  name             TEXT    NOT NULL,
  status           TEXT    NOT NULL DEFAULT 'active' CHECK (status IN ('active','suspended','cancelled')),
  price_cents      INTEGER NOT NULL DEFAULT 0 CHECK (price_cents >= 0),
  term_months      INTEGER NOT NULL DEFAULT 1 CHECK (term_months BETWEEN 0 AND 36),
  starts_on        TEXT    NOT NULL,
  expires_on       TEXT,
  suspended_by     TEXT    NOT NULL DEFAULT '' CHECK (suspended_by IN ('','auto','staff')),
  suspended_reason TEXT    NOT NULL DEFAULT '',
  suspended_at     TEXT,
  license_hash     TEXT    UNIQUE,
  license_hint     TEXT    NOT NULL DEFAULT '',
  last_check_at    TEXT,
  last_version     TEXT    NOT NULL DEFAULT '',
  reminded         TEXT    NOT NULL DEFAULT '',
  notes            TEXT    NOT NULL DEFAULT '',
  created_at       TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at       TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_services_customer ON services(customer_id);
CREATE INDEX IF NOT EXISTS idx_services_expiry ON services(status, expires_on);

CREATE TABLE IF NOT EXISTS service_events (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  service_id INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
  action     TEXT    NOT NULL,
  detail     TEXT    NOT NULL DEFAULT '',
  actor      TEXT    NOT NULL DEFAULT '',
  visible    INTEGER NOT NULL DEFAULT 1,
  created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_service_events ON service_events(service_id);
