<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use Selective\Config\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Sports\Person;
use Sports\Team\Player;
use stdClass;
use SuperElf\Formation;
use SuperElf\OneTeamSimultaneous;
use SuperElf\Period\View as ViewPeriod;
use SuperElf\Period\View\Person as ViewPeriodPerson;
use SuperElf\Period\View\Person\Repository as ViewPeriodPersonRepository;
use SuperElf\Pool\User\ViewPeriodPerson as PoolUserViewPeriodPerson;
use SuperElf\Pool\User\ViewPeriodPerson\Repository as PoolUserViewPeriodPersonRepository;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Formation\Repository as FormationRepository;
use Sports\Person\Repository as PersonRepository;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Custom as SportCustom;

final class FormationAction extends Action
{
    protected PoolUserRepository $poolUserRepos;
    protected FormationRepository $formationRepos;
    protected PersonRepository $personRepos;
    protected ViewPeriodPersonRepository $viewPeriodPersonRepos;
    protected PoolUserViewPeriodPersonRepository $poolUserViewPeriodPersonRepos;
    protected Configuration $config;
    protected OneTeamSimultaneous $oneTeamSimultaneous;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolUserRepository $poolUserRepos,
        FormationRepository $formationRepos,
        PersonRepository $personRepos,
        ViewPeriodPersonRepository $viewPeriodPersonRepos,
        PoolUserViewPeriodPersonRepository $poolUserViewPeriodPersonRepos,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolUserRepos = $poolUserRepos;
        $this->formationRepos = $formationRepos;
        $this->viewPeriodPersonRepos = $viewPeriodPersonRepos;
        $this->poolUserViewPeriodPersonRepos = $poolUserViewPeriodPersonRepos;
        $this->personRepos = $personRepos;
        $this->config = $config;
        $this->oneTeamSimultaneous = new OneTeamSimultaneous();
    }

    public function add(Request $request, Response $response, $args): Response
    {
        try {
            $formation = $this->process( $request );
            $json = $this->serializer->serialize($formation, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function edit(Request $request, Response $response, $args): Response
    {
        try {
            $formation = $this->process( $request );
            return $this->respondWithJson( $response, $this->serializer->serialize( $formation,'json' ) );
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function process(Request $request ): Formation
    {
        /** @var PoolUser $poolUser */
        $poolUser = $request->getAttribute("poolUser");

        if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
            throw new \Exception("je kan alleen een formatie wijzigen tijdens de periode waarin je een team samenstelt");
        }

        $formationData = $this->getFormData($request);
        if (property_exists($formationData, "name") === false) {
            throw new \Exception("geen naam ingevoerd");
        }
        /** @var stdClass $formationData */
        if (property_exists($formationData, "lines") === false) {
            throw new \Exception("geen naam ingevoerd");
        }

        // NEED TO READ TO USE LATER
        $oldFormation = $poolUser->getAssembleFormation();
        if( $oldFormation !== null ) {
            foreach( $oldFormation->getLines() as $line ) {
                foreach( $line->getViewPeriodPersons() as $person ) {
                }
                $z = $line->getSubstitute();
            }
            $poolUser->setAssembleFormation( null );
            $this->formationRepos->remove($oldFormation);
        }

        /** @var stdClass $lines */
        $lines = $formationData->lines;
        $newFormation = new Formation();
        for( $lineNumber = 1 ; $lineNumber <= SportCustom::Football_Line_All ; $lineNumber *= 2) {
            $formationLine = new Formation\Line( $newFormation, $lineNumber, $lines->{$lineNumber});
            if( $oldFormation === null ) {
                continue;
            }
            $oldLine = $oldFormation->getLine( $lineNumber );
            $oldLinePersons = $oldLine->getViewPeriodPersons()->toArray();
            while( count( $oldLinePersons ) > 0 && $formationLine->getViewPeriodPersons()->count() < $formationLine->getMaxNrOfPersons()) {
                $formationLine->getViewPeriodPersons()->add( array_shift($oldLinePersons) );
            }
            $substitute = count( $oldLinePersons ) > 0 ? array_shift($oldLinePersons) : ( $oldLine->getSubstitute() );
            $formationLine->setSubstitute( $substitute );
        }

        $poolUser->setAssembleFormation( $newFormation );
        $this->poolUserRepos->save($poolUser);

        return $newFormation;
    }

    public function remove(Request $request, Response $response, $args): Response
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

    public function addViewPeriodPerson(Request $request, Response $response, $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();

            if( !$assemblePeriod->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            $formationLine = $formation->getLine( (int) $args["lineNumber"] );

            /** @var Person $serPerson */
            $serPerson = $this->serializer->deserialize(
                $this->getRawData(),
                Person::class,
                'json'
            );
            $person = $this->personRepos->find( $serPerson->getId() );
            if( $person === null ) {
                throw new \Exception("de persoon kan niet gevonden worden", E_ERROR );
            }

            $player = $this->oneTeamSimultaneous->getPlayer( $person );
            $personSameTeam = $formation->getPerson( $player->getTeam() );
            if( $personSameTeam !== null ) {
                throw new \Exception("er is al een persoon die voor hetzelfde team uitkomt", E_ERROR );
            }

            $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
            $viewPeriodPerson = $this->createViewPeriodPerson( $viewPeriod, $player->getPerson() );
            $formationLine->getViewPeriodPersons()->add( $viewPeriodPerson );


            $this->formationRepos->save($formation);

            $json = $this->serializer->serialize($viewPeriodPerson, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function addSubstitute(Request $request, Response $response, $args): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            $assemblePeriod = $poolUser->getPool()->getAssemblePeriod();

            if( !$assemblePeriod->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            $formationLine = $formation->getLine( (int) $args["lineNumber"] );

            /** @var Person $serPerson */
            $serPerson = $this->serializer->deserialize(
                $this->getRawData(),
                Person::class,
                'json'
            );
            $person = $this->personRepos->find( $serPerson->getId() );
            if( $person === null ) {
                throw new \Exception("de persoon kan niet gevonden worden", E_ERROR );
            }

            $player = $this->oneTeamSimultaneous->getPlayer( $person );
            $personSameTeam = $formation->getPerson( $player->getTeam() );
            if( $personSameTeam !== null ) {
                throw new \Exception("er is al een persoon die voor hetzelfde team uitkomt", E_ERROR );
            }

            $poolUserViewPeriodPerson = $this->createPoolUserViewPeriodPerson( $poolUser, $player->getPerson());
            $formationLine->setSubstitute( $poolUserViewPeriodPerson );

            $this->formationRepos->save($formation);

            $json = $this->serializer->serialize($poolUserViewPeriodPerson, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function createViewPeriodPerson( ViewPeriod $viewPeriod, Person $person): ViewPeriodPerson {
        $viewPeriodPerson = $this->viewPeriodPersonRepos->findOneBy( ["viewPeriod" => $viewPeriod, "person" => $person ]);
        if( $viewPeriodPerson !== null ) {
            return $viewPeriodPerson;
        }
        $viewPeriodPerson = new ViewPeriodPerson( $viewPeriod, $person);
        return $this->viewPeriodPersonRepos->save($viewPeriodPerson);
    }

    protected function createPoolUserViewPeriodPerson( PoolUser $poolUser, Person $person): PoolUserViewPeriodPerson {
        $viewPeriod = $poolUser->getPool()->getAssemblePeriod()->getViewPeriod();
        $viewPeriodPerson = $this->createViewPeriodPerson( $viewPeriod, $person);
        return new PoolUserViewPeriodPerson( $poolUser, $viewPeriodPerson );
    }

    public function removeViewPeriodPerson(Request $request, Response $response, $args): Response
    {
        $viewPeriodPerson = $this->viewPeriodPersonRepos->find( (int) $args["viewPeriodPersonId"] );
        if( $viewPeriodPerson === null ) {
            throw new \Exception("de te verwijderen speler kan niet gevonden worden", E_ERROR);
        }
        return $this->removePersonHelper($request, $response, $args, $viewPeriodPerson, null );
    }

    public function removeSubstitute(Request $request, Response $response, $args): Response
    {
        $substistute = $this->poolUserViewPeriodPersonRepos->find( (int) $args["substistuteId"] );
        if( $substistute === null ) {
            throw new \Exception("de te verwijderen wissel kan niet gevonden worden", E_ERROR);
        }
        return $this->removePersonHelper($request, $response, $args, null, $substistute );
    }

    public function removePersonHelper(Request $request, Response $response, $args,
        ViewPeriodPerson $viewPeriodPerson = null, PoolUserViewPeriodPerson $substistute = null): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();
            $formationLine = $formation->getLine( (int) $args["lineNumber"] );

            if( $substistute !== null ) {
                $formationLine->setSubstitute( null );
            } else {
                $formationLine->getViewPeriodPersons()->removeElement( $viewPeriodPerson );
            }

            $this->formationRepos->save($formation);

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
