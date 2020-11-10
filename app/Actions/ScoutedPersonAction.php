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
use Sports\Season\Repository as SeasonRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SuperElf\User;
use Sports\Person\Repository as PersonRepository;

final class ScoutedPersonAction extends Action
{
    private ScoutedPersonRepository $scoutedPersonRepos;
    private SeasonRepository $seasonRepos;
    private PersonRepository $personRepos;

    public function __construct(
        LoggerInterface $logger,
        ScoutedPersonRepository $scoutedPersonRepos,
        SeasonRepository $seasonRepos,
        PersonRepository $personRepos,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->scoutedPersonRepos = $scoutedPersonRepos;
        $this->seasonRepos = $seasonRepos;
        $this->personRepos = $personRepos;
        $this->serializer = $serializer;
    }

    public function fetch(Request $request, Response $response, $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");

            $season = $this->seasonRepos->find( $args["seasonId"] );
            if( $season === null ) {
                throw new \Exception("het seizoen is niet gevonden", E_ERROR);
            }
            // dit moet kunnen in de personrepository :
            // er kan gezocht worden op linie & team
            // er moet een maximum kunnen worden opgegeven, voor nu in deze controller definieren

            $scoutedPersons = $this->scoutedPersonRepos->findBy( ["user" => $user, "season" => $season ]);

            $json = $this->serializer->serialize($scoutedPersons, 'json' );
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 400);
        }
    }

    public function add(Request $request, Response $response, $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");

            /** @var ScoutedPerson $serScoutedPerson */
            $serScoutedPerson = $this->serializer->deserialize(
                $this->getRawData(),
                ScoutedPerson::class,
                'json'
            );

            $season = $this->seasonRepos->find( $args["seasonId"] );
            if( $season === null ) {
                throw new \Exception("het seizoen is niet gevonden", E_ERROR);
            }

            $person = $this->personRepos->find( $serScoutedPerson->getPerson()->getId() );
            if( $person === null ) {
                throw new \Exception("de persoon is niet gevonden", E_ERROR);
            }

            $scoutedPerson = new ScoutedPerson( $user, $season, $person, $serScoutedPerson->getNrOfStars() );

            $this->scoutedPersonRepos->save($scoutedPerson);
            $json = $this->serializer->serialize($scoutedPerson, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function edit(Request $request, Response $response, $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");

            /** @var ScoutedPerson $serScoutedPerson */
            $serScoutedPerson = $this->serializer->deserialize(
                $this->getRawData(),
                ScoutedPerson::class,
                'json'
            );

            $scoutedPerson = $this->scoutedPersonRepos->find( (int)$args["scoutedPersonId"] );
            if ($scoutedPerson === null ) {
                throw new \Exception("de gescoute persoon is niet gevonden", E_ERROR);
            }
            if ($scoutedPerson->getUser() !== $user ) {
                throw new \Exception("de gescoute persoon is niet van de gebruiker", E_ERROR);
            }

            $scoutedPerson->setNrOfStars( $serScoutedPerson->getNrOfStars() );
            $this->scoutedPersonRepos->save($scoutedPerson);
            return $this->respondWithJson(
                $response,
                $this->serializer->serialize( $scoutedPerson,'json' )
            );
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function remove(Request $request, Response $response, $args): Response
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
}
