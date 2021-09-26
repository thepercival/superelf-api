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
    protected PoolRepository $poolRepos;
    protected CompetitionRepository $competitionRepos;
    protected Configuration $config;
    protected PoolAvailabilityChecker $poolAvailabilityChecker;
    protected PoolAdministrator $poolAdministrator;
    protected ActiveConfigService $activeConfigService;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolRepository $poolRepos,
        CompetitionRepository $competitionRepos,
        PoolAvailabilityChecker $poolAvailabilityChecker,
        PoolAdministrator $poolAdministrator,
        ActiveConfigService $activeConfigService,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolRepos = $poolRepos;
        $this->competitionRepos = $competitionRepos;
        $this->config = $config;
        $this->poolAvailabilityChecker = $poolAvailabilityChecker;
        $this->poolAdministrator = $poolAdministrator;
        $this->activeConfigService = $activeConfigService;
    }

    public function fetchOne(Request $request, Response $response, $args): Response
    {
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

    public function add(Request $request, Response $response, $args): Response
    {
        try {
            $user = $request->getAttribute("user");

            /** @var stdClass $poolData */
            $poolData = $this->getFormData($request);
            if (property_exists($poolData, "name") === false) {
                throw new \Exception("geen naam ingevoerd");
            }
            if (property_exists($poolData, "sourceCompetitionId") === false) {
                throw new \Exception("geen bron-competitie ingevoerd");
            }

            $sourceCompetition = $this->competitionRepos->find( (int)$poolData->sourceCompetitionId );
            if( $sourceCompetition === null ) {
                throw new \Exception("er kan geen bron-competitie gevonden worden", E_ERROR);
            }

            $this->poolAvailabilityChecker->check(
                $sourceCompetition->getSeason(), $poolData->name, $user
            );
            $pool = $this->poolAdministrator->createPool( $sourceCompetition, $poolData->name, $user );

            $this->poolRepos->save($pool);
            $serializationContext = $this->getSerializationContext($pool, $user);
            $json = $this->serializer->serialize($pool, 'json', $serializationContext);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function joinUrl(Request $request, Response $response, $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $baseUrl = $this->config->getString("www.wwwurl");
            $url = $baseUrl . "pool/join/" . $pool->getId() . "/" . $this->getJoinKey($pool);
            return $this->respondWithJson($response, json_encode([ "url" => $url ]) );
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function join(Request $request, Response $response, $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            /** @var stdClass $registerData */
            $registerData = $this->getFormData($request);
            if (property_exists($registerData, "key") === false) {
                throw new \Exception("geen uitnodigings-link gevonden");
            }

            if( $registerData->key !== $this->getJoinKey( $pool ) ) {
                throw new \Exception("uitnodigings-link niet correct gevonden");
            }
            if( $pool->getUser($user) !== null ) {
                throw new \Exception("je bent al ingeschreven voor de pool");
            }
            $poolUser = $this->poolAdministrator->addUser( $pool, $user, false );

            return $response->withStatus( 200 );
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function getJoinKey(Pool $pool): string {
        // every pool should have a random joinkey in the database
        return hash( "sha1", $this->config->getString("auth.validatesecret") . $pool->getId() );
    }

    protected function getDeserializationContext(User $user = null)
    {
        $serGroups = ['Default'];

        if ($user !== null) {
            $serGroups[] = 'privacy';
        }
        return DeserializationContext::create()->setGroups($serGroups);
    }

    protected function getSerializationContext(Pool $pool, User $user = null)
    {
        $serGroups = ['Default'];
        if ($user !== null) {
            $poolUser = $pool->getUser($user);
            if ($poolUser !== null) {
                $serGroups[] = 'users';
                if ($poolUser->getAdmin() ) {
                    $serGroups[] = 'privacy';
                }
            }
        }
        return SerializationContext::create()->setGroups($serGroups);
    }
}
