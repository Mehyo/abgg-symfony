<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\User;
use AppBundle\Form\RegistrationType;
use AppBundle\Services\CheckDataServices;

/**
 * User controller.
 *
 * @Route("/player")
 */
class UserController extends Controller
{

    /**
     * Lists all User entities.
     *
     * @Route("/", name="player")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:User')->findAll();

        return array(
            'entities' => $entities,
        );
    }
    /**
     * Lists all User entities but only the information avaible to everyone.
     *
     * @Route("/search/{game}", name="search_player")
     * @Method("GET")
     * @Template()
     */
    public function searchAction($game)
    {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
		    'SELECT p
		    FROM AppBundle:Game p
		    WHERE p.systName = :name'
		)->setParameter('name', $game);
		$gaming = $query->getResult();
		$gameId = $gaming[0]->getId();
		
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
		    'SELECT p
		    FROM AppBundle:Team p
		    WHERE p.tournament = :id'
		)->setParameter('id', $gameId);
		$teams = $query->getResult();
		
		if (($this->getUser()) != null)
		{
			$userId = $this->getUser()->getId();
	        $query = $em->createQuery(
			    'SELECT p
			    FROM AppBundle:Application p
			    WHERE p.user = :id'
			)->setParameter('id', $userId);
			$userApp = $query->getResult();
			
			$i=0;
			if ($userApp!=null)
			{
				foreach ($userApp as $user)
				{
					$userAppTeams[$i] = $user->getTeam();
					$i++;
				}
			}
			else
			{
				$userAppTeams=0;
			}
		}
		else {
			$userAppTeams=0;
		}
		
        return array(
            'entities' => $teams,
            'appTeam'  => $userAppTeams,
        );
    }
	
    /**
     * Creates a new User entity.
     *
     * @Route("/", name="player_create")
     * @Method("POST")
     * @Template("AppBundle:User:new.html.twig")
     */
    public function createAction(Request $request)
    {
        $entity = new User();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('player_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form'   => $form->createView(),
        );
    }

    /**
     * Creates a form to create a User entity.
     *
     * @param User $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(new UserType(), $entity, array(
            'action' => $this->generateUrl('player_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Finds and displays a User entity.
     *
     * @Route("/{id}", name="player_show")
     * @Method("GET")
     * @Template(":default:profil.html.twig")
     */
    public function showAction()
    {
    	$id = $this->getUser()->getId();
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('AppBundle:User')->find($id);
		$checkData = $this -> container -> get('checkDataServices');
		
		$experience = $checkData -> checkData($user, 'getExperience', 'Experience');
		if ($experience != null)
		{
			$ranking = $checkData -> checkData($experience, 'getRanking', 'Ranking');
			$underRanking = $checkData -> checkData($experience, 'getUnderRanking', 'UnderRanking');
		}
		else {
			$ranking = 0;
			$underRanking = 0;
		}
		$game = $checkData -> checkData($user, 'getTournament', 'Game');
		$role = $checkData -> checkData($user, 'getRole', 'Role');
		$team = $checkData -> checkData($user, 'getTeam', 'Team');
		
        if (!$user) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }
		
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'user'         => $user,
            'experience'   => $experience,
            'ranking'      => $ranking,
            'underRanking' => $underRanking,
            'game'         => $game,
            'role'         => $role,
            'team'         => $team,
            'delete_form'  => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing User entity.
     *
     * @Route("/{id}/edit", name="player_edit")
     * @Method("GET")
     * @Template()
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
    * Creates a form to edit a User entity.
    *
    * @param User $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(User $entity)
    {
        $form = $this->createForm(new RegistrationType(), $entity, array(
            'action' => $this->generateUrl('player_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing User entity.
     *
     * @Route("/{id}", name="player_update")
     * @Method("PUT")
     * @Template("AppBundle:User:edit.html.twig")
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:User')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find User entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            
			return $this->redirect($this->generateUrl('player_show', array('id' => $this->getUser()->getId())));
        }

        return array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }
    /**
     * Deletes a User entity.
     *
     * @Route("/{id}", name="player_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('AppBundle:User')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find User entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('player'));
    }

    /**
     * Creates a form to delete a User entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('player_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
