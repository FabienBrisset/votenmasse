<?php

/* VotenmasseVotenmasseBundle:Votenmasse:index.html.twig */
class __TwigTemplate_b8926e6ab279f9e1bd55661476a01539203dfebdb3c019b27517fd8839fe202a extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("VotenmasseVotenmasseBundle::layout.html.twig");

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'votenmasse_body' => array($this, 'block_votenmasse_body'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "VotenmasseVotenmasseBundle::layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        // line 4
        echo " ";
        $this->displayParentBlock("title", $context, $blocks);
        echo " - Accueil 
";
    }

    // line 7
    public function block_votenmasse_body($context, array $blocks = array())
    {
        // line 8
        echo "

";
    }

    public function getTemplateName()
    {
        return "VotenmasseVotenmasseBundle:Votenmasse:index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
