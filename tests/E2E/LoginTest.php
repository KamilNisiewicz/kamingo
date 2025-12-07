<?php

namespace App\Tests\E2E;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Zaloguj się');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Wypełnij formularz logowania
        $form = $crawler->selectButton('Zaloguj się')->form([
            '_username' => 'tester',
            '_password' => 'password',
        ]);

        $client->submit($form);

        // Powinno przekierować do dashboard
        $this->assertResponseRedirects('/dashboard');
        $client->followRedirect();

        // Sprawdź czy jesteśmy na dashboardzie
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Witaj, tester');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Niepoprawne dane
        $form = $crawler->selectButton('Zaloguj się')->form([
            '_username' => 'tester',
            '_password' => 'wrongpassword',
        ]);

        $client->submit($form);

        // Powinno zostać na stronie logowania z błędem
        $this->assertResponseRedirects('/login');
        $crawler = $client->followRedirect();

        // Sprawdź czy jest error message (czerwone tło z emoji ❌)
        $this->assertSelectorExists('.border-red-300');
    }

    public function testLogout(): void
    {
        $client = static::createClient();

        // Zaloguj się najpierw
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Zaloguj się')->form([
            '_username' => 'tester',
            '_password' => 'password',
        ]);
        $client->submit($form);
        $client->followRedirect();

        // Teraz wyloguj
        $client->request('GET', '/logout');

        // Powinno przekierować do strony głównej lub logowania
        $this->assertResponseRedirects();
    }
}
