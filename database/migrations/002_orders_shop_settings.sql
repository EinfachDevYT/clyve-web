-- 002: Auftragsablauf (Nachrichten, Angebote), Script-Shop, Einstellungen

CREATE TABLE requests_new (
  id                INTEGER PRIMARY KEY AUTOINCREMENT,
  public_id         TEXT    NOT NULL UNIQUE,
  customer_id       INTEGER REFERENCES customers(id) ON DELETE SET NULL,
  type              TEXT    NOT NULL,
  name              TEXT    NOT NULL,
  title             TEXT    NOT NULL DEFAULT '',
  discord           TEXT    NOT NULL DEFAULT '',
  email             TEXT    NOT NULL DEFAULT '',
  budget            TEXT    NOT NULL DEFAULT '',
  deadline          TEXT    NOT NULL DEFAULT '',
  message           TEXT    NOT NULL,
  details_json      TEXT    NOT NULL DEFAULT '{}',
  access_token_salt TEXT,
  access_token_hash TEXT    UNIQUE,
  status            TEXT    NOT NULL DEFAULT 'new' CHECK (status IN
                      ('new','in_review','offer_sent','accepted','in_progress','delivered','done','declined','cancelled')),
  progress          INTEGER NOT NULL DEFAULT 0 CHECK (progress BETWEEN 0 AND 100),
  unread_staff      INTEGER NOT NULL DEFAULT 1,
  unread_customer   INTEGER NOT NULL DEFAULT 0,
  created_at        TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at        TEXT    NOT NULL DEFAULT (datetime('now'))
);

INSERT INTO requests_new (id, public_id, customer_id, type, name, title, discord, email, budget, deadline, message, status, created_at, updated_at)
SELECT id, public_id, customer_id, type, name, title, discord, email, budget, deadline, message,
       CASE status WHEN 'contacted' THEN 'in_review' ELSE status END, created_at, created_at
FROM requests;

DROP TABLE requests;
ALTER TABLE requests_new RENAME TO requests;
CREATE INDEX idx_requests_customer ON requests(customer_id);
CREATE INDEX idx_requests_status ON requests(status);

CREATE TABLE request_messages (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  request_id  INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
  author_type TEXT    NOT NULL CHECK (author_type IN ('customer','staff','system')),
  author_id   INTEGER,
  body        TEXT    NOT NULL,
  internal    INTEGER NOT NULL DEFAULT 0,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX idx_request_messages ON request_messages(request_id);

CREATE TABLE request_offers (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  request_id    INTEGER NOT NULL REFERENCES requests(id) ON DELETE CASCADE,
  amount_cents  INTEGER NOT NULL,
  delivery      TEXT    NOT NULL DEFAULT '',
  description   TEXT    NOT NULL,
  status        TEXT    NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','accepted','declined','withdrawn')),
  created_by    INTEGER REFERENCES staff(id),
  created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
  responded_at  TEXT
);
CREATE INDEX idx_request_offers ON request_offers(request_id);

ALTER TABLE invoices ADD COLUMN request_id INTEGER REFERENCES requests(id) ON DELETE SET NULL;

CREATE TABLE settings (
  key        TEXT PRIMARY KEY,
  value      TEXT NOT NULL,
  updated_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE products (
  id          INTEGER PRIMARY KEY AUTOINCREMENT,
  tebex_id    INTEGER UNIQUE,
  title       TEXT    NOT NULL,
  category    TEXT    NOT NULL DEFAULT '',
  summary     TEXT    NOT NULL DEFAULT '',
  description TEXT    NOT NULL DEFAULT '',
  price_label TEXT    NOT NULL DEFAULT '',
  image_url   TEXT    NOT NULL DEFAULT '',
  tebex_url   TEXT    NOT NULL DEFAULT '',
  frameworks  TEXT    NOT NULL DEFAULT '',
  active      INTEGER NOT NULL DEFAULT 1,
  featured    INTEGER NOT NULL DEFAULT 0,
  sort        INTEGER NOT NULL DEFAULT 0,
  created_at  TEXT    NOT NULL DEFAULT (datetime('now')),
  updated_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);
