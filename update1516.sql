insert into competitions (againstRuleSet, startDateTime, leagueId, seasonId)
    (
        select 	c.againstRuleSet, '2017-07-01 00:00:00', l.id, (select id from seasons where name = '2017/2018')
        from 	competitions c
                    join seasons s on s.id = c.seasonId
                    join leagues l on l.id = c.leagueId
                    join associations a on a.id = l.associationId
        where a.name = 'kamp duim' and s.name = '2014/2015' and l.name = 'Competition'
    );

insert into trophies(createDateTime, poolUserId, competitionId)
    (
        select 	CURRENT_DATE(), pu.Id,
                  (
                      select 	csub.id
                      from 	competitions csub
                                  join leagues lsub on lsub.id = csub.leagueId
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
        where  	a.name = 'kamp duim' and u.name = 'joris' and ss.name = '2017/2018'
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
        where s.name = '2017/2018' and a.name = 'kamp duim' and u.name = 'ben'
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
        where s.name = '2017/2018' and a.name = 'kamp duim' and u.name = 'joris'
    );

# insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
#     (
#         select 	CURRENT_DATE(), 'Sheet', pu.Id, p.id, cc.id
#         from 	competitionConfigs cc
#                     join pools p on p.competitionConfigId = cc.id
#                     join poolCollections pc on p.collectionId  = pc.id
#                     join associations a on a.id = pc.associationId
#                     join leagues l on l.associationId = a.id
#                     join competitions sc on sc.id = cc.sourceCompetitionId
#                     join seasons s on s.id = sc.seasonId
#                     join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
#                     join poolUsers pu on p.id = pu.poolId
#                     join users u on u.id = pu.userId
#         where s.name = '2017/2018' and a.name = 'kamp duim' and u.name = 'duncan'
#     );
# insert into badges(createDateTime, category, poolUserId, poolId, competitionConfigId)
#     (
#         select 	CURRENT_DATE(), 'Card', pu.Id, p.id, cc.id
#         from 	competitionConfigs cc
#                     join pools p on p.competitionConfigId = cc.id
#                     join poolCollections pc on p.collectionId  = pc.id
#                     join associations a on a.id = pc.associationId
#                     join leagues l on l.associationId = a.id
#                     join competitions sc on sc.id = cc.sourceCompetitionId
#                     join seasons s on s.id = sc.seasonId
#                     join competitions c on c.leagueId = l.id and c.seasonId = sc.seasonId
#                     join poolUsers pu on p.id = pu.poolId
#                     join users u on u.id = pu.userId
#         where s.name = '2017/2018' and a.name = 'kamp duim' and u.name = 'tiem'
#     );