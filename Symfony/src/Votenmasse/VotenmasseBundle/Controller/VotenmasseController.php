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
use Votenmasse\VotenmasseBundle\Entity\Commentaire;
use Votenmasse\VotenmasseBundle\Entity\VoteCommentaireUtilisateur;
use Votenmasse\VotenmasseBundle\Entity\DonnerAvis;

class VotenmasseController extends Controller
{
	public function indexAction()
	{
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$inscription_valide = $session->get('inscription_valide');
		
		if(!is_null($inscription_valide)) {
			$session->remove('inscription_valide');
			$message_inscription_valide = "Félicitation vous avez rejoins la communauté Votenmasse";
		}
		else {
			$message_inscription_valide = NULL;
		}
	
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
					 ->add('motDePasse', 'password', array(
												'mapped' => false))
					 ->add('mail', 'email')
					 ->getForm();
					 
		$utilisateur_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($request->request->get("form")['login']);
			
		if($utilisateur_existe_deja != NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
															  'form' => $form->createView(),
															  'utilisateur' => $u,
															  'erreur' => "Le login saisi est déjà pris, veuillez en choisir un autre"));
		}
		
		$mail_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByMail($request->request->get("form")['mail']);
			
		if($mail_existe_deja != NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
														  'form' => $form->createView(),
														  'utilisateur' => $u,
														  'erreur' => "L'adresse mail indiquée existe déjà"));
		}

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
		  $form->bind($request);

		  // On vérifie que les valeurs entrées sont correctes
		  // (Nous verrons la validation des objets en détail dans le prochain chapitre)
		  if ($form->isValid()) {
			// On l'enregistre notre objet $utilisateur dans la base de données
			
			$pass = $request->request->get("form")['motDePasse'];
				
			$pass_md5 = md5($pass);
			
			$utilisateur->setMotDePasse($pass_md5);
				
			$em = $this->getDoctrine()->getManager();
			$em->persist($utilisateur);
			$em->flush();
			
			$session->set('inscription_valide', true); 
			
			// On redirige vers la page de connexion
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		  }
		}

		// À ce stade :
		// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
		// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
		  'form' => $form->createView(),
		  'utilisateur' => $u,
		  'inscription_valide' => $message_inscription_valide
		));
	}
	
	public function creationVoteAction() {
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$infos_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($u);	
		
		$groupesUtilisateur_utilisateur_courant = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findByUtilisateur($infos_utilisateur->getId());
		
		foreach ($groupesUtilisateur_utilisateur_courant as $cle => $valeur) {
			if (isset($groupes_utilisateur_courant)) {
				$groupes_utilisateur_courant += $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Groupe')
					->findById($valeur->getGroupe());
			}
			else {
				$groupes_utilisateur_courant = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Groupe')
					->findById($valeur->getGroupe());
			}
		}
		
		if (isset($groupes_utilisateur_courant)) {
			$groupes_utilisateur_courant_a_ajouter = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findByAdministrateur($u);
				
			$taille_groupes_utilisateur_ou_ajouter = sizeof($groupes_utilisateur_courant);	
				
			foreach ($groupes_utilisateur_courant_a_ajouter as $cle => $valeur) {
				$groupes_utilisateur_courant[$taille_groupes_utilisateur_ou_ajouter] = $valeur;
				$taille_groupes_utilisateur_ou_ajouter++;
			}
		}
		else {
			$groupes_utilisateur_courant = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findByAdministrateur($u);
		}
	
		$vote = new Vote;
		
		if ($groupes_utilisateur_courant != NULL) {
			for ($i = 0; $i<sizeof($groupes_utilisateur_courant); $i++) {
				$groupes[$groupes_utilisateur_courant[$i]->getNom()] = $groupes_utilisateur_courant[$i]->getNom();
			}
		}
		else {
			$groupes = NULL;
		}
			
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
					 ->add('groupeAssocie', 'choice', array( 
													'choices' => $groupes,
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
		
		$vote_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneByNom($request->request->get("form")['nom']);
			
		if($vote_existe_deja != NULL) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
				  'form' => $form->createView(),
				  'utilisateur' => $u,
				  'erreur' => "Un vote du même nom existe déjà, veuillez en choisir un autre"));
		}

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
		  
		  $createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
		  
		  $vote->setCreateur($createur->getId());

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
		
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = new Groupe;
		
		$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
		
		if ($utilisateurs != NULL) {
			for ($i = 0; $i<sizeof($utilisateurs); $i++) {
				if ($utilisateurs[$i]->getLogin() != $u) {
					$utilisateurs_login[$utilisateurs[$i]->getLogin()] = $utilisateurs[$i]->getLogin();
				}
			}
			
			if(isset($utilisateurs_login)) {
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
							 
				$groupe_existe_deja = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneByNom($request->request->get("form")['nom']);
				
				if($groupe_existe_deja != NULL) {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_groupe.html.twig', array(
						  'form' => $form->createView(),
						  'utilisateur' => $u,
						  'erreur' => "Un groupe du même nom existe déjà, veuillez en choisir un autre"));
				}

				// On vérifie qu'elle est de type POST
				if ($request->getMethod() == 'POST') {
						
				  // On fait le lien Requête <-> Formulaire
				  // À partir de maintenant, la variable $utilisateur contient les valeurs entrées dans le formulaire par le visiteur
				  $form->bind($request);
			  
					$groupe->setAdministrateur($u);

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
				  
				  $groupe->setAdministrateur($u);


					// On l'enregistre notre objet $utilisateur dans la base de données
					$em = $this->getDoctrine()->getManager();
					$em->persist($groupe);
					$em->flush();

					// On redirige vers la page de connexion
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));

				}
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
			  
			  $groupe->setAdministrateur($u);

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
			$pass = md5($request->request->get('mot_de_passe'));
		
			$utilisateur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findBy(array('login' => $request->request->get('login'),
										'motDePasse' => $pass));
		
			if ($utilisateur != NULL) {		
				$session = new Session();
				$session->start();
			
				$session->set('utilisateur', $request->request->get('login')); 
				
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
					'utilisateur' => $session->get('utilisateur')));
			}
			else {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
		}
	
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
	}
	
	public function deconnexionAction() {
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		

		$session->invalidate();
			
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
	}
	
	public function administrationAction() {
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u != NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
	
		// On récupère la requête
		$request = $this->get('request');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
			if (($request->request->get('mot_de_passe') == 'abcde98765') || ($request->request->get('connecte') == true)) {
				$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findAll();
					
				$groupes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Groupe')
					->findAll();
					
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
				foreach ($votes as $cle => $valeur) {
					$createur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneById($valeur->getCreateur());
						
					$createurs[$cle] = $createur->getLogin();
				}
			
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'groupes' => $groupes,
					'votes' => $votes,
					'vote_createurs' => $createurs));
			}
			else {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_administration'));
			}
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration_connexion.html.twig');
	}
	
	public function votesAction() {
		// On récupère la requête
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
	
		// On récupère la requête
		$request = $this->get('request');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
			$en_cours = false;
			$termine = false;
			$public = false;
			$reserve = false;
			$prive = false;
		
			if (($request->request->get('type') == null) && ($request->request->get('etat') == null)) {
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
				foreach ($votes as $cle => $valeur) {
					$createur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneById($valeur->getCreateur());
						
					$createurs[$cle] = $createur->getLogin();
				}
				
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
					'utilisateur' => $u,
					'votes' => $votes,
					'vote_createurs' => $createurs));
			}
			else if (($request->request->get('type') == null) && ($request->request->get('etat') != null)){
				foreach ($request->request->get('etat') as $cle => $valeur) {
					if ($valeur == 'en_cours') {
						$en_cours = true;
					}
					if ($valeur == 'terminé') {
						$termine = true;
					}
				}
				
				if ($en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findByEtat(true);
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findByEtat(false);
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
			}
			else if (($request->request->get('type') != null) && ($request->request->get('etat') == null)){
				foreach ($request->request->get('type') as $cle => $valeur) {
					if ($valeur == 'public') {
						$public = true;
					}
					if ($valeur == 'réservé') {
						$reserve = true;
					}
					if ($valeur == 'privé') {
						$prive = true;
					}
				}
				
				foreach ($request->request->get('type') as $cle => $valeur) {
					if ($valeur == 'public') {
						$public = true;
					}
					if ($valeur == 'réservé') {
						$reserve = true;
					}
					if ($valeur == 'privé') {
						$prive = true;
					}
				}
				
				if ($public == true && $reserve == true && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
			}
			else {
				foreach ($request->request->get('type') as $cle => $valeur) {
					if ($valeur == 'public') {
						$public = true;
					}
					if ($valeur == 'réservé') {
						$reserve = true;
					}
					if ($valeur == 'privé') {
						$prive = true;
					}
				}
				
				foreach ($request->request->get('etat') as $cle => $valeur) {
					if ($valeur == 'en_cours') {
						$en_cours = true;
					}
					if ($valeur == 'terminé') {
						$termine = true;
					}
				}
				
				if ($public == true && $reserve == true && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == false && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == true && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findByEtat(false);
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits'), 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public', 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == false && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé', 'etat' => false));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == true && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findByEtat(true);
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits'), 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public', 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé', 'etat' => true));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
							'utilisateur' => $u));
					}
				}
			}
		}
		$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					
		foreach ($votes as $cle => $valeur) {
			$createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneById($valeur->getCreateur());
				
			$createurs[$cle] = $createur->getLogin();
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
			'utilisateur' => $u,
			'votes' => $votes,
			'vote_createurs' => $createurs));
	}
	
	public function afficherVoteAction($vote = null) {
		$request = $this->get('request');
		
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		if ($request->getMethod() != 'POST') {
			$session->set('vote', $vote); 
		}
		
		if ($vote == null && $session->get('vote') == null) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
		}
		else {
			$utilisateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
			
			if ($vote != null) {
				$infos_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findOneById($vote);
			}
			else {
				$infos_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findOneById($session->get('vote'));
			}
				
			$avis_existe_deja = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findOneBy(array('utilisateur' => $utilisateur, 'vote' => $infos_vote));
				
			if($avis_existe_deja) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
			}
		
			$donner_avis = new DonnerAvis;
				
			if ($infos_vote->getChoix3() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix4() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix5() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix6() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix7() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->add('choix6', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix8() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->add('choix6', 'text', array(
												'mapped' => false))
						 ->add('choix7', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix9() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->add('choix6', 'text', array(
												'mapped' => false))
						 ->add('choix7', 'text', array(
												'mapped' => false))
						 ->add('choix8', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else if ($infos_vote->getChoix10() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->add('choix6', 'text', array(
												'mapped' => false))
						 ->add('choix7', 'text', array(
												'mapped' => false))
						 ->add('choix8', 'text', array(
												'mapped' => false))
						 ->add('choix9', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			else {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->add('choix3', 'text', array(
												'mapped' => false))
						 ->add('choix4', 'text', array(
												'mapped' => false))
						 ->add('choix5', 'text', array(
												'mapped' => false))
						 ->add('choix6', 'text', array(
												'mapped' => false))
						 ->add('choix7', 'text', array(
												'mapped' => false))
						 ->add('choix8', 'text', array(
												'mapped' => false))
						 ->add('choix9', 'text', array(
												'mapped' => false))
						 ->add('choix10', 'text', array(
												'mapped' => false))
						 ->getForm();
			}

			// On vérifie qu'elle est de type POST
			if ($request->getMethod() == 'POST') {
			  $session->set('vote', null);
			
			  $avis = new DonnerAvis;
			  if ($request->request->get("form")['choix1'] == '1') {
				$avis->setChoix1($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '1') {
				$avis->setChoix1($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '1') {
					$avis->setChoix1($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '1') {
					$avis->setChoix1($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '1') {
					$avis->setChoix1($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '1') {
					$avis->setChoix1($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '1') {
					$avis->setChoix1($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '1') {
					$avis->setChoix1($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '1') {
					$avis->setChoix1($infos_vote->getChoix9());
				}
			  }
			 if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '1') {
				$avis->setChoix1($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '2') {
				$avis->setChoix2($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '2') {
				$avis->setChoix2($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '2') {
					$avis->setChoix2($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '2') {
					$avis->setChoix2($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '2') {
					$avis->setChoix2($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '2') {
					$avis->setChoix2($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '2') {
					$avis->setChoix2($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '2') {
					$avis->setChoix2($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '2') {
					$avis->setChoix2($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '2') {
				$avis->setChoix2($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '3') {
				$avis->setChoix3($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '3') {
				$avis->setChoix3($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '3') {
					$avis->setChoix3($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '3') {
					$avis->setChoix3($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '3') {
					$avis->setChoix3($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '3') {
					$avis->setChoix3($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '3') {
					$avis->setChoix3($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '3') {
					$avis->setChoix3($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '3') {
					$avis->setChoix3($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '3') {
				$avis->setChoix3($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '4') {
				$avis->setChoix4($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '4') {
				$avis->setChoix4($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '4') {
					$avis->setChoix4($infos_vote->getChoix3());
				}
			  }
			 if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '4') {
					$avis->setChoix4($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '4') {
					$avis->setChoix4($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '4') {
					$avis->setChoix4($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '4') {
					$avis->setChoix4($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '4') {
					$avis->setChoix4($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '4') {
					$avis->setChoix4($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '4') {
				$avis->setChoix4($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '5') {
				$avis->setChoix5($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '5') {
				$avis->setChoix5($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '5') {
					$avis->setChoix5($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '5') {
					$avis->setChoix5($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '5') {
					$avis->setChoix5($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '5') {
					$avis->setChoix5($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '5') {
					$avis->setChoix5($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '5') {
					$avis->setChoix5($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '5') {
					$avis->setChoix5($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '5') {
				$avis->setChoix5($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '6') {
				$avis->setChoix6($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '6') {
				$avis->setChoix6($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '6') {
					$avis->setChoix6($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '6') {
					$avis->setChoix6($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '6') {
					$avis->setChoix6($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '6') {
					$avis->setChoix6($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '6') {
					$avis->setChoix6($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '6') {
					$avis->setChoix6($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '6') {
					$avis->setChoix6($infos_vote->getChoix9());
				}
			  }
			   if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '6') {
				$avis->setChoix6($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '7') {
				$avis->setChoix7($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '7') {
				$avis->setChoix7($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '7') {
					$avis->setChoix7($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '7') {
					$avis->setChoix7($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '7') {
					$avis->setChoix7($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '7') {
					$avis->setChoix7($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '7') {
					$avis->setChoix7($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '7') {
					$avis->setChoix7($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '7') {
					$avis->setChoix7($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '7') {
				$avis->setChoix7($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '8') {
				$avis->setChoix8($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '8') {
				$avis->setChoix8($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '8') {
					$avis->setChoix8($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '8') {
					$avis->setChoix8($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '8') {
					$avis->setChoix8($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '8') {
					$avis->setChoix8($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '8') {
					$avis->setChoix8($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '8') {
					$avis->setChoix8($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '8') {
					$avis->setChoix8($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '8') {
				$avis->setChoix8($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '9') {
				$avis->setChoix9($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '9') {
				$avis->setChoix9($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '9') {
					$avis->setChoix9($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '9') {
					$avis->setChoix9($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '9') {
					$avis->setChoix9($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '9') {
					$avis->setChoix9($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '9') {
					$avis->setChoix9($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '9') {
					$avis->setChoix9($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '9') {
					$avis->setChoix9($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '9') {
				$avis->setChoix2($infos_vote->getChoix10());
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '10') {
				$avis->setChoix10($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '10') {
				$avis->setChoix10($infos_vote->getChoix2());
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '10') {
					$avis->setChoix10($infos_vote->getChoix3());
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '10') {
					$avis->setChoix10($infos_vote->getChoix4());
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '10') {
					$avis->setChoix10($infos_vote->getChoix5());
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '10') {
					$avis->setChoix10($infos_vote->getChoix6());
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '10') {
					$avis->setChoix10($infos_vote->getChoix7());
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '10') {
					$avis->setChoix10($infos_vote->getChoix8());
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '10') {
					$avis->setChoix10($infos_vote->getChoix9());
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '10') {
				$avis->setChoix10($infos_vote->getChoix10());
				}
			  }
				
			  $avis->setVote($infos_vote);	
			  $avis->setUtilisateur($utilisateur);
				
			  $em = $this->getDoctrine()->getManager();
			  $em->persist($avis);
			  $em->flush();
			 
			  // On redirige vers la page de connexion
			  return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
			}

			// À ce stade :
			// - Soit la requête est de type GET, donc le visiteur vient d'arriver sur la page et veut voir le formulaire
			// - Soit la requête est de type POST, mais le formulaire n'est pas valide, donc on l'affiche de nouveau

			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
			  'form' => $form->createView(),
			  'utilisateur' => $u,
			  'vote_id' => $vote,
			  'vote_nom' => $infos_vote->getNom(),
			  'vote_texte' => $infos_vote->getTexte(),
			  'choix1' => $infos_vote->getChoix1(),
			  'choix2' => $infos_vote->getChoix2(),
			  'choix3' => $infos_vote->getChoix3(),
			  'choix4' => $infos_vote->getChoix4(),
			  'choix5' => $infos_vote->getChoix5(),
			  'choix6' => $infos_vote->getChoix6(),
			  'choix7' => $infos_vote->getChoix7(),
			  'choix8' => $infos_vote->getChoix8(),
			  'choix9' => $infos_vote->getChoix9(),
			  'choix10' => $infos_vote->getChoix10()
			));
		}
	}
	
	public function forumAction() {
        $request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
					'votes' => $votes));
	}
	
	public function commentaireAction($nomV) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		//$nomVote=$request->query->get('nomVote');
		//$nomVote=$request->request->get('nomVote');
		//$request->request->get("form")['nom']
		
		if ($request->getMethod() == 'POST') {
			$form->bind($request);
			$commantaireUti = new VoteCommentaireUtilisateur;
			$commentaire_id=new Commentaire;
			   //on recupere le texte du commentaire
			$text=$request->request->get("form")['texteCommentaire'];
			if($text!=NULL) {
				$commentaire_id->setTexteCommentaire($text);
				$commantaireUti->setCommentaire($commentaire_id);
				//on enregistre le commentaire
				$em = $this->getDoctrine()->getManager();
			    $em->persist($commantaire_id);
			    $em->flush();
			}
			
			$form = $this->createFormBuilder($commentaire_id)
							 ->add('texteCommentaire', 'text')
							 ->getForm();
		
			$vote=$this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findOneByNom($nomV);
					
			if($vote!=NULL){
				$commantaireUti->setVote($vote);
			}
				
		
			$utilisateur_id=$this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
				
			if($utilisateur_id!=NULL) {
				$commantaireUti->setUtilisateur($utilisateur_id);
			}
				
			
			// On l'enregistre notre objet $commentaireUtilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($commantaireUti);
			$em->flush();
			// On redirige vers la page 
			//return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire'));
			$listeVote=$this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findOneByVote($vote);
			$tableau=array();
			if($listeVote !=NULL) {
				for ($i=0; $i <sizeof($listevote) ; $i++) { 
					$tab=array(
						'login'=>$listevote[$i]->getUtilisateur()->getLogin(),
						'message'=>$listevote[$i]->getCommentaire()->getTexteCommentaire()
						);
					$tableau[]=$tab;
				}
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig',array(
								'tableau'=>$tableau));
			}
		}
		else {
			$commentaire=new Commentaire;
		    $form = $this->createFormBuilder($commentaire)
							 ->add('texteCommentaire', 'text')
							 ->getForm();
			$listeVote=$this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->find($nomV);
				$tableau=array();
				if($listeVote !=NULL) {
					for ($i=0; $i <sizeof($listevote) ; $i++) { 
						$tab=array(
							'login'=>$listevote[$i]->getUtilisateur()->getLogin(),
							'message'=>$listevote[$i]->getCommentaire()->getTexteCommentaire());
						$tableau[]=$tab;
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig',array(
		       						'tableau'=>$tableau));
					}
					
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
		  					'form' => $form->createView()));
			}
		}	
	}	
}