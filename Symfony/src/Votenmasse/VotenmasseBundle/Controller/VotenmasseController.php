<?php

namespace Votenmasse\VotenmasseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Votenmasse\VotenmasseBundle\Entity\Utilisateur;

class VotenmasseController extends Controller
{
	public function indexAction()
	{
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
	
	public function connexionAction() {
		return $this->render('VotenmasseVotenmasseBundle:Votenmasse:index.html.twig');
	}
}