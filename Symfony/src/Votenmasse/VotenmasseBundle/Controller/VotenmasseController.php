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
use Votenmasse\VotenmasseBundle\Entity\UtilisateurVote;
use Votenmasse\VotenmasseBundle\Entity\Inscription;

class VotenmasseController extends Controller {

	public function indexAction() {	
		// On récupère les variables de session
		$request = $this->get('request');
		
		$em = $this->getDoctrine()->getManager();
		
		$inscription = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
		
		if ($inscription == NULL) {
			$inscr = new Inscription;
			
			$inscr->setEtat("Ouvertes");
			
			$em->persist($inscr);
			$em->flush();
		}
		else { 
			if ($inscription->getEtat() == "Ouvertes") {
				// On va valider tous les utilisateurs en attente (si avant nous étions sous modération ou fermées)
				
				$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
				
				foreach ($utilisateurs as $cle => $valeur) {
					if ($valeur->getAccepte() == false) {
						$valeur->setAccepte(true);
						
						$em->persist($valeur);
						$em->flush();
					}
				}
			}
		}
		
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$i = $session->get('invite');
		
		if ($u != NULL) {
		
			$utilisateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
		
			// Je récupère tous les votes
			$votes_admin = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findBy(array('createur' => $utilisateur), array('dateDeCreation' => 'desc'), 10);
				
			$votes_utilisateur_moderation = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
				->findByUtilisateur($utilisateur);
				
			foreach ($votes_utilisateur_moderation as $cle => $valeur) {
				$votes_u_moderateur[] = $valeur->getVote();
			}
			
			$votes_moderation = $votes_admin;
			
			if(isset($votes_u_moderateur)) {
				if (isset($votes_moderation)) {
					foreach ($votes_u_moderateur as $cle => $valeur) {
						$votes_moderation[] = $valeur;
					}
				}
				else {
					foreach ($votes_u_moderateur as $cle => $valeur) {
						$votes_moderation = $valeur;
					}
				}
			}
				
			// On recupère tous les groupes (Modo ou membre)
			$groupes_utilisateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findByUtilisateur($utilisateur);
				
			$groupes_u = NULL;
				
			foreach ($groupes_utilisateur as $cle => $valeur) {
				$groupes_u[] = $valeur->getGroupe();
			}
			
			$votes_groupes_associes = NULL;
		
			if ($groupes_u != NULL) {
				foreach ($groupes_u as $cle => $valeur) {
					// On recupère tous les votes associés aux groupes auxquels appartient l'utilisateur
					$votes_groupes_associes[] = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Vote')
						->findByGroupeAssocie($valeur);
				}
			}
			
			// On recupère tous les groupes Modo
			$groupes_moderateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findBy(array("utilisateur" => $utilisateur, "moderateur" => true));
				
			$groupes_m = NULL;
				
			foreach ($groupes_moderateur as $cle => $valeur) {
				$groupes_m[] = $valeur->getGroupe();
			}
			
			$votes_moderation = $votes_admin;
			
			// Votes associés à un groupe dont l'utilisateur est modérateur
			if($groupes_m != NULL) {
				foreach ($groupes_m as $cle => $valeur) {
					$votes_groupe_moderateur_moderation = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Vote')
						->findByGroupeAssocie($valeur);
				}
					
				foreach ($votes_groupe_moderateur_moderation as $cle => $valeur) {
					$votes_g_moderateur_moderation[] = $valeur;
				}
				
				if(isset($votes_g_moderateur_moderation)) {
					if ($votes_moderation != NULL) {
						$cpt = sizeof($votes_moderation);
			
						foreach ($votes_g_moderateur_moderation as $cle => $valeur) {
							$existe_deja = false;
							
							for ($i = ($cpt-1); $i >= 0; $i--) {
								if ($votes_moderation[$i] == $valeur) {
									$existe_deja = true;
								}
							}
							
							if ($existe_deja == false) {
								$votes_moderation[$cpt] = $valeur;
								$cpt++;
							}
						}
					}
					else {
						foreach ($votes_g_moderateur_moderation as $cle => $valeur) {
							$votes_moderation = $valeur;
						}
					}
				}
			}
			
			$groupes = NULL;
			
			$groupes_administrateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findByAdministrateur($utilisateur);
				
			if (isset($groupes_administrateur )) {
				if ($groupes_administrateur != NULL) {
					$groupes[] = $groupes_administrateur;
				}
			}
			
			// Vote associé à un groupe dont l'utilisateur est administrateur
			if($groupes != NULL) {
				foreach ($groupes as $cle => $valeur) {
					$votes_groupe_administrateur_moderation = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Vote')
						->findByGroupeAssocie($valeur);
				}
					
				foreach ($votes_groupe_administrateur_moderation as $cle => $valeur) {
					$votes_g_administrateur_moderation[] = $valeur;
				}
				
				if(isset($votes_g_administrateur_moderation)) {
					if ($votes_moderation != NULL) {
						$cpt = sizeof($votes_moderation);
			
						foreach ($votes_g_administrateur_moderation as $cle => $valeur) {
							$existe_deja = false;
							
							for ($i = ($cpt-1); $i >= 0; $i--) {
								if ($votes_moderation[$i] == $valeur) {
									$existe_deja = true;
								}
							}
							
							if ($existe_deja == false) {
								$votes_moderation[$cpt] = $valeur;
								$cpt++;
							}
						}
					}
					else {
						foreach ($votes_g_administrateur_moderation as $cle => $valeur) {
							$votes_moderation = $valeur;
						}
					}
				}
			}
			
			$votes_moderation_createurs = null;
		
			if ($votes_moderation != NULL) {
				foreach ($votes_moderation as $cle => $valeur) {
					$vote_moderation_createurs = $valeur->getCreateur();

					$votes_moderation_createurs[$cle] = $vote_moderation_createurs->getLogin();
				}		
			}
			
			$votes = $votes_moderation;
			
			$donner_avis = NULL;
			
			$votes_donner_avis = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findByUtilisateur($utilisateur);
				
			foreach ($votes_donner_avis as $cle => $valeur) {
				// On recupère tous les votes pour lesquels il a donné son avis
				$donner_avis[] = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findByGroupeAssocie($valeur->getVote());
			}
			
			if ($donner_avis != NULL) {
				$cpt = sizeof($votes);
				foreach ($donner_avis as $cle => $valeur) {
					foreach ($valeur as $key => $value) {
						$existe_deja = false;
						for ($i = ($cpt-1); $i >= 0; $i--) {
							if ($votes[$i] == $value) {
								$existe_deja = true;
							}
						}
						
						if ($existe_deja == false) {
							$votes[$cpt] = $value;
							$cpt++;
						}
					}
				}
			}
			
			if ($votes_groupes_associes != NULL) {
				$cpt = sizeof($votes);
				foreach ($votes_groupes_associes as $cle => $valeur) {
					foreach ($valeur as $key => $value) {
						$existe_deja = false;
						for ($i = ($cpt-1); $i >= 0; $i--) {
							if ($votes[$i] == $value) {
								$existe_deja = true;
							}
						}
						
						if ($existe_deja == false) {
							$votes[$cpt] = $value;
							$cpt++;
						}
					}
				}
			}
			
			$groupes_moderation = $groupes_administrateur;
			
			$groupes_utilisateur_moderation = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findBy(array('utilisateur' => $utilisateur, 'moderateur' => true));
				
			foreach ($groupes_utilisateur_moderation as $cle => $valeur) {
				$groupes_u_moderateur[] = $valeur->getGroupe();
			}
			
			if(isset($groupes_u_moderateur)) {
				if (isset($groupes_moderation)) {
					foreach ($groupes_u_moderateur as $cle => $valeur) {
						$groupes_moderation[] = $valeur;
					}
				}
				else {
					foreach ($groupes_u_moderateur as $cle => $valeur) {
						$groupes_moderation = $valeur;
					}
				}
			}
			
			if(isset($groupes_u)) {
				if (isset($groupes)) {
					$groupes[] = $groupes_u;
				}
				else {
					$groupes = $groupes_u;
				}
			}
			
			if (!isset($groupes)) {
				$groupes = NULL;
			}
			
			if (is_array($groupes[0]) && $groupes[0] != NULL) {
				foreach ($groupes as $cle => $valeur) {
					foreach ($valeur as $key => $value) {
						$groupes_final[] = $value;
					}
				}
			}
			else if (isset($groupes) && $groupes[0] != NULL) {
				$groupes_final = $groupes;
			}
			else {
				$groupes_final = NULL;
			}

			if ($groupes_final != NULL) {
				foreach ($groupes_final as $cle => $valeur) {
					if ($valeur->getAdministrateur() != $utilisateur) {
						$accepte = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
						->findOneBy(array('groupe' => $valeur->getId(), 'utilisateur' => $utilisateur));

						$acceptes[$cle] = $accepte->getAccepte();
					}
					else {
						$acceptes[$cle] = true;
					}
				}		
			}
			
			$cpt = 0;
			
			if ($groupes_final != NULL) {
				if (sizeof($groupes_final) > 10) {
					while ($cpt < (sizeof($groupes_final)-10)) {
						$groupes_final[$cpt] = null;
						$cpt++;
					}
				}
			}
			
			$createurs = null;
		
			if ($votes != NULL) {
				foreach ($votes as $cle => $valeur) {
					$createur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneById($valeur->getCreateur());

					$createurs[$cle] = $createur->getLogin();
				}		
			}
			
			$last_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findOneBy(array('groupeAssocie' => NULL), array('id' => 'desc'));
				
			$last_groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findBy(array(), array('id' => 'desc'), 3);
				
			$req_avis_existe_deja_pour_last_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findOneBy(array('utilisateur' => $utilisateur, 'vote' => $last_vote));
				
			if ($req_avis_existe_deja_pour_last_vote != NULL) {
				$avis_existe_deja_pour_last_vote = true;
			}
			else {
				$avis_existe_deja_pour_last_vote = false;
			}
			
			if ($last_groupes == NULL) {
				$last_groupes = NULL;
			}
			
			if ($groupes_moderation == NULL) {
				$groupes_moderation = NULL;
			}
			
			if ($votes_moderation == NULL) {
				$votes_moderation = NULL;
			}
		
			if ($votes != NULL && $groupes_final != NULL) {
				if($groupes_moderation == NULL && $votes_moderation == NULL) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'acceptes' => $acceptes));
				}
				else if($groupes_moderation != NULL && $votes_moderation == NULL) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'acceptes' => $acceptes,
						'groupes_moderation' => $groupes_moderation));
				}
				else if($groupes_moderation == NULL && $votes_moderation != NULL) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'acceptes' => $acceptes,
						'votes_moderation' => $votes_moderation,
						'votes_moderation_createurs' => $votes_moderation_createurs));
				}
				else {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'acceptes' => $acceptes,
						'votes_moderation' => $votes_moderation,
						'groupes_moderation' => $groupes_moderation,
						'votes_moderation_createurs' => $votes_moderation_createurs));
				}
			}
			else if ($votes != NULL && $groupes_final == NULL) {
				if($votes_moderation != NULL) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'votes_moderation' => $votes_moderation,
						'votes_moderation_createurs' => $votes_moderation_createurs));
				}
				else {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'votes' => $votes,
						'vote_createurs' => $createurs,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes));
				}
			}
			else if ($votes == NULL && $groupes_final != NULL) {
				if($groupes_moderation != NULL) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'groupes_moderation' => $groupes_moderation,
						'acceptes' => $acceptes,));
				}
				else {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'groupes' => $groupes_final,
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes,
						'acceptes' => $acceptes));
				}
			}
			else {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
						'utilisateur' => $session->get('utilisateur'),
						'last_vote' => $last_vote,
						'deja_vote' => $avis_existe_deja_pour_last_vote,
						'last_groupes' => $last_groupes));
			}
		}
		
		$last_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findOneBy(array('type' => 'Vote public', 'groupeAssocie' => NULL), array('id' => 'desc'));
		
		$invite = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($i);
		
		$avis_existe_deja_pour_last_vote = false;
				
		if ($invite != NULL) {
			$req_avis_existe_deja_pour_last_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findOneBy(array('utilisateur' => $invite, 'vote' => $last_vote));
			
				
			if ($req_avis_existe_deja_pour_last_vote != NULL) {
				$avis_existe_deja_pour_last_vote = true;
			}
			else {
				$avis_existe_deja_pour_last_vote = false;
			}
		}
		
		$inscription_valide = $session->get('inscription_valide');
		
		// Si l'inscription est valide alors l'utilisateur vient de s'inscrire
		if(!is_null($inscription_valide)) {
			$ins=$this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
			if ($ins->getEtat()=='Fermées') {
				$session->remove('inscription_valide');
				$message_inscription_valide = "Désolé, les inscriptions sont momentanément fermées";
			}
			if ($ins->getEtat()=='Sous modération') {
				$session->remove('inscription_valide');
				$message_inscription_valide = "Les inscriptions sont actuellement sous modération. Un administrateur validera ou non votre demande sous peu.";
			}
			
			if ($ins->getEtat()=='Ouvertes') {
				$session->remove('inscription_valide');
				$message_inscription_valide = "Félicitation vous avez rejoins la communauté Votenmasse";
			}
			
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
															  'last_vote' => $last_vote,
															  'deja_vote' => $avis_existe_deja_pour_last_vote,
														      'invite' => $invite,
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
														  'last_vote' => $last_vote,
														  'deja_vote' => $avis_existe_deja_pour_last_vote,
														  'invite' => $invite,
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
		  	$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
		  	if ($ins->getEtat() == 'Fermées') {
		  		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
														  'form' => $form->createView(),
														  'utilisateur' => $u,
														  'last_vote' => $last_vote,
														  'deja_vote' => $avis_existe_deja_pour_last_vote,
														  'invite' => $invite,
														  'erreur' => "Désolé, les inscriptions sont momentanément fermées."
															));		
		  	}
		  	
			$pass = $request->request->get("form")['motDePasse'];
			$pass_md5 = md5($pass);
		
			$utilisateur->setMotDePasse($pass_md5);
			if ($ins->getEtat() == 'Sous modération') {
				$utilisateur->setAccepte(false);
			}
			else if ($ins->getEtat()=='Ouvertes') {
				$utilisateur->setAccepte(true);
			}
			
			// On enregistre notre objet $utilisateur dans la base de données
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
		  'last_vote' => $last_vote,
		  'deja_vote' => $avis_existe_deja_pour_last_vote,
		  'invite' => $invite,
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
				->findByAdministrateur($infos_utilisateur);
				
			$taille_groupes_utilisateur_ou_ajouter = sizeof($groupes_utilisateur_courant);	
				
			foreach ($groupes_utilisateur_courant_a_ajouter as $cle => $valeur) {
				$groupes_utilisateur_courant[$taille_groupes_utilisateur_ou_ajouter] = $valeur;
				$taille_groupes_utilisateur_ou_ajouter++;
			}
		}
		else {
			$groupes_utilisateur_courant = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findByAdministrateur($infos_utilisateur);
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
													'label' => 'Groupe associé',
													'mapped' => false))
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

		// On vérifie que l'on soit en POST
		if ($request->getMethod() == 'POST') {
		  // On fait le lien Requête <-> Formulaire
		  $form->bind($request);
		  
		  $vote_existe_deja = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneByNom($request->request->get("form")['nom']);
			
		  if($vote_existe_deja != NULL) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
				  'form' => $form->createView(),
				  'utilisateur' => $u,
				  'erreur' => "Un vote du même nom existe déjà, veuillez en choisir un autre"));
		  }
		  
		  // Si l'utilisateur a demandé à associer un vote à un groupe
		  if($request->request->get("form")['groupeAssocie'] != NULL) {
			$groupeAssocie = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneByNom($request->request->get("form")['groupeAssocie']); 
				
			$date_du_jour = date_create(date('Y-m-d'));
			$date_fin = date_create($request->request->get("form")['dateDeFin']['month'].'/'.$request->request->get("form")['dateDeFin']['day'].'/'.$request->request->get("form")['dateDeFin']['year']);
			if (strtotime($date_fin->format("d-m-y")) < strtotime($date_du_jour->format("d-m-y"))) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => "La date de fin doit être supérieure à la date du jour",
					'utilisateur' => $u));
			}	;
			
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
				else {
					$vote->setGroupeAssocie($groupeAssocie);
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
			$date_du_jour = date_create(date('Y-m-d'));
			$date_fin = date_create($request->request->get("form")['dateDeFin']['month'].'/'.$request->request->get("form")['dateDeFin']['day'].'/'.$request->request->get("form")['dateDeFin']['year']);
			if (strtotime($date_fin->format("d-m-y")) < strtotime($date_du_jour->format("d-m-y"))) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur_date' => "La date de fin doit être supérieure à la date du jour",
					'utilisateur' => $u));
			}
		  
			if($request->request->get("form")['type'] == 'Vote privé') {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
					'form' => $form->createView(),
					'message_erreur' => 'Un vote privé doit obligatoirement être associé à un groupe privé',
					'utilisateur' => $u));
			}
		  }
		  
		  // On vérifie qu'il n'y a pas deux choix identiques
		  $choix10 = $request->request->get("form")['choix10'];
		  $choix9 = $request->request->get("form")['choix9'];
		  $choix8 = $request->request->get("form")['choix8'];
		  $choix7 = $request->request->get("form")['choix7'];
		  $choix6 = $request->request->get("form")['choix6'];
		  $choix5 = $request->request->get("form")['choix5'];
		  $choix4 = $request->request->get("form")['choix4'];
		  $choix3 = $request->request->get("form")['choix3'];
		  
		  if($choix10 != NULL) {
			if ($request->request->get("form")['choix10'] == $request->request->get("form")['choix9'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix8'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix10'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix8'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix9) {
			if ($request->request->get("form")['choix9'] == $request->request->get("form")['choix8'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix9'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix8) {
			if ($request->request->get("form")['choix8'] == $request->request->get("form")['choix7'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix8'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix7) {
			if ($request->request->get("form")['choix7'] == $request->request->get("form")['choix6'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix7'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix6) {
			if ($request->request->get("form")['choix6'] == $request->request->get("form")['choix5'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix6'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix5) {
			if ($request->request->get("form")['choix5'] == $request->request->get("form")['choix4'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix5'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix4) {
			if ($request->request->get("form")['choix4'] == $request->request->get("form")['choix3'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix4'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else if ($choix3) {
			if ($request->request->get("form")['choix3'] == $request->request->get("form")['choix2'] ||
				$request->request->get("form")['choix3'] == $request->request->get("form")['choix1'] ||
				$request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  else {
			if ($request->request->get("form")['choix2'] == $request->request->get("form")['choix1']) {
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:creation_vote.html.twig', array(
						'form' => $form->createView(),
						'message_erreur' => 'Tous les choix doivent être différents',
						'utilisateur' => $u));
				}
		  }
		  
		  $createur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
		  
		  $vote->setCreateur($createur);

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
		
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($u);
		
		$groupe = new Groupe;
		
		// On récupère tous les utilisateurs de la base pour que le créateur du groupe puisse ajouter des membres au groupe
		$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
		
		if ($utilisateurs != NULL) {
			for ($i = 0; $i<sizeof($utilisateurs); $i++) {
				// On ajoute tous les utilisateurs sauf l'utilisateur courant
				if ($utilisateurs[$i]->getLogin() != $u && (preg_match("/Invité/", $utilisateurs[$i]->getLogin()) == false)) {
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
			  
					$groupe->setAdministrateur($utilisateur);

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
				
				if ($request->getMethod() == 'POST') {echo "aaa";
				  // On fait le lien Requête <-> Formulaire
				  $form->bind($request);
				  
				  $groupe->setAdministrateur($utilisateur);


					// On enregistre notre objet $groupe dans la base de données
					$em = $this->getDoctrine()->getManager();
					$em->persist($groupe);
					$em->flush();

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
			  
			  $groupe->setAdministrateur($utilisateur);

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
		$session = $request->getSession();		
		$i = $session->get('invite');

		// On vérifie qu'elle est de type POST
		if ($request->getMethod() == 'POST') {
			// On crypte le mot de passe entré
			$pass = md5($request->request->get('mot_de_passe'));
		
			// On regarde si l'association login-motDePasse existe dans la base 
			$utilisateur = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findOneBy(array('login' => $request->request->get('login'),
										'motDePasse' => $pass));
			
			$last_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findOneBy(array('type' => 'Vote public', 'groupeAssocie' => NULL), array('id' => 'desc'));
		
			$invite = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($i);
			
			$avis_existe_deja_pour_last_vote = false;
					
			if ($invite != NULL) {
				$req_avis_existe_deja_pour_last_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findOneBy(array('utilisateur' => $invite, 'vote' => $last_vote));
				
					
				if ($req_avis_existe_deja_pour_last_vote != NULL) {
					$avis_existe_deja_pour_last_vote = true;
				}
				else {
					$avis_existe_deja_pour_last_vote = false;
				}
			}
		
			// S'il existe on créé la variable de session de connexion
			if ($utilisateur != NULL) {	
				if ($utilisateur->getAccepte() == false) {
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
				
					return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
												  'form' => $form->createView(),
												  'last_vote' => $last_vote,
												  'deja_vote' => $avis_existe_deja_pour_last_vote,
												  'invite' => $invite,
												  'erreur' => "Désolé, votre inscription n'a pas été validée par un administrateur."
													));	
				}
			
				if ($request->getSession() != NULL) {
					$session = $request->getSession();
					if ($session->get('invite') != NULL) {
						$session->invalidate();
					}
					
					$session->set('utilisateur', $request->request->get('login')); 
				}
				else {
					$session = new Session();
					$session->start();
				
					$session->set('utilisateur', $request->request->get('login')); 
				}
						
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			// Sinon on redirige l'utilisateur vers la page de connexion
			else {
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
			
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig', array(
										  'form' => $form->createView(),
										  'last_vote' => $last_vote,
										  'deja_vote' => $avis_existe_deja_pour_last_vote,
										  'invite' => $invite,
										  'erreur' => "Pseudo ou mot de passe incorrect."
											));	
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
					
				$commentaires = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findAll();
					
				if ($commentaires == NULL) {
					$commentaires = NULL;
				}
					
				$createurs = NULL;	
				
				if ($votes != NULL) {
					foreach ($votes as $cle => $valeur) {
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
				}
				
				if ($votes == NULL) {
					$votes = NULL;
				}
				
				if ($groupes == NULL) {
					$groupes = NULL;
				}
				
				if ($commentaires == NULL) {
					$commentaires = NULL;
				}
			
				$inscription = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Inscription')
						->findOneById(1);
				
				$utilisateurs_en_moderation = NULL;
				
				if ($inscription == NULL) {
					$inscr = new Inscription;
			
					$inscr->setEtat("Ouvertes");
					
					$em->persist($inscr);
					$em->flush();
				}
				
				if ($inscription->getEtat() == "Sous modération") {
					$utilisateurs_en_moderation = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
						->findByAccepte(false);
				}
			
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'utilisateurs_en_moderation' => $utilisateurs_en_moderation,
					'inscrip' => $inscription->getEtat()));
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
					->findBy(array("type" => "Vote public"), array('dateDeCreation' => 'desc'));
			
			if ($votes == NULL) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:votes.html.twig', array(
				'message' => "Aucun vote"));
			}
					
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
					
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
			$createurs = NULL;
			
			// S'il n'a pas filtré
			if (($request->request->get('type') == null) && ($request->request->get('etat') == null)) {
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));

				foreach ($votes as $cle => $valeur) {
					$createur = $valeur->getCreateur();
						
					$createurs[$cle] = $createur->getLogin();
				}
				
				if ($votes == NULL) {
					$votes = NULL;
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
			$createur = $valeur->getCreateur();
				
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
					->findBy(array("type" => "Vote public", "etat" => false), array('dateDeCreation' => 'desc'));
					
			if ($votes == NULL) {
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:resultats.html.twig', array(
					'message' => "Aucun vote"));
			}
			
			foreach ($votes as $cle => $valeur) {
			$createur = $valeur->getCreateur();
				
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
					->findBy(array("createur" => $infos_utilisateur, "etat" => false), array('dateDeCreation' => 'desc'));
		
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
					$existe_deja = false;
					for ($i = ($cpt-1); $i >= 0; $i--) {
						if ($votes[$i] == $value) {
							$existe_deja = true;
						}
					}
					
					if ($existe_deja == false) {
						$votes[$cpt] = $value;
						$cpt++;
					}
				}
			}
		}
								
		foreach ($votes as $cle => $valeur) {
			$createur = $valeur->getCreateur();
				
			$createurs[$cle] = $createur->getLogin();
		}
		
		if (empty($votes)) {
			$votes = NULL;
		}
		
		if (isset($createurs)) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:resultats.html.twig', array(
				'utilisateur' => $u,
				'votes' => $votes,
				'vote_createurs' => $createurs));
		}
		else {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:resultats.html.twig', array(
				'utilisateur' => $u,
				'votes' => $votes));
		}
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
			
			// Si l'utilisateur a triché en voulant accéder à un vote qui n'existe pas on le redirige vers la page de votes
			if ($infos_vote == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
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
				$invite = $session->get('invite');
				$infos_invite = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($invite);
				
				if ($infos_vote->getGroupeAssocie() != NULL) {
					// On va vérifier que l'invité fait bien parti du groupe
					$invite_groupe = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
						->findOneBy(array('groupe' => $infos_vote->getGroupeAssocie(), 'utilisateur' => $infos_invite, 'accepte' => true));
					
					if($invite_groupe != NULL) {
						if ($infos_vote->getType() == "Vote public") {
							if ($infos_vote->getEtat() == true) {					
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
									  
									  $em = $this->getDoctrine()->getManager();
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
									  $utilisateur->setAccepte(true);
									  
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
								$em = $this->getDoctrine()->getManager();
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
							return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
						}
					}
					else {
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $infos_vote->getGroupeAssocie()->getId())));
					}
				}
				else {
					if ($infos_vote->getType() == "Vote public") {
						if ($infos_vote->getEtat() == true) {					
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
								  
								  $em = $this->getDoctrine()->getManager();
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
								  $utilisateur->setAccepte(true);
								  
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
							$em = $this->getDoctrine()->getManager();
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
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_votes'));
					}
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
						->findOneByAdministrateur($utilisateur);
						
					if ($groupes_utilisateur_courant_admin != NULL) {
						$admis = true;
					}
				}
				
				if ($admis == false) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $infos_vote->getGroupeAssocie()->getId())));
				}
			}
			
			if ($infos_vote->getEtat() == false) {
				// Afficher le résultat
						
				// Première position
				$em = $this->getDoctrine()->getManager();
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
				return $this->render('VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig', array(
					'message' => "Aucun vote à commenter"));
			}
					
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
					
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
			$createurs = false;
		
			if (($request->request->get('type') == null) && ($request->request->get('etat') == null)) {
				$votes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Vote')
					->findBy(array(), array('dateDeCreation' => 'desc'));
					
				foreach ($votes as $cle => $valeur) {
					$createur = $valeur->getCreateur();
						
					$createurs[$cle] = $createur->getLogin();
				}
				
				if ($votes == NULL) {
					$votes = NULL;
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
						$createur = $valeur->getCreateur();
							
						$createurs[$cle] = $createur->getLogin();
					}
					
					if ($votes == NULL) {
						$votes = NULL;
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
							$createur = $valeur->getCreateur();
								
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
			$createur = $valeur->getCreateur();
				
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
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
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
				if (isset($infos_vote)) {
					if ($infos_vote->getType() == "Vote public") {
						$invite = $session->get('invite');
						
						$listeVote=$this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
							->findBy(array('vote'=>$infos_vote), array('identifier' => 'desc'));
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
						return $this->redirect($this->generateUrl('votenmasse_votenmasse_forum'));
					}
				}
				else {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_forum'));
				}
			}

			$listeVote=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
						->findBy(array('vote'=>$infos_vote), array('identifier' => 'desc'));
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
								
							  $em = $this->getDoctrine()->getManager();
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
							  $utilisateur->setAccepte(true);
							  
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
					
					$req_max_id = $em->createQuery(
						'SELECT MAX(vcu.identifier) AS max_identifier
						FROM VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur vcu');

				    $last_commentaire = (int)$req_max_id->getResult()[0]['max_identifier'];
					
					$commentaireUti->setIdentifier($last_commentaire + 1);
						
					
					// On enregistre notre objet $commentaireUtilisateur dans la base de données
					$em = $this->getDoctrine()->getManager();
					$em->persist($commentaireUti);
					$em->flush();
					
					$listeVote=$this->getDoctrine()
								->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
								->findBy(array('vote'=>$infos_vote), array('identifier' => 'desc'));
								
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
				
			if($infos_vote!=NULL){
				$commentaireUti->setVote($infos_vote);
			}
		
			$utilisateur_id=$this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
				
			if($utilisateur_id!=NULL) {
				$commentaireUti->setUtilisateur($utilisateur_id);
			}
			
			$req_max_id = $em->createQuery(
				'SELECT MAX(vcu.identifier) AS max_identifier
				FROM VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur vcu');

			$last_commentaire = (int)$req_max_id->getResult()[0]['max_identifier'];
			
			$commentaireUti->setIdentifier($last_commentaire + 1);
				
			
			// On enregistre notre objet $commentaireUtilisateur dans la base de données
			$em = $this->getDoctrine()->getManager();
			$em->persist($commentaireUti);
			$em->flush();
			
			$listeVote=$this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
						->findBy(array('vote'=>$infos_vote), array('identifier' => 'desc'));
						
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
	
	public function groupesAction() {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$i = $session->get('invite');
		$groupes = NULL;
	
		if ($u != NULL) {
			if ($request->getMethod() == 'POST') {
				// On définit par défaut que l'utilisateur n'a pas demandé de filtre
				$public = false;
				$reserve = false;
				$prive = false;
			
				// S'il n'a pas filtré
				if ($request->request->get('type') == null) {
					$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:groupe')
						->findAll();
				}
				// Sinon s'il a demandé un filtre sur le type on va tester tous les cas possible pour type
				else if ($request->request->get('type') != null) {
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
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findAll();
					}
					else if ($public == true && $reserve == true && $prive == false) {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => array('Groupe public','Groupe réservé aux inscrits')));
					}
					else if ($public == true && $reserve == false && $prive == true) {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => array('Groupe public','Groupe privé')));
					}
					else if ($public == false && $reserve == true && $prive == true) {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => array('Groupe réservé aux inscrits','Groupe privé')));
					}
					else if ($public == true && $reserve == false && $prive == false) {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => 'Groupe public'));
					}
					else if ($public == false && $reserve == true && $prive == false) {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => 'Groupe réservé aux inscrits'));
					}
					else {
						$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findBy(array('etat' => 'Groupe privé'));
					}
				}
			}
		}
		
		$invite = NULL;
		
		if ($i != NULL) {
			$invite = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($i);
		
			if ($request->getMethod() == 'POST') {
				if ($request->request->get("filtre") != NULL) {
					$mes_groupes = false;
					$autres_groupes = false;
					
					foreach ($request->request->get('filtre') as $cle => $valeur) {
						if ($valeur == 'mes_groupes') {
							$mes_groupes = true;
						}
						if ($valeur == 'autres_groupes') {
							$autres_groupes = true;
						}
					}
					
					$groupes = NULL;
					
					if ($mes_groupes == true && $autres_groupes == true) {
						$groupes = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Groupe')
							->findByEtat("Groupe public");
					}
					else if ($mes_groupes == true && $autres_groupes == false) {
						$groupes_utilisateur = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
							->findByUtilisateur($invite);
						
						if ($groupes_utilisateur != NULL) {
							foreach ($groupes_utilisateur as $cle => $valeur) {
								$groupes[] = $valeur->getGroupe();
							}
						}
					}
					else {
						$em = $this->getDoctrine()->getManager();
						$query = $em->createQuery("SELECT g1
							 FROM VotenmasseVotenmasseBundle:Groupe g1
							 WHERE g1.etat = 'Groupe public'
							 AND g1 NOT IN (
								 SELECT g2
									 FROM VotenmasseVotenmasseBundle:GroupeUtilisateur gu
									 JOIN gu.groupe g2
									 WHERE gu.utilisateur = :invite 
									)")
						->setParameter('invite', $invite);
						
						$groupes = $query->getResult();
					}
				}
				else {
					$groupes = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Groupe')
						->findByEtat("Groupe public");
				}
			}
		}
		else if ($i == NULL && $u == NULL) {
			$groupes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Groupe')
					->findByEtat('Groupe public');
		}
		
		$message = NULL;
		
		if ($groupes == NULL) {
			$groupes = NULL;
			$message = "Aucun groupe";
		}
		
		if ($u != NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:groupes.html.twig', array(
				'utilisateur' => $u,
				'groupes' => $groupes,
				'message' => $message));
		}
		else {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:groupes.html.twig', array(
				'groupes' => $groupes,
				'message' => $message,
				'invite' => $invite));
		}	
	}


	public function afficherGroupeAction($groupe_id = null) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$i = $session->get('invite');
		
		if ($u != NULL) {
			$utilisateur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($u);
		}
		
		$groupes_utilisateurs = NULL;
		
		$groupe_infos = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		$req_groupes_utilisateurs = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findBy(array('groupe' => $groupe_infos, 'accepte' => true));
			
		$utilisateurs_en_attente = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findBy(array('groupe' => $groupe_infos, 'accepte' => false));
			
		if ($utilisateurs_en_attente == NULL) {
			$utilisateurs_en_attente = NULL;
		}
		
		foreach ($req_groupes_utilisateurs as $cle => $valeur) {
			if (preg_match("/Invité/", $valeur->getUtilisateur()->getLogin()) == false) {
				$groupes_utilisateurs[] = $valeur;
			}
		}
		
		$votes = NULL;
		
		if (isset($groupe_infos)) {
			if ($groupe_infos->getEtat() != "Groupe public" && $u == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
			
			$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findByGroupeAssocie($groupe_infos);
				
			$administrateur_groupe_infos = $groupe_infos->getAdministrateur();
		}
			
		if ($votes != NULL) {
			$votes_associes = $votes;
		}
		else {
			$votes_associes = NULL;
		}
			
		$createurs = NULL;
			
		if ($votes_associes != NULL) {
			foreach ($votes_associes as $cle => $valeur) {
				$createur = $valeur->getCreateur();

				$createurs[$cle] = $createur->getLogin();
			}		
		}
		
		$valide = false;
		$administrateur = false;
		$moderateur = false;
		$membre = false;
		$en_attente = false;
		
		if ($u != NULL) {
			if (isset($administrateur_groupe_infos)) {
				if ($administrateur_groupe_infos == $utilisateur) {
					$valide = true;
					$administrateur = true;
				}
			}
			
			if ($valide == false) {
				if (isset($groupes_utilisateurs)) {
					foreach ($groupes_utilisateurs as $cle => $valeur) {
						if ($valeur->getUtilisateur()->getLogin() == $u && $valeur->getModerateur() == true) {
							$moderateur = true;
							$valide = true;
						}
						else if ($valeur->getUtilisateur()->getLogin() == $u && $valeur->getModerateur() == false) {
							$membre = true;
							$valide = true;
						}
					}
				}
			}
			
			if ($valide == false) {
				if (isset($utilisateurs_en_attente)) {
					foreach ($utilisateurs_en_attente as $cle => $valeur) {
						if ($valeur->getUtilisateur()->getLogin() == $u) {
							$en_attente = true;
							$valide = true;
						}
					}
				}
			}
		}
		if (isset($i)) {
			if ($valide == false) {
				$invite = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($i);
			
				$req_groupes_utilisateurs_invite = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
					->findOneBy(array('groupe' => $groupe_infos, 'utilisateur' => $invite));
				
				if ($req_groupes_utilisateurs_invite != NULL) {
					$membre = true;
					$valide = true;
				}
			}
		}
		
		$utilisateurs = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findAll();
		
		$utilisateurs_final = NULL;
		
		if ($utilisateurs != NULL) {
			$cpt = sizeof($groupes_utilisateurs);
		
			foreach ($utilisateurs as $cle => $valeur) {
				$est_membre_du_groupe = false;
				
				for ($i = ($cpt-1); $i >= 0; $i--) {
					if ($groupes_utilisateurs[$i]->getUtilisateur() == $valeur) {
						$est_membre_du_groupe = true;
					}
				}
				
				if ($est_membre_du_groupe == false) {
					$utilisateurs_final[] = $valeur;
				}
			}
		}
		
		$utilisateurs_final_final_version = NULL;
		
		if ($utilisateurs_final != NULL) {
			foreach ($utilisateurs_final as $cle => $valeur) {
				if ($valeur != $administrateur_groupe_infos) {
					$utilisateurs_final_final_version[] = $valeur;
				}
			}
		}
			
		if ($utilisateurs_final_final_version == NULL) {
			$utilisateurs_final_final_version = NULL;
		}
		
		if ($groupe_infos == NULL) {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
					'utilisateur' => $u,
					'message' => $groupe_id, 
					'groupe_id' => $groupe_id));		
		} 
		else {
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:afficheGroupe.html.twig', array(
						'utilisateur' => $u,
						'membres' => $groupes_utilisateurs,
						'groupe' => $groupe_infos, 
						'groupe_id' => $groupe_id,
						'votes_associes' => $votes_associes,
						'administrateur_groupe_infos' => $administrateur_groupe_infos,
						'vote_createurs' => $createurs,
						'valide' => $valide,
						'administrateur' => $administrateur,
						'moderateur' => $moderateur,
						'membre' => $membre,
						'en_attente' => $en_attente,
						'demandes' => $utilisateurs_en_attente,
						'utilisateurs' => $utilisateurs_final_final_version));	
		}
	}
	
	public function quitterGroupeAction($groupe_id = null) {
		$request = $this->get('request');
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$i = $session->get('invite');
		
		if ($u != NULL) {
			$utilisateur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($u);
			
			$groupe_infos = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneById($groupe_id);	
				
			if (isset($groupe_infos)) {				
				if ($groupe_infos->getAdministrateur() == $utilisateur) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
				}
				
				$groupe_current_utilisateur_current = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
					->findOneBy(array('groupe' => $groupe_infos, 'utilisateur' => $utilisateur));
					
				if ($groupe_current_utilisateur_current == NULL) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
				}
				
				 $em = $this->getDoctrine()->getManager();
				 $em->remove($groupe_current_utilisateur_current);
				 $em->flush();
				
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			else {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
		}
		
		// Ici on traite les invités
		$invite = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($i);
		
		$groupe_infos = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);	
			
		if (isset($groupe_infos)) {
			if ($groupe_infos->getEtat() != "Groupe public") {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
			
			$groupe_current_utilisateur_current = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findOneBy(array('groupe' => $groupe_infos, 'utilisateur' => $invite));
				
			if ($groupe_current_utilisateur_current == NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
			
			 $em = $this->getDoctrine()->getManager();
			 $em->remove($groupe_current_utilisateur_current);
			 $em->flush();
			
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		else {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
		}
	}
	
	public function rejoindreGroupeAction($groupe_id = null) {
		$request = $this->get('request');
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$i = $session->get('invite');
		
		if ($u != NULL) {
			$utilisateur = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneByLogin($u);
			
			$groupe_infos = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findOneById($groupe_id);	
				
			if (isset($groupe_infos)) {				
				if ($groupe_infos->getAdministrateur() == $utilisateur) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
				}
				
				$groupe_current_utilisateur_current = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
					->findOneBy(array('groupe' => $groupe_infos, 'utilisateur' => $utilisateur));
					
				if ($groupe_current_utilisateur_current != NULL) {
					return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
				}
				
				$demande = new GroupeUtilisateur;
				
				$demande->setGroupe($groupe_infos);
				$demande->setUtilisateur($utilisateur);
				$demande->setModerateur(false);
				$demande->setAccepte(false);
				
				if ($request->request->get('message_rejoindre_groupe') != "    ") {
					$demande->setMessage($request->request->get('message_rejoindre_groupe'));
				}
				
				$em = $this->getDoctrine()->getManager();
				$em->persist($demande);
				$em->flush();
				
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
			}
			else {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
		}
		
		// Ici on traite les invités
		if ($i == NULL) {
			  $utilisateur = new Utilisateur;
									  
			  $pass = "abcdefghijklmnopqrstuvwxyz987654321012345643198536985prokfjaidinend";
			  $pass_md5 = md5($pass);
				
			  $utilisateur->setMotDePasse($pass_md5);
			  $date = date_create(date('Y-m-d'));
			  
			  $utilisateur->setDateDeNaissance($date);
			  $utilisateur->setSexe('H');
			  
			  $em = $this->getDoctrine()->getManager();
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
			  $utilisateur->setAccepte(true);
			  
			  $session->set('invite', $invite);
			  $i = $invite;
					
			  // On enregistre notre objet $utilisateur dans la base de données
			  $em->persist($utilisateur);
			  $em->flush();
		}
		
		$invite = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($i);
		
		$groupe_infos = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);	

		if (isset($groupe_infos)) {
			if ($groupe_infos->getEtat() != "Groupe public") {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}
			
			$groupe_current_utilisateur_current = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findOneBy(array('groupe' => $groupe_infos, 'utilisateur' => $invite));
			
			if ($groupe_current_utilisateur_current != NULL) {
				return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
			}

			$demande = new GroupeUtilisateur;
			
			$demande->setGroupe($groupe_infos);
			$demande->setUtilisateur($invite);
			$demande->setModerateur(false);
			$demande->setAccepte(false);
			
			if ($request->request->get('message_rejoindre_groupe') != NULL) {
				$demande->setMessage($request->request->get('message_rejoindre_groupe'));
			}
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($demande);
			$em->flush();
			
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		else {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_groupes'));
		}
	}
	
	public function supprimerUtilisateurAction() {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
	
		$utilisateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findOneByLogin($u);
				
		$pass = md5($request->request->get("mot_de_passe"));
				
		if ($utilisateur->getMotDePasse() == $pass) {	
			$em = $this->getDoctrine()->getManager();	
			// Modo de vote
			$utilisateur_vote = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
				->findByUtilisateur($utilisateur);
			
			if ($utilisateur_vote != NULL) {
				foreach ($utilisateur_vote as $cle => $valeur) {
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// Membre ou modo d'un groupe
			$groupe_utilisateur = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findByUtilisateur($utilisateur);
			
			if ($groupe_utilisateur != NULL) {
				foreach ($groupe_utilisateur as $cle => $valeur) {
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// Avis donnés
			$avis_donnes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
				->findByUtilisateur($utilisateur);
			
			if ($avis_donnes != NULL) {
				foreach ($avis_donnes as $cle => $valeur) {
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// Commentaires donnés
			$commentaires_donnes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findByUtilisateur($utilisateur);
			
			if ($commentaires_donnes != NULL) {
				foreach ($commentaires_donnes as $cle => $valeur) {
					$commentaires_a_supprimer = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:Commentaire')
						->findById($valeur->getCommentaire()->getIdentifier());
				
					$em->remove($valeur);
					$em->flush();
				}
				
				foreach ($commentaires_a_supprimer as $cle => $valeur) {
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// Groupes créés
			$groupes_crees = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findByAdministrateur($utilisateur);
			
			if ($groupes_crees != NULL) {
				foreach ($groupes_crees as $cle => $valeur) {
					$groupes_utilisateurs_autres = $this->getDoctrine()
						->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
						->findByGroupe($valeur);
						
					foreach ($groupes_utilisateurs_autres as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// Votes créés
			$votes_crees = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findByCreateur($utilisateur);
			
			if ($votes_crees != NULL) {
				foreach ($votes_crees as $cle => $valeur) {
					$em->remove($valeur);
					$em->flush();
				}
			}
			
			// L'utilisateur lui-même
			$em->remove($utilisateur);
			$em->flush();
			
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_deconnexion'));
		}
		else {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
	}
	
	public function supprimerGroupeAction($groupe_id = NULL) {
		$request = $this->get('request');
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
				
		$em = $this->getDoctrine()->getManager();	
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		// Membres et modos du groupe
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findByGroupe($groupe);
		
		if ($groupe_utilisateur != NULL) {
			foreach ($groupe_utilisateur as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Votes associés
		$votes_associes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findByGroupeAssocie($groupe);
		
		if ($votes_associes != NULL) {
			foreach ($votes_associes as $cle => $valeur) {
				// Modo de vote
				$utilisateur_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
					->findByVote($valeur);
					
				if ($utilisateur_vote != NULL) {
					foreach ($utilisateur_vote as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Avis donnés
				$avis_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findByVote($valeur);
				
				if ($avis_donnes != NULL) {
					foreach ($avis_donnes as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Commentaires donnés
				$commentaires_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findByVote($valeur);
				
				if ($commentaires_donnes != NULL) {
					foreach ($commentaires_donnes as $key => $value) {
						$commentaires_a_supprimer = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Commentaire')
							->findById($value->getCommentaire()->getIdentifier());
					
						$em->remove($value);
						$em->flush();
					}
					
					foreach ($commentaires_a_supprimer as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
			
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Le groupe lui-même
		$em->remove($groupe);
		$em->flush();
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
	}
	
	public function groupeAjouterUtilisateurAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($request->request->get("utilisateur_id"));
		
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findOneBy(array('utilisateur' => $utilisateur, 'groupe' => $groupe));
			
		if ($groupe_utilisateur == NULL) {
			$groupe_utilisateur = NULL;
		}
		
		if ($groupe_utilisateur != NULL) {
			$groupe_utilisateur->setAccepte(true);
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($groupe_utilisateur);
			$em->flush();
		}
		else {
			$groupe_utilisateur = new GroupeUtilisateur();
			$groupe_utilisateur->setUtilisateur($utilisateur);
			$groupe_utilisateur->setGroupe($groupe);
			$groupe_utilisateur->setAccepte(true);
			$groupe_utilisateur->setModerateur(false);
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($groupe_utilisateur);
			$em->flush();
		}
		
		$groupe_id = (int)$groupe_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function groupeRefuserUtilisateurAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($request->request->get("utilisateur_id"));
		
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findOneBy(array('utilisateur' => $utilisateur, 'groupe' => $groupe));
		
		$em = $this->getDoctrine()->getManager();
		$em->remove($groupe_utilisateur);
		$em->flush();
		
		$groupe_id = (int)$groupe_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function supprimerVoteAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote_id = (int)$request->request->get("vote_id");
		
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
		
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		// Modo de vote
		$utilisateur_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findByVote($vote);
		
		$em = $this->getDoctrine()->getManager();
			
		if ($utilisateur_vote != NULL) {
			foreach ($utilisateur_vote as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Avis donnés
		$avis_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
			->findByVote($vote);
		
		if ($avis_donnes != NULL) {
			foreach ($avis_donnes as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Commentaires donnés
		$commentaires_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findByVote($vote);
		
		if ($commentaires_donnes != NULL) {
			foreach ($commentaires_donnes as $cle => $valeur) {
				$commentaires_a_supprimer = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Commentaire')
					->findById($valeur->getCommentaire()->getIdentifier());
			
				$em->remove($valeur);
				$em->flush();
			}
			
			foreach ($commentaires_a_supprimer as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
	
		// Le vote lui-même
		$em->remove($vote);
		$em->flush();
		
		$groupe_id = (int)$groupe_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function groupeSupprimerUtilisateurAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$utilisateur_id = (int)$request->request->get("membre_id");
		
		if ($utilsateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
			
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}	
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
		
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$em = $this->getDoctrine()->getManager();
		
		// Du groupe
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findBy(array("groupe" => $groupe, "utilisateur" => $utilisateur));
		
		if ($groupe_utilisateur != NULL) {
			foreach ($groupe_utilisateur as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Des votes qu'il a créé associés au groupe
		$votes_associes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findBy(array("groupeAssocie" => $groupe, "createur" => $utilisateur));
		
		if ($votes_associes != NULL) {
			foreach ($votes_associes as $cle => $valeur) {
				// Modo de vote
				$utilisateur_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
					->findByVote($valeur);
					
				if ($utilisateur_vote != NULL) {
					foreach ($utilisateur_vote as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Avis donnés
				$avis_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findByVote($valeur);
				
				if ($avis_donnes != NULL) {
					foreach ($avis_donnes as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Commentaires donnés
				$commentaires_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findByVote($valeur);
				
				if ($commentaires_donnes != NULL) {
					foreach ($commentaires_donnes as $key => $value) {
						$commentaires_a_supprimer = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Commentaire')
							->findById($value->getCommentaire()->getIdentifier());
					
						$em->remove($value);
						$em->flush();
					}
					
					foreach ($commentaires_a_supprimer as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
			
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Des votes du groupe auquel il a participé
		$votes_associes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findBy(array("groupeAssocie" => $groupe));
		
		if ($votes_associes != NULL) {
			foreach ($votes_associes as $cle => $valeur) {
				// Modo de vote
				$utilisateur_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
					->findBy(array("vote" => $valeur, "utilisateur" => $utilisateur));
					
				if ($utilisateur_vote != NULL) {
					foreach ($utilisateur_vote as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Avis donnés
				$avis_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findBy(array("vote" => $valeur, "utilisateur" => $utilisateur));
				
				if ($avis_donnes != NULL) {
					foreach ($avis_donnes as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Commentaires donnés
				$commentaires_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findBy(array("vote" => $valeur, "utilisateur" => $utilisateur));
				
				if ($commentaires_donnes != NULL) {
					foreach ($commentaires_donnes as $key => $value) {
						$commentaires_a_supprimer = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Commentaire')
							->findById($value->getCommentaire()->getIdentifier());
					
						$em->remove($value);
						$em->flush();
					}
					
					foreach ($commentaires_a_supprimer as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
			}
		}
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function groupeDonnerPrivilegeAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$utilisateur_id = (int)$request->request->get("membre_id");
		
		if ($utilisateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
			
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
			
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findOneBy(array('utilisateur' => $utilisateur, 'groupe' => $groupe));
			
		$groupe_utilisateur->setModerateur(true);
		
		$em = $this->getDoctrine()->getManager();
		$em->persist($groupe_utilisateur);
		$em->flush();
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function groupeSupprimerPrivilegeAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$utilisateur_id = (int)$request->request->get("membre_id");
		
		if ($utilisateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
		
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}	
		
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findOneBy(array('utilisateur' => $utilisateur, 'groupe' => $groupe));
			
		$groupe_utilisateur->setModerateur(false);
		
		$em = $this->getDoctrine()->getManager();
		$em->persist($groupe_utilisateur);
		$em->flush();
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_affichage_groupe', array('groupe_id' => $groupe_id)));
	}
	
	public function voteModererAction($vote_id = NULL) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
	
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneByLogin($u);
	
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
		
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$groupe_associe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($vote->getGroupeAssocie());
		
		$vote_moderateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findOneBy(array("vote" => $vote, "utilisateur" => $utilisateur));
			
		$vote_createur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneBy(array("id" => $vote_id, "createur" => $utilisateur));
		
		$groupe_moderateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findOneBy(array("groupe" => $vote->getGroupeAssocie(), "utilisateur" => $utilisateur, "moderateur" => true));
		
		$groupe_administrateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneBy(array("id" => $vote->getGroupeAssocie(), "administrateur" => $utilisateur));
			
		if ($vote_moderateur == NULL && $vote_createur == NULL && $groupe_moderateur == NULL && $groupe_administrateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		// A partir d'ici on sait qu'il dispose des droits
		
		$liste_commentaires_associes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findByVote($vote, array('identifier' => 'desc'));	
		
		if ($liste_commentaires_associes == NULL) {
			$liste_commentaires_associes = NULL;
		}
			
		$utilisateurs = NULL;
		$liste_utilisateurs = NULL;
		
		if ($groupe_associe == NULL) {
			$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
			
			foreach ($utilisateurs as $cle => $valeur) {
				if ($valeur != $utilisateur) {
					if (preg_match("/Invité/", $valeur->getLogin()) == false) {
						$liste_utilisateurs[] = $valeur;
					}
				}
			}
		}
		else {
			$groupe_utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
				->findByModerateur(false);
			
			foreach ($groupe_utilisateurs as $cle => $valeur) {
				if ($valeur->getUtilisateur() != $utilisateur) {
					if (preg_match("/Invité/", $valeur->getUtilisateur()->getLogin()) == false) {
						$liste_utilisateurs[] = $valeur->getUtilisateur();
					}
				}
			}
		}
		
		$delete = false;
		
		if ($vote_createur != NULL || $groupe_moderateur != NULL || $groupe_administrateur != NULL) {
			// Seule le createur d'un vote ou les modérateurs ou l'administrateur d'un groupe peuvent supprimer un vote
			$delete = true;	
		}
		
		$add_moderators = false;
		
		if ($vote_createur != NULL) {
			$add_moderators = true;
		}	
		
		$moderateurs_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findByVote($vote);
		
		if ($moderateurs_vote != NULL) {
			foreach ($moderateurs_vote as $cle => $valeur) {
				$moderateurs[] = $valeur->getUtilisateur();
			}
		}
		
		if ($moderateurs_vote == NULL) {
			$moderateurs = NULL;
		}
		
		$liste_utilisateurs_finale = NULL;
		
		if ($moderateurs != NULL) {
			foreach ($liste_utilisateurs as $cle => $valeur) {
				$is_moderator = false;
				
				foreach ($moderateurs as $key => $value) {
					if ($valeur == $value) {
						$is_moderator = true;
					}
				}
				
				if ($is_moderator == false) {
					$liste_utilisateurs_finale[] = $valeur;
				}
			}
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:moderationVote.html.twig', array(
					'utilisateur' => $u,
					'delete' => $delete, 
					'liste_utilisateurs' => $liste_utilisateurs_finale,
					'liste_commentaires_associes' => $liste_commentaires_associes,
					'vote_id' => $vote_id,
					'vote' => $vote,
					'add_moderators' => $add_moderators,
					'moderateurs' => $moderateurs));	
	}
	
	public function voteAjouterModerateurAction() {
		$request = $this->get('request');
		
		$vote_id = $request->request->get("vote_id");
		$utilisateur_id = $request->request->get("membre_id");
		
		if ($vote_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		if($utilisateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}	
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
			
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
		
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$utilisateur_vote = new UtilisateurVote();
		$utilisateur_vote->setUtilisateur($utilisateur);
		$utilisateur_vote->setVote($vote);
		
		$em = $this->getDoctrine()->getManager();
		$em->persist($utilisateur_vote);
		$em->flush();
		
		$vote_id = (int)$vote_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote_moderer', array('vote_id' => $vote_id)));
	}
	
	public function voteSupprimerModerateurAction() {
		$request = $this->get('request');
		
		$vote_id = $request->request->get("vote_id");
		$utilisateur_id = $request->request->get("membre_id");
		
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		if ($utilisateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
		
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
		
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findOneBy(array('utilisateur' => $utilisateur, 'vote' => $vote));
		
		$em = $this->getDoctrine()->getManager();
		$em->remove($utilisateur_vote);
		$em->flush();
		
		$vote_id = (int)$vote_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote_moderer', array('vote_id' => $vote_id)));
	}
	
	public function commentaireSupprimerAction() {
		$request = $this->get('request');
		
		$vote_id = $request->request->get("vote_id");
		$commentaire_id = $request->request->get("commentaire_id");
		
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		if ($commentaire_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
			
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$commentaire = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Commentaire')
			->findOneById($commentaire_id);
		
		if ($commentaire == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$vote_commentaire_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findOneBy(array('commentaire' => $commentaire, 'vote' => $vote));
		
		if ($vote_commentaire_utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$em = $this->getDoctrine()->getManager();
		$em->remove($vote_commentaire_utilisateur);
		$em->flush();
		
		$vote_id = (int)$vote_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote_moderer', array('vote_id' => $vote_id)));
	}
	
	public function voteSupprimerAction() {
		$request = $this->get('request');
		
		$vote_id = $request->request->get("vote_id");
		
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
		
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		// Modo de vote
		$utilisateur_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findByVote($vote);
		
		$em = $this->getDoctrine()->getManager();
			
		if ($utilisateur_vote != NULL) {
			foreach ($utilisateur_vote as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Avis donnés
		$avis_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
			->findByVote($vote);
		
		if ($avis_donnes != NULL) {
			foreach ($avis_donnes as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Commentaires donnés
		$commentaires_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findByVote($vote);
		
		if ($commentaires_donnes != NULL) {
			foreach ($commentaires_donnes as $cle => $valeur) {
				$commentaires_a_supprimer = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Commentaire')
					->findById($valeur->getCommentaire()->getIdentifier());
			
				$em->remove($valeur);
				$em->flush();
			}
			
			foreach ($commentaires_a_supprimer as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
	
		// Le vote lui-même
		$em->remove($vote);
		$em->flush();
		
		$vote_id = (int)$vote_id;
		
		return $this->redirect($this->generateUrl('votenmasse_votenmasse_vote_moderer', array('vote_id' => $vote_id)));
	}
	
	public function moderationSupprimerAction($log = null) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$em = $this->getDoctrine()->getManager();
		
		$uti = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneBylogin($log);
					
		if ($uti == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
					
		$em->remove($uti);
		$em->flush();
		

		/////////////////////////
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();

		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte"=>true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte"=>false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
			
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
			
		$createurs = NULL;
			
		if ($votes != NULL) {	
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}

	public function moderationAccepterAction($log = null) {
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		$em = $this->getDoctrine()->getManager();
		
		$uti = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findOneBylogin($log);
		
		if ($uti == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
					
		$uti->setAccepte(true);
		$em->persist($uti);
		$em->flush();

		/////////////////////////
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
			
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createurs = NULL;		
			
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}
	
	public function moderationAction($etat = NULL) {
		//$etat est passé en parametre depuis la vue
		$request = $this->get('request');
		$session = $request->getSession();		
		$u = $session->get('utilisateur');
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
		
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
			
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createur = NULL;		
					
		if ($votes != NULL) {			
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
		
		$ins_etat = $ins->getEtat();
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
		
		if ($etat == NULL || $ins_etat == $etat) {
			
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation' => $utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip' => $ins_etat));
		}
		else {
			$em = $this->getDoctrine()->getManager();
			
			if ($etat == "Ouvertes") {
				// On va valider tous les utilisateurs en attente (si avant nous étions sous modération ou fermées)
				
				$utilisateurs = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
				->findAll();
				
				foreach ($utilisateurs as $cle => $valeur) {
					if ($valeur->getAccepte() == false) {
						$valeur->setAccepte(true);
						
						$em->persist($valeur);
						$em->flush();
					}
				}
			}
			
			$ins->setEtat($etat);
			$em->persist($ins);
			$em->flush();
			
			return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation' => $utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip' => $etat));
		}
		
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
				'connecte' => true,
				'utilisateurs' => $utilisateurs,
				'utilisateurs_en_moderation' => $utilisateurs_en_moderation,
				'groupes' => $groupes,
				'votes' => $votes,
				'commentaires' => $commentaires,
				'vote_createurs' => $createurs,
				'inscrip'=>$ins_etat));
	
	
	}
	
	public function bannirAction() {
		$request = $this->get('request');
		
		$utilisateur_id = $request->request->get("membre_id");
		
		if ($utilisateur_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
			->findOneById($utilisateur_id);
		
		if ($utilisateur == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		$em = $this->getDoctrine()->getManager();
		
		// Des groupes où il est utilisateur
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findByUtilisateur($utilisateur);
		
		if ($groupe_utilisateur != NULL) {
			foreach ($groupe_utilisateur as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Des votes qu'il a créé
		$votes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findByCreateur($utilisateur);
		
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				// Modérateur des votes où il est administrateur
				$utilisateur_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
					->findByVote($valeur);
					
				if ($utilisateur_vote != NULL) {
					foreach ($utilisateur_vote as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Avis donnés sur les votes où il est administrateur
				$avis_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findByVote($valeur);
				
				if ($avis_donnes != NULL) {
					foreach ($avis_donnes as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Commentaires donnés sur les votes où il est administrateur
				$commentaires_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findByVote($valeur);
				
				if ($commentaires_donnes != NULL) {
					foreach ($commentaires_donnes as $key => $value) {
						$commentaires_a_supprimer = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Commentaire')
							->findById($value->getCommentaire()->getIdentifier());
					
						$em->remove($value);
						$em->flush();
					}
					
					foreach ($commentaires_a_supprimer as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
			
				$em->remove($valeur);
				$em->flush();
			}
		}

		// Modo de vote
		$utilisateur_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findByUtilisateur($utilisateur);
			
		if ($utilisateur_vote != NULL) {
			foreach ($utilisateur_vote as $key => $value) {
				$em->remove($value);
				$em->flush();
			}
		}
		
		// Avis donnés
		$avis_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
			->findByUtilisateur($utilisateur);
		
		if ($avis_donnes != NULL) {
			foreach ($avis_donnes as $key => $value) {
				$em->remove($value);
				$em->flush();
			}
		}
		
		// Commentaires donnés
		$commentaires_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findByUtilisateur($utilisateur);
		
		if ($commentaires_donnes != NULL) {
			foreach ($commentaires_donnes as $key => $value) {
				$commentaires_a_supprimer = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Commentaire')
					->findById($value->getCommentaire()->getIdentifier());
			
				$em->remove($value);
				$em->flush();
			}
			
			foreach ($commentaires_a_supprimer as $key => $value) {
				$em->remove($value);
				$em->flush();
			}
		}
		
		// L'utilisateur lui-même
		$em->remove($utilisateur);
		$em->flush();
		
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
					
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createurs = NULL;		
			
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}
	
	public function gSupprimerAction() {
		$request = $this->get('request');
		
		$groupe_id = $request->request->get("groupe_id");
		
		if ($groupe_id == NULL) {
			$this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
				
		$em = $this->getDoctrine()->getManager();	
		
		$groupe = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Groupe')
			->findOneById($groupe_id);
		
		if ($groupe == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		// Membres et modos du groupe
		$groupe_utilisateur = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:GroupeUtilisateur')
			->findByGroupe($groupe);
		
		if ($groupe_utilisateur != NULL) {
			foreach ($groupe_utilisateur as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Votes associés
		$votes_associes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findByGroupeAssocie($groupe);
		
		if ($votes_associes != NULL) {
			foreach ($votes_associes as $cle => $valeur) {
				// Modo de vote
				$utilisateur_vote = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
					->findByVote($valeur);
					
				if ($utilisateur_vote != NULL) {
					foreach ($utilisateur_vote as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Avis donnés
				$avis_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
					->findByVote($valeur);
				
				if ($avis_donnes != NULL) {
					foreach ($avis_donnes as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
				
				// Commentaires donnés
				$commentaires_donnes = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
					->findByVote($valeur);
				
				if ($commentaires_donnes != NULL) {
					foreach ($commentaires_donnes as $key => $value) {
						$commentaires_a_supprimer = $this->getDoctrine()
							->getRepository('VotenmasseVotenmasseBundle:Commentaire')
							->findById($value->getCommentaire()->getIdentifier());
					
						$em->remove($value);
						$em->flush();
					}
					
					foreach ($commentaires_a_supprimer as $key => $value) {
						$em->remove($value);
						$em->flush();
					}
				}
			
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Le groupe lui-même
		$em->remove($groupe);
		$em->flush();
		
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
		
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
				
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createurs = NULL;		
			
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}
	
	public function vSupprimerAction() {
		$request = $this->get('request');
		
		$vote_id = (int)$request->request->get("vote_id");
		
		if ($vote_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Vote')
			->findOneById($vote_id);
		
		if ($vote == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
			
		// Modo de vote
		$utilisateur_vote = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:UtilisateurVote')
			->findByVote($vote);
		
		$em = $this->getDoctrine()->getManager();
			
		if ($utilisateur_vote != NULL) {
			foreach ($utilisateur_vote as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Avis donnés
		$avis_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:DonnerAvis')
			->findByVote($vote);
		
		if ($avis_donnes != NULL) {
			foreach ($avis_donnes as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
		
		// Commentaires donnés
		$commentaires_donnes = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findByVote($vote);
		
		if ($commentaires_donnes != NULL) {
			foreach ($commentaires_donnes as $cle => $valeur) {
				$commentaires_a_supprimer = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Commentaire')
					->findById($valeur->getCommentaire()->getIdentifier());
			
				$em->remove($valeur);
				$em->flush();
			}
			
			foreach ($commentaires_a_supprimer as $cle => $valeur) {
				$em->remove($valeur);
				$em->flush();
			}
		}
	
		// Le vote lui-même
		$em->remove($vote);
		$em->flush();
		
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
					
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createurs = NULL;		
			
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}
	
	public function cSupprimerAction() {
		$request = $this->get('request');
		
		$em = $this->getDoctrine()->getManager();
		
		$commentaire_id = (int)$request->request->get("commentaire_id");
		
		if ($commentaire_id == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		$commentaire = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
			->findOneByIdentifier($commentaire_id);
		
		if ($commentaire == NULL) {
			return $this->redirect($this->generateUrl('votenmasse_votenmasse_index'));
		}
		
		// Le VoteCommentaireUtilisateur
		$em->remove($commentaire);
		$em->flush();
		
		$commentaire_a_supprimer = $this->getDoctrine()
			->getRepository('VotenmasseVotenmasseBundle:Commentaire')
			->findOneById($commentaire->getIdentifier());
	
		$em->remove($commentaire_a_supprimer);
		$em->flush();
		
		$ins = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Inscription')
				->findOneById(1);
				
		$et = $ins->getEtat();
		
		$utilisateurs = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => true));
					
		$utilisateurs_en_moderation = $this->getDoctrine()
					->getRepository('VotenmasseVotenmasseBundle:Utilisateur')
					->findBy(array("accepte" => false));
					
		$groupes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Groupe')
				->findAll();
					
		$votes = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:Vote')
				->findAll();
				
		$commentaires = $this->getDoctrine()
				->getRepository('VotenmasseVotenmasseBundle:VoteCommentaireUtilisateur')
				->findAll();
					
		if ($commentaires == NULL) {
			$commentaires = NULL;
		}
				
		$createurs = NULL;		
			
		if ($votes != NULL) {
			foreach ($votes as $cle => $valeur) {
				$createur = $valeur->getCreateur();
						
				$createurs[$cle] = $createur->getLogin();
			}
		}
		
		if ($utilisateurs == NULL) {
			$utilisateurs = NULL;
		}
		
		if ($utilisateurs_en_moderation == NULL) {
			$utilisateurs_en_moderation = NULL;
		}
		
		if ($groupes == NULL) {
			$groupes = NULL;
		}
		
		if ($votes == NULL) {
			$votes = NULL;
		}
				
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:administration.html.twig', array(
					'connecte' => true,
					'utilisateurs' => $utilisateurs,
					'utilisateurs_en_moderation'=>$utilisateurs_en_moderation,
					'groupes' => $groupes,
					'votes' => $votes,
					'commentaires' => $commentaires,
					'vote_createurs' => $createurs,
					'inscrip'=>$et));
	}
}