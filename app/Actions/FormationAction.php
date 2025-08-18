<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use DateTimeImmutable;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Selective\Config\Configuration;
use Sports\Formation as SportsFormation;
use Sports\Person;
use Sports\Person\Repository as PersonRepository;
use SuperElf\Formation;
use SuperElf\Formation\Editor as FormationEditor;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\Formation\Repository as FormationRepository;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Periods\ViewPeriod as ViewPeriod;
use SuperElf\Periods\ViewPeriod\Repository as ViewPeriodRepository;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Player\Syncer as S11PlayerSyncer;
use SuperElf\Pool;
use SuperElf\Pool\User as PoolUser;
use SuperElf\Pool\User\Repository as PoolUserRepository;

final class FormationAction extends Action
{
    protected OneTeamSimultaneous $oneTeamSimultaneous;
    protected FormationEditor $formationEditor;

    public function __construct(
        protected PoolUserRepository $poolUserRepos,
        protected FormationRepository $formationRepos,
        protected FormationPlaceRepository $formationPlaceRepos,
        protected ViewPeriodRepository $viewPeriodRepos,
        protected PersonRepository $personRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected S11PlayerSyncer $s11PlayerSyncer,
        protected Configuration $config,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->oneTeamSimultaneous = new OneTeamSimultaneous();
        $this->formationEditor = new FormationEditor($this->config, false);
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
            /** @var Pool $pool */
//            $pool = $request->getAttribute("pool");

            $poolUser = $this->poolUserRepos->find((int)$args['poolUserId']);
            if ($poolUser === null) {
                throw new \Exception('geen deelnemer met het opgegeven id gevonden', E_ERROR);
            }
            $viewPeriod = $this->viewPeriodRepos->find((int)$args['viewPeriodId']);
            if ($viewPeriod === null) {
                throw new \Exception('geen viewperiod opgegeven', E_ERROR);
            }

//            $withTransferActions = true;
//            if ($pool->getAssemblePeriod()->getPeriod()->contains(new DateTimeImmutable())) {
//                $withTransferActions = false;
//            } else if ($pool->getTransferPeriod()->getPeriod()->contains(new DateTimeImmutable())) {
//                $withTransferActions = false;
//            }
//
//            $serGroups = ['person'/* for transferActions */];
//            if( $withTransferActions ) {
//                $serGroups[] = 'transferactions';
//            }

            $formation = $poolUser->getFormation($viewPeriod);

            if( $formation === null) {
                throw new \Exception('het team kan niet opgehaald worden');
            }
            $json = $this->serializer->serialize(
                $formation, 'json'/*, $this->getSerializationContext($serGroups)*/
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

            $viewPeriod = $this->viewPeriodRepos->find((int)$args['viewPeriodId']);
            if ($viewPeriod === null) {
                throw new \Exception('geen viewperiod opgegeven', E_ERROR);
            }

            $map = [];
            foreach( $pool->getUsers() as $poolUser ) {
                $map[(string)$poolUser->getId()] = $poolUser->getFormation($viewPeriod);
            }


            $json = $this->serializer->serialize(
                $map,
                'json'/*,
                    $this->getSerializationContext($serGroups)*/
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
    public function edit(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");
            /** @var SportsFormation $newSportsFormation */
            $newSportsFormation = $this->serializer->deserialize($this->getRawData(), SportsFormation::class, 'json');

            if (!$poolUser->getPool()->getAssemblePeriod()->contains()) {
                throw new \Exception("je kan alleen een formatie wijzigen tijdens de periode waarin je een team samenstelt");
            }
            $assembleFormation = $poolUser->getAssembleFormation();

            if ($assembleFormation === null) {
                $assembleFormation = $this->formationEditor->createAssemble($poolUser, $newSportsFormation);
            } else {
                $this->editAssemable($assembleFormation, $newSportsFormation);
            }

            $this->poolUserRepos->save($poolUser);

            return $this->respondWithJson($response, $this->serializer->serialize($assembleFormation, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    protected function editAssemable(Formation $formation, SportsFormation $newSportsFormation): void
    {
        $formationPlaceRemovals = $this->formationEditor->removeAssemble($formation, $newSportsFormation);
        /* SAVE REMOVED FORMATIONPLACES TO DATABASE */
        foreach ($formationPlaceRemovals as $formationPlaceRemoval) {
            $formationPlace = $formationPlaceRemoval->getFormationPlace();
            $formationLine = $formationPlace->getFormationLine();
            $this->formationPlaceRepos->remove($formationPlace, true);

            $playerWithoutPlace = $formationPlaceRemoval->getPlayer();
            if ($playerWithoutPlace !== null) {
                $lastPlaceWithoutPlayer = $this->formationEditor->getLastStartingPlaceWithoutPlayer($formationLine);
                if ($lastPlaceWithoutPlayer !== null) {
                    $lastPlaceWithoutPlayer->setPlayer($playerWithoutPlace);
                    $this->s11PlayerRepos->save($playerWithoutPlace, true);
                }
            }
        }

        $addedFormationPlaces = $this->formationEditor->addAssemble($formation, $newSportsFormation);
        /* SAVE ADDED FORMATIONPLACES TO DATABASE */
        foreach ($addedFormationPlaces as $addedFormationPlace) {
            $this->formationPlaceRepos->save($addedFormationPlace);
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
//        try {
//            /** @var PoolUser $poolUser */
//            $poolUser = $request->getAttribute("poolUser");
//
//            if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
//                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
//            }
//
//            $formation = $poolUser->getAssembleFormation();
//            $poolUser->setAssembleFormation( null );
//            $this->formationRepos->remove($formation);
//            return $response->withStatus(200);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
        return new ErrorResponse("implement", 422, $this->logger);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function editPlace(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();

            if (!$assemblePeriod->contains()) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            if ($formation === null) {
                throw new \Exception("je moet eerst een formatie kiezen voordat je een speler kan toevoegen");
            }

            $placeId = (int) $args["placeId"];
            if ($placeId === 0) {
                throw new \Exception("de formatie-plaats-id is niet opgegeven", E_ERROR);
            }
            $formationPlace = $this->formationPlaceRepos->find($placeId);
            if ($formationPlace === null) {
                throw new \Exception("de formatie-plaats kan niet gevonden worden", E_ERROR);
            }

            if ($formation !== $formationPlace->getFormationLine()->getFormation()) {
                throw new \Exception("je mag alleem een plaats van je eigen formatie wijzigen");
            }

            $rawData = $this->getRawData();
            $person = null;
            if (strlen($rawData) > 0) {
                /** @var Person $serPerson */
                $serPerson = $this->serializer->deserialize(
                    $rawData,
                    Person::class,
                    'json'
                );
                $person = $this->personRepos->find($serPerson->getId());
            }

            $s11Player = null;
            if ($person !== null) {
                $player = $this->oneTeamSimultaneous->getPlayer($person);
                if ($player === null) {
                    throw new \Exception('"' . $person->getName() . '" speelt niet voor een team', E_ERROR);
                }
                $personSameTeam = $formation->getPerson($player->getTeam());
                if ($personSameTeam !== null) {
                    throw new \Exception("er is al een persoon in je team die voor dezelfde club uitkomt", E_ERROR);
                }
                $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
                $s11Player = $this->s11PlayerSyncer->syncS11Player($viewPeriod, $player->getPerson());

                $formationPlace->setMarketValue($player->getMarketValue());
            }
            if ($s11Player && $formationPlace->getFormationLine()->getNumber() !== $s11Player->getLineFromPlayers()->value) {
                throw new \Exception("de linies komen niet overeen", E_ERROR);
            }
            $formationPlace->setPlayer($s11Player);

            $this->formationPlaceRepos->save($formationPlace);

            if ($s11Player === null) {
                return $response->withStatus(200);
            }
            $json = $this->serializer->serialize($s11Player, 'json');
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
    public function replacePlace(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $transferPeriod = $poolUser->getPool()->getTransferPeriod();

            if (!$transferPeriod->contains()) {
                throw new \Exception("je kan alleen een vervanging tijdens de transferperiode doen");
            }

            $formation = $poolUser->getAssembleFormation();
            if ($formation === null) {
                throw new \Exception("je hebt geen formatie gekozen");
            }

            $placeId = (int) $args["placeId"];
            if ($placeId === 0) {
                throw new \Exception("de formatie-plaats-id is niet opgegeven", E_ERROR);
            }
            $formationPlace = $this->formationPlaceRepos->find($placeId);
            if ($formationPlace === null) {
                throw new \Exception("de formatie-plaats kan niet gevonden worden", E_ERROR);
            }

            if ($formation !== $formationPlace->getFormationLine()->getFormation()) {
                throw new \Exception("je mag alleem een plaats van je eigen formatie wijzigen");
            }

            $rawData = $this->getRawData();
            $person = null;
            if (strlen($rawData) > 0) {
                /** @var Person $serPerson */
                $serPerson = $this->serializer->deserialize(
                    $rawData,
                    Person::class,
                    'json'
                );
                $person = $this->personRepos->find($serPerson->getId());
            }

            $s11Player = null;
            if ($person !== null) {
                $player = $this->oneTeamSimultaneous->getPlayer($person);
                if ($player === null) {
                    throw new \Exception('"' . $person->getName() . '" speelt niet voor een team', E_ERROR);
                }
                $personSameTeam = $formation->getPerson($player->getTeam());
                if ($personSameTeam !== null) {
                    throw new \Exception("er is al een persoon in je team die voor dezelfde club uitkomt", E_ERROR);
                }
                $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
                $s11Player = $this->s11PlayerSyncer->syncS11Player($viewPeriod, $player->getPerson());
            }
            if ($s11Player && $formationPlace->getFormationLine()->getNumber() !== $s11Player->getLineFromPlayers()->value) {
                throw new \Exception("de linies komen niet overeen", E_ERROR);
            }
            $formationPlace->setPlayer($s11Player);

            $this->formationPlaceRepos->save($formationPlace);

            if ($s11Player === null) {
                return $response->withStatus(200);
            }
            $json = $this->serializer->serialize($s11Player, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    protected function getPlayer(ViewPeriod $viewPeriod, Person $person): S11Player
    {
        $s11Player = $this->s11PlayerRepos->findOneBy(["viewPeriod" => $viewPeriod, "person" => $person ]);
        if ($s11Player === null) {
            throw new \Exception('player could not be found', E_ERROR);
        }
        return $s11Player;
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
