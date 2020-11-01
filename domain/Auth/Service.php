<?php

declare(strict_types=1);

namespace SuperElf\Auth;

use Exception;
use SuperElf\Auth\SyncService as AuthSyncService;
use SuperElf\Competitor;
use SuperElf\Role;
use SuperElf\Pool;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Pool\Invitation\Repository as PoolInvitationRepository;
use SuperElf\Pool\Repository as PoolRepository;
use Firebase\JWT\JWT;
use Selective\Config\Configuration;
use Tuupola\Base62;
use App\Mailer;

class Service
{
    protected UserRepository $userRepos;
    protected PoolRepository $poolRepos;
    protected AuthSyncService $syncService;
    protected Configuration $config;
    protected Mailer $mailer;

    public function __construct(
        UserRepository $userRepos,
        PoolRepository $poolRepos,
        AuthSyncService $syncService,
        Configuration $config,
        Mailer $mailer
    ) {
        $this->userRepos = $userRepos;
        $this->poolRepos = $poolRepos;
        $this->syncService = $syncService;
        $this->config = $config;
        $this->mailer = $mailer;
    }

    /**
     * @param string $emailaddress
     * @param string $name
     * @param string $password
     * @return User|null
     * @throws Exception
     */
    public function register(string $emailaddress, string $name, string $password): ?User
    {
        if (strlen($password) < User::MIN_LENGTH_PASSWORD or strlen($password) > User::MAX_LENGTH_PASSWORD) {
            throw new \InvalidArgumentException(
                "het wachtwoord moet minimaal " . User::MIN_LENGTH_PASSWORD . " karakters bevatten en mag maximaal " . User::MAX_LENGTH_PASSWORD . " karakters bevatten",
                E_ERROR
            );
        }
        $userTmp = $this->userRepos->findOneBy(array('emailaddress' => $emailaddress));
        if ($userTmp) {
            throw new Exception("het emailadres is al in gebruik", E_ERROR);
        }
        $userTmp = $this->userRepos->findOneBy(array('name' => $name));
        if ($userTmp) {
            throw new Exception("de naam is al in gebruik", E_ERROR);
        }

        $user = new User($emailaddress);
        $user->setName($name);
        $user->setSalt(bin2hex(random_bytes(15)));
        $user->setPassword(password_hash($user->getSalt() . $password, PASSWORD_DEFAULT));

        $savedUser = $this->userRepos->save($user);
        $this->sendRegisterEmail($emailaddress);
        return $savedUser;
    }

    /**
     * @param string $emailaddress
     */
    protected function sendRegisterEmail(string $emailaddress)
    {
        $subject = 'welkom bij SuperElf';
        $baseUrl = $this->config->getString("www.wwwurl");
        $bodyBegin = <<<EOT
<p>Hallo,</p>
<p>Welkom bij de SuperElf! Wij wensen je veel plezier met het gebruik van de SuperElf. Om je account te gebruiken dien je je emailadres te valideren.
Dat kan met onderstaande link:</p>
EOT;
        $bodyMiddle = '<p>';
        $validateUrl = $baseUrl . 'public/auth/validate?emailaddress=' . urlencode($emailaddress) . '&key=' . $this->getValidateKey($emailaddress);
        $bodyMiddle .= '<a href="'.$validateUrl.'">' . $validateUrl . '</a>';
        $bodyMiddle .= '</p>';

        $bodyEnd = '<p>met vriendelijke groet,<br/><br/>Coen Dunnink<br/><a href="' . $baseUrl . '">SuperElf</a></p>';
        $this->mailer->send($subject, $bodyBegin . $bodyMiddle . $bodyEnd, $emailaddress);
    }

    protected function getValidateKey(string $emailaddress): string {
        return hash( "sha1", $this->config->getString("auth.validatesecret") . $emailaddress );
    }

    public function validate(string $emailaddress, string $key)
    {
        $user = $this->userRepos->findOneBy(array('emailaddress' => $emailaddress));
        if ($user === null || $user->getValidated() ) {
            throw new Exception("de gebruiker kan niet gevalideerd worden", E_ERROR);
        }
        if( $key !== $this->getValidateKey($emailaddress) ) {
            throw new Exception("de gebruiker kan niet gevalideerd worden", E_ERROR);
        }
        $user->setValidated(true);
        $this->userRepos->save($user);
    }

    public function sendPasswordCode($emailAddress)
    {
        $user = $this->userRepos->findOneBy(array('emailaddress' => $emailAddress));
        if (!$user) {
            throw new \Exception("kan geen code versturen");
        }
        $conn = $this->userRepos->getEM()->getConnection();
        $conn->beginTransaction();
        try {
            $user->resetForgetpassword();
            $this->userRepos->save($user);
            $this->mailPasswordCode($user);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }


        return true;
    }

    public function createToken(User $user)
    {
        $jti = (new Base62)->encode(random_bytes(16));

        $now = new \DateTimeImmutable();
        $future = $now->modify("+3 months");
        // $future = $now->modify("+10 seconds");

        $payload = [
            "iat" => $now->getTimestamp(),
            "exp" => $future->getTimestamp(),
            "jti" => $jti,
            "sub" => $user->getId(),
        ];

        return JWT::encode($payload, $this->config->getString("auth.jwtsecret"));
    }

    protected function mailPasswordCode(User $user)
    {
        $subject = 'wachtwoord herstellen';
        $forgetpasswordToken = $user->getForgetpasswordToken();
        $forgetpasswordDeadline = $user->getForgetpasswordDeadline()->modify("-1 days")->format("Y-m-d");
        $body = <<<EOT
<p>Hallo,</p>
<p>            
Met deze code kun je je wachtwoord herstellen: $forgetpasswordToken 
</p>
<p>            
Let op : je kunt deze code gebruiken tot en met $forgetpasswordDeadline
</p>
<p>
met vriendelijke groet,
<br>
FCToernooi
</p>
EOT;
        $this->mailer->send($subject, $body, $user->getEmailaddress());
    }

    public function changePassword($emailAddress, $password, $code)
    {
        $user = $this->userRepos->findOneBy(array('emailaddress' => $emailAddress));
        if (!$user) {
            throw new \Exception("het wachtwoord kan niet gewijzigd worden");
        }
        // check code and deadline
        if ($user->getForgetpasswordToken() !== $code) {
            throw new \Exception("het wachtwoord kan niet gewijzigd worden, je hebt een onjuiste code gebruikt");
        }
        $now = new \DateTimeImmutable();
        if ($now > $user->getForgetpasswordDeadline()) {
            throw new \Exception("het wachtwoord kan niet gewijzigd worden, de wijzigingstermijn is voorbij");
        }

        // set password
        $user->setPassword(password_hash($user->getSalt() . $password, PASSWORD_DEFAULT));
        $user->setForgetpassword(null);
        return $this->userRepos->save($user);
    }
}
