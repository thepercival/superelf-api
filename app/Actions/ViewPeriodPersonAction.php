<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use SuperElf\Period\View\Person\Repository as ViewPeriodPersonRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\Period\View\Person\Filter as ViewPeriodPersonFilter;

final class ViewPeriodPersonAction extends Action
{
    protected ViewPeriodPersonRepository $viewPeriodPersonRepos;
    protected ViewPeriodRepository $viewPeriodRepos;
    protected TeamRepository $teamRepos;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        ViewPeriodPersonRepository $viewPeriodPersonRepos,
        ViewPeriodRepository $viewPeriodRepos,
        TeamRepository $teamRepos
    ) {
        parent::__construct($logger, $serializer);
        $this->viewPeriodPersonRepos = $viewPeriodPersonRepos;
        $this->viewPeriodRepos = $viewPeriodRepos;
        $this->teamRepos = $teamRepos;
    }

    public function fetch(Request $request, Response $response, $args): Response
    {
        try {
            /** @var ViewPeriodPersonFilter $personFilter */
            $personFilter = $this->serializer->deserialize($this->getRawData(), ViewPeriodPersonFilter::class, 'json');
            $maxResults = 5000;
            $team = $personFilter->getTeamId() !== null ? $this->teamRepos->find( $personFilter->getTeamId() ) : null;
            $viewPeriod = $this->viewPeriodRepos->find( $personFilter->getViewPeriodId() );
            if ( $viewPeriod === null ) {
                throw new \Exception("de periode is niet meegegeven in het filter", E_ERROR );
            }
            $persons = $this->viewPeriodPersonRepos->findByExt( $viewPeriod, $team, $personFilter->getLine(), $maxResults );
            // aan de persons moeten punten gekoppeld worden en daarna pas vrijgegeven worden???

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
