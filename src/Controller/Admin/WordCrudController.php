<?php

namespace App\Controller\Admin;

use App\Entity\Word;
use App\Entity\WordCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class WordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Word::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Słówko')
            ->setEntityLabelInPlural('Słówka')
            ->setPageTitle('index', 'Lista słówek')
            ->setPageTitle('new', 'Dodaj nowe słówko')
            ->setPageTitle('edit', 'Edytuj słówko')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('word', 'Słowo (EN)')->setRequired(true),
            TextField::new('translation', 'Tłumaczenie (PL)')->setRequired(true),
            TextareaField::new('example', 'Przykład')->setRequired(false),
            ChoiceField::new('category', 'Kategoria')
                ->setChoices([
                    'Programming' => WordCategory::PROGRAMMING,
                    'Travel' => WordCategory::TRAVEL,
                ])
                ->setRequired(true),
            DateTimeField::new('createdAt', 'Data utworzenia')
                ->hideOnForm()
                ->setFormat('dd.MM.yyyy HH:mm'),
        ];
    }
}
