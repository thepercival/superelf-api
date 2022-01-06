-- PRE PRE PRE doctrine-update =============================================================



-- POST POST POST doctrine-update ===========================================================
INSERT INTO externalSystems (name,website,username,password,apiurl,apikey) VALUES ('SofaScore','https://www.sofascore.com/',null,null,'https://api.sofascore.com/api/v1/',null);


INSERT INTO sports(name,team,defaultGameMode,defaultNrOfSidePlaces, customId) values('superelf', false, 3, 0, 0);

-- ONE TIME AFTER IMPORT SPORTS
update sports set customId = 11 where name = 'football';

-- INSERT INTO formations(name, sportId)
-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
