insert into competitions (againstRuleSet, startDateTime, leagueId, seasonId)
   (
     select 	c.againstRuleSet, '2020-07-31 00:00:00', l.id, (select id from seasons where name = '2020/2021')
     from 	competitions c
                 join seasons s on s.id = c.seasonId
                 join leagues l on l.id = c.leagueId
                 join associations a on a.id = l.associationId
     where a.name = 'Deerns' and s.name = '2022/2023' and l.name = 'Competition'
      );

insert into trophies(createDateTime, poolUserId, competitionId)
    (
        select 	CURRENT_DATE(), pu.Id,
                  (
                      select 	csub.id
                      from 	competitions csub
                                  join leagues lsub on lsub.id = csub.leagueId  and lsub.associationId = a.id
                      where 	csub.seasonId = ss.id and lsub.name = 'Competition'
                  )
        from 	poolUsers pu
                    join users u on u.id = pu.userId
                    join pools p on pu.poolId = p.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join competitionConfigs cc on p.competitionConfigId = cc.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons ss on ss.id = sc.seasonId
        where  	a.name = 'Deerns' and u.name = 'richardm' and ss.name = '2020/2021'
    );

insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
    (
        select 	CURRENT_DATE(), 'Result', pu.Id, p.id, cc.id
        from 	competitionConfigs cc
                    join pools p on p.competitionConfigId = cc.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join leagues l on l.associationId = a.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons s on s.id = sc.seasonId
                    join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
                    join poolUsers pu on p.id = pu.poolId
                    join users u on u.id = pu.userId
        where s.name = '2020/2021' and a.name = 'Deerns' and u.name = 'richardm'
    );

insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
    (
        select 	CURRENT_DATE(), 'Goal', pu.Id, p.id, cc.id
        from 	competitionConfigs cc
                    join pools p on p.competitionConfigId = cc.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join leagues l on l.associationId = a.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons s on s.id = sc.seasonId
                    join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
                    join poolUsers pu on p.id = pu.poolId
                    join users u on u.id = pu.userId
        where s.name = '2020/2021' and a.name = 'Deerns' and u.name = 'richardm'
    );

insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
    (
        select 	CURRENT_DATE(), 'Assist', pu.Id, p.id, cc.id
        from 	competitionConfigs cc
                    join pools p on p.competitionConfigId = cc.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join leagues l on l.associationId = a.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons s on s.id = sc.seasonId
                    join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
                    join poolUsers pu on p.id = pu.poolId
                    join users u on u.id = pu.userId
        where s.name = '2020/2021' and a.name = 'Deerns' and u.name = 'ralph'
    );

insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
    (
        select 	CURRENT_DATE(), 'Sheet', pu.Id, p.id, cc.id
        from 	competitionConfigs cc
                    join pools p on p.competitionConfigId = cc.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join leagues l on l.associationId = a.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons s on s.id = sc.seasonId
                    join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
                    join poolUsers pu on p.id = pu.poolId
                    join users u on u.id = pu.userId
        where s.name = '2020/2021' and a.name = 'Deerns' and u.name = 'higuita'
    );
insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
    (
        select 	CURRENT_DATE(), 'Card', pu.Id, p.id, cc.id
        from 	competitionConfigs cc
                    join pools p on p.competitionConfigId = cc.id
                    join poolCollections pc on p.collectionId  = pc.id
                    join associations a on a.id = pc.associationId
                    join leagues l on l.associationId = a.id
                    join competitions sc on sc.id = cc.sourceCompetitionId
                    join seasons s on s.id = sc.seasonId
                    join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
                    join poolUsers pu on p.id = pu.poolId
                    join users u on u.id = pu.userId
        where s.name = '2020/2021' and a.name = 'Deerns' and u.name = 'richardm'
    );