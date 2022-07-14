<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }


/*    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt')->renderAsNativeWidget(false)
        ];
    }*/

}
