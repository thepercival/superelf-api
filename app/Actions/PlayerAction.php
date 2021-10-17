<?php
declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializationContext;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use SuperElf\Player\Repository as PlayerRepository;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use Sports\Competition\Repository as CompetitionRepository;
use Sports\Team\Repository as TeamRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\Player\Filter as PlayerFilter;

final class PlayerAction extends Action
{
    public function __construct(
        protected PlayerRepository $playerRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected TeamRepository $teamRepos,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetch(Request $request, Response $response, array $args): Response
    {
        try {
            $playerFilter = $this->serializer->deserialize($this->getRawData(), PlayerFilter::class, 'json');

            $viewPeriod = $this->viewPeriodRepos->find($playerFilter->getViewPeriodId());
            if ($viewPeriod === null) {
                throw new \Exception("de periode is niet meegegeven in het filter", E_ERROR);
            }

            $maxResults = 50;
            $team = $playerFilter->getTeamId() !== null ? $this->teamRepos->find($playerFilter->getTeamId()) : null;


            $players = $this->playerRepos->findByExt($viewPeriod, $team, $playerFilter->getLine(), $maxResults);
            // aan de persons moeten punten gekoppeld worden en daarna pas vrijgegeven worden???

            $json = $this->serializer->serialize($players, 'json', $this->getSerializationContext());
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function getSerializationContext(): SerializationContext
    {
        $serGroups = ['Default','players'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
