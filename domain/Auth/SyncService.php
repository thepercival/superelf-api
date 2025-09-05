<?php

declare(strict_types=1);

namespace SuperElf\Auth;

use App\Mailer;
use DateTimeImmutable;
use SuperElf\Role;
use SuperElf\User;
use SuperElf\User\Repository as UserRepository;
use SuperElf\Pool;
use Selective\Config\Configuration;

final class SyncService
{
    public function __construct(
        /*private UserRepository $userRepos,*/
        protected Mailer $mailer,
        protected Configuration $config
    ) {
    }

    public function add(Pool $pool, int $roles, string $emailaddress = null, bool $sendMail = false): void
    {
//        if (strlen($emailaddress) === 0) {
//            return;
//        }
//        /** @var User|null $user */
//        $user = $this->userRepos->findOneBy(["emailaddress" => $emailaddress]);
//
//        if ($user !== null) {
//            $poolUser = $pool->getUser($user);
//            $newUser = $poolUser === null;
//            if ($newUser) {
//                $poolUser = new PoolUser($pool, $user, $roles);
//            } else {
//                $poolUser->setRoles($poolUser->getRoles() | $roles);
//            }
//            $this->poolUserRepos->save($poolUser);
//            if ($sendMail && $newUser) {
//                $this->sendEmailPoolUser($poolUser);
//            }
//            return $poolUser;
//        }
//
//        $invitation = $this->poolInvitationRepos->findOneBy(
//            ["pool" => $pool, "emailaddress" => $emailaddress]
//        );
//        $newInvitation = $invitation === null;
//        if ($newInvitation) {
//            $invitation = new PoolInvitation($pool, $emailaddress, $roles);
//            $invitation->setCreatedDateTime(new DateTimeImmutable());
//        } else {
//            $invitation->setRoles($invitation->getRoles() | $roles);
//        }
//        $this->poolInvitationRepos->save($invitation);
//        if ($sendMail && $newInvitation) {
//            $this->sendEmailPoolInvitation($invitation);
//        }
//        return $invitation;
    }

    public function remove(Pool $pool, int $roles, string $emailaddress = null): void
    {
//        if (strlen($emailaddress) === 0) {
//            return;
//        }
//        /** @var User|null $user */
//        $user = $this->userRepos->findOneBy(["emailaddress" => $emailaddress]);
//
//        if ($user !== null) {
//            $poolUser = $pool->getUser($user);
//            if ($poolUser === null) {
//                return;
//            }
//            $rolesToRemove = $poolUser->getRoles() & $roles;
//            if ($poolUser->getRoles() === $rolesToRemove) {
//                $pool->getUsers()->removeElement($poolUser);
//                $this->poolUserRepos->remove($poolUser);
//            } else {
//                $poolUser->setRoles($poolUser->getRoles() - $rolesToRemove);
//                $this->poolUserRepos->save($poolUser);
//            }
//            return;
//        }
//
//        $invitation = $this->poolInvitationRepos->findOneBy(
//            ["pool" => $pool, "emailaddress" => $emailaddress]
//        );
//        if ($invitation === null) {
//            return;
//        }
//        $rolesToRemove = $invitation->getRoles() & $roles;
//        if ($invitation->getRoles() === $rolesToRemove) {
//            $this->poolInvitationRepos->remove($invitation);
//        } else {
//            $invitation->setRoles($invitation->getRoles() - $rolesToRemove);
//            $this->poolInvitationRepos->save($invitation);
//        }
    }

    /**
     * @param User $user
     * @return list<string>
     */
    public function processInvitations(User $user/*, array $invitations*/): array
    {
//        $poolUsers = [];
//        while (count($invitations) > 0) {
//            $invitation = array_shift($invitations);
//            $this->poolInvitationRepos->remove($invitation);
//            $poolUsers[] = $this->poolUserRepos->save(
//                new PoolUser(
//                    $invitation->getPool(), $user, $invitation->getRoles()
//                )
//            );
//        }
//        return $poolUsers;
        return [];
    }

    /**
     * @param User $user
     * @return list<string>
     */
    public function revertPoolUsers(User $user): array
    {
        return [];
//        $invitations = [];
//        $poolUsers = $this->poolUserRepos->findBy(["user" => $user]);
//        while (count($poolUsers) > 0) {
//            $poolUser = array_shift($poolUsers);
//            $poolUser->getPool()->getUsers()->removeElement($poolUser);
//            $this->poolUserRepos->remove($poolUser);
//            $invitation = new PoolInvitation(
//                $poolUser->getPool(),
//                $poolUser->getUser()->getEmailaddress(),
//                $poolUser->getRoles()
//            );
//            $invitation->setCreatedDateTime(new DateTimeImmutable());
//            $invitations[] = $this->poolInvitationRepos->save($invitation);
//        }
//        return $invitations;
    }

//    protected function sendEmailPoolUser(PoolUser $poolUser)
//    {
//        $url = $this->config->getString('www.wwwurl');
//        $poolName = $poolUser->getPool()->getCompetition()->getLeague()->getName();
//        $suffix = "<p>Wanneer je <a href=\"" . $url . "user/login\">inlogt</a> op " . $url . " staat toernooi \"" . $poolName . "\"  bij je toernooien. </a></p>";
//        $this->sendEmailForAuthorization(
//            $poolUser->getUser()->getEmailaddress(),
//            $poolName,
//            $poolUser->getRoles(),
//            $suffix
//        );
//    }
//
//    protected function sendEmailPoolInvitation(PoolInvitation $invitation)
//    {
//        $url = $this->config->getString('www.wwwurl');
//        $poolName = $invitation->getPool()->getCompetition()->getLeague()->getName();
//        $suffix = "<p>Wanneer je je <a href=\"" . $url . "user/register\">registreert</a> op " . $url . " staat toernooi \"" . $poolName . "\"  bij je toernooien. </a></p>";
//        $this->sendEmailForAuthorization(
//            $invitation->getEmailaddress(),
//            $poolName,
//            $invitation->getRoles(),
//            $suffix
//        );
//    }

//    protected function sendEmailForAuthorization(
//        string $emailadress,
//        string $poolName,
//        int $roles,
//        string $suffix
//    ) {
//        $subject = 'uitnodiging voor toernooi "' . $poolName . '"';
//        $url = $this->config->getString('www.wwwurl');
//
//        $body = "<p>Hallo,</p>" .
//            "<p>Je bent uitgenodigd op " . $url . " voor toernooi \"" . $poolName . "\" door de beheerder van dit toernooi.<br/>" .
//            "Je hebt de volgende rollen gekregen:</p>" .
//            $this->getRoleDefinitions($roles) .
//            $suffix .
//            "<p>met vriendelijke groet,<br/>FCToernooi</p>";
//        $this->mailer->send($subject, $body, $emailadress);
//    }
//
//    protected function getRoleDefinitions(int $roles): string
//    {
//        $retVal = "<table>";
//        foreach (Role::getDefinitions($roles) as $definition) {
//            $retVal .= "<tr><td>" . $definition["name"] . "</td><td>" . $definition["description"] . "</td></tr>";
//        }
//        return $retVal . "</table>";
//    }
}
