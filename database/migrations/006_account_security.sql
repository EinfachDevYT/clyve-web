-- 006: Zwei-Faktor-Anmeldung, Sitzungen, Passwort-Reset, E-Mail-Wechsel, Anmeldeverlauf
ALTER TABLE customers ADD COLUMN totp_secret TEXT;
ALTER TABLE customers ADD COLUMN totp_enabled_at TEXT;
ALTER TABLE customers ADD COLUMN totp_last_step INTEGER NOT NULL DEFAULT 0;
ALTER TABLE customers ADD COLUMN session_version INTEGER NOT NULL DEFAULT 1;
ALTER TABLE customers ADD COLUMN password_changed_at TEXT;

ALTER TABLE staff ADD COLUMN totp_secret TEXT;
ALTER TABLE staff ADD COLUMN totp_enabled_at TEXT;
ALTER TABLE staff ADD COLUMN totp_last_step INTEGER NOT NULL DEFAULT 0;
ALTER TABLE staff ADD COLUMN session_version INTEGER NOT NULL DEFAULT 1;
ALTER TABLE staff ADD COLUMN password_changed_at TEXT;

CREATE TABLE IF NOT EXISTS recovery_codes (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  account_type TEXT    NOT NULL CHECK (account_type IN ('customer','staff')),
  account_id   INTEGER NOT NULL,
  code_hash    TEXT    NOT NULL,
  used_at      TEXT,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_recovery_account ON recovery_codes(account_type, account_id);

CREATE TABLE IF NOT EXISTS auth_tokens (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  purpose      TEXT    NOT NULL CHECK (purpose IN ('reset','invite','email')),
  account_type TEXT    NOT NULL CHECK (account_type IN ('customer','staff')),
  account_id   INTEGER NOT NULL,
  token_hash   TEXT    NOT NULL UNIQUE,
  data         TEXT    NOT NULL DEFAULT '',
  expires_at   TEXT    NOT NULL,
  used_at      TEXT,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_tokens_account ON auth_tokens(account_type, account_id, purpose);

CREATE TABLE IF NOT EXISTS account_events (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  account_type TEXT    NOT NULL CHECK (account_type IN ('customer','staff')),
  account_id   INTEGER NOT NULL,
  event        TEXT    NOT NULL,
  ip_hint      TEXT    NOT NULL DEFAULT '',
  agent        TEXT    NOT NULL DEFAULT '',
  created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_account_events ON account_events(account_type, account_id, id);
