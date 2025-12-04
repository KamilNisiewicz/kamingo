<?php

namespace App\Service;

class PromptBuilder
{
    public function buildPrompt(string $category, int $count): string
    {
        return match ($category) {
            'programming' => $this->buildProgrammingPrompt($count),
            'travel' => $this->buildTravelPrompt($count),
            default => throw new \InvalidArgumentException("Unknown category: {$category}"),
        };
    }

    private function buildProgrammingPrompt(int $count): string
    {
        return <<<PROMPT
You are an English teacher creating vocabulary flashcards for software developers learning English.

Generate exactly {$count} English words with Polish translations and example sentences.

Requirements:
- Target audience: Software developers (programmers)
- Vocabulary focus: Technical terms, software development, coding, IT industry
- Difficulty level: B1-C1 (upper-intermediate to advanced)
- Context: Real software development scenarios (code reviews, meetings, documentation, debugging, deployment)
- Each word must be unique (no duplicates)
- Examples should show the word used in a realistic programming context

Word categories to include:
- Development processes (refactor, debug, deploy, merge, commit)
- Technical concepts (API, framework, library, dependency, interface)
- Code quality (optimization, scalability, maintainability, performance)
- Team collaboration (code review, pull request, sprint, standup)
- Common verbs (implement, integrate, migrate, refactor)

Output format (IMPORTANT - return valid JSON only):
{
  "vocabulary": [
    {
      "word": "refactor",
      "translation": "refaktoryzować, przepisać kod",
      "example": "We need to refactor this legacy code before adding new features."
    }
  ]
}

Generate exactly {$count} unique words. Return ONLY valid JSON, no additional text.
PROMPT;
    }

    private function buildTravelPrompt(int $count): string
    {
        return <<<PROMPT
You are an English teacher creating vocabulary flashcards for Polish travelers learning English.

Generate exactly {$count} English words with Polish translations and example sentences.

Requirements:
- Target audience: Polish travelers
- Vocabulary focus: Practical travel situations (airports, hotels, restaurants, transportation, sightseeing)
- Difficulty level: B1-C1 (upper-intermediate to advanced)
- Context: Real travel scenarios (booking hotels, asking for directions, ordering food, buying tickets)
- Each word must be unique (no duplicates)
- Examples should show the word used in realistic travel situations

Word categories to include:
- Airport & flights (departure, boarding, gate, luggage, customs)
- Accommodation (reservation, check-in, amenities, complimentary)
- Transportation (platform, schedule, fare, destination)
- Dining (cuisine, reservation, bill, tip, appetizer)
- Sightseeing (landmark, guided tour, admission, souvenir)
- Common phrases (directions, emergencies, complaints, requests)

Output format (IMPORTANT - return valid JSON only):
{
  "vocabulary": [
    {
      "word": "departure",
      "translation": "odjazd, wylot",
      "example": "The departure time for your flight is 14:30 from gate B12."
    }
  ]
}

Generate exactly {$count} unique words. Return ONLY valid JSON, no additional text.
PROMPT;
    }
}
