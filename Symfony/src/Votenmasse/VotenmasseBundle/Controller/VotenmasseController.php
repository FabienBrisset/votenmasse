<?php

namespace Votenmasse\VotenmasseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Votenmasse\VotenmasseBundle\Entity\Utilisateur;

class VotenmasseController extends Controller
{
	public function indexAction()
	{
		$utilisateur = new Utilisateur;

		$form = $this->createFormBuilder($utilisateur)
					 ->add('nom', 'text')
					 ->add('prenom', 'text')
					 ->add('dateDeNaissance', 'birthday')
					 ->add('sexe', 'choice', array(
												'choices' => array(
													2 => "Femme"),
												'multiple' => false,
												'expanded' => false,
												'empty_value' => 'Homme',
												'empty_data'  => 1))
					 ->add('login', 'text')
					 ->add('motDePasse', 'password')
					 ->add('mail', 'text')
					 ->getForm();

		// On récupère la requête
		$request = $this->get('request');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
		  $form->bind($request);

		  // On vérifie que les valeurs entrées sont correctes
		  // (Nous verrons la validation des objets en détail dans le prochain chapitre)
		  if ($form->isValid()) {
			// On l'enregistre notre objet $utilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($utilisateur);
			$em->flush();

			// On redirige vers la page de connexion
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_connexion'));
		  }
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
		  'form' => $form->createView(),
		));
	}
	
	public function connexionAction() {
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
}