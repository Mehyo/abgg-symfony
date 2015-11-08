<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use AppBundle\Entity\Team;
use AppBundle\Form\TeamType;

/**
* Team controller.
*
* @Route("/team")
*/
class TeamController extends Controller
{

  /**
  * Lists all Team entities.
  *
  * @Route("/", name="team")
  * @Method("GET")
  * @Template("AppBundle:Team:index.html.twig")
  */
  public function indexAction()
  {
    $em = $this->getDoctrine()->getManager();

    $entities = $em->getRepository('AppBundle:Team')->findAll();

    return array(
      'entities' => $entities,
    );
  }

  /**
  * Lists all Team entities but only the information avaible to everyone.
  *
  * @Route("/{needs_posts}/search", name="search_team")
  * @Method("GET")
  * @Template()
  */
  public function searchAction($needs_posts)
  {
    if($needs_posts == "lol")
      $needs_posts = 1;

    $em = $this->getDoctrine()->getManager();

    $entities = $em->getRepository('AppBundle:Team')->findAll();

    return array(
      'entities' => $entities,
      'needs_posts' => $needs_posts,
    );
  }
  /**
  * Creates a new Team entity.
  *
  * @Route("/", name="team_create")
  * @Method("POST")
  * @Template("AppBundle:Team:new.html.twig")
  */
  public function createAction(Request $request)
  {
    $entity = new Team();
    $form = $this->createCreateForm($entity);
    $form->handleRequest($request);

    if ($form->isValid()) {
      $usr = $this->get('security.token_storage')->getToken()->getUser();
      $usr->setCaptain(true);
      $usr->setTeam($entity->getName());

      $entity->setCaptain($usr);

      $em = $this->getDoctrine()->getManager();
      $em->persist($entity);
      $em->flush();

      $em->persist($usr);
      $em->flush();

      return $this->redirect($this->generateUrl('team_show', array('id' => $entity->getId())));
    }

    return array(
      'entity' => $entity,
      'form'   => $form->createView(),
    );
  }

  /**
  * Creates a form to create a Team entity.
  *
  * @param Team $entity The entity
  *
  * @return \Symfony\Component\Form\Form The form
  */
  private function createCreateForm(Team $entity)
  {
    $form = $this->createForm(new TeamType(), $entity, array(
      'action' => $this->generateUrl('team_create'),
      'method' => 'POST',
    ));

    $form->add('submit', 'submit', array('label' => 'Create'));

    return $form;
  }

  /**
  * Displays a form to create a new Team entity.
  *
  * @Route("/new", name="team_new")
  * @Method("GET")
  * @Template()
  */
  public function newAction()
  {
    $entity = new Team();
    $form   = $this->createCreateForm($entity);

    return array(
      'entity' => $entity,
      'form'   => $form->createView(),
    );
  }

  /**
  * Display information on a team
  *
  * @Route("/{id}", name="team_show")
  * @Method("GET")
  * @Template("AppBundle:Team:apply.html.twig")
  */
  public function showAction($id)
  {
    $em = $this->getDoctrine()->getManager();

    $entity = $em->getRepository('AppBundle:Team')->find($id);

    if (!$entity) {
      throw $this->createNotFoundException('Unable to find Team entity.');
    }

    $deleteForm = $this->createDeleteForm($id);

    $applicant = $this->showApplyAction($id);

    return array(
      'entity'      => $entity,
      'delete_form' => $deleteForm->createView(),
      'applicant'   => $applicant,
    );
  }

  /**
  * Displays a form to edit an existing Team entity.
  *
  * @Route("/{id}/edit", name="team_edit")
  * @Method("GET")
  * @Template()
  */
  public function editAction($id)
  {
    $em = $this->getDoctrine()->getManager();

    $entity = $em->getRepository('AppBundle:Team')->find($id);

    if (!$entity) {
      throw $this->createNotFoundException('Unable to find Team entity.');
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
  * Creates a form to edit a Team entity.
  *
  * @param Team $entity The entity
  *
  * @return \Symfony\Component\Form\Form The form
  */
  private function createEditForm(Team $entity)
  {
    $form = $this->createForm(new TeamType(), $entity, array(
      'action' => $this->generateUrl('team_update', array('id' => $entity->getId())),
      'method' => 'PUT',
    ));

    $form->add('submit', 'submit', array('label' => 'Update'));

    return $form;
  }
  /**
  * Edits an existing Team entity.
  *
  * @Route("/{id}", name="team_update")
  * @Method("PUT")
  * @Template("AppBundle:Team:edit.html.twig")
  */
  public function updateAction(Request $request, $id)
  {
    $em = $this->getDoctrine()->getManager();

    $entity = $em->getRepository('AppBundle:Team')->find($id);

    if (!$entity) {
      throw $this->createNotFoundException('Unable to find Team entity.');
    }

    $deleteForm = $this->createDeleteForm($id);
    $editForm = $this->createEditForm($entity);
    $editForm->handleRequest($request);

    if ($editForm->isValid()) {
      $em->flush();

      return $this->redirect($this->generateUrl('team_edit', array('id' => $id)));
    }

    return array(
      'entity'      => $entity,
      'edit_form'   => $editForm->createView(),
      'delete_form' => $deleteForm->createView(),
    );
  }

  /**
  * Deletes a Team entity.
  *
  * @Route("/{id}", name="team_delete")
  * @Method("DELETE")
  */
  public function deleteAction(Request $request, $id)
  {
    $form = $this->createDeleteForm($id);
    $form->handleRequest($request);

    if ($form->isValid()) {
      $em = $this->getDoctrine()->getManager();
      $entity = $em->getRepository('AppBundle:Team')->find($id);

      if (!$entity) {
        throw $this->createNotFoundException('Unable to find Team entity.');
      }

      $listPlayer = $this->findPlayer($entity);
      $size = sizeof($listPlayer);
      for ($i=0; $i <$size ; $i++) { 
        $usr = $listPlayer[$i];
        if ($usr->getCaptain())
          $usr->setCaptain(false);
        $usr->setTeam(null);
        $em->persist($usr);
      }

      $em->remove($entity);
      $em->flush();
    }

    return $this->redirect($this->generateUrl('team'));
  }

  /**
  * Find all player of one team
  *
  * @param a team
  *
  * @return array of users
  */
  private function findPlayer(Team $team)
  {
    return array($team->getPost1(), $team->getPost2(), $team->getPost3(), $team->getPost4(), $team->getPost5());
  }

  /**
  * Creates a form to delete a Team entity by id.
  *
  * @param mixed $id The entity id
  *
  * @return \Symfony\Component\Form\Form The form
  */
  private function createDeleteForm($id)
  {
    return $this->createFormBuilder()
    ->setAction($this->generateUrl('team_delete', array('id' => $id)))
    ->setMethod('DELETE')
    ->add('submit', 'submit', array('label' => 'Delete'))
    ->getForm()
    ;
  }

    /**
  * Add a applicant
  *
  * @Route("/{id}", name="team_apply")
  * @Method("PUT")
  * @Template()
  */
  public function applyAction(Request $request, $id)
  {

    $em = $this->getDoctrine()->getManager();
    
    $usr = $this->get('security.token_storage')->getToken()->getUser();
    $entity->addApplicant($usr);

    $em->persist($entity);
    $em->flush();

    return $this->redirect($this->generateUrl('team'));
  }

  /**
  * Show all the applicant of a team
  *
  */
  public function showApplyAction($id)
  {
    $listApplicant = $this->getApplication($id);
    return $listApplicant;
  }

  public function acceptApplyAction($id)
  {
    //
  }

  private function getApplication($id)
  {
    $em = $this->getDoctrine()->getManager();
    $entity = $em->getRepository('AppBundle:Team')->find($id);

    if (!$entity) {
      throw $this->createNotFoundException('Unable to find Team entity.');
    }

    $applicant = $entity->getApplicant();
    $listUser = array();
    $listSize = sizeof($applicant);
    for ($i=0; $i < $listSize; $i++) { 
      $listUser[i] = $applicant[i]->getName();
    }

    return $listUser;
    
  }
}
