# Kamingo

[![CI](https://github.com/KamilNisiewicz/kamingo/actions/workflows/ci.yml/badge.svg)](https://github.com/KamilNisiewicz/kamingo/actions)

Aplikacja webowa do nauki języka angielskiego z wykorzystaniem spaced repetition i AI. Specjalistyczne słownictwo dla programistów (**Programming**) i podróżników (**Travel**).

## Live Demo

🚀 **Live app:** https://kamingo.cfolks.pl/

**Test credentials:**
- Username: `tester`
- Password: `password`

---

## Tech Stack

- Symfony 7.2 + PHP 8.2
- MySQL 8.0
- Twig + Tailwind CSS
- OpenRouter (Claude 3.5 Sonnet) - AI word generation
- PHPUnit - E2E tests
- GitHub Actions - CI/CD

## Instalacja

### Wymagania

- PHP 8.2+
- MySQL 8.0+
- Composer

### Setup

```bash
# Clone
git clone https://github.com/KamilNisiewicz/kamingo.git
cd kamingo

# Install dependencies
composer install

# Configure database (.env.local)
DATABASE_URL="mysql://user:password@127.0.0.1:3306/kamingo?serverVersion=8.0.44"
OPENROUTER_API_KEY="your-api-key"

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Create users
php bin/console app:create-user tester password

# Generate words (requires OpenRouter API key)
php bin/console app:generate-words programming 50
php bin/console app:generate-words travel 50

# Start server
symfony server:start
# or
php -S localhost:8000 -t public/
```

Visit: http://localhost:8000

## Login

- **User:** `tester` / `password` (admin panel: `/admin`)

## Funkcjonalności

- System logowania
- Dwie kategorie słówek: Programming i Travel
- Daily sessions: 5 nowych + powtórka
- Spaced repetition: 1d → 3d → 7d → 14d → 30d
- Flashcards UI
- Progress tracking (X/2 sesji dzisiaj)
- AI-generated words (OpenRouter)
- Admin panel (zarządzanie słówkami)

## Komendy

```bash
# Create user
php bin/console app:create-user <username> <password>

# Change password
php bin/console app:change-password <username> <new-password>

# Generate AI words
php bin/console app:generate-words <category> <count>
```

## Testy

### Setup testów

```bash
# Create test database
sudo mysql
CREATE DATABASE kamingo_test;
GRANT ALL PRIVILEGES ON kamingo_test.* TO 'kamingo_user'@'localhost';
EXIT;

# Create schema
php bin/console doctrine:schema:create --env=test

# Seed test data
php bin/console app:create-user tester password --env=test
```

### Uruchomienie

```bash
# All tests
php bin/phpunit

# E2E only
php bin/phpunit tests/E2E/

# Specific test
php bin/phpunit tests/E2E/LoginTest.php
```

**Pokrycie:** Login, Sessions, Progress persistence

## Struktura

```
src/
├── Command/         # CLI commands
├── Controller/      # Controllers
├── Entity/          # Doctrine entities
├── Repository/      # Repositories
└── Service/         # Business logic
templates/           # Twig templates
tests/E2E/           # E2E tests
```

## Deployment

```bash
composer install --no-dev --optimize-autoloader

# Configure .env.local for production
APP_ENV=prod
DATABASE_URL="..."
OPENROUTER_API_KEY="..."

php bin/console doctrine:migrations:migrate --no-interaction
```

## Licencja

Proprietary

## Autor

Kamil Nisiewicz - [@KamilNisiewicz](https://github.com/KamilNisiewicz)
