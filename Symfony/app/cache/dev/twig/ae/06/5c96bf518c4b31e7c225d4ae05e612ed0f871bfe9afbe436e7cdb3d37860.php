<?php

/* VotenmasseVotenmasseBundle:Votenmasse:index.html.twig */
class __TwigTemplate_ae065c96bf518c4b31e7c225d4ae05e612ed0f871bfe9afbe436e7cdb3d37860 extends Twig_Template
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
  <div class=\"well\">
  <form method=\"post\" ";
        // line 10
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'enctype');
        echo ">
    ";
        // line 11
        echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'widget');
        echo "
    <input type=\"submit\" class=\"btn btn-primary\" />
  </form>
</div>

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
        return array (  50 => 11,  46 => 10,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
