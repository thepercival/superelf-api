<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Actions\Action;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Sports\Team\Player\Repository as PlayerRepository;
use Sports\Team\Repository as TeamRepository;

final class PlayerAction extends Action
{
    protected PlayerRepository $playerRepos;
    protected TeamRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PlayerRepository $playerRepos,
        TeamRepository $teamRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->playerRepos = $playerRepos;
        $this->teamRepos = $teamRepos;
    }

    // findByExt needs Period, so convert $personFilter->getSourceCompetitionId to Period if needed
//    public function fetch(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var CompetitionPersonFilter $personFilter */
//            $personFilter = $this->serializer->deserialize($this->getRawData(), CompetitionPersonFilter::class, 'json');
//            $maxResults = 50;
//            $team = $personFilter->getTeamId() !== null ? $this->teamRepos->find( $personFilter->getTeamId() ) : null;
//            $players = $this->playerRepos->findByExt( $personFilter->getSourceCompetitionId(), $team, $personFilter->getLine(), $maxResults );
//
//            $json = $this->serializer->serialize($players, 'json', $this->getSerializationContext() );
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }

}
