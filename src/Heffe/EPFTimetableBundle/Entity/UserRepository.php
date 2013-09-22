<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fabien
 * Date: 25/02/13
 * Time: 21:29
 * To change this template use File | Settings | File Templates.
 */
namespace Heffe\EPFTimetableBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{
    public function findValidUsers()
    {
        $query = $this->_em->createQuery('SELECT u FROM HeffeEPFTimetableBundle:User u WHERE u.validated = 1 AND u.username IS NOT NULL');

        $users = $query->getResult();

        return $users;
    }
}