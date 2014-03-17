<?php

namespace Votenmasse\VotenmasseBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('VotenmasseVotenmasseBundle:Default:index.html.twig', array('name' => $name));
    }
}
