-- 004: ox_core wird nicht mehr als Framework angeboten
UPDATE products
SET frameworks = trim(replace(replace(',' || frameworks || ',', ',ox_core,', ','), ',,', ','), ',')
WHERE ',' || frameworks || ',' LIKE '%,ox_core,%';
