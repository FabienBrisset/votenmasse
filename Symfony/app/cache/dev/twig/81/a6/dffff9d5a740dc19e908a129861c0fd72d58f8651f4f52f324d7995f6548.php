<?php

/* VotenmasseVotenmasseBundle::layout.html.twig */
class __TwigTemplate_81a6dffff9d5a740dc19e908a129861c0fd72d58f8651f4f52f324d7995f6548 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = $this->env->loadTemplate("::layout.html.twig");

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'body' => array($this, 'block_body'),
            'votenmasse_body' => array($this, 'block_votenmasse_body'),
        );
    }

    protected function doGetParent(array $context)
    {
        return "::layout.html.twig";
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $this->parent->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_title($context, array $blocks = array())
    {
        // line 4
        echo "  ";
        $this->displayParentBlock("title", $context, $blocks);
        echo "
";
    }

    // line 7
    public function block_body($context, array $blocks = array())
    {
        // line 8
        echo "\t<div class = \"well\">
\t\t<div align=\"center\">
\t\t\t<img src=\"";
        // line 10
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/Votenmasse.png"), "html", null, true);
        echo "\" alt=\"Votenmasse\"/>
\t\t</div>
\t\t<br>
\t\t<div>
\t\t\t<ul class=\"nav nav-pills nav-stacked\">
\t\t\t\t<form action=\"";
        // line 15
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_connexion");
        echo "\" method=\"post\">
\t\t\t\t\t<li><button class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 16
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Accueil</a></button>&nbsp;&nbsp;
\t\t\t\t\t<button class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 17
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Votes</a></button>&nbsp;&nbsp;
\t\t\t\t\t<button class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 18
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Résultats</a></button>&nbsp;&nbsp;
\t\t\t\t\t<button class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 19
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Forum</a></button>
\t\t\t\t\t<span style = \"margin-left: 50px;\">Login 
\t\t\t\t\t<input class=\"input-mini\" type=\"text\" id=\"login\" name=\"login\" />&nbsp;&nbsp;&nbsp;&nbsp;
\t\t\t\t\tMot de passe 
\t\t\t\t\t<input class=\"input-mini\" type=\"password\" id=\"mot_de_passe\" name=\"mot_de_passe\" style = \"margin-right: 10px;\" />&nbsp;&nbsp;&nbsp;&nbsp;
\t\t\t\t\t<button class=\"btn btn-primary btn-mini\" type=\"submit\">Connexion <i class=\"icon-white icon-ok-sign\"></i> </button></span>
\t\t\t\t\t</li>
\t\t\t\t</form>
\t\t\t</ul>
\t\t</div>
\t</div>

  <hr>

  ";
        // line 33
        $this->displayBlock('votenmasse_body', $context, $blocks);
        // line 35
        echo "  
  <hr>
  
";
    }

    // line 33
    public function block_votenmasse_body($context, array $blocks = array())
    {
        // line 34
        echo "  ";
    }

    public function getTemplateName()
    {
        return "VotenmasseVotenmasseBundle::layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  100 => 34,  97 => 33,  90 => 35,  88 => 33,  71 => 19,  67 => 18,  63 => 17,  59 => 16,  55 => 15,  47 => 10,  43 => 8,  40 => 7,  33 => 4,  30 => 3,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
