<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use App\Response\ForbiddenResponse;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Sports\Poule\Repository as PouleRepository;
use SuperElf\ChatMessage\Repository as ChatMessageRepository;
use SuperElf\ChatMessage\Unread\Repository as UnreadChatMessageRepository;
use SuperElf\Pool;
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

            // $serContext = $this->getSerializationContext(['players']);
            $json = $this->serializer->serialize($chatMessages, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
