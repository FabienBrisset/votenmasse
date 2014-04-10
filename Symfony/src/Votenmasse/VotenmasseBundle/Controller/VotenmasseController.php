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

class VotenmasseController extends Controller {

	public function indexAction() {	
		// On récupère les variables de session
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		$inscription_valide = $session->get('inscription_valide');
		
		// Si l'inscription est valide alors l'utilisateur vient de s'inscrire
		if(!is_null($inscription_valide)) {
			$session->remove('inscription_valide');
			$message_inscription_valide = "Félicitation vous avez rejoins la communauté Votenmasse";
		}
		else {
			$message_inscription_valide = NULL;
		}
	
		$utilisateur = new Utilisateur;

		// On génère le formulaire d'inscription
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
												'mapped' => false)) // mapped = false ==> Ne pas enregistrer les données reçues dans l'entité
					 ->add('mail', 'email')
					 ->getForm();
					 
		// On vérifie que le login donné n'existe pas déjà : si on est en GET et pas en POST alors retournera NULL
		$utilisateur_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($request->request->get("form")['login']);
			
		// Si le login est déjà pris on redirige l'utilisateur avec un message d'erreur
		if($utilisateur_existe_deja != NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
															  'form' => $form->createView(),
															  'utilisateur' => $u,
															  'erreur' => "Le login saisi est déjà pris, veuillez en choisir un autre"));
		}
		
		// On vérifie aussi le mail
		$mail_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByMail($request->request->get("form")['mail']);
			
		if($mail_existe_deja != NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
														  'form' => $form->createView(),
														  'utilisateur' => $u,
														  'erreur' => "L'adresse mail indiquée existe déjà"));
		}

		// On vérifie qu'on est en POST
		if ($request->getMethod() == 'POST') {
			$form_exist = $request->request->get("form");
			if($form_exist == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			
		  // On fait le lien Requête <-> Formulaire
		  $form->bind($request);

		  // On vérifie que les valeurs entrées sont correctes
		  if ($form->isValid()) {
			// On cripte le mot de passe ==> C'est pour ça qu'on ne voulait pas de suite enregistrer la valeur dans l'entité
			$pass = $request->request->get("form")['motDePasse'];
				
			$pass_md5 = md5($pass);
			
			$utilisateur->setMotDePasse($pass_md5);
				
			// On enregistre notre objet $utilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($utilisateur);
			$em->flush();
			
			// On définit une variable de session pour indiquer lors du rappel de la route que l'utilisateur vient de s'inscrire
			$session->set('inscription_valide', true); 
			
			// On redirige vers la la page d'accueil
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
		// On récupère les variables de session
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		// Si l'utilisateur n'est pas "loggé" alors on le redirige vers la page d'accueil
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		// SELECT * FROM Utilisateur WHERE login = $u
		$infos_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($u);	
		
		// SELECT * FROM GroupeUtilisateur WHERE utilisateur = $infos_utilisateur->getId()
		$groupesUtilisateur_utilisateur_courant = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findByUtilisateur($infos_utilisateur->getId());
		
		// Pour tous les Groupes dont l'utilisateur courrant est membre on les ajoute à la liste des groupes à afficher
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
		
		// S'il y a des groupes où l'utilisateur est administrateur on les ajoute à la liste des groupes à afficher
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
		
		if ($groupes_utilisateur_courant != NULL) {
			for ($i = 0; $i<sizeof($groupes_utilisateur_courant); $i++) {
				$groupes[$groupes_utilisateur_courant[$i]->getNom()] = $groupes_utilisateur_courant[$i]->getNom();
			}
		}
		else {
			$groupes = NULL;
		}
		
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

		// On vérifie que l'on soit en POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  $form->bind($request);
		  
		  // Si l'utilisateur a demandé à associer un vote à un groupe
		  if($request->request->get("form")['groupeAssocie'] != NULL) {
			$groupeAssocie = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneByNom($request->request->get("form")['groupeAssocie']);
			
			// On regarde si le groupe existe
			if($groupeAssocie != NULL) {
				// On vérifie que s'il a indiqué un vote privé le groupe soit bien privé
				if($request->request->get("form")['type'] == 'Vote privé' && $groupeAssocie->getEtat() != 'Groupe privé') {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Un vote privé doit être associé à un groupe privé",
					'utilisateur' => $u));
				}
				// On vérifie que s'il a indiqué un vote réservé aux inscrits le groupe soit bien réservé aux inscrits
				else if($request->request->get("form")['type'] == 'Vote réservé aux inscrits' && $groupeAssocie->getEtat() != 'Groupe réservé aux inscrits') {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "Un vote réservé aux inscrits doit être associé à un groupe réservé aux inscrits",
					'utilisateur' => $u));
				}
				// On vérifie que s'il a indiqué un vote public le groupe soit bien public
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
		  // S'il n'a pas associé de groupe au vote on vérifie que le vote ne soit pas privé
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

		  // On enregistre notre objet $vote dans la base de données
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
	
	public function creationGroupeAction() {
		// On récupère les variables de session
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = new Groupe;
		
		// On récupère tous les utilisateurs de la base pour que le créateur du groupe puisse ajouter des membres au groupe
		$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
		
		if ($utilisateurs != NULL) {
			for ($i = 0; $i<sizeof($utilisateurs); $i++) {
				// On ajoute tous les utilisateurs sauf l'utilisateur courrant
				if ($utilisateurs[$i]->getLogin() != $u) {
					$utilisateurs_login[$utilisateurs[$i]->getLogin()] = $utilisateurs[$i]->getLogin();
				}
			}
			
			// S'il y a des utilisateurs dans la base alors on affiche le formulaire avec l'ajout de membres
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

				if ($request->getMethod() == 'POST') {	
				    // On fait le lien Requête <-> Formulaire
				    $form->bind($request);
			  
					$groupe->setAdministrateur($u);

					// On enregistre notre objet $groupe dans la base de données
					$em = $this->getDoctrine()->getManager();
					$em->persist($groupe);
					$em->flush();
					
					// Si le créateur a ajouté des membres
					if(isset($request->request->get("form")['utilisateurs'])) {		
						$groupe_id = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Groupe')
							->findOneByNom($request->request->get("form")['nom']);
							
						// On ajoute les membres au groupe
						for($i = 0; $i < sizeof($request->request->get("form")['utilisateurs']); $i++) {
							  $groupeUtilisateur[$i] = new GroupeUtilisateur;
							  
							  $utilisateur_id = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneBylogin($request->request->get("form")['utilisateurs'][$i]);

							  // On la lie au groupe, qui est ici toujours le même
							  $groupeUtilisateur[$i]->setGroupe($groupe_id);
							  // On la lie à l'utilisateur, qui change ici dans la boucle foreach
							  $groupeUtilisateur[$i]->setUtilisateur($utilisateur_id);
							  // Par défaut les membres ne sont pas modérateurs
							  $groupeUtilisateur[$i]->setModerateur(false);
							  // Par défaut les membres sont acceptés puisque le créateur les a ajoutés
							  $groupeUtilisateur[$i]->setAccepte(true);

							  // Et bien sûr, on persiste cette entité de relation, propriétaire des deux autres relations
							  $em->persist($groupeUtilisateur[$i]);
							}
							
							// On déclenche l'enregistrement
							$em->flush();
						}

					// On redirige vers la page d'accueil
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
				}
			}
			// Sinon s'il n'y a pas d'utilisateur dans la base alors on affiche le formulaire sans l'ajout de membres
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

				if ($request->getMethod() == 'POST') {
				  // On fait le lien Requête <-> Formulaire
				  $form->bind($request);
				  
				  $groupe->setAdministrateur($u);


					// On enregistre notre objet $groupe dans la base de données
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

			if ($request->getMethod() == 'POST') {
			  // On fait le lien Requête <-> Formulaire
			  $form->bind($request);
			  
			  $groupe->setAdministrateur($u);

			  // On vérifie que les valeurs entrées sont correctes
			  if ($form->isValid()) {
				// On enregistre notre objet $groupe dans la base de données
				$em = $this->getDoctrine()->getManager();
				$em->persist($groupe);
				$em->flush();

				// On redirige vers la page d'accueil
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
			// On crypte le mot de passe entré
			$pass = md5($request->request->get('mot_de_passe'));
		
			// On regarde si l'association login-motDePasse existe dans la base 
			$utilisateur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findBy(array('login' => $request->request->get('login'),
										'motDePasse' => $pass));
		
			// S'il existe on créé la variable de session de connexion
			if ($utilisateur != NULL) {		
				$session = new Session();
				$session->start();
			
				$session->set('utilisateur', $request->request->get('login')); 
				//je recupere tous les votes
 				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findAll();
					//on recupere tous les groupe
				$groupes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Groupe')
					->findAll();
			
				if ($votes != NULL) {
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneById($valeur->getCreateur());
						//if($createur!=NULL)
						$createurs[$cle] = $createur->getLogin();
					}		
				}
				/*if ($groupes!=NULL) {
					foreach ($groupes as $cle => $valeur) {
						$administrateur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneByLogin($valeur->getAdministrateur());
						//if($createur!=NULL)
						$administrateurs[$cle] = $administrateur->getLogin();
						}
				}*/
						
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
					'utilisateur' => $session->get('utilisateur'),
					'votes' => $votes,
					'vote_createurs' => $createurs,
					'groupes' => $groupes));
			}
			// Sinon on redirige l'utilisateur vers la page de connexion
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

		// On remet à 0 les variables de session
		$session->invalidate();
			
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
	}
	
	public function administrationAction() {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		// Si l'utilisateur est connecté alors il n'est pas administrateur du site donc on le redirige vers la page d'accueil
		if ($u != NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}

		if ($request->getMethod() == 'POST') {
			// On vérifie que le mot de passe est bon ou que l'administrateur est déjà connecté
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
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("type" => "Vote public", "groupeAssocie" => NULL), array('dateDeCreation' => 'desc'));
			
			if ($votes == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
					
			foreach ($votes as $cle => $valeur) {
				$createur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneById($valeur->getCreateur());
					
				$createurs[$cle] = $createur->getLogin();
			}
			
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
				'votes' => $votes,
				'vote_createurs' => $createurs));
		}

		if ($request->getMethod() == 'POST') {
			// On définit par défaut que l'utilisateur n'a pas demandé de filtre
			$en_cours = false;
			$termine = false;
			$public = false;
			$reserve = false;
			$prive = false;
		
			// S'il n'a pas filtré
			if (($request->request->get('type') == null) && ($request->request->get('etat') == null)) {
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
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
			// Sinon s'il a demandé un filtre sur l'état on va tester tous les cas possible pour état et afficher le résultat filtré
			else if (($request->request->get('type') == null) && ($request->request->get('etat') != null)){
				foreach ($request->request->get('etat') as $cle => $valeur) {
					if ($valeur == 'en_cours') {
						$en_cours = true;
					}
					if ($valeur == 'termine') {
						$termine = true;
					}
				}
				
				if ($en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array("etat" => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array("etat" => false), array('dateDeCreation' => 'desc'));
					
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
			// Sinon s'il a demandé un filtre sur le type on va tester tous les cas possible pour type et afficher le résultat filtré
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
				
				if ($public == true && $reserve == true && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote privé')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote public'), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote réservé aux inscrits'), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote privé'), array('dateDeCreation' => 'desc'));
					
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
			// Sinon s'il a demandé un filtre sur l'état et sur le type on va tester tous les cas possible pour état et type et afficher le résultat filtré
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
					if ($valeur == 'termine') {
						$termine = true;
					}
				}
				
				if ($public == true && $reserve == true && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote privé')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote public'), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote réservé aux inscrits'), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote privé'), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array("etat" => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public', 'Vote réservé aux inscrits'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote public', 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote privé', 'etat' => false), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array("etat" => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote public', 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array('type' => 'Vote privé', 'etat' => true), array('dateDeCreation' => 'desc'));
					
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
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
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
	
	public function resultatsAction() {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("type" => "Vote public", "etat" => false, "groupeAssocie" => NULL), array('dateDeCreation' => 'desc'));
					
			if ($votes == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			
			foreach ($votes as $cle => $valeur) {
			$createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneById($valeur->getCreateur());
				
			$createurs[$cle] = $createur->getLogin();
		}
			
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:resultats.html.twig', array(
			'votes' => $votes,
			'vote_createurs' => $createurs));
		}
		
		$infos_utilisateur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($u);
		
		$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("createur" => $infos_utilisateur->getId(), "etat" => false), array('dateDeCreation' => 'desc'));
		
		$votesAvis = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findByUtilisateur($infos_utilisateur);

		$cpt = 0;
		foreach ($votesAvis as $cle => $valeur) {
			$vote_courrant = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Vote')
						->findBy(array("id" => $valeur->getVote()->getId(), "etat" => false), array('dateDeCreation' => 'desc'));
			
			if ($vote_courrant != null) {
				$votesParVotesAvis[$cpt] = $vote_courrant;
				$cpt++;
			}
		}
		
		if (isset($votesParVotesAvis)) {
			$cpt = sizeof($votes);
			foreach ($votesParVotesAvis as $cle => $valeur) {
				foreach ($valeur as $key => $value) {
					$votes[$cpt] = $value;
					$cpt++;
				}
			}
		}
					
		foreach ($votes as $cle => $valeur) {
			$createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneById($valeur->getCreateur());
				
			$createurs[$cle] = $createur->getLogin();
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:resultats.html.twig', array(
			'utilisateur' => $u,
			'votes' => $votes,
			'vote_createurs' => $createurs));
	}
	
	// On passe l'id du vote en paramètre
	public function afficherVoteAction($vote = null) {
		$request = $this->get('request');
		
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		// On stocke dans une variable de session le vote en cours pour le transmettre ensuite quand on sera en POST pour la redirection vers les commentaires
		if ($request->getMethod() != 'POST') {
			$session->set('vote', $vote); 
		}
		
		if ($vote == null && $session->get('vote') == null) { 
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
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
			
			// Si l'utilisateur a triché en voulant accéder à un vote qui n'existe pas on le redirige vers la page d'accueil
			if ($infos_vote == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			
			$date_du_jour = date_create(date('Y-m-d'));
			if ((strtotime($infos_vote->getDateDeFin()->format("d-m-y")) < strtotime($date_du_jour->format("d-m-y"))) && ($infos_vote->getEtat() == true)) {
				 $infos_vote->setEtat(false);
								
			     $em = $this->getDoctrine()->getManager();
			     $em->persist($infos_vote);
			     $em->flush();
				 
				 return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $vote)));
			}
			
			if ($u == NULL) {
				if ($infos_vote->getType() == "Vote public") {
					if ($infos_vote->getEtat() == true) {
						$invite = $session->get('invite');
						
						$infos_invite = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneByLogin($invite);
						
						if ($invite != NULL) {
							// S'il a déjà voté alors on le redirige vers les commentaires du vote
							$avis_existe_deja = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
								->findOneBy(array('utilisateur' => $infos_invite, 'vote' => $infos_vote));
											
							if ($avis_existe_deja) {
								return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $vote)));
							}
						}
						
						if ($request->getMethod() != 'POST') {
							$donner_avis = new DonnerAvis;
					
							// S'il n'y a que 2 propositions
							if ($infos_vote->getChoix3() == NULL) {
								$form = $this->createFormBuilder($donner_avis)
										 ->add('choix1', 'text', array(
																'mapped' => false))
										 ->add('choix2', 'text', array(
																'mapped' => false))
										 ->getForm();
							}
							// S'il y a 3 propositions
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
							// S'il y a 4 propositions
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
							// S'il y a 5 propositions
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
							// S'il y a 6 propositions
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
							// S'il y a 7 propositions
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
							// S'il y a 8 propositions
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
							// S'il y a 9 propositions
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
							// S'il y a 10 propositions
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
							
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'form' => $form->createView(),
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
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
						else {
							// On met la valeur de la variable de session vote dans fin et vote à null
							  $session->set('fin', $session->get('vote'));
							  $session->set('vote', null);
							
							  $avis = new DonnerAvis;
							  
							  // S'il y avait 10 choix et que tous les choix ne sont pas entre 1 et 10
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix1'] > 10 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 10 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 10 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 10 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 10 || $request->request->get("form")['choix5'] < 1 ||
								$request->request->get("form")['choix6'] > 10 || $request->request->get("form")['choix6'] < 1 ||
								$request->request->get("form")['choix7'] > 10 || $request->request->get("form")['choix7'] < 1 ||
								$request->request->get("form")['choix8'] > 10 || $request->request->get("form")['choix8'] < 1 ||
								$request->request->get("form")['choix9'] > 10 || $request->request->get("form")['choix9'] < 1 ||
								$request->request->get("form")['choix10'] > 10 || $request->request->get("form")['choix10'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							 // S'il y avait 9 choix et que tous les choix ne sont pas entre 1 et 9
							  else if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix1'] > 9 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 9 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 9 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 9 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 9 || $request->request->get("form")['choix5'] < 1 ||
								$request->request->get("form")['choix6'] > 9 || $request->request->get("form")['choix6'] < 1 ||
								$request->request->get("form")['choix7'] > 9 || $request->request->get("form")['choix7'] < 1 ||
								$request->request->get("form")['choix8'] > 9 || $request->request->get("form")['choix8'] < 1 ||
								$request->request->get("form")['choix9'] > 9 || $request->request->get("form")['choix9'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 8 choix et que tous les choix ne sont pas entre 1 et 8
							  else if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix1'] > 8 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 8 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 8 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 8 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 8 || $request->request->get("form")['choix5'] < 1 ||
								$request->request->get("form")['choix6'] > 8 || $request->request->get("form")['choix6'] < 1 ||
								$request->request->get("form")['choix7'] > 8 || $request->request->get("form")['choix7'] < 1 ||
								$request->request->get("form")['choix8'] > 8 || $request->request->get("form")['choix8'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 7 choix et que tous les choix ne sont pas entre 1 et 7
							  else if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix1'] > 7 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 7 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 7 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 7 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 7 || $request->request->get("form")['choix5'] < 1 ||
								$request->request->get("form")['choix6'] > 7 || $request->request->get("form")['choix6'] < 1 ||
								$request->request->get("form")['choix7'] > 7 || $request->request->get("form")['choix7'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 6 choix et que tous les choix ne sont pas entre 1 et 6
							  else if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix1'] > 6 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 6 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 6 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 6 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 6 || $request->request->get("form")['choix5'] < 1 ||
								$request->request->get("form")['choix6'] > 6 || $request->request->get("form")['choix6'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 5 choix et que tous les choix ne sont pas entre 1 et 5
							  else if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix1'] > 5 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 5 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 5 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 5 || $request->request->get("form")['choix4'] < 1 ||
								$request->request->get("form")['choix5'] > 5 || $request->request->get("form")['choix5'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 4 choix et que tous les choix ne sont pas entre 1 et 4
							  else if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix1'] > 4 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 4 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 4 || $request->request->get("form")['choix3'] < 1 ||
								$request->request->get("form")['choix4'] > 4 || $request->request->get("form")['choix4'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il y avait 3 choix et que tous les choix ne sont pas entre 1 et 3
							  else if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix1'] > 3 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 3 || $request->request->get("form")['choix2'] < 1 ||
								$request->request->get("form")['choix3'] > 3 || $request->request->get("form")['choix3'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  // S'il n'y avait que 2 choix et que tous les choix ne sont pas entre 1 et 2
							  else {
								if ($request->request->get("form")['choix1'] > 2 || $request->request->get("form")['choix1'] < 1 ||
								$request->request->get("form")['choix2'] > 2 || $request->request->get("form")['choix2'] < 1) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
							  }
							  
							  // A partir d'ici on a tester tous les choix pour voir lequel est classé en numéro 1, 2, ... et on les stocke
							  if ($request->request->get("form")['choix1'] == '1') {
								$choix1 = 1;
								$avis->setChoix1($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '1') {
								// On vérifie que deux choix ne soient pas identiques
								if (isset($choix1)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix1 = 2;
									$avis->setChoix1($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 3;
										$avis->setChoix1($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 4;
										$avis->setChoix1($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 5;
										$avis->setChoix1($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 6;
										$avis->setChoix1($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 7;
										$avis->setChoix1($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 8;
										$avis->setChoix1($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 9;
										$avis->setChoix1($infos_vote->getChoix9());
									}
								}
							  }
							 if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '1') {
									if (isset($choix1)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix1 = 10;
										$avis->setChoix1($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '2') {
								$choix2 = 1;
								$avis->setChoix2($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '2') {
								if (isset($choix2)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix2 = 2;
									$avis->setChoix2($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 3;
										$avis->setChoix2($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 4;
										$avis->setChoix2($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 5;
										$avis->setChoix2($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 6;
										$avis->setChoix2($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 7;
										$avis->setChoix2($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 8;
										$avis->setChoix2($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 9;
										$avis->setChoix2($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '2') {
									if (isset($choix2)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix2 = 10;
										$avis->setChoix2($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '3') {
								$choix3 = 1;
								$avis->setChoix3($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '3') {
								if (isset($choix3)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix3 = 2;
									$avis->setChoix3($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 3;
										$avis->setChoix3($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 4;
										$avis->setChoix3($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 5;
										$avis->setChoix3($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 6;
										$avis->setChoix3($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 7;
										$avis->setChoix3($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 8;
										$avis->setChoix3($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 9;
										$avis->setChoix3($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '3') {
									if (isset($choix3)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix3 = 10;
										$avis->setChoix3($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '4') {
								$choix4 = 1;
								$avis->setChoix4($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '4') {
								if (isset($choix4)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix4 = 2;
									$avis->setChoix4($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 3;
										$avis->setChoix4($infos_vote->getChoix3());
									}
								}
							  }
							 if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 4;
										$avis->setChoix4($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 5;
										$avis->setChoix4($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 6;
										$avis->setChoix4($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 7;
										$avis->setChoix4($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 8;
										$avis->setChoix4($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 9;
										$avis->setChoix4($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '4') {
									if (isset($choix4)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix4 = 10;
										$avis->setChoix4($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '5') {
								$choix5 = 1;
								$avis->setChoix5($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '5') {
								if (isset($choix5)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix5 = 2;
									$avis->setChoix5($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 3;
										$avis->setChoix5($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 4;
										$avis->setChoix5($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 5;
										$avis->setChoix5($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 6;
										$avis->setChoix5($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 7;
										$avis->setChoix5($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 8;
										$avis->setChoix5($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 9;
										$avis->setChoix5($infos_vote->getChoix9());
									}
								  }
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '5') {
									if (isset($choix5)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix5 = 10;
										$avis->setChoix5($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '6') {
								$choix6 = 1;
								$avis->setChoix6($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '6') {
								if (isset($choix6)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix6 = 2;
									$avis->setChoix6($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 3;
										$avis->setChoix6($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 4;
										$avis->setChoix6($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 5;
										$avis->setChoix6($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 6;
										$avis->setChoix6($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 7;
										$avis->setChoix6($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 8;
										$avis->setChoix6($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 9;
										$avis->setChoix6($infos_vote->getChoix9());
									}
								}
							  }
							   if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '6') {
									if (isset($choix6)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix6 = 10;
										$avis->setChoix6($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '7') {
								$choix7 = 1;
								$avis->setChoix7($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '7') {
								if (isset($choix7)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix7 = 2;
									$avis->setChoix7($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 3;
										$avis->setChoix7($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 4;
										$avis->setChoix7($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 5;
										$avis->setChoix7($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 6;
										$avis->setChoix7($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 7;
										$avis->setChoix7($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 8;
										$avis->setChoix7($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 9;
										$avis->setChoix7($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '7') {
									if (isset($choix7)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix7 = 10;
										$avis->setChoix7($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '8') {
								$choix8 = 1;
								$avis->setChoix8($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '8') {
								if (isset($choix8)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix8 = 2;
									$avis->setChoix8($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 3;
										$avis->setChoix8($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 4;
										$avis->setChoix8($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '8') {	
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 5;
										$avis->setChoix8($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 6;
										$avis->setChoix8($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 7;
										$avis->setChoix8($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 8;
										$avis->setChoix8($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 9;
										$avis->setChoix8($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix9'] == '8') {
									if (isset($choix8)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix8 = 10;
										$avis->setChoix8($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '9') {
								$choix9 = 1;
								$avis->setChoix9($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '9') {
								if (isset($choix9)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix9 = 2;
									$avis->setChoix9($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 3;
										$avis->setChoix9($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 4;
										$avis->setChoix9($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 5;
										$avis->setChoix9($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 6;
										$avis->setChoix9($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 7;
										$avis->setChoix9($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 8;
										$avis->setChoix9($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 9;
										$avis->setChoix9($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '9') {
									if (isset($choix9)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix9 = 10;
										$avis->setChoix2($infos_vote->getChoix10());
									}
								}
							  }
							  
							  if ($request->request->get("form")['choix1'] == '10') {
								$choix10 = 1;
								$avis->setChoix10($infos_vote->getChoix1());
							  }
							  if ($request->request->get("form")['choix2'] == '10') {
								if (isset($choix10)) {
									return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
								}
								else {
									$choix10 = 2;
									$avis->setChoix10($infos_vote->getChoix2());
								}
							  }
							  if (isset($request->request->get("form")['choix3'])) {
								if ($request->request->get("form")['choix3'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 3;
										$avis->setChoix10($infos_vote->getChoix3());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix4'])) {
								if ($request->request->get("form")['choix4'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 4;
										$avis->setChoix10($infos_vote->getChoix4());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix5'])) {
								if ($request->request->get("form")['choix5'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 5;
										$avis->setChoix10($infos_vote->getChoix5());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix6'])) {
								if ($request->request->get("form")['choix6'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 6;
										$avis->setChoix10($infos_vote->getChoix6());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix7'])) {
								if ($request->request->get("form")['choix7'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 7;
										$avis->setChoix10($infos_vote->getChoix7());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix8'])) {
								if ($request->request->get("form")['choix8'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 8;
										$avis->setChoix10($infos_vote->getChoix8());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix9'])) {
								if ($request->request->get("form")['choix9'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 9;
										$avis->setChoix10($infos_vote->getChoix9());
									}
								}
							  }
							  if (isset($request->request->get("form")['choix10'])) {
								if ($request->request->get("form")['choix10'] == '10') {
									if (isset($choix10)) {
										return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
									}
									else {
										$choix10 = 10;
										$avis->setChoix10($infos_vote->getChoix10());
									}
								}
							  }
								
							  // On enregistre le donnerAvis
							  $avis->setVote($infos_vote);	
							  
							  $utilisateur = new Utilisateur;
							  
							  $pass = "abcdefghijklmnopqrstuvwxyz987654321012345643198536985prokfjaidinend";
							  $pass_md5 = md5($pass);
								
							  $utilisateur->setMotDePasse($pass_md5);
							  $date = date_create(date('Y-m-d'));
							  
							  $utilisateur->setDateDeNaissance($date);
							  $utilisateur->setSexe('H');
							  
							  $em = $this->getDoctrine()->getEntityManager();
							  $req_max_id = $em->createQuery(
								'SELECT MAX(u.id) AS max_id
								FROM VotenmasseVotenmasseBundle:Utilisateur u');

							  $max_id = $req_max_id->getResult();

							  $base = "Invité_";
							  $num = $max_id[0]["max_id"] + 1;
							  $invite = $base.$num;
							  
							  $utilisateur->setMail($invite."@fake.com");
							  $utilisateur->setPrenom($invite);
							  $utilisateur->setNom($invite);
							  $utilisateur->setLogin($invite);
							  
							  $session->set('invite', $invite);
									
							  // On enregistre notre objet $utilisateur dans la base de données
							  $em->persist($utilisateur);
							  $em->flush();
							  
							  $avis->setUtilisateur($utilisateur);
								
							  $em = $this->getDoctrine()->getManager();
							  $em->persist($avis);
							  $em->flush();
							  
							  // On redirige vers la page de commentaires du vote en question
							  return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $session->get('fin'), 'supp' => true)));
						}
					}
					else {
						// Afficher le résultat
						
						// Première position
						$em = $this->getDoctrine()->getEntityManager();
					    $choix_premiere_position = $em->createQuery(
							"SELECT DISTINCT da.choix1
							FROM VotenmasseVotenmasseBundle:DonnerAvis da
							WHERE da.vote = ".$vote);

					    $choix_premiere_position_result = $choix_premiere_position->getResult();
						
						$count_choix_premiere_position = array();
						
						$cpt = 0;

						foreach ($choix_premiere_position_result as $cle => $valeur) {
							foreach ($valeur as $key => $value) {
								if ($value != NULL) {
									 $data = addslashes($value);
									 $count_choix_premiere_position[$cpt] = $em->createQuery(
										"SELECT COUNT(da.choix1) as nb_choix1
										FROM VotenmasseVotenmasseBundle:DonnerAvis da
										WHERE da.vote = ".$vote."
										AND da.choix1 = :value")
										->setParameter('value', $value);;
										
									 $count_choix_premiere_position_result[$cpt] = $count_choix_premiere_position[$cpt]->getResult();
									 $cpt++;
								}
								else {
									$count_choix_premiere_position_result = $count_choix_premiere_position;
								}
							}
						}	
						
						if (isset($count_choix_premiere_position_result)) {
						
							if ($count_choix_premiere_position_result != null) {
								$premiere_position = (-1);
								$count_nb_premiere_position = 0;
								
								foreach ($count_choix_premiere_position_result as $cle => $valeur) {
									foreach ($valeur as $key => $value) {
										if ($value['nb_choix1'] > $count_nb_premiere_position) {
											$premiere_position = $cle;
											$count_nb_premiere_position = $value['nb_choix1'];
										}
									}
								}
								$premier = $choix_premiere_position_result[$premiere_position]["choix1"];
							}
							
							// Seconde position
							$choix_seconde_position = $em->createQuery(
								"SELECT DISTINCT da.choix2
								FROM VotenmasseVotenmasseBundle:DonnerAvis da
								WHERE da.vote = ".$vote."
								AND da.choix2 != :premier")
								->setParameter('premier', $premier);				

							$choix_seconde_position_result = $choix_seconde_position->getResult();
							
							$count_choix_seconde_position = array();
							
							$cpt = 0;

							foreach ($choix_seconde_position_result as $cle => $valeur) {
								foreach ($valeur as $key => $value) {
									if ($value != NULL) {
										 $count_choix_seconde_position = $em->createQuery(
											"SELECT COUNT(da.choix2) as nb_choix2
											FROM VotenmasseVotenmasseBundle:DonnerAvis da
											WHERE da.vote = ".$vote."
											AND da.choix2 = :value")
											->setParameter('value', $value);
										 
										 $count_choix_seconde_position_result[$cpt] = $count_choix_seconde_position->getResult();
										 $cpt++;
									}
									else {
										$count_choix_seconde_position_result = $count_choix_seconde_position;
									}
								}
							}
							
							if (isset($count_choix_seconde_position_result)) {
								if ($count_choix_seconde_position_result != null) {
									$seconde_position = (-1);
									$count_nb_seconde_position = 0;
									
									foreach ($count_choix_seconde_position_result as $cle => $valeur) {
										foreach ($valeur as $key => $value) {
											if ($value['nb_choix2'] > $count_nb_seconde_position) {
												$seconde_position = $cle;
												$count_nb_seconde_position = $value['nb_choix2'];
											}
										}
									}
									$second = $choix_seconde_position_result[$seconde_position]["choix2"];
								}
								
								// Troisieme position
								$choix_troisieme_position = $em->createQuery(
									"SELECT DISTINCT da.choix3
									FROM VotenmasseVotenmasseBundle:DonnerAvis da
									WHERE da.vote = ".$vote."
									AND da.choix3 NOT IN (:premier, :second)")
									->setParameter('premier', $premier)
									->setParameter('second', $second);	

								$choix_troisieme_position_result = $choix_troisieme_position->getResult();
								
								$count_choix_troisieme_position = array();
								
								$cpt = 0;

								foreach ($choix_troisieme_position_result as $cle => $valeur) {
									foreach ($valeur as $key => $value) {
										if ($value != NULL) {
											 $count_choix_troisieme_position = $em->createQuery(
												"SELECT COUNT(da.choix3) as nb_choix3
												FROM VotenmasseVotenmasseBundle:DonnerAvis da
												WHERE da.vote = ".$vote."
												AND da.choix3 = :value")
												->setParameter('value', $value);

											 $count_choix_troisieme_position_result[$cpt] = $count_choix_troisieme_position->getResult();
											 $cpt++;
										}
										else {
											$count_choix_troisieme_position_result = $count_choix_troisieme_position;
										}
									}
								}
								
								if (isset($count_choix_troisieme_position_result)) {
								
									if ($count_choix_troisieme_position_result != null) {
										$troisieme_position = (-1);
										$count_nb_troisieme_position = 0;
										
										foreach ($count_choix_troisieme_position_result as $cle => $valeur) {
											foreach ($valeur as $key => $value) {
												if ($value['nb_choix3'] > $count_nb_troisieme_position) {
													$troisieme_position = $cle;
													$count_nb_troisieme_position = $value['nb_choix3'];
												}
											}
										}
										$troisieme = $choix_troisieme_position_result[$troisieme_position]["choix3"];
									}
									
									// Quatrieme position
									$choix_quatrieme_position = $em->createQuery(
										"SELECT DISTINCT da.choix4
										FROM VotenmasseVotenmasseBundle:DonnerAvis da
										WHERE da.vote = ".$vote."
										AND da.choix4 NOT IN (:premier, :second, :troisieme)")
										->setParameter('premier', $premier)
										->setParameter('second', $second)
										->setParameter('troisieme', $troisieme);	

									$choix_quatrieme_position_result = $choix_quatrieme_position->getResult();
									
									$count_choix_quatrieme_position = array();
									
									$cpt = 0;

									foreach ($choix_quatrieme_position_result as $cle => $valeur) {
										foreach ($valeur as $key => $value) {
											if ($value != NULL) {
												 $count_choix_quatrieme_position = $em->createQuery(
													"SELECT COUNT(da.choix4) as nb_choix4
													FROM VotenmasseVotenmasseBundle:DonnerAvis da
													WHERE da.vote = ".$vote."
													AND da.choix4 = :value")
													->setParameter('value', $value);
												 $count_choix_quatrieme_position_result[$cpt] = $count_choix_quatrieme_position->getResult();
												 $cpt++;
											}
											else {
												$count_choix_quatrieme_position_result = $count_choix_quatrieme_position;
											}
										}
									}
									
									if (isset($count_choix_quatrieme_position_result)) {
									
										if ($count_choix_quatrieme_position_result != null) {
											$quatrieme_position = (-1);
											$count_nb_quatrieme_position = 0;
											
											foreach ($count_choix_quatrieme_position_result as $cle => $valeur) {
												foreach ($valeur as $key => $value) {
													if ($value['nb_choix4'] > $count_nb_quatrieme_position) {
														$quatrieme_position = $cle;
														$count_nb_quatrieme_position = $value['nb_choix4'];
													}
												}
											}
											$quatrieme = $choix_quatrieme_position_result[$quatrieme_position]["choix4"];
										}
										
										// Cinquieme position
										$choix_cinquieme_position = $em->createQuery(
											"SELECT DISTINCT da.choix5
											FROM VotenmasseVotenmasseBundle:DonnerAvis da
											WHERE da.vote = ".$vote."
											AND da.choix5 NOT IN (:premier, :second, :troisieme, :quatrieme)")
											->setParameter('premier', $premier)
											->setParameter('second', $second)
											->setParameter('troisieme', $troisieme)
											->setParameter('quatrieme', $quatrieme);

										$choix_cinquieme_position_result = $choix_cinquieme_position->getResult();
										
										$count_choix_cinquieme_position = array();
										
										$cpt = 0;

										foreach ($choix_cinquieme_position_result as $cle => $valeur) {
											foreach ($valeur as $key => $value) {
												if ($value != NULL) {
													 $count_choix_cinquieme_position = $em->createQuery(
														"SELECT COUNT(da.choix5) as nb_choix5
														FROM VotenmasseVotenmasseBundle:DonnerAvis da
														WHERE da.vote = ".$vote."
														AND da.choix5 = :value")
														->setParameter('value', $value);
													 
													 $count_choix_cinquieme_position_result[$cpt] = $count_choix_cinquieme_position->getResult();
													 $cpt++;
												}
												else {
													$count_choix_cinquieme_position_result = $count_choix_cinquieme_position;
												}
											}
										}
										
										if (isset($count_choix_cinquieme_position_result)) {
										
											if ($count_choix_cinquieme_position_result != null) {
												$cinquieme_position = (-1);
												$count_nb_cinquieme_position = 0;
												
												foreach ($count_choix_cinquieme_position_result as $cle => $valeur) {
													foreach ($valeur as $key => $value) {
														if ($value['nb_choix5'] > $count_nb_cinquieme_position) {
															$cinquieme_position = $cle;
															$count_nb_cinquieme_position = $value['nb_choix5'];
														}
													}
												}
												$cinquieme = $choix_cinquieme_position_result[$cinquieme_position]["choix5"];
											}
											
											// Sixieme position
											$choix_sixieme_position = $em->createQuery(
												"SELECT DISTINCT da.choix6
												FROM VotenmasseVotenmasseBundle:DonnerAvis da
												WHERE da.vote = ".$vote."
												AND da.choix6 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme)")
												->setParameter('premier', $premier)
												->setParameter('second', $second)
												->setParameter('troisieme', $troisieme)
												->setParameter('quatrieme', $quatrieme)
												->setParameter('cinquieme', $cinquieme);

											$choix_sixieme_position_result = $choix_sixieme_position->getResult();
											
											$count_choix_sixieme_position = array();
											
											$cpt = 0;

											foreach ($choix_sixieme_position_result as $cle => $valeur) {
												foreach ($valeur as $key => $value) {
													if ($value != NULL) {
														 $count_choix_sixieme_position = $em->createQuery(
															"SELECT COUNT(da.choix6) as nb_choix6
															FROM VotenmasseVotenmasseBundle:DonnerAvis da
															WHERE da.vote = ".$vote."
															AND da.choix6 = :value")
															->setParameter('value', $value);
														 
														 $count_choix_sixieme_position_result[$cpt] = $count_choix_sixieme_position->getResult();
														 $cpt++;
													}
													else {
														$count_choix_sixieme_position_result = $count_choix_sixieme_position;
													}
												}
											}
											
											if (isset($count_choix_sixieme_position_result)) {
											
												if ($count_choix_sixieme_position_result != null) {
													$sixieme_position = (-1);
													$count_nb_sixieme_position = 0;
													
													foreach ($count_choix_sixieme_position_result as $cle => $valeur) {
														foreach ($valeur as $key => $value) {
															if ($value['nb_choix6'] > $count_nb_sixieme_position) {
																$sixieme_position = $cle;
																$count_nb_sixieme_position = $value['nb_choix6'];
															}
														}
													}
													$sixieme = $choix_sixieme_position_result[$sixieme_position]["choix6"];
												}
												
												// Septieme position
												$choix_septieme_position = $em->createQuery(
													"SELECT DISTINCT da.choix7
													FROM VotenmasseVotenmasseBundle:DonnerAvis da
													WHERE da.vote = ".$vote."
													AND da.choix7 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme)")
													->setParameter('premier', $premier)
													->setParameter('second', $second)
													->setParameter('troisieme', $troisieme)
													->setParameter('quatrieme', $quatrieme)
													->setParameter('cinquieme', $cinquieme)
													->setParameter('sixieme', $sixieme);

												$choix_septieme_position_result = $choix_septieme_position->getResult();
												
												$count_choix_septieme_position = array();
												
												$cpt = 0;

												foreach ($choix_septieme_position_result as $cle => $valeur) {
													foreach ($valeur as $key => $value) {
														if ($value != NULL) {
															 $count_choix_septieme_position = $em->createQuery(
																"SELECT COUNT(da.choix7) as nb_choix7
																FROM VotenmasseVotenmasseBundle:DonnerAvis da
																WHERE da.vote = ".$vote."
																AND da.choix7 = :value")
																->setParameter('value', $value);
															 
															 $count_choix_septieme_position_result[$cpt] = $count_choix_septieme_position->getResult();
															 $cpt++;
														}
														else {
															$count_choix_septieme_position_result = $count_choix_septieme_position;
														}
													}
												}
												
												if (isset($count_choix_septieme_position_result)) {
												
													if ($count_choix_septieme_position_result != null) {
														$septieme_position = (-1);
														$count_nb_septieme_position = 0;
														
														foreach ($count_choix_septieme_position_result as $cle => $valeur) {
															foreach ($valeur as $key => $value) {
																if ($value['nb_choix7'] > $count_nb_septieme_position) {
																	$septieme_position = $cle;
																	$count_nb_septieme_position = $value['nb_choix7'];
																}
															}
														}
														$septieme = $choix_septieme_position_result[$septieme_position]["choix7"];
													}
													
													// Huitieme position
													$choix_huitieme_position = $em->createQuery(
														"SELECT DISTINCT da.choix8
														FROM VotenmasseVotenmasseBundle:DonnerAvis da
														WHERE da.vote = ".$vote."
														AND da.choix8 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme)")
														->setParameter('premier', $premier)
														->setParameter('second', $second)
														->setParameter('troisieme', $troisieme)
														->setParameter('quatrieme', $quatrieme)
														->setParameter('cinquieme', $cinquieme)
														->setParameter('sixieme', $sixieme)
														->setParameter('septieme', $septieme);

													$choix_huitieme_position_result = $choix_huitieme_position->getResult();
													
													$count_choix_huitieme_position = array();
													
													$cpt = 0;

													foreach ($choix_huitieme_position_result as $cle => $valeur) {
														foreach ($valeur as $key => $value) {
															if ($value != NULL) {
																 $count_choix_huitieme_position = $em->createQuery(
																	"SELECT COUNT(da.choix8) as nb_choix8
																	FROM VotenmasseVotenmasseBundle:DonnerAvis da
																	WHERE da.vote = ".$vote."
																	AND da.choix8 = :value")
																	->setParameter('value', $value);
																 
																 $count_choix_huitieme_position_result[$cpt] = $count_choix_huitieme_position->getResult();
																 $cpt++;
															}
															else {
																$count_choix_huitieme_position_result = $count_choix_huitieme_position;
															}
														}
													}
													
													if (isset($count_choix_huitieme_position_result)) {
													
														if ($count_choix_huitieme_position_result != null) {
															$huitieme_position = (-1);
															$count_nb_huitieme_position = 0;
															
															foreach ($count_choix_huitieme_position_result as $cle => $valeur) {
																foreach ($valeur as $key => $value) {
																	if ($value['nb_choix8'] > $count_nb_huitieme_position) {
																		$huitieme_position = $cle;
																		$count_nb_huitieme_position = $value['nb_choix8'];
																	}
																}
															}
															$huitieme = $choix_huitieme_position_result[$huitieme_position]["choix8"];
														}
														
														// Neuvieme position
														$choix_neuvieme_position = $em->createQuery(
															"SELECT DISTINCT da.choix9
															FROM VotenmasseVotenmasseBundle:DonnerAvis da
															WHERE da.vote = ".$vote."
															AND da.choix9 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme, :huitieme)")
															->setParameter('premier', $premier)
															->setParameter('second', $second)
															->setParameter('troisieme', $troisieme)
															->setParameter('quatrieme', $quatrieme)
															->setParameter('cinquieme', $cinquieme)
															->setParameter('sixieme', $sixieme)
															->setParameter('septieme', $septieme)
															->setParameter('huitieme', $huitieme);

														$choix_neuvieme_position_result = $choix_neuvieme_position->getResult();
														
														$count_choix_neuvieme_position = array();
														
														$cpt = 0;

														foreach ($choix_neuvieme_position_result as $cle => $valeur) {
															foreach ($valeur as $key => $value) {
																if ($value != NULL) {
																	 $count_choix_neuvieme_position = $em->createQuery(
																		"SELECT COUNT(da.choix9) as nb_choix9
																		FROM VotenmasseVotenmasseBundle:DonnerAvis da
																		WHERE da.vote = ".$vote."
																		AND da.choix9 = :value")
																		->setParameter('value', $value);
																	 
																	 $count_choix_neuvieme_position_result[$cpt] = $count_choix_neuvieme_position->getResult();
																	 $cpt++;
																}
																else {
																	$count_choix_neuvieme_position_result = $count_choix_neuvieme_position;
																}
															}
														}
														
														if (isset($count_choix_neuvieme_position_result)) {
														
															if ($count_choix_neuvieme_position_result != null) {
																$neuvieme_position = (-1);
																$count_nb_neuvieme_position = 0;
																
																foreach ($count_choix_neuvieme_position_result as $cle => $valeur) {
																	foreach ($valeur as $key => $value) {
																		if ($value['nb_choix9'] > $count_nb_neuvieme_position) {
																			$neuvieme_position = $cle;
																			$count_nb_neuvieme_position = $value['nb_choix9'];
																		}
																	}
																}
																$neuvieme = $choix_neuvieme_position_result[$neuvieme_position]["choix9"];
															}
															
															// Dixieme position
															$choix_dixieme_position = $em->createQuery(
																"SELECT DISTINCT da.choix10
																FROM VotenmasseVotenmasseBundle:DonnerAvis da
																WHERE da.vote = ".$vote."
																AND da.choix10 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme, :huitieme, :neuvieme)")
																->setParameter('premier', $premier)
																->setParameter('second', $second)
																->setParameter('troisieme', $troisieme)
																->setParameter('quatrieme', $quatrieme)
																->setParameter('cinquieme', $cinquieme)
																->setParameter('sixieme', $sixieme)
																->setParameter('septieme', $septieme)
																->setParameter('huitieme', $huitieme)
																->setParameter('neuvieme', $neuvieme);

															$choix_dixieme_position_result = $choix_dixieme_position->getResult();
															
															$count_choix_dixieme_position = array();
															
															$cpt = 0;

															foreach ($choix_dixieme_position_result as $cle => $valeur) {
																foreach ($valeur as $key => $value) {
																	if ($value != NULL) {
																		 $count_choix_dixieme_position = $em->createQuery(
																			"SELECT COUNT(da.choix10) as nb_choix10
																			FROM VotenmasseVotenmasseBundle:DonnerAvis da
																			WHERE da.vote = ".$vote."
																			AND da.choix10 = :value")
																			->setParameter('value', $value);
																		 
																		 $count_choix_dixieme_position_result[$cpt] = $count_choix_dixieme_position->getResult();
																		 $cpt++;
																	}
																	else {
																		$count_choix_dixieme_position_result = $count_choix_dixieme_position;
																	}
																}
															}
															
															if (isset($count_choix_dixieme_position_result)) {
															
																if ($count_choix_dixieme_position_result != null) {
																	$dixieme_position = (-1);
																	$count_nb_dixieme_position = 0;
																	
																	foreach ($count_choix_dixieme_position_result as $cle => $valeur) {
																		foreach ($valeur as $key => $value) {
																			if ($value['nb_choix10'] > $count_nb_dixieme_position) {
																				$dixieme_position = $cle;
																				$count_nb_dixieme_position = $value['nb_choix10'];
																			}
																		}
																	}
																	$dixieme = $choix_dixieme_position_result[$dixieme_position]["choix10"];
																}
															}
														}
													}
												}
											}
										}
									}
								}
							}
						}
						
						if (isset($dixieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme,
								  'cinquieme' => $cinquieme,
								  'sixieme' => $sixieme,
								  'septieme' => $septieme,
								  'huitieme' => $huitieme,
								  'neuvieme' => $neuvieme,
								  'dixieme' => $dixieme));
						}
						if (isset($neuvieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme,
								  'cinquieme' => $cinquieme,
								  'sixieme' => $sixieme,
								  'septieme' => $septieme,
								  'huitieme' => $huitieme,
								  'neuvieme' => $neuvieme));
						}
						if (isset($huitieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme,
								  'cinquieme' => $cinquieme,
								  'sixieme' => $sixieme,
								  'septieme' => $septieme,
								  'huitieme' => $huitieme));
						}
						if (isset($septieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme,
								  'cinquieme' => $cinquieme,
								  'sixieme' => $sixieme,
								  'septieme' => $septieme));
						}
						if (isset($sixieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
									  'vote_id' => $vote,
									  'vote_nom' => $infos_vote->getNom(),
									  'vote_texte' => $infos_vote->getTexte(),
									  'groupe_associe' => $infos_vote->getGroupeAssocie(),
									  'premier' => $premier,
									  'second' => $second,
									  'troisieme' => $troisieme,
									  'quatrieme' => $quatrieme,
									  'cinquieme' => $cinquieme,
									  'sixieme' => $sixieme));
						}
						if (isset($cinquieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme,
								  'cinquieme' => $cinquieme));
						}
						if (isset($quatrieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme,
								  'quatrieme' => $quatrieme));
						}
						if (isset($troisieme)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second,
								  'troisieme' => $troisieme));
						}
						if (isset($second)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier,
								  'second' => $second));
						}
						if (isset($premier)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie(),
								  'premier' => $premier));
						}
						if (!isset($premier)) {
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
								  'vote_id' => $vote,
								  'vote_nom' => $infos_vote->getNom(),
								  'vote_texte' => $infos_vote->getTexte(),
								  'groupe_associe' => $infos_vote->getGroupeAssocie()));
						}
					}
				}
				else {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
				}
			}
			
			// Ici on teste que l'utilisateur fait bien parti du groupe du vote ou qu'il en est administrateur
			if ($infos_vote->getGroupeAssocie() != NULL) {
			
				$admis = false;
				
				$groupeUtilisateur_utilisateur_courant = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
					->findOneByUtilisateur($utilisateur->getId());
					
				if($groupeUtilisateur_utilisateur_courant != NULL) {
					$admis = true;
				}
		
				// On regarde si l'utilisateur est administrateur du groupe
				if ($admis == false) {
					$groupes_utilisateur_courant_admin = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findOneByAdministrateur($u);
						
					if ($groupes_utilisateur_courant_admin != NULL) {
						$admis = true;
					}
				}
				
				if ($admis == false) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
				}
			}
			
			if ($infos_vote->getEtat() == false) {
				// Afficher le résultat
						
				// Première position
				$em = $this->getDoctrine()->getEntityManager();
				$choix_premiere_position = $em->createQuery(
					"SELECT DISTINCT da.choix1
					FROM VotenmasseVotenmasseBundle:DonnerAvis da
					WHERE da.vote = ".$vote);

				$choix_premiere_position_result = $choix_premiere_position->getResult();
				
				$count_choix_premiere_position = array();
				
				$cpt = 0;

				foreach ($choix_premiere_position_result as $cle => $valeur) {
					foreach ($valeur as $key => $value) {
						if ($value != NULL) {
							 $data = addslashes($value);
							 $count_choix_premiere_position[$cpt] = $em->createQuery(
								"SELECT COUNT(da.choix1) as nb_choix1
								FROM VotenmasseVotenmasseBundle:DonnerAvis da
								WHERE da.vote = ".$vote."
								AND da.choix1 = :value")
								->setParameter('value', $value);;
								
							 $count_choix_premiere_position_result[$cpt] = $count_choix_premiere_position[$cpt]->getResult();
							 $cpt++;
						}
						else {
							$count_choix_premiere_position_result = $count_choix_premiere_position;
						}
					}
				}	
				
				if (isset($count_choix_premiere_position_result)) {
				
					if ($count_choix_premiere_position_result != null) {
						$premiere_position = (-1);
						$count_nb_premiere_position = 0;
						
						foreach ($count_choix_premiere_position_result as $cle => $valeur) {
							foreach ($valeur as $key => $value) {
								if ($value['nb_choix1'] > $count_nb_premiere_position) {
									$premiere_position = $cle;
									$count_nb_premiere_position = $value['nb_choix1'];
								}
							}
						}
						$premier = $choix_premiere_position_result[$premiere_position]["choix1"];
					}
					
					// Seconde position
					$choix_seconde_position = $em->createQuery(
						"SELECT DISTINCT da.choix2
						FROM VotenmasseVotenmasseBundle:DonnerAvis da
						WHERE da.vote = ".$vote."
						AND da.choix2 != :premier")
						->setParameter('premier', $premier);				

					$choix_seconde_position_result = $choix_seconde_position->getResult();
					
					$count_choix_seconde_position = array();
					
					$cpt = 0;

					foreach ($choix_seconde_position_result as $cle => $valeur) {
						foreach ($valeur as $key => $value) {
							if ($value != NULL) {
								 $count_choix_seconde_position = $em->createQuery(
									"SELECT COUNT(da.choix2) as nb_choix2
									FROM VotenmasseVotenmasseBundle:DonnerAvis da
									WHERE da.vote = ".$vote."
									AND da.choix2 = :value")
									->setParameter('value', $value);
								 
								 $count_choix_seconde_position_result[$cpt] = $count_choix_seconde_position->getResult();
								 $cpt++;
							}
							else {
								$count_choix_seconde_position_result = $count_choix_seconde_position;
							}
						}
					}
					
					if (isset($count_choix_seconde_position_result)) {
						if ($count_choix_seconde_position_result != null) {
							$seconde_position = (-1);
							$count_nb_seconde_position = 0;
							
							foreach ($count_choix_seconde_position_result as $cle => $valeur) {
								foreach ($valeur as $key => $value) {
									if ($value['nb_choix2'] > $count_nb_seconde_position) {
										$seconde_position = $cle;
										$count_nb_seconde_position = $value['nb_choix2'];
									}
								}
							}
							$second = $choix_seconde_position_result[$seconde_position]["choix2"];
						}
						
						// Troisieme position
						$choix_troisieme_position = $em->createQuery(
							"SELECT DISTINCT da.choix3
							FROM VotenmasseVotenmasseBundle:DonnerAvis da
							WHERE da.vote = ".$vote."
							AND da.choix3 NOT IN (:premier, :second)")
							->setParameter('premier', $premier)
							->setParameter('second', $second);	

						$choix_troisieme_position_result = $choix_troisieme_position->getResult();
						
						$count_choix_troisieme_position = array();
						
						$cpt = 0;

						foreach ($choix_troisieme_position_result as $cle => $valeur) {
							foreach ($valeur as $key => $value) {
								if ($value != NULL) {
									 $count_choix_troisieme_position = $em->createQuery(
										"SELECT COUNT(da.choix3) as nb_choix3
										FROM VotenmasseVotenmasseBundle:DonnerAvis da
										WHERE da.vote = ".$vote."
										AND da.choix3 = :value")
										->setParameter('value', $value);

									 $count_choix_troisieme_position_result[$cpt] = $count_choix_troisieme_position->getResult();
									 $cpt++;
								}
								else {
									$count_choix_troisieme_position_result = $count_choix_troisieme_position;
								}
							}
						}
						
						if (isset($count_choix_troisieme_position_result)) {
						
							if ($count_choix_troisieme_position_result != null) {
								$troisieme_position = (-1);
								$count_nb_troisieme_position = 0;
								
								foreach ($count_choix_troisieme_position_result as $cle => $valeur) {
									foreach ($valeur as $key => $value) {
										if ($value['nb_choix3'] > $count_nb_troisieme_position) {
											$troisieme_position = $cle;
											$count_nb_troisieme_position = $value['nb_choix3'];
										}
									}
								}
								$troisieme = $choix_troisieme_position_result[$troisieme_position]["choix3"];
							}
							
							// Quatrieme position
							$choix_quatrieme_position = $em->createQuery(
								"SELECT DISTINCT da.choix4
								FROM VotenmasseVotenmasseBundle:DonnerAvis da
								WHERE da.vote = ".$vote."
								AND da.choix4 NOT IN (:premier, :second, :troisieme)")
								->setParameter('premier', $premier)
								->setParameter('second', $second)
								->setParameter('troisieme', $troisieme);	

							$choix_quatrieme_position_result = $choix_quatrieme_position->getResult();
							
							$count_choix_quatrieme_position = array();
							
							$cpt = 0;

							foreach ($choix_quatrieme_position_result as $cle => $valeur) {
								foreach ($valeur as $key => $value) {
									if ($value != NULL) {
										 $count_choix_quatrieme_position = $em->createQuery(
											"SELECT COUNT(da.choix4) as nb_choix4
											FROM VotenmasseVotenmasseBundle:DonnerAvis da
											WHERE da.vote = ".$vote."
											AND da.choix4 = :value")
											->setParameter('value', $value);
										 $count_choix_quatrieme_position_result[$cpt] = $count_choix_quatrieme_position->getResult();
										 $cpt++;
									}
									else {
										$count_choix_quatrieme_position_result = $count_choix_quatrieme_position;
									}
								}
							}
							
							if (isset($count_choix_quatrieme_position_result)) {
							
								if ($count_choix_quatrieme_position_result != null) {
									$quatrieme_position = (-1);
									$count_nb_quatrieme_position = 0;
									
									foreach ($count_choix_quatrieme_position_result as $cle => $valeur) {
										foreach ($valeur as $key => $value) {
											if ($value['nb_choix4'] > $count_nb_quatrieme_position) {
												$quatrieme_position = $cle;
												$count_nb_quatrieme_position = $value['nb_choix4'];
											}
										}
									}
									$quatrieme = $choix_quatrieme_position_result[$quatrieme_position]["choix4"];
								}
								
								// Cinquieme position
								$choix_cinquieme_position = $em->createQuery(
									"SELECT DISTINCT da.choix5
									FROM VotenmasseVotenmasseBundle:DonnerAvis da
									WHERE da.vote = ".$vote."
									AND da.choix5 NOT IN (:premier, :second, :troisieme, :quatrieme)")
									->setParameter('premier', $premier)
									->setParameter('second', $second)
									->setParameter('troisieme', $troisieme)
									->setParameter('quatrieme', $quatrieme);

								$choix_cinquieme_position_result = $choix_cinquieme_position->getResult();
								
								$count_choix_cinquieme_position = array();
								
								$cpt = 0;

								foreach ($choix_cinquieme_position_result as $cle => $valeur) {
									foreach ($valeur as $key => $value) {
										if ($value != NULL) {
											 $count_choix_cinquieme_position = $em->createQuery(
												"SELECT COUNT(da.choix5) as nb_choix5
												FROM VotenmasseVotenmasseBundle:DonnerAvis da
												WHERE da.vote = ".$vote."
												AND da.choix5 = :value")
												->setParameter('value', $value);
											 
											 $count_choix_cinquieme_position_result[$cpt] = $count_choix_cinquieme_position->getResult();
											 $cpt++;
										}
										else {
											$count_choix_cinquieme_position_result = $count_choix_cinquieme_position;
										}
									}
								}
								
								if (isset($count_choix_cinquieme_position_result)) {
								
									if ($count_choix_cinquieme_position_result != null) {
										$cinquieme_position = (-1);
										$count_nb_cinquieme_position = 0;
										
										foreach ($count_choix_cinquieme_position_result as $cle => $valeur) {
											foreach ($valeur as $key => $value) {
												if ($value['nb_choix5'] > $count_nb_cinquieme_position) {
													$cinquieme_position = $cle;
													$count_nb_cinquieme_position = $value['nb_choix5'];
												}
											}
										}
										$cinquieme = $choix_cinquieme_position_result[$cinquieme_position]["choix5"];
									}
									
									// Sixieme position
									$choix_sixieme_position = $em->createQuery(
										"SELECT DISTINCT da.choix6
										FROM VotenmasseVotenmasseBundle:DonnerAvis da
										WHERE da.vote = ".$vote."
										AND da.choix6 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme)")
										->setParameter('premier', $premier)
										->setParameter('second', $second)
										->setParameter('troisieme', $troisieme)
										->setParameter('quatrieme', $quatrieme)
										->setParameter('cinquieme', $cinquieme);

									$choix_sixieme_position_result = $choix_sixieme_position->getResult();
									
									$count_choix_sixieme_position = array();
									
									$cpt = 0;

									foreach ($choix_sixieme_position_result as $cle => $valeur) {
										foreach ($valeur as $key => $value) {
											if ($value != NULL) {
												 $count_choix_sixieme_position = $em->createQuery(
													"SELECT COUNT(da.choix6) as nb_choix6
													FROM VotenmasseVotenmasseBundle:DonnerAvis da
													WHERE da.vote = ".$vote."
													AND da.choix6 = :value")
													->setParameter('value', $value);
												 
												 $count_choix_sixieme_position_result[$cpt] = $count_choix_sixieme_position->getResult();
												 $cpt++;
											}
											else {
												$count_choix_sixieme_position_result = $count_choix_sixieme_position;
											}
										}
									}
									
									if (isset($count_choix_sixieme_position_result)) {
									
										if ($count_choix_sixieme_position_result != null) {
											$sixieme_position = (-1);
											$count_nb_sixieme_position = 0;
											
											foreach ($count_choix_sixieme_position_result as $cle => $valeur) {
												foreach ($valeur as $key => $value) {
													if ($value['nb_choix6'] > $count_nb_sixieme_position) {
														$sixieme_position = $cle;
														$count_nb_sixieme_position = $value['nb_choix6'];
													}
												}
											}
											$sixieme = $choix_sixieme_position_result[$sixieme_position]["choix6"];
										}
										
										// Septieme position
										$choix_septieme_position = $em->createQuery(
											"SELECT DISTINCT da.choix7
											FROM VotenmasseVotenmasseBundle:DonnerAvis da
											WHERE da.vote = ".$vote."
											AND da.choix7 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme)")
											->setParameter('premier', $premier)
											->setParameter('second', $second)
											->setParameter('troisieme', $troisieme)
											->setParameter('quatrieme', $quatrieme)
											->setParameter('cinquieme', $cinquieme)
											->setParameter('sixieme', $sixieme);

										$choix_septieme_position_result = $choix_septieme_position->getResult();
										
										$count_choix_septieme_position = array();
										
										$cpt = 0;

										foreach ($choix_septieme_position_result as $cle => $valeur) {
											foreach ($valeur as $key => $value) {
												if ($value != NULL) {
													 $count_choix_septieme_position = $em->createQuery(
														"SELECT COUNT(da.choix7) as nb_choix7
														FROM VotenmasseVotenmasseBundle:DonnerAvis da
														WHERE da.vote = ".$vote."
														AND da.choix7 = :value")
														->setParameter('value', $value);
													 
													 $count_choix_septieme_position_result[$cpt] = $count_choix_septieme_position->getResult();
													 $cpt++;
												}
												else {
													$count_choix_septieme_position_result = $count_choix_septieme_position;
												}
											}
										}
										
										if (isset($count_choix_septieme_position_result)) {
										
											if ($count_choix_septieme_position_result != null) {
												$septieme_position = (-1);
												$count_nb_septieme_position = 0;
												
												foreach ($count_choix_septieme_position_result as $cle => $valeur) {
													foreach ($valeur as $key => $value) {
														if ($value['nb_choix7'] > $count_nb_septieme_position) {
															$septieme_position = $cle;
															$count_nb_septieme_position = $value['nb_choix7'];
														}
													}
												}
												$septieme = $choix_septieme_position_result[$septieme_position]["choix7"];
											}
											
											// Huitieme position
											$choix_huitieme_position = $em->createQuery(
												"SELECT DISTINCT da.choix8
												FROM VotenmasseVotenmasseBundle:DonnerAvis da
												WHERE da.vote = ".$vote."
												AND da.choix8 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme)")
												->setParameter('premier', $premier)
												->setParameter('second', $second)
												->setParameter('troisieme', $troisieme)
												->setParameter('quatrieme', $quatrieme)
												->setParameter('cinquieme', $cinquieme)
												->setParameter('sixieme', $sixieme)
												->setParameter('septieme', $septieme);

											$choix_huitieme_position_result = $choix_huitieme_position->getResult();
											
											$count_choix_huitieme_position = array();
											
											$cpt = 0;

											foreach ($choix_huitieme_position_result as $cle => $valeur) {
												foreach ($valeur as $key => $value) {
													if ($value != NULL) {
														 $count_choix_huitieme_position = $em->createQuery(
															"SELECT COUNT(da.choix8) as nb_choix8
															FROM VotenmasseVotenmasseBundle:DonnerAvis da
															WHERE da.vote = ".$vote."
															AND da.choix8 = :value")
															->setParameter('value', $value);
														 
														 $count_choix_huitieme_position_result[$cpt] = $count_choix_huitieme_position->getResult();
														 $cpt++;
													}
													else {
														$count_choix_huitieme_position_result = $count_choix_huitieme_position;
													}
												}
											}
											
											if (isset($count_choix_huitieme_position_result)) {
											
												if ($count_choix_huitieme_position_result != null) {
													$huitieme_position = (-1);
													$count_nb_huitieme_position = 0;
													
													foreach ($count_choix_huitieme_position_result as $cle => $valeur) {
														foreach ($valeur as $key => $value) {
															if ($value['nb_choix8'] > $count_nb_huitieme_position) {
																$huitieme_position = $cle;
																$count_nb_huitieme_position = $value['nb_choix8'];
															}
														}
													}
													$huitieme = $choix_huitieme_position_result[$huitieme_position]["choix8"];
												}
												
												// Neuvieme position
												$choix_neuvieme_position = $em->createQuery(
													"SELECT DISTINCT da.choix9
													FROM VotenmasseVotenmasseBundle:DonnerAvis da
													WHERE da.vote = ".$vote."
													AND da.choix9 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme, :huitieme)")
													->setParameter('premier', $premier)
													->setParameter('second', $second)
													->setParameter('troisieme', $troisieme)
													->setParameter('quatrieme', $quatrieme)
													->setParameter('cinquieme', $cinquieme)
													->setParameter('sixieme', $sixieme)
													->setParameter('septieme', $septieme)
													->setParameter('huitieme', $huitieme);

												$choix_neuvieme_position_result = $choix_neuvieme_position->getResult();
												
												$count_choix_neuvieme_position = array();
												
												$cpt = 0;

												foreach ($choix_neuvieme_position_result as $cle => $valeur) {
													foreach ($valeur as $key => $value) {
														if ($value != NULL) {
															 $count_choix_neuvieme_position = $em->createQuery(
																"SELECT COUNT(da.choix9) as nb_choix9
																FROM VotenmasseVotenmasseBundle:DonnerAvis da
																WHERE da.vote = ".$vote."
																AND da.choix9 = :value")
																->setParameter('value', $value);
															 
															 $count_choix_neuvieme_position_result[$cpt] = $count_choix_neuvieme_position->getResult();
															 $cpt++;
														}
														else {
															$count_choix_neuvieme_position_result = $count_choix_neuvieme_position;
														}
													}
												}
												
												if (isset($count_choix_neuvieme_position_result)) {
												
													if ($count_choix_neuvieme_position_result != null) {
														$neuvieme_position = (-1);
														$count_nb_neuvieme_position = 0;
														
														foreach ($count_choix_neuvieme_position_result as $cle => $valeur) {
															foreach ($valeur as $key => $value) {
																if ($value['nb_choix9'] > $count_nb_neuvieme_position) {
																	$neuvieme_position = $cle;
																	$count_nb_neuvieme_position = $value['nb_choix9'];
																}
															}
														}
														$neuvieme = $choix_neuvieme_position_result[$neuvieme_position]["choix9"];
													}
													
													// Dixieme position
													$choix_dixieme_position = $em->createQuery(
														"SELECT DISTINCT da.choix10
														FROM VotenmasseVotenmasseBundle:DonnerAvis da
														WHERE da.vote = ".$vote."
														AND da.choix10 NOT IN (:premier, :second, :troisieme, :quatrieme, :cinquieme, :sixieme, :septieme, :huitieme, :neuvieme)")
														->setParameter('premier', $premier)
														->setParameter('second', $second)
														->setParameter('troisieme', $troisieme)
														->setParameter('quatrieme', $quatrieme)
														->setParameter('cinquieme', $cinquieme)
														->setParameter('sixieme', $sixieme)
														->setParameter('septieme', $septieme)
														->setParameter('huitieme', $huitieme)
														->setParameter('neuvieme', $neuvieme);

													$choix_dixieme_position_result = $choix_dixieme_position->getResult();
													
													$count_choix_dixieme_position = array();
													
													$cpt = 0;

													foreach ($choix_dixieme_position_result as $cle => $valeur) {
														foreach ($valeur as $key => $value) {
															if ($value != NULL) {
																 $count_choix_dixieme_position = $em->createQuery(
																	"SELECT COUNT(da.choix10) as nb_choix10
																	FROM VotenmasseVotenmasseBundle:DonnerAvis da
																	WHERE da.vote = ".$vote."
																	AND da.choix10 = :value")
																	->setParameter('value', $value);
																 
																 $count_choix_dixieme_position_result[$cpt] = $count_choix_dixieme_position->getResult();
																 $cpt++;
															}
															else {
																$count_choix_dixieme_position_result = $count_choix_dixieme_position;
															}
														}
													}
													
													if (isset($count_choix_dixieme_position_result)) {
													
														if ($count_choix_dixieme_position_result != null) {
															$dixieme_position = (-1);
															$count_nb_dixieme_position = 0;
															
															foreach ($count_choix_dixieme_position_result as $cle => $valeur) {
																foreach ($valeur as $key => $value) {
																	if ($value['nb_choix10'] > $count_nb_dixieme_position) {
																		$dixieme_position = $cle;
																		$count_nb_dixieme_position = $value['nb_choix10'];
																	}
																}
															}
															$dixieme = $choix_dixieme_position_result[$dixieme_position]["choix10"];
														}
													}
												}
											}
										}
									}
								}
							}
						}
					}
				}
				
				if (isset($dixieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme,
						  'cinquieme' => $cinquieme,
						  'sixieme' => $sixieme,
						  'septieme' => $septieme,
						  'huitieme' => $huitieme,
						  'neuvieme' => $neuvieme,
						  'dixieme' => $dixieme));
				}
				if (isset($neuvieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme,
						  'cinquieme' => $cinquieme,
						  'sixieme' => $sixieme,
						  'septieme' => $septieme,
						  'huitieme' => $huitieme,
						  'neuvieme' => $neuvieme));
				}
				if (isset($huitieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme,
						  'cinquieme' => $cinquieme,
						  'sixieme' => $sixieme,
						  'septieme' => $septieme,
						  'huitieme' => $huitieme));
				}
				if (isset($septieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme,
						  'cinquieme' => $cinquieme,
						  'sixieme' => $sixieme,
						  'septieme' => $septieme));
				}
				if (isset($sixieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
							  'utilisateur' => $u,
							  'vote_id' => $vote,
							  'vote_nom' => $infos_vote->getNom(),
							  'vote_texte' => $infos_vote->getTexte(),
							  'groupe_associe' => $infos_vote->getGroupeAssocie(),
							  'premier' => $premier,
							  'second' => $second,
							  'troisieme' => $troisieme,
							  'quatrieme' => $quatrieme,
							  'cinquieme' => $cinquieme,
							  'sixieme' => $sixieme));
				}
				if (isset($cinquieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme,
						  'cinquieme' => $cinquieme));
				}
				if (isset($quatrieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme,
						  'quatrieme' => $quatrieme));
				}
				if (isset($troisieme)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second,
						  'troisieme' => $troisieme));
				}
				if (isset($second)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier,
						  'second' => $second));
				}
				if (isset($premier)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie(),
						  'premier' => $premier));
				}
				if (!isset($premier)) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:affichage_vote.html.twig', array(
						  'utilisateur' => $u,
						  'vote_id' => $vote,
						  'vote_nom' => $infos_vote->getNom(),
						  'vote_texte' => $infos_vote->getTexte(),
						  'groupe_associe' => $infos_vote->getGroupeAssocie()));
				}
			}
				
			// S'il a déjà voté alors on le redirige vers les commentaires du vote
			$avis_existe_deja = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findOneBy(array('utilisateur' => $utilisateur, 'vote' => $infos_vote));
				
			$fin = $session->get('fin');
				
			if ($avis_existe_deja && $fin != NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $session->get('fin'), 'supp' => true)));
			}
			if ($avis_existe_deja && $fin == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $vote)));
			}
		
			$donner_avis = new DonnerAvis;
				
			// S'il n'y a que 2 propositions
			if ($infos_vote->getChoix3() == NULL) {
				$form = $this->createFormBuilder($donner_avis)
						 ->add('choix1', 'text', array(
												'mapped' => false))
						 ->add('choix2', 'text', array(
												'mapped' => false))
						 ->getForm();
			}
			// S'il y a 3 propositions
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
			// S'il y a 4 propositions
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
			// S'il y a 5 propositions
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
			// S'il y a 6 propositions
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
			// S'il y a 7 propositions
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
			// S'il y a 8 propositions
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
			// S'il y a 9 propositions
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
			// S'il y a 10 propositions
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

			if ($request->getMethod() == 'POST') {
			  // On met la valeur de la variable de session vote dans fin et vote à null
			  $session->set('fin', $session->get('vote'));
			  $session->set('vote', null);
			
			  $avis = new DonnerAvis;
			  
			  // S'il y avait 10 choix et que tous les choix ne sont pas entre 1 et 10
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix1'] > 10 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 10 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 10 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 10 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 10 || $request->request->get("form")['choix5'] < 1 ||
				$request->request->get("form")['choix6'] > 10 || $request->request->get("form")['choix6'] < 1 ||
				$request->request->get("form")['choix7'] > 10 || $request->request->get("form")['choix7'] < 1 ||
				$request->request->get("form")['choix8'] > 10 || $request->request->get("form")['choix8'] < 1 ||
				$request->request->get("form")['choix9'] > 10 || $request->request->get("form")['choix9'] < 1 ||
				$request->request->get("form")['choix10'] > 10 || $request->request->get("form")['choix10'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			 // S'il y avait 9 choix et que tous les choix ne sont pas entre 1 et 9
			  else if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix1'] > 9 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 9 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 9 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 9 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 9 || $request->request->get("form")['choix5'] < 1 ||
				$request->request->get("form")['choix6'] > 9 || $request->request->get("form")['choix6'] < 1 ||
				$request->request->get("form")['choix7'] > 9 || $request->request->get("form")['choix7'] < 1 ||
				$request->request->get("form")['choix8'] > 9 || $request->request->get("form")['choix8'] < 1 ||
				$request->request->get("form")['choix9'] > 9 || $request->request->get("form")['choix9'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 8 choix et que tous les choix ne sont pas entre 1 et 8
			  else if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix1'] > 8 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 8 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 8 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 8 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 8 || $request->request->get("form")['choix5'] < 1 ||
				$request->request->get("form")['choix6'] > 8 || $request->request->get("form")['choix6'] < 1 ||
				$request->request->get("form")['choix7'] > 8 || $request->request->get("form")['choix7'] < 1 ||
				$request->request->get("form")['choix8'] > 8 || $request->request->get("form")['choix8'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 7 choix et que tous les choix ne sont pas entre 1 et 7
			  else if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix1'] > 7 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 7 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 7 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 7 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 7 || $request->request->get("form")['choix5'] < 1 ||
				$request->request->get("form")['choix6'] > 7 || $request->request->get("form")['choix6'] < 1 ||
				$request->request->get("form")['choix7'] > 7 || $request->request->get("form")['choix7'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 6 choix et que tous les choix ne sont pas entre 1 et 6
			  else if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix1'] > 6 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 6 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 6 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 6 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 6 || $request->request->get("form")['choix5'] < 1 ||
				$request->request->get("form")['choix6'] > 6 || $request->request->get("form")['choix6'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 5 choix et que tous les choix ne sont pas entre 1 et 5
			  else if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix1'] > 5 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 5 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 5 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 5 || $request->request->get("form")['choix4'] < 1 ||
				$request->request->get("form")['choix5'] > 5 || $request->request->get("form")['choix5'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 4 choix et que tous les choix ne sont pas entre 1 et 4
			  else if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix1'] > 4 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 4 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 4 || $request->request->get("form")['choix3'] < 1 ||
				$request->request->get("form")['choix4'] > 4 || $request->request->get("form")['choix4'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il y avait 3 choix et que tous les choix ne sont pas entre 1 et 3
			  else if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix1'] > 3 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 3 || $request->request->get("form")['choix2'] < 1 ||
				$request->request->get("form")['choix3'] > 3 || $request->request->get("form")['choix3'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  // S'il n'y avait que 2 choix et que tous les choix ne sont pas entre 1 et 2
			  else {
				if ($request->request->get("form")['choix1'] > 2 || $request->request->get("form")['choix1'] < 1 ||
				$request->request->get("form")['choix2'] > 2 || $request->request->get("form")['choix2'] < 1) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
			  }
			  
			  // A partir d'ici on a tester tous les choix pour voir lequel est classé en numéro 1, 2, ... et on les stocke
			  if ($request->request->get("form")['choix1'] == '1') {
				$choix1 = 1;
				$avis->setChoix1($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '1') {
				// On vérifie que deux choix ne soient pas identiques
				if (isset($choix1)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix1 = 2;
					$avis->setChoix1($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 3;
						$avis->setChoix1($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 4;
						$avis->setChoix1($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 5;
						$avis->setChoix1($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 6;
						$avis->setChoix1($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 7;
						$avis->setChoix1($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 8;
						$avis->setChoix1($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 9;
						$avis->setChoix1($infos_vote->getChoix9());
					}
				}
			  }
			 if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '1') {
					if (isset($choix1)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix1 = 10;
						$avis->setChoix1($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '2') {
				$choix2 = 1;
				$avis->setChoix2($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '2') {
				if (isset($choix2)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix2 = 2;
					$avis->setChoix2($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 3;
						$avis->setChoix2($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 4;
						$avis->setChoix2($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 5;
						$avis->setChoix2($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 6;
						$avis->setChoix2($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 7;
						$avis->setChoix2($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 8;
						$avis->setChoix2($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 9;
						$avis->setChoix2($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '2') {
					if (isset($choix2)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix2 = 10;
						$avis->setChoix2($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '3') {
				$choix3 = 1;
				$avis->setChoix3($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '3') {
				if (isset($choix3)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix3 = 2;
					$avis->setChoix3($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 3;
						$avis->setChoix3($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 4;
						$avis->setChoix3($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 5;
						$avis->setChoix3($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 6;
						$avis->setChoix3($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 7;
						$avis->setChoix3($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 8;
						$avis->setChoix3($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 9;
						$avis->setChoix3($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '3') {
					if (isset($choix3)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix3 = 10;
						$avis->setChoix3($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '4') {
				$choix4 = 1;
				$avis->setChoix4($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '4') {
				if (isset($choix4)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix4 = 2;
					$avis->setChoix4($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 3;
						$avis->setChoix4($infos_vote->getChoix3());
					}
				}
			  }
			 if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 4;
						$avis->setChoix4($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 5;
						$avis->setChoix4($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 6;
						$avis->setChoix4($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 7;
						$avis->setChoix4($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 8;
						$avis->setChoix4($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 9;
						$avis->setChoix4($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '4') {
					if (isset($choix4)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix4 = 10;
						$avis->setChoix4($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '5') {
				$choix5 = 1;
				$avis->setChoix5($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '5') {
				if (isset($choix5)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix5 = 2;
					$avis->setChoix5($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 3;
						$avis->setChoix5($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 4;
						$avis->setChoix5($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 5;
						$avis->setChoix5($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 6;
						$avis->setChoix5($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 7;
						$avis->setChoix5($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 8;
						$avis->setChoix5($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 9;
						$avis->setChoix5($infos_vote->getChoix9());
					}
				  }
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '5') {
					if (isset($choix5)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix5 = 10;
						$avis->setChoix5($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '6') {
				$choix6 = 1;
				$avis->setChoix6($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '6') {
				if (isset($choix6)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix6 = 2;
					$avis->setChoix6($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 3;
						$avis->setChoix6($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 4;
						$avis->setChoix6($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 5;
						$avis->setChoix6($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 6;
						$avis->setChoix6($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 7;
						$avis->setChoix6($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 8;
						$avis->setChoix6($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 9;
						$avis->setChoix6($infos_vote->getChoix9());
					}
				}
			  }
			   if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '6') {
					if (isset($choix6)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix6 = 10;
						$avis->setChoix6($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '7') {
				$choix7 = 1;
				$avis->setChoix7($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '7') {
				if (isset($choix7)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix7 = 2;
					$avis->setChoix7($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 3;
						$avis->setChoix7($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 4;
						$avis->setChoix7($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 5;
						$avis->setChoix7($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 6;
						$avis->setChoix7($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 7;
						$avis->setChoix7($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 8;
						$avis->setChoix7($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 9;
						$avis->setChoix7($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '7') {
					if (isset($choix7)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix7 = 10;
						$avis->setChoix7($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '8') {
				$choix8 = 1;
				$avis->setChoix8($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '8') {
				if (isset($choix8)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix8 = 2;
					$avis->setChoix8($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 3;
						$avis->setChoix8($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 4;
						$avis->setChoix8($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '8') {	
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 5;
						$avis->setChoix8($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 6;
						$avis->setChoix8($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 7;
						$avis->setChoix8($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 8;
						$avis->setChoix8($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 9;
						$avis->setChoix8($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix9'] == '8') {
					if (isset($choix8)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix8 = 10;
						$avis->setChoix8($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '9') {
				$choix9 = 1;
				$avis->setChoix9($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '9') {
				if (isset($choix9)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix9 = 2;
					$avis->setChoix9($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 3;
						$avis->setChoix9($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 4;
						$avis->setChoix9($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 5;
						$avis->setChoix9($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 6;
						$avis->setChoix9($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 7;
						$avis->setChoix9($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 8;
						$avis->setChoix9($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 9;
						$avis->setChoix9($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '9') {
					if (isset($choix9)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix9 = 10;
						$avis->setChoix2($infos_vote->getChoix10());
					}
				}
			  }
			  
			  if ($request->request->get("form")['choix1'] == '10') {
				$choix10 = 1;
				$avis->setChoix10($infos_vote->getChoix1());
			  }
			  if ($request->request->get("form")['choix2'] == '10') {
				if (isset($choix10)) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
				}
				else {
					$choix10 = 2;
					$avis->setChoix10($infos_vote->getChoix2());
				}
			  }
			  if (isset($request->request->get("form")['choix3'])) {
				if ($request->request->get("form")['choix3'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 3;
						$avis->setChoix10($infos_vote->getChoix3());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix4'])) {
				if ($request->request->get("form")['choix4'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 4;
						$avis->setChoix10($infos_vote->getChoix4());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix5'])) {
				if ($request->request->get("form")['choix5'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 5;
						$avis->setChoix10($infos_vote->getChoix5());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix6'])) {
				if ($request->request->get("form")['choix6'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 6;
						$avis->setChoix10($infos_vote->getChoix6());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix7'])) {
				if ($request->request->get("form")['choix7'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 7;
						$avis->setChoix10($infos_vote->getChoix7());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix8'])) {
				if ($request->request->get("form")['choix8'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 8;
						$avis->setChoix10($infos_vote->getChoix8());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix9'])) {
				if ($request->request->get("form")['choix9'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 9;
						$avis->setChoix10($infos_vote->getChoix9());
					}
				}
			  }
			  if (isset($request->request->get("form")['choix10'])) {
				if ($request->request->get("form")['choix10'] == '10') {
					if (isset($choix10)) {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote', array('vote' => $session->get('fin'))));
					}
					else {
						$choix10 = 10;
						$avis->setChoix10($infos_vote->getChoix10());
					}
				}
			  }
				
			  // On enregistre le donnerAvis
			  $avis->setVote($infos_vote);	
			  $avis->setUtilisateur($utilisateur);
				
			  $em = $this->getDoctrine()->getManager();
			  $em->persist($avis);
			  $em->flush();
			  
			  // On redirige vers la page de commentaires du vote en question
			  return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $session->get('fin'), 'supp' => true)));
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
			  'groupe_associe' => $infos_vote->getGroupeAssocie(),
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
	
	// Même principe que pour l'affichage des votes mais pour commenter
	public function forumAction() {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		if ($u == NULL) {
			$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("type" => "Vote public", "groupeAssocie" => NULL), array('dateDeCreation' => 'desc'));
			
			if ($votes == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
					
			foreach ($votes as $cle => $valeur) {
				$createur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneById($valeur->getCreateur());
					
				$createurs[$cle] = $createur->getLogin();
			}
			
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
				'votes' => $votes,
				'vote_createurs' => $createurs));
		}

		if ($request->getMethod() == 'POST') {
			$en_cours = false;
			$termine = false;
			$public = false;
			$reserve = false;
			$prive = false;
		
			if (($request->request->get('type') == null) && ($request->request->get('etat') == null)) {
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
				foreach ($votes as $cle => $valeur) {
					$createur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneById($valeur->getCreateur());
						
					$createurs[$cle] = $createur->getLogin();
				}
				
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
					'utilisateur' => $u,
					'votes' => $votes,
					'vote_createurs' => $createurs));
			}
			else if (($request->request->get('type') == null) && ($request->request->get('etat') != null)){
				foreach ($request->request->get('etat') as $cle => $valeur) {
					if ($valeur == 'en_cours') {
						$en_cours = true;
					}
					if ($valeur == 'termine') {
						$termine = true;
					}
				}
				
				if ($en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("etat" => true), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("etat" => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
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
				
				if ($public == true && $reserve == true && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
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
					if ($valeur == 'termine') {
						$termine = true;
					}
				}
				
				if ($public == true && $reserve == true && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé')), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == false && $prive == true && $en_cours == true && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé'), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == true && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("etat" => false), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public', 'Vote réservé aux inscrits'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public', 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == false && $prive == true && $en_cours == false && $termine == true) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé', 'etat' => false), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == true && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array("etat" => true), array('dateDeCreation' => 'desc'));
					
					foreach ($votes as $cle => $valeur) {
						$createur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
							->findOneById($valeur->getCreateur());
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
						'utilisateur' => $u,
						'votes' => $votes,
						'vote_createurs' => $createurs));
				}
				else if ($public == true && $reserve == true && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote réservé aux inscrits'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote public','Vote privé'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == true && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => array('Vote réservé aux inscrits','Vote privé'), 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == true && $reserve == false && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote public', 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else if ($public == false && $reserve == true && $prive == false && $en_cours == true && $termine == false) {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote réservé aux inscrits', 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
				else {
					$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array('type' => 'Vote privé', 'etat' => true), array('dateDeCreation' => 'desc'));
					
					if ($votes != NULL) {
						foreach ($votes as $cle => $valeur) {
							$createur = $this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
								->findOneById($valeur->getCreateur());
								
							$createurs[$cle] = $createur->getLogin();
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u,
							'votes' => $votes,
							'vote_createurs' => $createurs));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
							'utilisateur' => $u));
					}
				}
			}
		}
		$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
		foreach ($votes as $cle => $valeur) {
			$createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneById($valeur->getCreateur());
				
			$createurs[$cle] = $createur->getLogin();
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
			'utilisateur' => $u,
			'votes' => $votes,
			'vote_createurs' => $createurs));
	}
	
	// $vote est l'id du vote en question et $supp indique qu'il va falloir remettre à 0 la variable de session 'fin' si $supp == true
	// Même principe que l'affichage du vote
	public function commentaireAction($vote=null, $supp=false) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		$commentaire_id = new Commentaire;
		
		$form = $this->createFormBuilder($commentaire_id)
					->add('texteCommentaire', 'text', array(
														'label' => 'Saisissez votre commentaire'))
					->getForm();
		
		if ($supp == true) {
			$session->set('fin', null);
		}
		
		if ($request->getMethod() != 'POST') {
			$session->set('vote', $vote); 
		}
		
		if ($vote == null && $session->get('vote') == null) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_forum'));
		}
		
		if ($request->getMethod() != 'POST') {
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
			
			if ($u == NULL) {
				if ($infos_vote->getType() == "Vote public") {
					$invite = $session->get('invite');
					
					$listeVote=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
						->findBy(array('vote'=>$infos_vote));
					$tableau=array();
					
					if($listeVote != NULL) {
						for ($i=0; $i <sizeof($listeVote) ; $i++) { 
							$tab = array(
								'login'=>$listeVote[$i]->getUtilisateur()->getLogin(),
								'message'=>$listeVote[$i]->getCommentaire()->getTexteCommentaire(),
								'dateCreation'=>$listeVote[$i]->getDateCreation());
			
							$tableau[]=$tab;
						}
						
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig',array(
										'form' => $form->createView(),
										'tableau' => $tableau,
										'vote' => $vote,
										'nom_vote' => $infos_vote->getNom(),
										'texte_vote' => $infos_vote->getTexte(),
										'groupe_associe' => $infos_vote->getGroupeAssocie()));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig', array(
								'form' => $form->createView(),
								'vote' => $vote,
								'nom_vote' => $infos_vote->getNom(),
								'texte_vote' => $infos_vote->getTexte(),
								'groupe_associe' => $infos_vote->getGroupeAssocie()));
					}
				}
				else {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
				}
			}
			
			//if (// Ici on testera que l'utilisateur fait bien parti du groupe du vote ) {
			
			//}

			$listeVote=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
						->findBy(array('vote'=>$infos_vote));
			$tableau=array();
			
			if($listeVote != NULL) {
				for ($i=0; $i <sizeof($listeVote) ; $i++) { 
					$tab = array(
						'login'=>$listeVote[$i]->getUtilisateur()->getLogin(),
						'message'=>$listeVote[$i]->getCommentaire()->getTexteCommentaire(),
						'dateCreation'=>$listeVote[$i]->getDateCreation());
	
					$tableau[]=$tab;
				}
				
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig',array(
								'form' => $form->createView(),
								'tableau' => $tableau,
								'utilisateur' => $u,
								'vote' => $vote,
								'nom_vote' => $infos_vote->getNom(),
								'texte_vote' => $infos_vote->getTexte(),
								'groupe_associe' => $infos_vote->getGroupeAssocie()));
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:listeCommentaire.html.twig', array(
						'form' => $form->createView(),
						'utilisateur' => $u,
						'vote' => $vote,
						'nom_vote' => $infos_vote->getNom(),
						'texte_vote' => $infos_vote->getTexte(),
						'groupe_associe' => $infos_vote->getGroupeAssocie()));
			}

		}
		else {
			$session->set('vote', null);
			$form->bind($request);
			$commentaireUti = new VoteCommentaireUtilisateur;
			$commentaire_id = new Commentaire;
		    // On recupère le texte du commentaire
			$text=$request->request->get("form")['texteCommentaire'];
			if($text!=NULL) {
				$commentaire_id->setTexteCommentaire($text);
				$commentaireUti->setCommentaire($commentaire_id);
				// On enregistre le commentaire
				$em = $this->getDoctrine()->getManager();
			    $em->persist($commentaire_id);
			    $em->flush();
			}
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
			
			if ($u == NULL) {
				if ($infos_vote->getType() == "Vote public") {
					if($infos_vote!=NULL){
						$commentaireUti->setVote($infos_vote);
					}
					
					$invite = $session->get('invite');
					
					if ($invite == null) {
							  $utilisateur = new Utilisateur;
							  
							  $pass = "abcdefghijklmnopqrstuvwxyz987654321012345643198536985prokfjaidinend";
							  $pass_md5 = md5($pass);
								
							  $utilisateur->setMotDePasse($pass_md5);
							  $date = date_create(date('Y-m-d'));
							  
							  $utilisateur->setDateDeNaissance($date);
							  $utilisateur->setSexe('H');
								
							  $em = $this->getDoctrine()->getEntityManager();
							  $req_max_id = $em->createQuery(
								'SELECT MAX(u.id) AS max_id
								FROM VotenmasseVotenmasseBundle:Utilisateur u');

							  $max_id = $req_max_id->getResult();

							  $base = "Invité_";
							  $num = $max_id[0]["max_id"] + 1;
							  $invite = $base.$num;
							  
							  $utilisateur->setMail($invite."@fake.com");
							  $utilisateur->setPrenom($invite);
							  $utilisateur->setNom($invite);
							  $utilisateur->setLogin($invite);
							  
							  $session->set('invite', $invite);
							  
							  $em->persist($utilisateur);
							  $em->flush();
					}
				
					$utilisateur_id=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneByLogin($invite);
						
					if($utilisateur_id!=NULL) {
						$commentaireUti->setUtilisateur($utilisateur_id);
					}
						
					
					// On enregistre notre objet $commentaireUtilisateur dans la base de données
					$em = $this->getDoctrine()->getManager();
					$em->persist($commentaireUti);
					$em->flush();
					
					$listeVote=$this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
								->findBy(array('vote'=>$infos_vote));
								
					$tableau=array();
					
					if($listeVote !=NULL) {
						for ($i=0; $i <sizeof($listeVote) ; $i++) { 
							$tab=array(
								'login'=>$listeVote[$i]->getUtilisateur()->getLogin(),
								'message'=>$listeVote[$i]->getCommentaire()->getTexteCommentaire());
							$tableau[]=$tab;
						}
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $vote)));
					}
					else {
						return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
									'form' => $form->createView(),
									'vote' => $vote,
									'nom_vote' => $infos_vote->getNom(),
									'texte_vote' => $infos_vote->getTexte(),
									'groupe_associe' => $infos_vote->getGroupeAssocie()));
					}
				}
				else {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
				}
			}
			
			//if (// Ici on testera que l'utilisateur fait bien parti du groupe du vote ) {
			
			//}
				
			if($infos_vote!=NULL){
				$commentaireUti->setVote($infos_vote);
			}
		
			$utilisateur_id=$this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
				
			if($utilisateur_id!=NULL) {
				$commentaireUti->setUtilisateur($utilisateur_id);
			}
				
			
			// On enregistre notre objet $commentaireUtilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($commentaireUti);
			$em->flush();
			
			$listeVote=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
						->findBy(array('vote'=>$infos_vote));
						
			$tableau=array();
			
			if($listeVote !=NULL) {
				for ($i=0; $i <sizeof($listeVote) ; $i++) { 
					$tab=array(
						'login'=>$listeVote[$i]->getUtilisateur()->getLogin(),
						'message'=>$listeVote[$i]->getCommentaire()->getTexteCommentaire());
					$tableau[]=$tab;
				}
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_commentaire', array('vote' => $vote)));
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
		  					'form' => $form->createView(),
							'utilisateur' => $u,
							'vote' => $vote,
							'nom_vote' => $infos_vote->getNom(),
							'texte_vote' => $infos_vote->getTexte(),
							'groupe_associe' => $infos_vote->getGroupeAssocie()));
			}
		}	
	}


public function afficherGroupeAction($groupe=null) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$utilisateur=$this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($u);
		
		//if ($request->getMethod() == 'GET') {
		/*if ($u==null) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
							'utilisateur' => $u,
							));		
		}else{*/
				$groupeU = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
					->findBy(array("utilisateur" => $utilisateur->getId(), "groupe" =>$groupe));

		if ($groupeU==NULL) //l'utilisateur n'est pas membre du groupe//ni moderateur//ni administrateur
			{
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
							'utilisateur' => $u,
							));		
			}else
				{//l'utilisateur est membre du groupe
					//on recupere le groupe associé
					$groupe_associe= $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findById($groupeU->getGroupe());
					//on recupere l'administrateur du groupe
					$adm= $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneByLogin($groupe_associe->getAdministrateur());//get administrateur renvoie le Login et on recupere l'utilisateur correspondant
					if ($groupeU->getUtilisateur()==$adm->getId())//il est l'aministrateur du groupe
					{
						
						
						if ($groupeU->getModerateur()==true)//si il est en plus moderateur du groupe
						{
							//on recupere les id de tous les membres du groupe
							$membres=$this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
								->findByGroupe($groupe);
							//on recupere les votes associés au groupe
							$liste_votes_associe=$this->getDoctrine()
									->getRepository('VotenmasseVotenmasseBundle:Vote')
									->findByGroupeAssocie($groupe_associe);
							//on recupere les entités Utilisateurs correspondant
							$liste_membres=array();
							for ($i=0; $i <sizeof($membres); $i++) 
							{ 
								$liste_membres=$this->getDoctrine()
									->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
									->findById($membres[$i]->getUtilisateur());
							}
							return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
							'utilisateur' => $u,
							'liste_votes_associe' => $liste_votes_associe,
							'liste_membres' => $liste_membres
							));		
						}else
								{//il est juste administrateur
									$membres=$this->getDoctrine()
										->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
										->findByGroupe($groupe);
									//on recupere les votes associés au groupe
									$liste_votes_associe=$this->getDoctrine()
										->getRepository('VotenmasseVotenmasseBundle:Vote')
										->findByGroupeAssocie($groupe_associe);

									//on recupere les entités Utilisateurs correspondant
									$liste_membres=array();
									for ($i=0; $i <sizeof($membres); $i++) 
									{ 
										$liste_membres=$this->getDoctrine()
											->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
											->findById($membres[$i]->getUtilisateur());
										$liste_votes_associe=$this->getDoctrine()
											->getRepository('VotenmasseVotenmasseBundle:Vote')
											->findByGroupeAssocie($groupe_associe);
									}
									return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
										'utilisateur' => $u,
										'liste_votes_associe' => $liste_votes_associe,
										'liste_membres' => $liste_membres
										));		
								}
					}else//il n'est pas administrateur
						{
							if ($groupeU->getModerateur()==true)//si il est plus moderateur du groupe
								{
									$membres=$this->getDoctrine()
										->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
										->findByGroupe($groupe);
									//on recupere les entités Utilisateurs correspondant
									$liste_membres=array();
									for ($i=0; $i <sizeof($membres); $i++) 
									{ 
										$liste_membres=$this->getDoctrine()
											->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
											->findById($membres[$i]->getUtilisateur());
									}
									return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
										'utilisateur' => $u,
										//'liste_votes_associe' => $liste_votes_associe,
										'liste_membres' => $liste_membres
										));		
								}else//il est juste membre du groupe
						 				{
						 					$membres=$this->getDoctrine()
												->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
												->findByGroupe($groupe);
											//on recupere les entités Utilisateurs correspondant
											$liste_membres=array();
											for ($i=0; $i <sizeof($membres); $i++) 
											{ 
												$liste_membres=$this->getDoctrine()
													->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
													->findById($membres[$i]->getUtilisateur());
											}
											return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
												'utilisateur' => $u,
												//'liste_votes_associe' => $liste_votes_associe,
												'liste_membres' => $liste_membres
											));		
						 				}

						}
				}
			//}
		}
				

}