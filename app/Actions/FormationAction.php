<?php
declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use Selective\Config\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Slim\Exception\HttpBadRequestException;
use Sports\Person;
use SuperElf\Formation\Place as FormationPlayer;
use SuperElf\Formation\Editor as FormationEditor;
use stdClass;
use SuperElf\Formation;
use Sports\Formation as SportsFormation;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Player as S11Player;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\Substitute;
use SuperElf\Substitute\Repository as SubstituteRepository;
use SuperElf\Formation\Place\Repository as FormationPlaceRepository;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Formation\Repository as FormationRepository;
use Sports\Person\Repository as PersonRepository;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Custom as SportCustom;

final class FormationAction extends Action
{
    protected OneTeamSimultaneous $oneTeamSimultaneous;

    public function __construct(
        protected PoolUserRepository $poolUserRepos,
        protected FormationRepository $formationRepos,
        protected FormationPlaceRepository $formationPlaceRepos,
        protected FormationEditor $formationEditor,
        protected PersonRepository $personRepos,
        protected S11PlayerRepository $s11PlayerRepos,
        protected Configuration $config,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->oneTeamSimultaneous = new OneTeamSimultaneous();
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

            $sportsFormation = $this->serializer->deserialize($this->getRawData(), SportsFormation::class, 'json');

            if (!$poolUser->getPool()->getAssemblePeriod()->contains()) {
                throw new \Exception("je kan alleen een formatie wijzigen tijdens de periode waarin je een team samenstelt");
            }
            $assembleFormation = $poolUser->getAssembleFormation();

            if ($assembleFormation !== null) {
                $removedFormationPlaces = $this->formationEditor->updateAssemble($assembleFormation, $sportsFormation);
                foreach( $removedFormationPlaces as $removedFormationPlace) {
                    $this->formationPlaceRepos->remove($removedFormationPlace);
                }
            } else {
                $assembleFormation = $this->formationEditor->createAssemble($poolUser, $sportsFormation);
            }

            $this->poolUserRepos->save($poolUser);

            return $this->respondWithJson($response, $this->serializer->serialize($assembleFormation, 'json'));
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
        return new ErrorResponse("implement", 422);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function addPlayer(Request $request, Response $response, array $args): Response
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
            $formationLine = $formation->getLine((int) $args["lineNumber"]);

            $serPerson = $this->serializer->deserialize(
                $this->getRawData(),
                Person::class,
                'json'
            );
            $person = $this->personRepos->find($serPerson->getId());
            if ($person === null) {
                throw new \Exception("de persoon kan niet gevonden worden", E_ERROR);
            }

            $player = $this->oneTeamSimultaneous->getPlayer($person);
            if ($player === null) {
                throw new \Exception('"' . $person->getName() .'" speelt niet voor een team', E_ERROR);
            }
            $personSameTeam = $formation->getPerson($player->getTeam());
            if ($personSameTeam !== null) {
                throw new \Exception("er is al een persoon die voor hetzelfde team uitkomt", E_ERROR);
            }

            $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
            $s11Player = $this->getPlayer($viewPeriod, $player->getPerson());
            new FormationPlayer($formationLine, $s11Player);


            $this->formationRepos->save($formation);

            $json = $this->serializer->serialize($s11Player, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

//    /**
//     * @param Request $request
//     * @param Response $response
//     * @param array<string, int|string> $args
//     * @return Response
//     */
//    public function addSubstitute(Request $request, Response $response, array $args): Response
//    {
//        try {
//            /** @var PoolUser $poolUser */
//            $poolUser = $request->getAttribute("poolUser");
//
//            $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();
//
//            if (!$assemblePeriod->contains()) {
//                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
//            }
//
//            $formation = $poolUser->getAssembleFormation();
//            if ($formation === null) {
//                throw new \Exception("je moet eerst een formatie kiezen voordat je een speler kan toevoegen");
//            }
//            $formationLine = $formation->getLine((int) $args["lineNumber"]);
//
//            $serPerson = $this->serializer->deserialize(
//                $this->getRawData(),
//                Person::class,
//                'json'
//            );
//            $person = $this->personRepos->find($serPerson->getId());
//            if ($person === null) {
//                throw new \Exception("de persoon kan niet gevonden worden", E_ERROR);
//            }
//
//            $player = $this->oneTeamSimultaneous->getPlayer($person);
//            if ($player === null) {
//                throw new \Exception('"' . $person->getName() .'" speelt niet voor een team', E_ERROR);
//            }
//
//            $personSameTeam = $formation->getPerson($player->getTeam());
//            if ($personSameTeam !== null) {
//                throw new \Exception("er is al een persoon die voor hetzelfde team uitkomt", E_ERROR);
//            }
//
//            $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
//            $substitute = $this->getPlayer($viewPeriod, $player->getPerson());
//            $formationLine->setSubstitute($substitute);
//
//            $this->formationRepos->save($formation);
//
//            $json = $this->serializer->serialize($substitute, 'json');
//            return $this->respondWithJson($response, $json);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }

    protected function getPlayer(ViewPeriod $viewPeriod, Person $person): S11Player
    {
        $s11Player = $this->s11PlayerRepos->findOneBy(["viewPeriod" => $viewPeriod, "person" => $person ]);
        if ($s11Player === null) {
            throw new \Exception('player could not be found', E_ERROR);
        }
        return $s11Player;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function removePlayer(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if (!$poolUser->getPool()->getAssemblePeriod()->contains()) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            if ($formation === null) {
                throw new \Exception("je moet eerst een formatie kiezen voordat je een speler kan verwijderen");
            }
            $formationLine = $formation->getLine((int) $args["lineNumber"]);

            $number = (int) $args["number"];
            if ($number < 1 || $number > $formationLine->getPlaces()->count() - 1) {
                throw new \Exception("de te verwijderen speler kan niet gevonden worden", E_ERROR);
            }

            $formationPlayer = $formationLine->getPlace($number);
            $formationLine->getPlaces()->removeElement($formationPlayer);

            $this->formationRepos->save($formation);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

//    /**
//     * @param Request $request
//     * @param Response $response
//     * @param array<string, int|string> $args
//     * @return Response
//     */
//    public function removeSubstitute(Request $request, Response $response, array $args): Response
//    {
//        try {
//            $substistute = $this->s11PlayerRepos->find((int) $args["substistuteId"]);
//            if ($substistute === null) {
//                throw new \Exception("de te verwijderen wissel kan niet gevonden worden", E_ERROR);
//            }
//
//            /** @var PoolUser $poolUser */
//            $poolUser = $request->getAttribute("poolUser");
//
//            if (!$poolUser->getPool()->getAssemblePeriod()->contains()) {
//                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
//            }
//
//            $formation = $poolUser->getAssembleFormation();
//            if ($formation === null) {
//                throw new \Exception("je moet eerst een formatie kiezen voordat je een speler kan verwijderen");
//            }
//            $formationLine = $formation->getLine((int) $args["lineNumber"]);
//
//            $formationLine->setSubstitute(null);
//
//            $this->formationRepos->save($formation);
//
//            return $response->withStatus(200);
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }



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
