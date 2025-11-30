# Dokument wymagań produktu (PRD) - Kamingo

Data: 30.11.2025
Wersja: 1.0 (MVP)

---

## 1. Przegląd produktu

Kamingo to aplikacja webowa do nauki języka angielskiego z wykorzystaniem spaced repetition i AI. Aplikacja koncentruje się na kontekstowym słownictwie dla dwóch specjalistycznych kategorii:

- Programming - słownictwo techniczne dla programistów (API, refactor, deploy, debugging)
- Travel - słownictwo podróżnicze (hotel, airport, restaurant, directions)

Aplikacja generuje słówka z przykładami przy użyciu AI (Claude 3.5 Sonnet), a następnie prowadzi użytkownika przez codzienne sesje nauki wykorzystując algorytm spaced repetition.

---

## 2. Problem użytkownika

Programiści i osoby podróżujące potrzebują specjalistycznego słownictwa, którego nie oferują ogólne aplikacje do nauki języków:

- Ogólne aplikacje uczą podstawowego słownictwa ("apple, cat, dog")
- Brak kontekstu branżowego w przykładach
- Słownictwo nie jest dostosowane do rzeczywistych sytuacji zawodowych/podróżniczych

Rezultat: Wolniejsza nauka, mniejsza retencja, frustracja z braku relevantnych materiałów.

---

## 3. Wymagania funkcjonalne (MVP)

### 3.1 Mechanizm kontroli dostępu
- Login form (email + password)
- Logout functionality
- Session management
- Aplikacja dla ograniczonej liczby użytkowników (personal use)

### 3.2 Model danych (CRUD)
- Entity: User (id, email, password, roles, createdAt)
- Entity: Word (id, word, translation, example, category, createdAt)
- Entity: UserProgress (id, user, word, status, nextReviewDate, repetitions, lastReviewedAt)
- Migracje bazy danych
- Admin panel do zarządzania słówkami

### 3.3 Logika biznesowa - Daily Sessions
- Programming Session:
  - 5 nowych słówek dziennie
  - ~15 słówek do powtórki (spaced repetition)
- Travel Session:
  - 5 nowych słówek dziennie
  - ~15 słówek do powtórki
- User decyduje którą sesję uruchomić (minimum 1, optimum 2)

### 3.4 Spaced Repetition Algorithm
- Algorytm interwałów:
  - Nowa karta → 1 dzień
  - Pamiętam → 3 dni
  - Pamiętam → 7 dni
  - Pamiętam → 14 dni
  - Pamiętam → 30 dni
  - Nie pamiętam → cofam o krok
- Status kart: new, learning, mastered
- Automatyczne obliczanie nextReviewDate

### 3.5 Flashcards UI
- Wyświetlenie słówka (angielskie)
- Przycisk "Show answer" (pokazuje tłumaczenie + przykład)
- Przyciski: "Again" / "Good" / "Easy"
- Progress bar (X/20 cards)
- Card flip animation

### 3.6 Dashboard
- Wyświetlenie aktualnego użytkownika
- Programming Session card:
  - "5 new + X review"
  - Przycisk [Start Session]
- Travel Session card:
  - "5 new + X review"
  - Przycisk [Start Session]
- Progress indicator: 0/2, 1/2, 2/2 sessions today
- "Perfect day!" message jeśli 2/2

### 3.7 AI Integration
- Command line tool do generowania słówek
- OpenRouter API client (Claude 3.5 Sonnet)
- Prompt engineering dla każdej kategorii:
  - Programming: technical vocabulary, code context, poziom B1-C1
  - Travel: practical phrases, real situations, poziom B1-C1
- Parsing AI response → Word entities
- Zapisywanie do bazy danych
- Generowanie 100+ słówek per kategoria

### 3.8 Testing & Quality Assurance
- Testy E2E pokrywające główne user flows:
  - Login
  - Start Programming Session
  - Przejście przez kompletną sesję
  - Sprawdzenie persystencji postępu
- CI/CD pipeline (automatyczne testy przy każdym commicie)

---

## 4. Granice produktu (Out of Scope dla MVP)

Następujące funkcjonalności NIE są częścią MVP (można dodać w przyszłości):

- Quiz mode (wybór z 4 opcji)
- Writing mode (wpisywanie tłumaczenia)
- Sentence completion
- Audio / Text-to-speech
- Listening practice
- Rejestracja użytkowników przez UI
- User preferences / settings UI
- Dashboard stats (streak, total words learned)
- "Mark as difficult" feature
- Practice mode (unlimited review)
- Więcej kategorii (business, medical, etc.)
- Mobile app
- Gamification (achievements, badges)

---

## 5. Metryki sukcesu

### 5.1 Funkcjonalność
- Aplikacja działa stabilnie bez crashów
- Spaced repetition algorithm działa poprawnie
- AI generuje wysokiej jakości słówka z kontekstem
- Sesje trwają ~10 minut (20 kart)
- Pipeline CI/CD passing

### 5.2 Jakość kodu i dokumentacja
- Kod jest czytelny i maintainable
- Testy pokrywają główne user flows
- Dokumentacja pozwala uruchomić projekt lokalnie w < 5 minut

### 5.3 User Experience
- Intuicyjny interface
- Responsywny design (działa na desktop i mobile)
- Szybkie ładowanie (< 2s)
- Flashcards działają płynnie

### 5.4 Użytkowanie (personal use)
- Codzienne użycie przez min. 1 tydzień po deploymencie
- Efektywna nauka: 10 nowych słówek dziennie (5+5)
- Retencja: słówka wracają w odpowiednich interwałach

---

Dokument utworzony: 30.11.2025
Status: Approved for implementation
