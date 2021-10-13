<?php
declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse as ForbiddenResponse;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use Selective\Config\Configuration;
use Slim\Factory\ServerRequestCreatorFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use stdClass;
use Sports\Competition\Repository as CompetitionRepository;
use SuperElf\Pool;
use SuperElf\Pool\Repository as PoolRepository;
use SuperElf\Pool\AvailabilityChecker as PoolAvailabilityChecker;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\User;
use SuperElf\ActiveConfig\Service as ActiveConfigService;

final class PoolAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected PoolRepository $poolRepos,
        protected CompetitionRepository $competitionRepos,
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
                $this->getSerializationContext($pool, $user)
            );
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function canCreate(Request $request, Response $response, array $args): Response
    {
        try {
            $now = new \DateTimeImmutable();
            $inCreatePeriod = $this->activeConfigService->getCreatePeriod()->contains($now);
            $json = $this->serializer->serialize($inCreatePeriod, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
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
            if (property_exists($poolData, "sourceCompetitionId") === false) {
                throw new \Exception("geen bron-competitie ingevoerd");
            }


            $sourceCompetition = $this->competitionRepos->find((int)$poolData->sourceCompetitionId);
            if ($sourceCompetition === null) {
                throw new \Exception("er kan geen bron-competitie gevonden worden", E_ERROR);
            }
            $poolName = (string)$poolData->name;

            $this->poolAvailabilityChecker->check($sourceCompetition->getSeason(), $poolName, $user);
            $pool = $this->poolAdministrator->createPool($sourceCompetition, $poolName, $user);

            // $this->poolRepos->save($pool);
            $serializationContext = $this->getSerializationContext($pool, $user);
            $json = $this->serializer->serialize($pool, 'json', $serializationContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
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
            return new ErrorResponse($e->getMessage(), 422);
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
            $this->poolAdministrator->addUser($pool, $user, false);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
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

    protected function getSerializationContext(Pool $pool, User $user = null): SerializationContext
    {
        $serGroups = ['Default','noReference'];
        if ($user !== null) {
            $poolUser = $pool->getUser($user);
            if ($poolUser !== null) {
                $serGroups[] = 'users';
                if ($poolUser->getAdmin()) {
                    $serGroups[] = 'privacy';
                }
            }
        }
        return SerializationContext::create()->setGroups($serGroups);
    }
}
