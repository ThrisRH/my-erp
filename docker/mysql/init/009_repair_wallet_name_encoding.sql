-- The initial wallet migration may be run by a MySQL CLI session without a utf8mb4 client charset.
-- Repair only the known seed value, preserving user-created wallet names.
SET NAMES utf8mb4;

UPDATE personal_finance_wallets
SET name = CONVERT(BINARY CONVERT(name USING latin1) USING utf8mb4)
WHERE HEX(name) = '5469C3A1C2BBC2816E206DC3A1C2BAC2B774';
