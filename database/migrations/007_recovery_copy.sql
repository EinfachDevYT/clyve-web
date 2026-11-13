-- 007: Verschlüsselte Kopie der Wiederherstellungscodes (nur Admins, nur für sich selbst abrufbar)
ALTER TABLE recovery_codes ADD COLUMN code_enc TEXT;
