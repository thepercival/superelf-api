<?php

declare(strict_types=1);

namespace App\Actions\Pool;

use App\Actions\Action;
use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use DateTimeImmutable;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocation;
use SuperElf\Pool;
use SuperElf\League as S11League;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\User;

final class UserAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        protected PoolUserRepository $poolUserRepos
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
        try {
            $poolUser = $this->poolUserRepos->find((int)$args['poolUserId']);
            if ($poolUser === null) {
                throw new \Exception('geen deelnemer met het opgegeven id gevonden', E_ERROR);
            }
            // return new ForbiddenResponse('de pool komt niet overeen met de pool van de deelnemer');

            // $withFormations = true;
            $withTransferActions = true;
            if ($poolUser->getPool()->getAssemblePeriod()->getPeriod()->contains(new DateTimeImmutable())) {
                // $withFormations = false;
                $withTransferActions = false;
            }//  else if ($poolUser->getPool()->getTransferPeriod()->getPeriod()->contains(new DateTimeImmutable())) {
                // $withTransferActions = false;
            // }

            return $this->fetchOneHelper($response, $poolUser, false, false, $withTransferActions);
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
    public function fetchOneFromSession(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            $poolUser = $pool->getUser($user);
            if ($poolUser === null) {
                return new ForbiddenResponse("de deelnemer kan niet gevonden worden");
            }

            return $this->fetchOneHelper($response, $poolUser, true, true, true);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400, $this->logger);
        }
    }

    public function fetchOneHelper(Response $response, PoolUser $poolUser, bool $self, bool $withFormations, bool $withTransferActions): Response
    {
        $serGroups = ['person'/* for transferActions */];
        if( $withFormations ) {
            $serGroups[] = 'formations';
        }
        if( $withTransferActions ) {
            $serGroups[] = 'transferactions';
        }
        if ($self) {
            $serGroups[] = 'admin';
        }
        try {
            $json = $this->serializer->serialize(
                $poolUser,
                'json',
                $this->getSerializationContext($serGroups)
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
    public function fetch(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            $startLocationsIds = [];
            $leagueName = null;
            $queryParams = $request->getQueryParams();
            if (array_key_exists('leagueName', $queryParams)) {
                $leagueName = S11League::from( (string)$queryParams['leagueName'] );
                $it = 0;
                while (array_key_exists('startLocation' . $it, $queryParams)) {
                    $startLocationString = (string)$queryParams['startLocation' . $it];
                    $startLocationParts = explode('.', $startLocationString );
                    if( count($startLocationParts) !== 3 ) {
                        break;
                    }
                    $catNr = array_shift($startLocationParts);
                    $pouleNr = array_shift($startLocationParts);
                    $placeNr = array_shift($startLocationParts);
//                    if( $catNr === null || $pouleNr === null || $placeNr === null ) {
//                        break;
//                    }
                    $startLocationsIds[] = (new StartLocation((int)$catNr,(int)$pouleNr,(int)$placeNr))->getStartId();
                    $it++;
                }
            }

//            if ($pool->getAssemblePeriod()->getPeriod()->contains(new DateTimeImmutable())) {
//                throw new \Exception('je mag andere deelnemers niet bekijken in de samenstel-periode', E_ERROR);
//            }
//            if ($pool->getTransferPeriod()->getPeriod()->contains(new DateTimeImmutable())) {
//                throw new \Exception('je mag andere deelnemers niet bekijken in de transfer-periode', E_ERROR);
//            }
            $withFormations = true;
            $withTransferActions = true;
            if ($pool->getAssemblePeriod()->getPeriod()->contains(new DateTimeImmutable())) {
                $withFormations = false;
                $withTransferActions = false;
            } else if ($pool->getTransferPeriod()->getPeriod()->contains(new DateTimeImmutable())) {
                $withTransferActions = false;
            }

            $poolUser = $pool->getUser($user);
            $serGroups = ['person'/* for transferActions */];
            if( $poolUser !== null && $poolUser->getAdmin() ) {
                $serGroups[] = 'admin';
            }

            if( $withFormations ) {
                $serGroups[] = 'formations';
            }
            if( $withTransferActions ) {
                $serGroups[] = 'transferactions';
            }
            if( $leagueName === null ) {
                $poolUsers = $pool->getUsers();
            } else {
                $poolUsers = [];
                // only poolUsers with certain startLocation in certain leagea!
                $competition = $pool->getCompetition($leagueName);
                if( $competition !== null ) {
                    foreach( $pool->getCompetitors($competition) as $competitor ) {
                        if ( array_search($competitor->getStartId(), $startLocationsIds ) !== false ) {
                            $poolUsers[] = $competitor->getPoolUser();
                        }
                    }
                }
            }
            $json = $this->serializer->serialize($poolUsers,'json',$this->getSerializationContext($serGroups));
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
    public function remove(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");

            $poolUser = $this->poolUserRepos->find((int)$args['poolUserId']);
            if ($poolUser === null) {
                throw new \Exception("geen deelnemer met het opgegeven id gevonden", E_ERROR);
            }
            if ($poolUser->getPool() !== $pool) {
                return new ForbiddenResponse("de pool komt niet overeen met de pool van de deelnemer");
            }

            $this->poolUserRepos->remove($poolUser);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
