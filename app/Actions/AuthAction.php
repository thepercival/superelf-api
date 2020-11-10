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
        Configuration $config) {
        parent::__construct($logger, $serializer);
        $this->authService = $authService;
        $this->userRepos = $userRepository;
        $this->config = $config;
    }

    public function validateToken(Request $request, Response $response, $args): Response
    {
        return $response->withStatus(200);
    }

    public function register(Request $request, Response $response, $args): Response
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
            $emailAddress = strtolower(trim($registerData->emailaddress));
            $name = strtolower(trim($registerData->name));
            $password = $registerData->password;

            $user = $this->authService->register($emailAddress, $name, $password);
            if ($user === null) {
                throw new \Exception("de nieuwe gebruiker kan niet worden geretourneerd");
            }

            return $response->withStatus(200);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function validate(Request $request, Response $response, $args): Response
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
            $user = $this->authService->validate($registerData->emailaddress, $registerData->key);

            $authItem = new AuthItem($this->authService->createToken($user), $user );
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function login(Request $request, Response $response, $args): Response
    {
        try {
            /** @var stdClass $authData */
            $authData = $this->getFormData($request);
            if (!property_exists($authData, "emailaddress") || strlen($authData->emailaddress) === 0) {
                throw new \Exception("het emailadres is niet opgegeven");
            }
            $emailaddress = filter_var($authData->emailaddress, FILTER_VALIDATE_EMAIL);
            if ($emailaddress === false) {
                throw new \Exception("het emailadres \"" . $authData->emailaddress . "\" is onjuist");
            }
            $emailAddress = strtolower(trim($emailaddress));
            if (!property_exists($authData, "password") || strlen($authData->password) === 0) {
                throw new \Exception("het wachtwoord is niet opgegeven");
            }

            $user = $this->userRepos->findOneBy(
                array('emailaddress' => $emailaddress)
            );

            if (!$user or !password_verify($user->getSalt() . $authData->password, $user->getPassword())) {
                throw new \Exception("ongeldige emailadres en wachtwoord combinatie");
            }

            /*if ( !$user->getActive() ) {
             throw new \Exception( "activeer eerst je account met behulp van de link in je ontvangen email", E_ERROR );
             }*/

            $authItem = new AuthItem($this->authService->createToken($user), $user );
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function passwordreset(Request $request, Response $response, $args): Response
    {
        try {
            /** @var stdClass $paswordResetData */
            $paswordResetData = $this->getFormData($request);
            if (property_exists($paswordResetData, "emailaddress") === false) {
                throw new \Exception("geen emailadres ingevoerd");
            }
            $emailAddress = strtolower(trim($paswordResetData->emailaddress));

            $retVal = $this->authService->sendPasswordCode($emailAddress);

            $data = ["retval" => $retVal];
            $json = $this->serializer->serialize($data, 'json');
            return $this->respondWithJson($response, $json);
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function passwordchange(Request $request, Response $response, $args): Response
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
            $emailAddress = $emailAddress = strtolower(trim($paswordChangeData->emailaddress));
            $password = $paswordChangeData->password;
            $code = (string)$paswordChangeData->code;

            $user = $this->authService->changePassword($emailAddress, $password, $code);

            $authItem = new AuthItem($this->authService->createToken($user), $user);
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }

    public function extendToken(Request $request, Response $response, $args): Response
    {
        try {
            /** @var User $user */
            $user = $request->getAttribute("user");
            $authItem = new AuthItem($this->authService->createToken($user), $user );
            return $this->respondWithJson($response, $this->serializer->serialize($authItem, 'json'));
        } catch (\Exception $e) {
            return new ErrorResponse($e->getMessage(), 422);
        }
    }
}
