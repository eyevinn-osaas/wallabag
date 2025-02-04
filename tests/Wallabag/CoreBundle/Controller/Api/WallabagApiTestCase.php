<?php

namespace Tests\Wallabag\CoreBundle\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Wallabag\CoreBundle\Entity\User;

abstract class WallabagApiTestCase extends WebTestCase
{
    /**
     * @var KernelBrowser
     */
    protected $client;

    /**
     * @var UserInterface
     */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createAuthorizedClient();
    }

    /**
     * @return KernelBrowser
     */
    protected function createUnauthorizedClient()
    {
        static::ensureKernelShutdown();

        return static::createClient();
    }

    /**
     * @return KernelBrowser
     */
    protected function createAuthorizedClient()
    {
        $client = $this->createUnauthorizedClient();
        $container = $client->getContainer();

        /** @var UserManager $userManager */
        $userManager = $container->get('fos_user.user_manager.test');
        $firewallName = $container->getParameter('fos_user.firewall_name');

        $this->user = $userManager->findUserBy(['username' => 'admin']);

        $client->loginUser($this->user, $firewallName);

        return $client;
    }

    /**
     * Return the ID for the user admin.
     * Used because on heavy testing we don't want to re-create the database on each run.
     * Which means "admin" user won't have id 1 all the time.
     *
     * @param string $username
     *
     * @return int
     */
    protected function getUserId($username = 'admin')
    {
        return $this->client
            ->getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findOneByUserName($username)
            ->getId();
    }
}
