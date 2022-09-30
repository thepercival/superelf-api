<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Competitor\StartLocationMap;
use Sports\Place;
use SuperElf\League as S11League;
use Sports\Poule;
use Sports\Poule\Repository as PouleRepository;
use stdClass;
use SuperElf\ChatMessage;
use SuperElf\ChatMessage\Repository as ChatMessageRepository;
use SuperElf\ChatMessage\Unread\Repository as UnreadChatMessageRepository;
use SuperElf\Pool;
use SuperElf\Competitor as PoolCompetitor;
use SuperElf\Pool\User as PoolUser;
use SuperElf\User;

final class ChatMessageAction extends Action
{
    public function __construct(
        protected PouleRepository $pouleRepos,
        protected ChatMessageRepository $chatMessageRepos,
        protected UnreadChatMessageRepository $unreadChatMessageRepos,
        LoggerInterface $logger,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function fetchNrOfUnreadMessages(Request $request, Response $response, array $args): Response
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

            $poule = $this->pouleRepos->find((int)$args["pouleId"]);
            if ($poule === null) {
                throw new \Exception('kan de poule met id "' . $args["pouleId"] . '" niet vinden', E_ERROR);
            }
            $nrOfUnread = $this->unreadChatMessageRepos->findNrOfUnread($poule, $poolUser);

            $json = $this->serializer->serialize(['nrOfUnreadMessages' => $nrOfUnread], 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $exception) {
            return new ErrorResponse($exception->getMessage(), 500, $this->logger);
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
        // {} fetch
        //{pouleId}/ fetchNrOfUnreadMessages

        try {
            /** @var Pool $pool */
            $pool = $request->getAttribute("pool");
            /** @var User $user */
            $user = $request->getAttribute("user");

            $poolUser = $pool->getUser($user);
            if ($poolUser === null) {
                return new ForbiddenResponse("de deelnemer kan niet gevonden worden");
            }

            $poule = $this->pouleRepos->find((int)$args["pouleId"]);
            if ($poule === null) {
                throw new \Exception('kan de poule met id "' . $args["pouleId"] . '" niet vinden', E_ERROR);
            }
            $chatMessages = $this->chatMessageRepos->findByExt($poule, $pool);
            $this->unreadChatMessageRepos->removeUnreadMessages($poolUser, $poule);

            // $serContext = $this->getSerializationContext(['players']);
            $json = $this->serializer->serialize($chatMessages, 'json');
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
    public function add(Request $request, Response $response, array $args): Response
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

            $poule = $this->pouleRepos->find((int)$args["pouleId"]);
            if ($poule === null) {
                throw new \Exception('kan de poule met id "' . $args["pouleId"] . '" niet vinden', E_ERROR);
            }

            /** @var stdClass $data */
            $data = $this->getFormData($request);
            if (property_exists($data, "message") === false) {
                throw new \Exception('geen bericht gevonden');
            }
            /** @var string $message */
            $message = $data->message;

            $chatMessage = new ChatMessage($poule, $poolUser, $message);

            $this->chatMessageRepos->save($chatMessage);

            $poolUsers = $this->getPoolUsers($poule, $chatMessage->getPoolUser());
            $this->unreadChatMessageRepos->saveUnreadMessages($chatMessage, $poolUsers);

            $json = $this->serializer->serialize($chatMessage, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }

    /**
     * @param Poule $poule
     * @param PoolUser $senderPoolUser
     * @return list<PoolUser>
     */
    public function getPoolUsers(Poule $poule, PoolUser $senderPoolUser): array
    {
        $leagueName = S11League::from($poule->getCompetition()->getLeague()->getName());

        $competition = $senderPoolUser->getPool()->getCompetition($leagueName);
        if( $competition === null ) {
            throw new \Exception('could not find competition', E_ERROR);
        }
        $startLocationMap = new StartLocationMap($senderPoolUser->getPool()->getCompetitors($competition));

        $competitors = array_map(function (Place $place) use ($startLocationMap): PoolCompetitor|null {
            $startLocation = $place->getStartLocation();
            if( $startLocation === null) {
                return null;
            }
            /** @var PoolCompetitor $competitor */
            $competitor = $startLocationMap->getCompetitor($startLocation);
            return $competitor;
        }, $poule->getPlaces()->toArray());

        $competitors = array_filter($competitors, function (PoolCompetitor|null $competitor) use ($senderPoolUser): bool {
            return $competitor !== null && $competitor->getPoolUser() !== $senderPoolUser;
        });

        return array_values( array_map(function (PoolCompetitor $competitor): PoolUser {
            return $competitor->getPoolUser();
        }, $competitors) );
    }

}
