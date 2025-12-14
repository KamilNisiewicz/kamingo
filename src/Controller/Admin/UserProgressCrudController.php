<?php

namespace App\Controller\Admin;

use App\Entity\UserProgress;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserProgressCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return UserProgress::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Postęp użytkownika')
            ->setEntityLabelInPlural('Postępy użytkowników')
            ->setPageTitle('index', 'Postępy użytkowników (read-only)')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user', 'Użytkownik'),
            AssociationField::new('word', 'Słówko'),
            TextField::new('statusValue', 'Status'),
            DateTimeField::new('nextReviewDate', 'Następna powtórka')
                ->setFormat('dd.MM.yyyy HH:mm'),
            IntegerField::new('repetitions', 'Liczba powtórek'),
            DateTimeField::new('lastReviewedAt', 'Ostatnia powtórka')
                ->setFormat('dd.MM.yyyy HH:mm'),
            DateTimeField::new('createdAt', 'Data utworzenia')
                ->setFormat('dd.MM.yyyy HH:mm'),
        ];
    }
}
