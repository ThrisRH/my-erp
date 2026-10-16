-- Repair demo strings that were imported as UTF-8 bytes interpreted as latin1.
UPDATE personal_finance_transactions
SET notes = CONVERT(BINARY CONVERT(notes USING latin1) USING utf8mb4)
WHERE employee_id = 1
  AND id IN (
      3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19,
      20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35,
      36, 37, 38, 39
  );
