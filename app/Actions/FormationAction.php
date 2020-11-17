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
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Pool\AvailabilityChecker as PoolAvailabilityChecker;
use SuperElf\Pool\Administrator as PoolAdministrator;
use SuperElf\User;
use SuperElf\ActiveConfig\Service as ActiveConfigService;
use Sports\Sport\Custom as SportCustom;

final class FormationAction extends Action
{
    protected PoolUserRepository $poolUserRepos;
    // protected CompetitionRepository $competitionRepos;
    protected Configuration $config;
    // protected PoolAvailabilityChecker $poolAvailabilityChecker;
   //  protected PoolAdministrator $poolAdministrator;
    // protected ActiveConfigService $activeConfigService;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolUserRepository $poolUserRepos,
//        CompetitionRepository $competitionRepos,
//        PoolAvailabilityChecker $poolAvailabilityChecker,
//        PoolAdministrator $poolAdministrator,
//        ActiveConfigService $activeConfigService,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolUserRepos = $poolUserRepos;
        //$this->competitionRepos = $competitionRepos;
        $this->config = $config;
//        $this->poolAvailabilityChecker = $poolAvailabilityChecker;
//        $this->poolAdministrator = $poolAdministrator;
//        $this->activeConfigService = $activeConfigService;
    }

//    public function fetchOne(Request $request, Response $response, $args): Response
//    {
//        $user = $request->getAttribute("user");
//        try {
//            /** @var Pool $pool */
//            $pool = $request->getAttribute("pool");
//            $json = $this->serializer->serialize(
//                $pool,
//                'json',
//                $this->getSerializationContext($pool, $user)
//            );
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 400);
//        }
//    }

    public function add(Request $request, Response $response, $args): Response
    {
        try {
            /** @var Pool\User $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $formationData = $this->getFormData($request);
            if (property_exists($formationData, "name") === false) {
                throw new \Exception("geen naam ingevoerd");
            }
            /** @var stdClass $formationData */
            if (property_exists($formationData, "lines") === false) {
                throw new \Exception("geen naam ingevoerd");
            }

            if( $poolUser->getAssembleFormation() !== null ) {
                throw new \Exception("er is al een formatie aanwezif");
            }

            /** @var stdClass $lines */
            $lines = $formationData->lines;
            $formation = new Formation();
            for( $lineNumber = 1 ; $lineNumber <= SportCustom::Football_Line_All ; $lineNumber *= 2) {
                new Formation\Line( $formation, $lineNumber, $lines->{$lineNumber});
            }

            $poolUser->setAssembleFormation( $formation );
            $this->poolUserRepos->save($poolUser);

             // $serializationContext = $this->getSerializationContext($pool, $user);
            $json = $this->serializer->serialize($formation, 'json'/*, $serializationContext*/);
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

//    protected function getDeserializationContext(User $user = null)
//    {
//        $serGroups = ['Default'];
//
//        if ($user !== null) {
//            $serGroups[] = 'privacy';
//        }
//        return DeserializationContext::create()->setGroups($serGroups);
//    }
//
//    protected function getSerializationContext(Pool $pool, User $user = null)
//    {
//        $serGroups = ['Default'];
//        if ($user !== null) {
//            $poolUser = $pool->getUser($user);
//            if ($poolUser !== null) {
//                $serGroups[] = 'users';
//                if ($poolUser->getAdmin() ) {
//                    $serGroups[] = 'privacy';
//                }
//            }
//        }
//        return SerializationContext::create()->setGroups($serGroups);
//    }
}
