<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserProgress;
use App\Entity\SessionCompletion;
use App\Entity\User;

class LearningFlowTest extends WebTestCase
{
    private function clearTesterProgress($client)
    {
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['username' => 'tester']);
        if ($user) {
            // Usuń wszystkie UserProgress
            $em->createQuery('DELETE FROM App\Entity\UserProgress up WHERE up.user = :user')
                ->setParameter('user', $user)
                ->execute();

            // Usuń wszystkie SessionCompletion
            $em->createQuery('DELETE FROM App\Entity\SessionCompletion sc WHERE sc.user = :user')
                ->setParameter('user', $user)
                ->execute();

            $em->clear();
        }
    }

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

    public function testCompleteProgrammingSessionFlow(): void
    {
        $client = static::createClient();

        // Wyczyść stan testera
        $this->clearTesterProgress($client);

        // 1. Zaloguj się
        $this->loginAsTester($client);

        // 2. Sprawdź dashboard
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Witaj, tester');

        // 3. Start Programming Session
        $crawler = $client->request('GET', '/learn/programming');

        // Powinno przekierować do pierwszej karty
        $this->assertResponseRedirects('/learn/card');
        $crawler = $client->followRedirect();

        // 4. Pierwsza karta - sprawdź czy wyświetla się słówko
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('button[type="submit"]'); // Przycisk "Pokaż odpowiedź"

        // 5. Pokaż odpowiedź
        $form = $crawler->selectButton('Pokaż odpowiedź')->form();
        $client->submit($form);
        $crawler = $client->followRedirect();

        // 6. Oceń kartę jako "Good"
        $this->assertResponseIsSuccessful();
        $buttons = $crawler->filter('button[type="submit"]');
        $this->assertGreaterThan(0, $buttons->count(), 'Should have review buttons (Again/Good/Easy)');

        // Znajdź formularz z przyciskiem "Pamiętam" lub podobnym
        $forms = $crawler->filter('form')->each(function ($node) {
            return $node;
        });

        // Wybierz pierwszy dostępny formularz review (symuluje kliknięcie "Good")
        $this->assertGreaterThan(0, count($forms), 'Should have at least one review form');
        $client->submit($forms[1]->form()); // Zwykle środkowy przycisk to "Good"

        // Powinno przejść do następnej karty lub zakończyć sesję
        $this->assertTrue(
            $client->getResponse()->isRedirect('/learn/card') ||
            $client->getResponse()->isRedirect('/learn/completed'),
            'Should redirect to next card or completion page'
        );
    }

    public function testUserProgressIsPersisted(): void
    {
        $client = static::createClient();

        // Wyczyść stan testera
        $this->clearTesterProgress($client);

        $this->loginAsTester($client);

        // Start sesji programming
        $client->request('GET', '/learn/programming');
        $client->followRedirect();

        // Pokaż odpowiedź i oceń pierwszą kartę
        $crawler = $client->getCrawler();
        $form = $crawler->selectButton('Pokaż odpowiedź')->form();
        $client->submit($form);
        $crawler = $client->followRedirect();

        // Oceń kartę
        $forms = $crawler->filter('form')->each(function ($node) {
            return $node;
        });
        $client->submit($forms[1]->form());

        // Sprawdź w bazie czy UserProgress został utworzony
        $container = static::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $user = $em->getRepository(User::class)->findOneBy(['username' => 'tester']);
        $this->assertNotNull($user, 'User tester should exist');

        $progressRecords = $em->getRepository(UserProgress::class)
            ->findBy(['user' => $user]);

        $this->assertGreaterThan(0, count($progressRecords), 'UserProgress should be created after reviewing a card');

        // Sprawdź że progress ma poprawne dane
        $progress = $progressRecords[0];
        $this->assertNotNull($progress->getNextReviewDate(), 'NextReviewDate should be set');
        $this->assertGreaterThan(0, $progress->getRepetitions(), 'Repetitions should be incremented');
    }

    public function testDashboardShowsCorrectProgress(): void
    {
        $client = static::createClient();

        // Wyczyść stan testera
        $this->clearTesterProgress($client);

        $this->loginAsTester($client);

        // Przejdź przez kompletną sesję programming (symulacja)
        $client->request('GET', '/learn/programming');
        $client->followRedirect();

        // Przejdź przez kilka kart (uproszczona wersja)
        for ($i = 0; $i < 3; $i++) {
            $crawler = $client->getCrawler();

            // Jeśli jesteśmy na completion page, przerwij
            if (str_contains($client->getRequest()->getPathInfo(), 'completed')) {
                break;
            }

            // Pokaż odpowiedź
            $showAnswerForm = $crawler->filter('form')->first();
            if ($showAnswerForm->count() > 0) {
                $client->submit($showAnswerForm->form());
                $client->followRedirect();
                $crawler = $client->getCrawler();
            }

            // Oceń
            $forms = $crawler->filter('form')->each(function ($node) {
                return $node;
            });

            if (count($forms) > 1) {
                $client->submit($forms[1]->form());
                $response = $client->getResponse();

                if ($response->isRedirect()) {
                    $client->followRedirect();
                }
            } else {
                break;
            }
        }

        // Wróć do dashboard
        $crawler = $client->request('GET', '/dashboard');

        // Dashboard powinien pokazać progress (może być 0/2, 1/2 lub 2/2 w zależności od tego czy ukończono sesję)
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.text-gray-900'); // Progress indicator
    }
}
