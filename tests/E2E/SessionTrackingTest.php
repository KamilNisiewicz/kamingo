<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\SessionCompletion;
use App\Entity\User;

class SessionTrackingTest extends WebTestCase
{
    private function loginAsTester($client)
    {
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            '_username' => 'tester',
            '_password' => 'password',
        ]);
        $client->submit($form);
        $client->followRedirect();
    }

    public function testDashboardLoadsSuccessfully(): void
    {
        $client = static::createClient();
        $this->loginAsTester($client);

        // Sprawdź że dashboard się ładuje
        $crawler = $client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // Powinna być sekcja z progress
        $this->assertSelectorExists('h3');
        $this->assertSelectorTextContains('h3', 'Dzisiejszy postęp');

        // Powinny być karty sesji
        $this->assertSelectorExists('.session-card');
    }

    public function testCanStartProgrammingSession(): void
    {
        $client = static::createClient();
        $this->loginAsTester($client);

        // Spróbuj rozpocząć sesję programming
        $client->request('GET', '/learn/programming');

        // Powinno przekierować do karty lub pokazać info o braku słówek
        $this->assertTrue(
            $client->getResponse()->isRedirect('/learn/card') ||
            $client->getResponse()->isRedirect('/dashboard'),
            'Should redirect to card or dashboard'
        );
    }

    public function testCanStartTravelSession(): void
    {
        $client = static::createClient();
        $this->loginAsTester($client);

        // Spróbuj rozpocząć sesję travel
        $client->request('GET', '/learn/travel');

        // Powinno przekierować do karty lub pokazać info o braku słówek
        $this->assertTrue(
            $client->getResponse()->isRedirect('/learn/card') ||
            $client->getResponse()->isRedirect('/dashboard'),
            'Should redirect to card or dashboard'
        );
    }

    public function testSessionCompletionEntityWorks(): void
    {
        // Test że możemy zapisać i odczytać SessionCompletion z bazy
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['username' => 'tester']);
        $this->assertNotNull($user, 'User tester should exist');

        // Wyczyść poprzednie
        $em->createQuery('DELETE FROM App\Entity\SessionCompletion sc WHERE sc.user = :user')
            ->setParameter('user', $user)
            ->execute();

        // Utwórz nowy SessionCompletion
        $sessionCompletion = new SessionCompletion();
        $sessionCompletion->setUser($user);
        $sessionCompletion->setCategory('programming');
        $sessionCompletion->setCompletedDate(\DateTime::createFromImmutable(new \DateTimeImmutable('today')));

        $em->persist($sessionCompletion);
        $em->flush();

        // Sprawdź że został zapisany
        $em->clear();
        $saved = $em->getRepository(SessionCompletion::class)->findOneBy(['user' => $user, 'category' => 'programming']);

        $this->assertNotNull($saved, 'SessionCompletion should be persisted');
        $this->assertEquals('programming', $saved->getCategory());
    }
}
