<?php
namespace Votenmasse\VotenmasseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Votenmasse\VotenmasseBundle\Entity\Utilisateur;
use Votenmasse\VotenmasseBundle\Entity\Vote;
use Votenmasse\VotenmasseBundle\Entity\Groupe;
use Votenmasse\VotenmasseBundle\Entity\GroupeUtilisateur;

class VotenmasseController extends Controller
{
	public function indexAction()
	{
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
	
		$utilisateur = new Utilisateur;

		$form = $this->createFormBuilder($utilisateur)
					 ->add('nom', 'text')
					 ->add('prenom', 'text', array(
											'label' => 'Prénom'))
					 ->add('dateDeNaissance', 'birthday')
					 ->add('sexe', 'choice', array(
												'choices' => array(
													'H' => 'Homme',
													'F' => "Femme"),
												'multiple' => false,
												'expanded' => false))
					 ->add('login', 'text', array(
											'label' => 'Pseudo'))
					 ->add('motDePasse', 'password')
					 ->add('mail', 'email')
					 ->getForm();

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
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		  }
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
		  'form' => $form->createView(),
		  'utilisateur' => $u
		));
	}
	
	public function creationVoteAction()
	{
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
	
		$vote = new Vote;

		$form = $this->createFormBuilder($vote)
					 ->add('nom', 'text')
					 ->add('texte', 'text')
					 ->add('dateDeFin', 'birthday')
					 ->add('type', 'choice', array(
												'choices' => array(
													'Vote public' => 'Vote public',
													"Vote réservé aux inscrits" => "Vote réservé aux inscrits",
													"Vote privé" => "Vote privé"),
												'multiple' => false,
												'expanded' => false))
					 ->add('groupeAssocie', 'text', array( 
													'required' => false,
													'label' => 'Groupe associé'))
					 ->add('choix1', 'text')
					 ->add('choix2', 'text')
					 ->add('choix3', 'text', array( 
											'required' => false))
					 ->add('choix4', 'text', array( 
											'required' => false))
					 ->add('choix5', 'text', array( 
											'required' => false))
					 ->add('choix6', 'text', array( 
											'required' => false))
					 ->add('choix7', 'text', array( 
											'required' => false))
					 ->add('choix8', 'text', array( 
											'required' => false))
					 ->add('choix9', 'text', array( 
											'required' => false))
					 ->add('choix10', 'text', array( 
											'required' => false))
				     ->getForm();

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
		  
		  $form->bind($request);
		  
		  if($request->request->get("form")['groupeAssocie'] != NULL) {
			$groupeAssocie = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneByNom($request->request->get("form")['groupeAssocie']);
			
			if($groupeAssocie != NULL) {
				if($request->request->get("form")['type'] == 'Vote privé' && $groupeAssocie->getEtat() != 'Groupe privé') {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Un vote privé doit être associé à un groupe privé",
					'utilisateur' => $u));
				}
				else if($request->request->get("form")['type'] == 'Vote réservé aux inscrits' && $groupeAssocie->getEtat() != 'Groupe réservé aux inscrits') {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Un vote réservé aux inscrits doit être associé à un groupe réservé aux inscrits",
					'utilisateur' => $u));
				}
				else if($request->request->get("form")['type'] == 'Vote public' && $groupeAssocie->getEtat() != 'Groupe public') {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Un vote public doit être associé à un groupe public",
					'utilisateur' => $u));
				}
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Le groupe associé que vous avez indiqué n'existe pas",
					'utilisateur' => $u));
			}
		  }
		  else {
			if($request->request->get("form")['type'] == 'Vote privé') {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => 'Un vote privé doit obligatoirement être associé à un groupe privé',
					'utilisateur' => $u));
			}
		  }

		  // On l'enregistre notre objet $utilisateur dans la base de données
		  $em = $this->getDoctrine()->getManager();
		  $em->persist($vote);
		  $em->flush();

		  // On redirige vers la page d'accueil
		  return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
		  'form' => $form->createView(),
		  'utilisateur' => $u 
		));
	}
	
	public function creationGroupeAction()
	{
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		$groupe = new Groupe;
		
		$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
		
		if ($utilisateurs != NULL) {
			for ($i = 0; $i<sizeof($utilisateurs); $i++) {
				$utilisateurs_login[$utilisateurs[$i]->getLogin()] = $utilisateurs[$i]->getLogin();
			}
		
			$form = $this->createFormBuilder($groupe)
						 ->add('nom', 'text')
						 ->add('description', 'text')
						 ->add('etat', 'choice', array(
													'choices' => array(
														'Groupe public' => 'Groupe public',
														"Groupe réservé aux inscrits" => "Groupe réservé aux inscrits",
														"Groupe privé" => "Groupe privé")))
						 ->add('utilisateurs', 'choice', array(
													'choices' => $utilisateurs_login,
													'multiple' => true,
													'required' => false,
													'mapped' => false))
						 ->getForm();

			// On vérifie qu'elle est de type POST
			if ($request->getMethod() == 'POST') {
					
			  // On fait le lien Requête <-> Formulaire
			  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
			  $form->bind($request);

			  // On vérifie que les valeurs entrées sont correctes
			  // (Nous verrons la validation des objets en détail dans le prochain chapitre)
				// On l'enregistre notre objet $utilisateur dans la base de données
				$em = $this->getDoctrine()->getManager();
				$em->persist($groupe);
				$em->flush();
				
				if(isset($request->request->get("form")['utilisateurs'])) {		
					$groupe_id = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findOneByNom($request->request->get("form")['nom']);
											
					for($i = 0; $i < sizeof($request->request->get("form")['utilisateurs']); $i++) {
						  // On crée une nouvelle « relation entre 1 article et 1 compétence »
						  $groupeUtilisateur[$i] = new GroupeUtilisateur;
						  
						  $utilisateur_id = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneBylogin($request->request->get("form")['utilisateurs'][$i]);

						  // On la lie au groupe, qui est ici toujours le même
						  $groupeUtilisateur[$i]->setGroupe($groupe_id);
						  // On la lie à l'utilisateur, qui change ici dans la boucle foreach
						  $groupeUtilisateur[$i]->setUtilisateur($utilisateur_id);
						  
						  $groupeUtilisateur[$i]->setModerateur(false);
						  $groupeUtilisateur[$i]->setAccepte(true);

						  // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
						  $em->persist($groupeUtilisateur[$i]);
						}
						
						// On déclenche l'enregistrement
						$em->flush();
					}

				// On redirige vers la page de connexion
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
		}
		else {
			$form = $this->createFormBuilder($groupe)
						 ->add('nom', 'text')
						 ->add('description', 'text')
						 ->add('etat', 'choice', array(
													'choices' => array(
														'Groupe public' => 'Groupe public',
														"Groupe réservé aux inscrits" => "Groupe réservé aux inscrits",
														"Groupe privé" => "Groupe privé")))
						 ->getForm();

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
				$em->persist($vote);
				$em->flush();

				// On redirige vers la page de connexion
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			  }
			}
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_groupe.html.twig', array(
		  'form' => $form->createView(),
		  'utilisateur' => $u
		));
	}
	
	public function connexionAction() {
		// On récupère la requête
		$request = $this->get('request');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
			$utilisateur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findBy(array('login' => $request->request->get('login'),
										'motDePasse' => $request->request->get('mot_de_passe')));
		
			if ($utilisateur != NULL) {		
				$session = new Session();
				$session->start();
			
				$session->set('utilisateur', $request->request->get('login')); 
				
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
					'utilisateur' => $session->get('utilisateur')));
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
			}
		}
	
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
	
	public function deconnexionAction() {
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		

		$session->invalidate();
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
}