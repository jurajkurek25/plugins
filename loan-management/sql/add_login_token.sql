-- Migrácia: Pridanie login tokenu pre QR kód prihlásenie
-- Vytvorené: 2026-01-07

-- Pridanie stĺpca pre login token
ALTER TABLE `users`
ADD COLUMN `login_token` VARCHAR(64) NULL UNIQUE AFTER `password_hash`,
ADD INDEX `idx_login_token` (`login_token`);

-- Vygenerovanie tokenov pre existujúcich používateľov
-- Tento skript môžete spustiť ručne alebo cez migračný systém
