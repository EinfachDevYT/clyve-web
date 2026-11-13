-- 008: E-Mail-Bestätigung für neue Kundenkonten
ALTER TABLE customers ADD COLUMN email_verified_at TEXT;
UPDATE customers SET email_verified_at = created_at;

CREATE TABLE auth_tokens_new (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  purpose      TEXT    NOT NULL CHECK (purpose IN ('reset','invite','email','verify')),
  account_type TEXT    NOT NULL CHECK (account_type IN ('customer','staff')),
  account_id   INTEGER NOT NULL,
  token_hash   TEXT    NOT NULL UNIQUE,
  data         TEXT    NOT NULL DEFAULT '',
  expires_at   TEXT    NOT NULL,
  used_at      TEXT,
  created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);
INSERT INTO auth_tokens_new SELECT id, purpose, account_type, account_id, token_hash, data, expires_at, used_at, created_at FROM auth_tokens;
DROP TABLE auth_tokens;
ALTER TABLE auth_tokens_new RENAME TO auth_tokens;
CREATE INDEX IF NOT EXISTS idx_tokens_account ON auth_tokens(account_type, account_id, purpose);
