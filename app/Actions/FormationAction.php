<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use Selective\Config\Configuration;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use JMS\Serializer\SerializerInterface;
use Sports\Team\Player;
use stdClass;
use SuperElf\Formation;
use SuperElf\Pool;
use SuperElf\Pool\User\Repository as PoolUserRepository;
use SuperElf\Formation\Repository as FormationRepository;
use Sports\Team\Player\Repository as PlayerRepository;
use SuperElf\Pool\User as PoolUser;
use Sports\Sport\Custom as SportCustom;
use SuperElf\User;

final class FormationAction extends Action
{
    protected PoolUserRepository $poolUserRepos;
    protected FormationRepository $formationRepos;
    protected PlayerRepository $playerRepos;
    protected Configuration $config;

    public function __construct(
        LoggerInterface $logger,
        SerializerInterface $serializer,
        PoolUserRepository $poolUserRepos,
        FormationRepository $formationRepos,
        PlayerRepository $playerRepos,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->poolUserRepos = $poolUserRepos;
        $this->formationRepos = $formationRepos;
        $this->playerRepos = $playerRepos;
        $this->config = $config;
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
                foreach( $line->getPersons() as $person ) {

                }
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
            $oldLinePersons = $oldLine->getPersons()->toArray();
            while( count( $oldLinePersons ) > 0 && $formationLine->getPersons()->count() < $formationLine->getMaxNrOfPersons()) {
                $formationLine->getPersons()->add( array_shift($oldLinePersons) );
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

    public function addPerson(Request $request, Response $response, $args): Response
    {
        return $this->addPersonHelper($request, $response );
    }

    public function addSubstitute(Request $request, Response $response, $args): Response
    {
        return $this->addPersonHelper($request, $response, true );
    }

    protected function addPersonHelper(Request $request, Response $response, bool $isSubstitute = false ): Response
    {
        sleep(2);
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();

            /** @var Player $serPlayer */
            $serPlayer = $this->serializer->deserialize(
                $this->getRawData(),
                Player::class,
                'json'
            );

            $player = $this->playerRepos->find( (int) $serPlayer->getId() );
            if( $player === null ) {
                throw new \Exception("de toe te voegen speler kan niet gevonden worden", E_ERROR);
            }

            $person = $formation->getPerson( $player->getTeam() );
            if( $person !== null ) {
                throw new \Exception("er is al een persoon die voor hetzelfde team uitkomt", E_ERROR );
            }

            $formationLine = $formation->getLine( $player->getLine() );
            if( $isSubstitute ) {
                $formationLine->setSubstitute( $player->getPerson() );
            } else {
                $formationLine->getPersons()->add( $player->getPerson() );
            }

            $this->formationRepos->save($formation);

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function removePerson(Request $request, Response $response, $args): Response
    {
        return $this->removePersonHelper($request, $response, $args );
    }

    public function removeSubstitute(Request $request, Response $response, $args): Response
    {
        return $this->removePersonHelper($request, $response, $args, true );
    }

    public function removePersonHelper(Request $request, Response $response, $args, bool $isSubstitute = false): Response
    {
        try {
            /** @var PoolUser $poolUser */
            $poolUser = $request->getAttribute("poolUser");

            if( !$poolUser->getPool()->getAssemblePeriod()->contains() ) {
                throw new \Exception("je kan alleen een formatie vewijderen tijdens de periode waarin je een team samenstelt");
            }

            $formation = $poolUser->getAssembleFormation();

            $player = $this->playerRepos->find( (int) $args["playerId"] );
            if( $player === null ) {
                throw new \Exception("de toe te voegen speler kan niet gevonden worden", E_ERROR);
            }

            $formationLine = $formation->getLine( $player->getLine() );
            if( $isSubstitute ) {
                $formationLine->setSubstitute( null );
            } else {
                $formationLine->getPersons()->removeElement( $player->getPerson() );
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
