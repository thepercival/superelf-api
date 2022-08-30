-- PRE PRE PRE doctrine-update =============================================================


-- POST POST POST doctrine-update ===========================================================
-- alter table plannings ADD timeoutState varchar(20) DEFAULT NULL COMMENT '(DC2Type:enum_PlanningTimeoutState)';

-- INITIAL DB STEPS
--      INSERT INTO externalSystems (name, website, username, password, apiurl, apikey) VALUES ('mySystem', 'https://mySystem.com/', null, null, 'https://api.mySystem.com/', null);
--      INSERT INTO sports(name, team, defaultGameMode, defaultNrOfSidePlaces, customId) values ('superelf', false, 3, 0, 0);
--      update sports set customId = 11 where name = 'football';

-- CUSTOM IMPORT =============================
--      mysqldump -u superelf_a_dba -p superelfacc table1 table2 > se_dumpfile.sql
--      mysql -u superelf_dba -p superelf < se_dumpfile.sql
