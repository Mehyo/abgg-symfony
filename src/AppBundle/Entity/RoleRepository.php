<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

/**
 * RoleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RoleRepository extends EntityRepository
{
	public function getGameId ($gameId)
    {
		$query = $this->createQueryBuilder('p')
		    ->where('p.game = :id')
		    ->setParameter('id', $gameId);

        return $query;
    }
	
	public function getManager ()
    {
		$query = $this->createQueryBuilder('p')
		    ->where('p.name = :manager')
		    ->setParameter('manager', 'Manager');
			
        return $query;
    }
}
