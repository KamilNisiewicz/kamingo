<?php

namespace App\Command;

use App\Entity\Word;
use App\Entity\WordCategory;
use App\Repository\WordRepository;
use App\Service\OpenRouterClient;
use App\Service\PromptBuilder;
use App\Service\WordParserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-words',
    description: 'Generate vocabulary words using OpenRouter AI',
)]
class GenerateWordsCommand extends Command
{
    public function __construct(
        private OpenRouterClient $openRouterClient,
        private PromptBuilder $promptBuilder,
        private WordParserService $wordParser,
        private EntityManagerInterface $entityManager,
        private WordRepository $wordRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('category', InputArgument::REQUIRED, 'Category: programming or travel')
            ->addArgument('count', InputArgument::OPTIONAL, 'Number of words to generate', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $categoryString = $input->getArgument('category');
        $count = (int) $input->getArgument('count');

        // Validate category
        if (!in_array($categoryString, ['programming', 'travel'])) {
            $io->error('Category must be either "programming" or "travel"');
            return Command::FAILURE;
        }

        // Validate count
        if ($count < 1 || $count > 100) {
            $io->error('Count must be between 1 and 100');
            return Command::FAILURE;
        }

        $category = WordCategory::from($categoryString);

        $io->title(sprintf('Generating %d %s words', $count, $categoryString));

        try {
            // Build prompt
            $io->section('Step 1: Building prompt');
            $prompt = $this->promptBuilder->buildPrompt($categoryString, $count);
            $io->info('Prompt ready');

            // Call AI
            $io->section('Step 2: Calling OpenRouter API');
            $progressBar = new ProgressBar($output);
            $progressBar->start();

            $response = $this->openRouterClient->generate($prompt);

            $progressBar->finish();
            $io->newLine(2);
            $io->success('API call successful!');

            // Parse response
            $io->section('Step 3: Parsing response');
            $words = $this->wordParser->parseAIResponse($response);
            $io->info(sprintf('Parsed %d words', count($words)));

            // Save to database
            $io->section('Step 4: Saving to database');
            $saved = 0;
            $skipped = 0;

            foreach ($words as $wordData) {
                // Check if word already exists
                $existingWord = $this->wordRepository->findOneBy([
                    'word' => $wordData['word'],
                    'category' => $category,
                ]);

                if ($existingWord) {
                    $io->warning(sprintf('Word "%s" already exists, skipping', $wordData['word']));
                    $skipped++;
                    continue;
                }

                // Create new word entity
                $word = new Word();
                $word->setWord($wordData['word']);
                $word->setTranslation($wordData['translation']);
                $word->setExample($wordData['example']);
                $word->setCategory($category);

                $this->entityManager->persist($word);
                $saved++;
            }

            $this->entityManager->flush();

            $io->newLine();
            $io->success(sprintf('Successfully saved %d words to database!', $saved));

            if ($skipped > 0) {
                $io->note(sprintf('Skipped %d duplicate words', $skipped));
            }

            // Show summary
            $io->section('Summary');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Requested', $count],
                    ['Generated', count($words)],
                    ['Saved', $saved],
                    ['Skipped (duplicates)', $skipped],
                ]
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to generate words: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
