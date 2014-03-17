<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * appProdUrlMatcher
 *
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class appProdUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    /**
     * Constructor.
     */
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($pathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($pathinfo);
        $context = $this->context;
        $request = $this->request;

        // votenmasse_votenmasse_homepage
        if (0 === strpos($pathinfo, '/hello') && preg_match('#^/hello/(?P<name>[^/]++)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'votenmasse_votenmasse_homepage')), array (  '_controller' => 'Votenmasse\\VotenmasseBundle\\Controller\\DefaultController::indexAction',));
        }

        // votenmasse_votenmasse_accueil
        if ($pathinfo === '/accueil') {
            return array (  '_controller' => 'Votenmasse\\VotenmasseBundle\\Controller\\VotenmasseController::indexAction',  '_route' => 'votenmasse_votenmasse_accueil',);
        }

        // votenmasse_votenmasse_connexion
        if ($pathinfo === '/connexion') {
            return array (  '_controller' => 'Votenmasse\\VotenmasseBundle\\Controller\\VotenmasseController::connexionAction',  '_route' => 'votenmasse_votenmasse_connexion',);
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
