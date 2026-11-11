-- 003: Belegnummern pro Serie (z. B. Q-2026, CLY-2026), damit Quittungen und Rechnungen getrennt zählen

CREATE TABLE invoice_series (
  series TEXT PRIMARY KEY,
  last   INTEGER NOT NULL
);

INSERT INTO invoice_series (series, last) SELECT 'legacy-' || year, last FROM invoice_counter;
DROP TABLE invoice_counter;
