-- Closed accounts that had to be kept for receipts are anonymised and marked here
ALTER TABLE customers ADD COLUMN deleted_at TEXT;
