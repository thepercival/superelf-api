-- PRE PRE PRE doctrine-update =============================================================

-- insert into leagues(name, abbreviation, associationId) ( select 'SuperCup', null, a.id from associations a where exists ( select * from poolCollections pc where pc.associationId = a.id) )


-- POST POST POST doctrine-update ===========================================================
-- update qualifyGroups set distribution = 'horizontalSnake';

-- INITIAL DB STEPS
--      INSERT INTO externalSystems (name, website, username, password, apiurl, apikey) VALUES ('mySystem', 'https://mySystem.com/', null, null, 'https://api.mySystem.com/', null);
--      INSERT INTO sports(name, team, defaultGameMode, defaultNrOfSidePlaces, customId) values ('superelf', false, 3, 0, 0);
--      update sports set customId = 11 where name = 'football';

-- CUSTOM IMPORT =============================
--      mysqldump -u superelf_a_dba -p superelfacc table1 table2 > se_dumpfile.sql
--      mysql -u superelf_dba -p superelf < se_dumpfile.sql
