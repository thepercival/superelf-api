-- PRE PRE PRE doctrine-update =============================================================

-- POST POST POST doctrine-update ===========================================================
INSERT INTO externalsystems (name,website,username,password,apiurl,apikey) VALUES ('SofaScore','https://www.sofascore.com/',null,null,'https://www.sofascore.com/',null);

INSERT INTO sports()
INSERT INTO formations(name, sportId)
-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
