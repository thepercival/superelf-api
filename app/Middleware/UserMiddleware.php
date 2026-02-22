<?php

declare(strict_types=1);

namespace App\Middleware;

use App\AuthToken as AuthToken;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SuperElf\User;

final class UserMiddleware implements MiddlewareInterface
{
    /** @var EntityRepository<User>  */
    protected EntityRepository $userRepos;

    public function __construct(
        protected EntityManagerInterface $entityManager)
    {
        $this->userRepos = $this->entityManager->getRepository(User::class);
    }

    #[\Override]
    public function process(Request $request, RequestHandler $handler): Response
    {
        if ($request->getMethod() === "OPTIONS") {
            return $handler->handle($request);
        }

        /** @var AuthToken|null $token */
        $token = $request->getAttribute('token');
        if ($token === null) {
            return $handler->handle($request);
        }
        $user = $this->getUser($token);
        if ($user === null) {
            return $handler->handle($request);
        }
        return $handler->handle($request->withAttribute("user", $user));
    }

    protected function getUser(AuthToken $token): ?User
    {
        return $this->userRepos->find($token->getUserId());
    }
}
