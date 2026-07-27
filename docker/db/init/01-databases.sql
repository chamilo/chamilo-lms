-- Chamilo needs two schemas: the live install (`chamilo`, created by the image
-- from MARIADB_DATABASE) and the PHPUnit database.
--
-- Note the doubled suffix. config/packages/doctrine.yaml has
--     when@test: dbal: dbname_suffix: '_test%env(default::TEST_TOKEN)%'
-- so DATABASE_NAME=chamilo_test in .env.test.local resolves to the physical
-- database `chamilo_test_test`. Keeping DATABASE_NAME at `chamilo_test` rather
-- than `chamilo` is deliberate: if the suffix ever stopped being applied, the
-- suite would fall back to an empty scratch database instead of dropping the
-- live one.
CREATE DATABASE IF NOT EXISTS `chamilo`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `chamilo_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `chamilo_test_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `chamilo`.* TO 'chamilo'@'%';
-- Wildcard covers chamilo_test, chamilo_test_test and the per-worker
-- chamilo_test_test<TEST_TOKEN> databases ParaTest would create.
-- Backslash escapes the underscore, which is a LIKE wildcard in grants.
GRANT ALL PRIVILEGES ON `chamilo\_%`.* TO 'chamilo'@'%';
FLUSH PRIVILEGES;
