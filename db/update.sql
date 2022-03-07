-- PRE PRE PRE doctrine-update =============================================================
update gameAmountConfigs
set amount = nrOfGamesPerPlaceMixed
where amount = 0;

alter table planningConfigs
    drop gamePlaceStrategy;
alter table planningSchedules
    drop gamePlaceStrategy;
alter table planningInputs
    drop gamePlaceStrategy;

delete
from planningSchedules;
delete
from planningInputs
where uniqueString like '%rndm%';
update planningInputs
set uniqueString = replace(uniqueString, '- gpstrat=>eql ', '');
delete
from planningInputs
where uniqueString like '%&%';
alter table plannings
    ADD timeoutState varchar(20) DEFAULT NULL COMMENT '(DC2Type:enum_PlanningTimeoutState)';


-- POST POST POST doctrine-update ===========================================================
-- STEP 2
INSERT INTO externalSystems (name, website, username, password, apiurl, apikey)
VALUES ('SofaScore', 'https://www.sofascore.com/', null, null, 'https://api.sofascore.com/api/v1/', null);

-- STEP 4 : ONE TIME AFTER IMPORT SPORTS
INSERT INTO sports(name,team,defaultGameMode,defaultNrOfSidePlaces, customId) values('superelf', false, 3, 0, 0);
update sports set customId = 11 where name = 'football';

-- INSERT INTO formations(name, sportId)
-- php bin/console.php app:create-default-planning-input --placesRange=2-4 --sendCreatePlanningMessage=true

-- CUSTOM IMPORT =============================
-- mysqldump -u fctoernooi_a_dba -p fctoernooiacc planninginputs plannings planningsports planningfields planningpoules planningplaces planningreferees planninggames planninggameplaces > planninginputs.sql
-- mysql -u fctoernooi_dba -p fctoernooi < planninginputs.sql
