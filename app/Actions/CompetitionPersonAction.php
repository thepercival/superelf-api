<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use SuperElf\CompetitionPerson\Repository as CompetitionPersonRepository;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\CompetitionPerson\Filter as CompetitionPersonFilter;

final class CompetitionPersonAction extends Action
{
    protected CompetitionPersonRepository $competitionPersonRepos;
    protected CompetitionRepository $competitionRepos;
    protected TeamRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        CompetitionPersonRepository $competitionPersonRepos,
        CompetitionRepository $competitionRepos,
        TeamRepository $teamRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->competitionPersonRepos = $competitionPersonRepos;
        $this->competitionRepos = $competitionRepos;
        $this->teamRepos = $teamRepos;
    }

    public function fetch(Request $request, Response $response, $args): Response
    {
        try {
            /** @var CompetitionPersonFilter $personFilter */
            $personFilter = $this->serializer->deserialize($this->getRawData(), CompetitionPersonFilter::class, 'json');
            $maxResults = 50;
            $team = $personFilter->getTeamId() !== null ? $this->teamRepos->find( $personFilter->getTeamId() ) : null;
            $sourceCompetition = $this->competitionRepos->find( $personFilter->getSourceCompetitionId() );
            if ( $sourceCompetition === null ) {
                throw new \Exception("de broncompetitie is niet meegegeven in het filter", E_ERROR );
            }
            $persons = $this->competitionPersonRepos->findByExt( $sourceCompetition, $team, $personFilter->getLine(), $maxResults );

            $json = $this->serializer->serialize($persons, 'json', $this->getSerializationContext() );
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function getSerializationContext()
    {
        $serGroups = ['Default','players'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
