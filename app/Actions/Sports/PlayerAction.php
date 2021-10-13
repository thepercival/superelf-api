<?php

declare(strict_types=1);

namespace App\Actions\Sports;

use App\Response\ErrorResponse;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\SerializationContext;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Sports\Team\Player;
use Sports\Team\Player\Repository as PlayerRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Actions\Action;
use SuperElf\CompetitionPerson\Filter as CompetitionPersonFilter;

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

    protected function getSerializationContext(): SerializationContext
    {
        $serGroups = ['Default','person'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
