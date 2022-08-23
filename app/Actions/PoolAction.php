<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use stdClass;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use SuperElf\CompetitionConfig\Repository as CompetitionConfigRepository;
use SuperElf\Pool;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\Pool\AvailabilityChecker as PoolAvailabilityChecker;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\User;

final class PoolAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected PoolRepository $poolRepos,
        protected PoolUserRepository $poolUserRepos,
        protected CompetitionConfigRepository $competitionConfigRepos,
        protected PoolAvailabilityChecker $poolAvailabilityChecker,
        protected PoolAdministrator $poolAdministrator,
        protected ActiveConfigService $activeConfigService,
        protected Configuration $config
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchOne(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute("user");
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            $json = $this->serializer->serialize(
                $pool,
                'json',
                $this->getPoolSerializationContext($pool, $user)
            );
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
    public function add(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");

            /** @var stdClass $poolData */
            $poolData = $this->getFormData($request);
            if (property_exists($poolData, "name") === false) {
                throw new \Exception("geen naam ingevoerd");
            }
            if (property_exists($poolData, "competitionConfigId") === false) {
                throw new \Exception("geen bron-competitie ingevoerd");
            }

            $competitionConfig = $this->competitionConfigRepos->find((int)$poolData->competitionConfigId);
            if ($competitionConfig === null) {
                throw new \Exception("er kan geen bron-competitie gevonden worden", E_ERROR);
            }
            $poolName = (string)$poolData->name;

            $sourceCompetition = $competitionConfig->getSourceCompetition();
            $this->poolAvailabilityChecker->check($sourceCompetition->getSeason(), $poolName, $user);
            $pool = $this->poolAdministrator->createPool($competitionConfig, $poolName, $user);

            // $this->poolRepos->save($pool);
            $serializationContext = $this->getPoolSerializationContext($pool, $user);
            $json = $this->serializer->serialize($pool, 'json', $serializationContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function joinUrl(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $baseUrl = $this->config->getString("www.wwwurl");
            $url = $baseUrl . "pool/join/" . (string)$pool->getId() . "/" . $this->getJoinKey($pool);
            return $this->respondWithJson($response, json_encode([ "url" => $url ]));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function join(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            /** @var stdClass $registerData */
            $registerData = $this->getFormData($request);
            if (property_exists($registerData, "key") === false) {
                throw new \Exception('geen uitnodigings-link gevonden');
            }

            if ($registerData->key !== $this->getJoinKey($pool)) {
                throw new \Exception('uitnodigings-link niet correct gevonden');
            }
            if ($pool->getUser($user) !== null) {
                throw new \Exception('je bent al ingeschreven voor de pool');
            }
            $poolUser = $this->poolAdministrator->addUser($pool, $user, false);

            $this->poolUserRepos->save($poolUser, true);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    protected function getJoinKey(Pool $pool): string
    {
        // every pool should have a random joinkey in the database
        return hash("sha1", $this->config->getString("auth.validatesecret") . (string)$pool->getId());
    }

    protected function getDeserializationContext(User $user = null): DeserializationContext
    {
        $serGroups = ['Default'];

        if ($user !== null) {
            $serGroups[] = 'privacy';
        }
        return DeserializationContext::create()->setGroups($serGroups);
    }

    protected function getPoolSerializationContext(Pool $pool, User $user = null): SerializationContext
    {
        $serGroups = ['noReference', 'teamCompetitors'];
        if ($user !== null) {
            $poolUser = $pool->getUser($user);
            if ($poolUser !== null) {
                $serGroups[] = 'users';
                if ($poolUser->getAdmin()) {
                    $serGroups[] = 'privacy';
                }
            }
        }
        return $this->getSerializationContext($serGroups);
    }
}
