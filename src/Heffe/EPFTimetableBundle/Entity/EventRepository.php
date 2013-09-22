<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fabien
 * Date: 15/01/13
 * Time: 17:30
 * To change this template use File | Settings | File Templates.
 */

namespace Heffe\EPFTimetableBundle\Entity;

use Doctrine\ORM\EntityRepository;

class EventRepository extends EntityRepository
{

    public function findEventsForWeek(\DateTime $startDate, \DateTime $endDate)
    {
        $startDate->setTime(0,0);
        $endDate->setTime(23,59);

        $query = $this->_em->createQuery(
                                'SELECT e FROM HeffeEPFTimetableBundle:Event e
                                WHERE e.start BETWEEN :date1 AND :date2
                                ORDER BY e.start ASC'
        );

        $query->setParameter('date1', $startDate);
        $query->setParameter('date2', $endDate);

        return $query->getResult();
    }

}