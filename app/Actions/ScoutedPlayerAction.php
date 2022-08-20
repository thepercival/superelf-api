<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Period\View\Repository as ViewPeriodRepository;
use SuperElf\Player\Repository as S11PlayerRepository;
use SuperElf\ScoutedPlayer;
use SuperElf\ScoutedPlayer\Repository as ScoutedPlayerRepository;
use SuperElf\User;

final class ScoutedPlayerAction extends Action
{
    private ScoutedPlayerRepository $scoutedPlayerRepos;
    private ViewPeriodRepository $viewPeriodRepos;
    private S11PlayerRepository $s11PlayerRepos;

    public function __construct(
        LoggerInterface $logger,
        ScoutedPlayerRepository $scoutedPlayerRepos,
        ViewPeriodRepository $viewPeriodRepos,
        S11PlayerRepository $s11PlayerRepos,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->scoutedPlayerRepos = $scoutedPlayerRepos;
        $this->viewPeriodRepos = $viewPeriodRepos;
        $this->s11PlayerRepos = $s11PlayerRepos;
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

            $viewPeriod = $this->viewPeriodRepos->find($args["viewPeriodId"]);
            if ($viewPeriod === null) {
                throw new \Exception("de broncompetitie is niet gevonden", E_ERROR);
            }

            $scoutedPlayers = $this->scoutedPlayerRepos->findByExt($user, $viewPeriod);

            $json = $this->serializer->serialize($scoutedPlayers, 'json');
            return $this->respondWithJson($response, $json);
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

            /** @var ScoutedPlayer $serScoutedPlayer */
            $serScoutedPlayer = $this->serializer->deserialize(
                $this->getRawData(),
                ScoutedPlayer::class,
                'json'
            );

            $viewPeriod = $this->viewPeriodRepos->find((int)$args["viewPeriodId"]);
            if ($viewPeriod === null) {
                throw new \Exception("de broncompetitie is niet gevonden", E_ERROR);
            }

            $s11Player = $this->s11PlayerRepos->find($serScoutedPlayer->getS11Player()->getId());
            if ($s11Player === null) {
                throw new \Exception("de speler is niet gevonden", E_ERROR);
            }

            if ($s11Player->getViewPeriod() !== $viewPeriod) {
                throw new \Exception("de competitie van de speler is anders als de meegegeven competitie", E_ERROR);
            }

            $scoutedPlayer = $this->scoutedPlayerRepos->findOneBy([
                "s11Player" => $s11Player, "user" => $user ]);
            if ($scoutedPlayer !== null) {
                throw new \Exception("de speler staat al in je scouting-lijst", E_ERROR);
            }
            $scoutedPlayer = new ScoutedPlayer($user, $s11Player, $serScoutedPlayer->getNrOfStars());

            $this->scoutedPlayerRepos->save($scoutedPlayer);
            $json = $this->serializer->serialize($scoutedPlayer, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function edit(Request $request, Response $response, $args): Response
    {
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
        return new ErrorResponse('implement', 422);
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
            /** @var User $user */
            $user = $request->getAttribute("user");

            $scoutedPlayer = $this->scoutedPlayerRepos->find((int)$args['scoutedPlayerId']);
            if ($scoutedPlayer === null) {
                throw new \Exception("de gescoute speler is niet gevonden", E_ERROR);
            }
            if ($scoutedPlayer->getUser() !== $user) {
                throw new \Exception("de gescoute speler is niet van de gebruiker", E_ERROR);
            }

            $this->scoutedPlayerRepos->remove($scoutedPlayer);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }
}
