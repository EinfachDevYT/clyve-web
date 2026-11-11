-- 001: Grundschema
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS customers (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
  name          TEXT    NOT NULL,
  password_hash TEXT    NOT NULL,
  discord       TEXT    NOT NULL DEFAULT '',
  company       TEXT    NOT NULL DEFAULT '',
  street        TEXT    NOT NULL DEFAULT '',
  zip           TEXT    NOT NULL DEFAULT '',
  city          TEXT    NOT NULL DEFAULT '',
  country       TEXT    NOT NULL DEFAULT 'Deutschland',
  created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS staff (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  email         TEXT    NOT NULL UNIQUE COLLATE NOCASE,
  name          TEXT    NOT NULL,
  password_hash TEXT    NOT NULL,
  role          TEXT    NOT NULL DEFAULT 'staff' CHECK (role IN ('admin','staff')),
  active        INTEGER NOT NULL DEFAULT 1,
  created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS requests (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  public_id   TEXT    NOT NULL UNIQUE,
  customer_id INTEGER REFERENCES customers(id) ON DELETE SET NULL,
  type        TEXT    NOT NULL,
  name        TEXT    NOT NULL,
  title       TEXT    NOT NULL DEFAULT '',
  discord     TEXT    NOT NULL DEFAULT '',
  email       TEXT    NOT NULL DEFAULT '',
  budget      TEXT    NOT NULL DEFAULT '',
  deadline    TEXT    NOT NULL DEFAULT '',
  message     TEXT    NOT NULL,
  status      TEXT    NOT NULL DEFAULT 'new' CHECK (status IN ('new','contacted','accepted','declined','done')),
  created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS tickets (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
  subject     TEXT    NOT NULL,
  category    TEXT    NOT NULL DEFAULT 'general',
  status      TEXT    NOT NULL DEFAULT 'open' CHECK (status IN ('open','answered','closed')),
  created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_tickets_customer ON tickets(customer_id);

CREATE TABLE IF NOT EXISTS ticket_messages (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_id   INTEGER NOT NULL REFERENCES tickets(id) ON DELETE CASCADE,
  author_type TEXT    NOT NULL CHECK (author_type IN ('customer','staff')),
  author_id   INTEGER NOT NULL,
  body        TEXT    NOT NULL,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_msgs_ticket ON ticket_messages(ticket_id);

-- Rechnungen: Entwürfe sind änderbar. Sobald "issued", werden Nummer, Datum,
-- Empfänger und Positionen eingefroren (GoBD). Korrektur nur über Storno.
CREATE TABLE IF NOT EXISTS invoices (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  number         TEXT    UNIQUE,
  customer_id    INTEGER NOT NULL REFERENCES customers(id) ON DELETE RESTRICT,
  status         TEXT    NOT NULL DEFAULT 'draft' CHECK (status IN ('draft','issued','paid','cancelled')),
  title          TEXT    NOT NULL DEFAULT '',
  notes          TEXT    NOT NULL DEFAULT '',
  issue_date     TEXT,
  service_date   TEXT    NOT NULL DEFAULT '',
  due_date       TEXT,
  recipient_json TEXT,
  sender_json    TEXT,
  total_cents    INTEGER NOT NULL DEFAULT 0,
  paid_at        TEXT,
  cancelled_at   TEXT,
  created_by     INTEGER REFERENCES staff(id),
  created_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_invoices_customer ON invoices(customer_id);

CREATE TABLE IF NOT EXISTS invoice_items (
  id               INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_id       INTEGER NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
  position         INTEGER NOT NULL DEFAULT 0,
  description      TEXT    NOT NULL,
  quantity         REAL    NOT NULL DEFAULT 1,
  unit             TEXT    NOT NULL DEFAULT 'Stk.',
  unit_price_cents INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS invoice_counter (
  year INTEGER PRIMARY KEY,
  last INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
  id         INTEGER PRIMARY KEY AUTOINCREMENT,
  actor      TEXT NOT NULL,
  action     TEXT NOT NULL,
  subject    TEXT NOT NULL DEFAULT '',
  created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
