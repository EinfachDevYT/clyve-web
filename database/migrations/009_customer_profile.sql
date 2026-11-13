-- Customer master data: salutation, split name, phone, structured address, VAT id
ALTER TABLE customers ADD COLUMN salutation TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN first_name TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN last_name TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN phone TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN house_number TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN address_extra TEXT NOT NULL DEFAULT '';
ALTER TABLE customers ADD COLUMN vat_id TEXT NOT NULL DEFAULT '';

UPDATE customers SET
  first_name = CASE WHEN instr(trim(name), ' ') > 0 THEN substr(trim(name), 1, instr(trim(name), ' ') - 1) ELSE trim(name) END,
  last_name  = CASE WHEN instr(trim(name), ' ') > 0 THEN trim(substr(trim(name), instr(trim(name), ' ') + 1)) ELSE '' END;
