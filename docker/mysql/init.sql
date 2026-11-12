-- A separate database for tests, so running them never touches the data of the local application.
CREATE DATABASE IF NOT EXISTS earning_line_test;
GRANT ALL PRIVILEGES ON earning_line_test.* TO 'earning_line'@'%';

-- The migrations create triggers. With binary logging on, MySQL lets a user without SUPER do that only with this setting.
-- Docker Compose also sets it at start, for volumes created before this line existed; CI relies on this file.
SET PERSIST log_bin_trust_function_creators = 1;
