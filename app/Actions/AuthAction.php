<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\DomainRecordNotFoundException;
use App\Response\ErrorResponse;
use Psr\Log\LoggerInterface;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Auth\Service as AuthService;
use SuperElf\Auth\Item as AuthItem;
use DateTimeImmutable;
use JMS\Serializer\SerializerInterface;
use \Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\Config\Configuration;
use stdClass;
use Tuupola\Base62;

final class AuthAction extends Action
{
    protected AuthService $authService;
    protected UserRepository $userRepos;
    protected Configuration $config;

    public function __construct(
        AuthService $authService,
        UserRepository $userRepository,
        LoggerInterface $logger,
        SerializerInterface $serializer,
        Configuration $config
    ) {
        parent::__construct($logger, $serializer);
        $this->authService = $authService;
        $this->userRepos = $userRepository;
        $this->config = $config;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function validateToken(Request $request, Response $response, array $args): Response
    {
        return $response->withStatus(200);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array<string, int|string> $args
     * @return Response
     */
    public function register(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var stdClass $registerData */
            $registerData = $this->getFormData($request);
            if (property_exists($registerData, "emailaddress") === false) {
                throw new \Exception("geen emailadres ingevoerd");
            }
            if (property_exists($registerData, "name") === false) {
                throw new \Exception("geen naam ingevoerd");
            }
            if (property_exists($registerData, "password") === false) {
                throw new \Exception("geen wachtwoord ingevoerd");
            }
            $emailAddress = strtolower(trim((string)$registerData->emailaddress));
            $name = strtolower(trim((string)$registerData->name));
            $password = (string)$registerData->password;

            $user = $this->authService->register($emailAddress, $name, $password);
            if ($user === null) {
                throw new \Exception("de nieuwe gebruiker kan niet worden geretourneerd");
            }

            return $response->withStatus(200);
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
    public function validate(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var stdClass $registerData */
            $registerData = $this->getFormData($request);
            if (property_exists($registerData, "emailaddress") === false) {
                throw new \Exception("geen emailadres ingevoerd");
            }
            if (property_exists($registerData, "key") === false) {
                throw new \Exception("geen sleutel ingevoerd");
            }
            $emailaddress = (string)$registerData->emailaddress;
            $key = (string)$registerData->key;
            $user = $this->authService->validate($emailaddress, $key);

            $authItem = new AuthItem($this->authService->createToken($user), $user);
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
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
    public function login(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var stdClass $authData */
            $authData = $this->getFormData($request);
            if (!property_exists($authData, "emailaddress")) {
                throw new \Exception("het emailadres is niet opgegeven", E_ERROR);
            }
            $emailaddress = (string)$authData->emailaddress;
            if (strlen($emailaddress) === 0) {
                throw new \Exception("het emailadres is niet opgegeven", E_ERROR);
            }
            $emailaddress = filter_var(strtolower(trim($emailaddress)), FILTER_VALIDATE_EMAIL);
            if ($emailaddress === false) {
                throw new \Exception('het emailadres is onjuist', E_ERROR);
            }
            if (!property_exists($authData, "password")) {
                throw new \Exception("het wachtwoord is niet opgegeven");
            }
            $password = (string)$authData->password;
            $user = $this->userRepos->findOneBy(array('emailaddress' => $emailaddress));

            if (!$user or !password_verify($user->getSalt() . $password, $user->getPassword())) {
                throw new \Exception("ongeldige emailadres-wachtwoord-combinatie");
            }

            if (!$user->getValidated()) {
                throw new \Exception("valideer eerst je emailadres met behulp van de link in je ontvangen email", E_ERROR);
            }

            $authItem = new AuthItem($this->authService->createToken($user), $user);
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
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
    public function passwordreset(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var stdClass $paswordResetData */
            $paswordResetData = $this->getFormData($request);
            if (property_exists($paswordResetData, "emailaddress") === false) {
                throw new \Exception("geen emailadres ingevoerd");
            }
            $emailAddress = strtolower(trim((string)$paswordResetData->emailaddress));

            $retVal = $this->authService->sendPasswordCode($emailAddress);

            $data = ["retval" => $retVal];
            $json = $this->serializer->serialize($data, 'json');
            return $this->respondWithJson($response, $json);
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
    public function passwordchange(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var stdClass $paswordChangeData */
            $paswordChangeData = $this->getFormData($request);
            if (property_exists($paswordChangeData, "emailaddress") === false) {
                throw new \Exception("geen emailadres ingevoerd");
            }
            if (property_exists($paswordChangeData, "password") === false) {
                throw new \Exception("geen wachtwoord ingevoerd");
            }
            if (property_exists($paswordChangeData, "code") === false) {
                throw new \Exception("geen code ingevoerd");
            }
            $emailAddress = strtolower(trim((string)$paswordChangeData->emailaddress));
            $password = (string)$paswordChangeData->password;
            $code = (string)$paswordChangeData->code;

            $user = $this->authService->changePassword($emailAddress, $password, $code);

            $authItem = new AuthItem($this->authService->createToken($user), $user);
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
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
    public function extendToken(Request $request, Response $response, array $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");
            $authItem = new AuthItem($this->authService->createToken($user), $user);
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }
}
