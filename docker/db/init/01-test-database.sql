-- Separate database used by the Pest test-suite.
CREATE DATABASE IF NOT EXISTS festes_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON festes_test.* TO 'festes'@'%';
FLUSH PRIVILEGES;
