<?php

declare(strict_types=1);

namespace App\Actions;

use App\Response\ErrorResponse;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use SuperElf\Auth\SyncService as AuthSyncService;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;

final class UserAction extends Action
{
    /**
     * @var UserRepository
     */
    private $userRepos;
    /**
     * @var AuthSyncService
     */
    protected $syncService;


    public function __construct(
        LoggerInterface $logger,
        UserRepository $userRepository,
        AuthSyncService $syncService,
        SerializerInterface $serializer
    ) {
        parent::__construct($logger, $serializer);
        $this->userRepos = $userRepository;
        $this->syncService = $syncService;
        $this->serializer = $serializer;
    }

    protected function getDeserializationContext(): DeserializationContext
    {
        $serGroups = ['Default', 'admin'];
        return DeserializationContext::create()->setGroups($serGroups);
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
            /** @var User $user */
            $user = $request->getAttribute("user");

            if ($user->getId() !== (int)$args['userId']) {
                throw new \Exception("de ingelogde gebruiker en de op te halen gebruiker zijn verschillend", E_ERROR);
            }
            $json = $this->serializer->serialize($user, 'json', $this->getSerializationContext(['admin']));
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
            /** @var User $userAuth */
            $userAuth = $request->getAttribute("user");

            /** @var User $userSer */
            $userSer = $this->serializer->deserialize(
                $this->getRawData(),
                User::class,
                'json',
                $this->getDeserializationContext()
            );

            if ($userAuth->getId() !== $userSer->getId()) {
                throw new \Exception("de ingelogde gebruiker en de aan te passen gebruiker zijn verschillend", E_ERROR);
            }

            $userAuth->setEmailaddress(strtolower(trim($userSer->getEmailaddress())));
            $this->userRepos->save($userAuth);
            return $this->respondWithJson(
                $response,
                $this->serializer->serialize(
                    $userAuth,
                    'json',
                    $this->getSerializationContext(['admin'])
                )
            );
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
    public function remove(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var User $userAuth */
            $userAuth = $request->getAttribute("user");

            $user = $this->userRepos->find((int)$args['userId']);
            if ($user === null || $userAuth->getId() !== $user->getId()) {
                throw new \Exception(
                    'de ingelogde gebruiker en de te verwijderen gebruiker zijn verschillend',
                    E_ERROR
                );
            }

            $this->syncService->revertPoolUsers($userAuth);

            $this->userRepos->remove($user);
            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422, $this->logger);
        }
    }
}
