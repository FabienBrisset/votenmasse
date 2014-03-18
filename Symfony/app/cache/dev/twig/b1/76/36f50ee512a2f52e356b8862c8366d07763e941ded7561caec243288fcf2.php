<?php

/* VotenmasseVotenmasseBundle:Votenmasse:index.html.twig */
class __TwigTemplate_b17636f50ee512a2f52e356b8862c8366d07763e941ded7561caec243288fcf2 extends Twig_Template
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
\t<div class=\"well\">
\t\t<h3 id = \"titre_formulaire\"><b>Formulaire d'inscription</b></h3>
\t\t<form method=\"post\" ";
        // line 11
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'enctype');
        echo ">
\t\t";
        // line 12
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'widget');
        echo "
\t\t<input type=\"submit\" class=\"btn btn-primary\" id=\"form_validatation\" />
\t\t</form>
\t</div>

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
        return array (  51 => 12,  47 => 11,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
