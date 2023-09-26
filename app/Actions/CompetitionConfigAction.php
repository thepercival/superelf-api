<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Formation\Validator as FormationValidator;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use Sports\Season\Repository as SeasonRepository;
use SuperElf\League as S11League;

final class CompetitionConfigAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected PoolRepository $poolRepos,
        protected SeasonRepository $seasonRepos,
        protected Configuration $config,
        protected FormationValidator $formationValidator
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchActive(Request $request, Response $response, array $args): Response
    {
        try {
            $competitionConfigs = $this->competitionConfigRepos->findActive();

            $context = $this->getSerializationContext(['noReference']);
            $json = $this->serializer->serialize($competitionConfigs, 'json', $context);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function poolActions(Request $request, Response $response, array $args): Response
    {
        try {
            $poolActions = 0;
            $now = new \DateTimeImmutable();

            $competitionConfigs = $this->competitionConfigRepos->findActive();
            foreach ($competitionConfigs as $competitionConfig) {
                if ($competitionConfig->getCreateAndJoinPeriod()->contains($now)) {
                    $poolActions += Pool\Actions::CreateAndJoin->value;
                }
                if ($competitionConfig->getAssemblePeriod()->contains($now)) {
                    $poolActions += Pool\Actions::Assemble->value;
                }
            }
            $json = $this->serializer->serialize($poolActions, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchAvailableFormations(Request $request, Response $response, array $args): Response
    {
        try {
            $competitionConfig = $this->competitionConfigRepos->find((int)$args['competitionConfigId']);
            if ($competitionConfig === null) {
                throw new \Exception("er kan geen bron-competitie gevonden worden", E_ERROR);
            }

            $availableFormations = $this->formationValidator->getAvailable();

            $json = $this->serializer->serialize($availableFormations, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchWorldCupId(Request $request, Response $response, array $args): Response
    {

        try {
            // GET WORLD CUP!!
            $season = $this->seasonRepos->find((int)$args['seasonId']);
            if ($season === null) {
                $season = $this->competitionConfigRepos->findCurrentSeason();
            }
            $competitionConfigs = $this->competitionConfigRepos->findBySeason($season);
            $competitionConfig = reset($competitionConfigs);
            if( $competitionConfig === false ) {
                throw new \Exception('could not find competitionConfig');
            }

            $worldCupPool = $this->poolRepos->findWorldCup( $competitionConfig );
            if( $worldCupPool === null ) {
                throw new \Exception('could not find worldcup-pool');
            }

            return $this->respondWithPlainText($response, (string) $worldCupPool->getId());
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }

}
