<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use Selective\Config\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use stdClass;
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Custom as SportCustom;

final class FormationAction extends Action
{
    protected PoolUserRepository $poolUserRepos;
    protected FormationRepository $formationRepos;
    protected Configuration $config;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolUserRepository $poolUserRepos,
        FormationRepository $formationRepos,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolUserRepos = $poolUserRepos;
        $this->formationRepos = $formationRepos;
        $this->config = $config;
    }

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
                throw new \Exception("er is al een formatie aanwezig");
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

    public function remove(Request $request, Response $response, $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            $poolUser->setAssembleFormation( null );
            $this->formationRepos->remove($formation);
            return $response->withStatus(200);
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
