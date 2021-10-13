<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use SuperElf\Auth\SyncService as AuthSyncService;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use SuperElf\Pool;
use SuperElf\ScoutedPerson;
use Psr\Log\LoggerInterface;
use SuperElf\ScoutedPerson\Repository as ScoutedPersonRepository;
use Sports\Competition\Repository as CompetitionRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\User;
use Sports\Person\Repository as PersonRepository;

final class ScoutedPersonAction extends Action
{
    private ScoutedPersonRepository $scoutedPersonRepos;
    private CompetitionRepository $competitionRepos;
    private PersonRepository $personRepos;

    public function __construct(
        LoggerInterface $logger,
        ScoutedPersonRepository $scoutedPersonRepos,
        CompetitionRepository $competitionRepos,
        PersonRepository $personRepos,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->scoutedPersonRepos = $scoutedPersonRepos;
        $this->competitionRepos = $competitionRepos;
        $this->personRepos = $personRepos;
        $this->serializer = $serializer;
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
            /** @var User $user */
            $user = $request->getAttribute("user");

            $sourceCompetition = $this->competitionRepos->find( $args["competitionId"] );
            if( $sourceCompetition === null ) {
                throw new \Exception("de broncompetitie is niet gevonden", E_ERROR);
            }

            $scoutedPersons = $this->scoutedPersonRepos->findBy( ["user" => $user, "sourceCompetition" => $sourceCompetition ]);

            $json = $this->serializer->serialize($scoutedPersons, 'json', $this->getSerializationContext() );
            return $this->respondWithJson($response, $json );
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

            $serScoutedPerson = $this->serializer->deserialize(
                $this->getRawData(),
                ScoutedPerson::class,
                'json'
            );

            $sourceCompetition = $this->competitionRepos->find( (int)$args["competitionId"] );
            if( $sourceCompetition === null ) {
                throw new \Exception("de broncompetitie is niet gevonden", E_ERROR);
            }

            $person = $this->personRepos->find( $serScoutedPerson->getPerson()->getId() );
            if( $person === null ) {
                throw new \Exception("de persoon is niet gevonden", E_ERROR);
            }

            $scoutedPerson = $this->scoutedPersonRepos->findOneBy( [
                "person" => $person, "sourceCompetition" => $sourceCompetition, "user" => $user ] );
            if( $scoutedPerson !== null ) {
                throw new \Exception("de persoon staat al in je scouting-lijst", E_ERROR);
            }
            $scoutedPerson = new ScoutedPerson( $user, $sourceCompetition, $person, $serScoutedPerson->getNrOfStars() );

            $this->scoutedPersonRepos->save($scoutedPerson);
            $json = $this->serializer->serialize($scoutedPerson, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

//    public function edit(Request $request, Response $response, $args): Response
//    {
//        try {
//            /** @var User $user */
//            $user = $request->getAttribute("user");
//
//            /** @var ScoutedPerson $serScoutedPerson */
//            $serScoutedPerson = $this->serializer->deserialize(
//                $this->getRawData(),
//                ScoutedPerson::class,
//                'json'
//            );
//
//            $scoutedPerson = $this->scoutedPersonRepos->find( (int)$args["scoutedPersonId"] );
//            if ($scoutedPerson === null ) {
//                throw new \Exception("de gescoute persoon is niet gevonden", E_ERROR);
//            }
//            if ($scoutedPerson->getUser() !== $user ) {
//                throw new \Exception("de gescoute persoon is niet van de gebruiker", E_ERROR);
//            }
//
//            $scoutedPerson->setNrOfStars( $serScoutedPerson->getNrOfStars() );
//            $this->scoutedPersonRepos->save($scoutedPerson);
//            return $this->respondWithJson(
//                $response,
//                $this->serializer->serialize( $scoutedPerson,'json' )
//            );
//        } catch (\Exception $e) {
//            return new ErrorResponse($e->getMessage(), 422);
//        }
//    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function remove(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");

            $scoutedPerson = $this->scoutedPersonRepos->find((int)$args['scoutedPersonId']);
            if ($scoutedPerson === null ) {
                throw new \Exception("de gescoute persoon is niet gevonden", E_ERROR);
            }
            if ($scoutedPerson->getUser() !== $user ) {
                throw new \Exception("de gescoute persoon is niet van de gebruiker", E_ERROR);
            }

            $this->scoutedPersonRepos->remove($scoutedPerson);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    protected function getSerializationContext(): SerializationContext
    {
        $serGroups = ['Default','players'];
        return SerializationContext::create()->setGroups($serGroups);
    }
}
