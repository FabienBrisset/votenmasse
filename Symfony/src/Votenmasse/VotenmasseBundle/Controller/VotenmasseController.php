<?php

namespace Votenmasse\VotenmasseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Votenmasse\VotenmasseBundle\Entity\Utilisateur;
use Votenmasse\VotenmasseBundle\Entity\Vote;

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
													1 => 'Homme',
													2 => "Femme"),
												'multiple' => false,
												'expanded' => false))
					 ->add('login', 'text')
					 ->add('motDePasse', 'password')
					 ->add('mail', 'email')
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
	
	public function creationVoteAction()
	{
		$vote = new Vote;

		$form1 = $this->createFormBuilder($vote)
					 ->add('nom', 'text')
					 ->add('texte', 'text')
					 ->add('dateDeFin', 'birthday')
				     ->getForm();
					 
		$form2 = $this->createFormBuilder($vote)
					->add('type', 'choice', array(
												'choices' => array(
													1 => 'Vote public',
													2 => "Vote réservé aux inscrits",
													3 => "Vote privé"),
												'multiple' => false,
												'expanded' => false))
					 ->add('groupeAssocie', 'text', array( 
													'required' => false))
					->getForm();
					 
		$form3 = $this->createFormBuilder($vote)
					 ->add('choix1', 'text')
					 ->add('choix2', 'text')
					 ->add('choix3', 'text')
					 ->add('choix4', 'text')
					 ->add('choix5', 'text')
					 ->add('choix6', 'text')
					 ->add('choix7', 'text')
					 ->add('choix8', 'text')
					 ->add('choix9', 'text')
					 ->add('choix10', 'text')
				     ->getForm();

		// On récupère la requête
		$request = $this->get('request');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
		  $form1->bind($request);
		  $form2->bind($request);
		  $form3->bind($request);

		  // On vérifie que les valeurs entrées sont correctes
		  // (Nous verrons la validation des objets en détail dans le prochain chapitre)
		  if ($form1->isValid() && $form2->isValid() && $form3->isValid()) {
			// On l'enregistre notre objet $utilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($vote);
			$em->flush();

			// On redirige vers la page de connexion
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_accueil'));
		  }
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
		  'form1' => $form1->createView(),
		  'form2' => $form2->createView(),
		  'form3' => $form3->createView()
		));
	}
	
	public function connexionAction() {
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
}