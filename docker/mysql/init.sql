-- A separate database for tests, so running them never touches the data of the local application.
CREATE DATABASE IF NOT EXISTS earning_line_test;
GRANT ALL PRIVILEGES ON earning_line_test.* TO 'earning_line'@'%';
