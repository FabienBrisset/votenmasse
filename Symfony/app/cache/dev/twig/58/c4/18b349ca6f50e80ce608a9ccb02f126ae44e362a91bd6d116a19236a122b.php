<?php

/* VotenmasseVotenmasseBundle::layout.html.twig */
class __TwigTemplate_58c418b349ca6f50e80ce608a9ccb02f126ae44e362a91bd6d116a19236a122b extends Twig_Template
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
\t\t\t\t<li><button style = \"margin-right: 5px\" class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 15
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Accueil</a></button>
\t\t\t\t<button style = \"margin-right: 5px\" class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 16
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_votes");
        echo "\">Votes</a></button>
\t\t\t\t<button style = \"margin-right: 5px\" class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 17
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Résultats</a></button>
\t\t\t\t<button class=\"btn btn-inverse btn-large\"><a href=\"";
        // line 18
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_forum");
        echo "\">Forum</a></button>
\t\t\t\t";
        // line 19
        if ((!array_key_exists("utilisateur", $context))) {
            // line 20
            echo "\t\t\t\t\t<form action=\"";
            echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_connexion");
            echo "\" method=\"post\">
\t\t\t\t\t\t<span style = \"float: right; margin-top: 10px;\">Pseudo 
\t\t\t\t\t\t<input style = \"margin-right: 10px\" class=\"input-mini\" type=\"text\" id=\"login\" name=\"login\" />
\t\t\t\t\t\tMot de passe 
\t\t\t\t\t\t<input class=\"input-mini\" type=\"password\" id=\"mot_de_passe\" name=\"mot_de_passe\" style = \"margin-right: 10px;\" />
\t\t\t\t\t\t<button class=\"btn btn-primary btn-mini\" type=\"submit\">Connexion <i class=\"icon-white icon-ok-sign\"></i> </button></span>
\t\t\t\t\t</form>
\t\t\t\t";
        }
        // line 28
        echo "\t\t\t\t";
        if (array_key_exists("utilisateur", $context)) {
            // line 29
            echo "\t\t\t\t\t";
            if (((isset($context["utilisateur"]) ? $context["utilisateur"] : $this->getContext($context, "utilisateur")) != null)) {
                // line 30
                echo "\t\t\t\t\t\t<form action=\"";
                echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_deconnexion");
                echo "\" method=\"post\">
\t\t\t\t\t\t\t<span style = \"float: right; margin-top: 10px; margin-right: 5px;\"><b>";
                // line 31
                echo twig_escape_filter($this->env, (isset($context["utilisateur"]) ? $context["utilisateur"] : $this->getContext($context, "utilisateur")), "html", null, true);
                echo "</b>
\t\t\t\t\t\t\t<button class=\"btn btn-primary btn-mini\" type=\"submit\">Déconnexion <i class=\"icon-white icon-ok-sign\"></i> </button></span>
\t\t\t\t\t\t</form>
\t\t\t\t\t";
            }
            // line 35
            echo "\t\t\t\t\t";
            if (((isset($context["utilisateur"]) ? $context["utilisateur"] : $this->getContext($context, "utilisateur")) == null)) {
                // line 36
                echo "\t\t\t\t\t\t<form action=\"";
                echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_connexion");
                echo "\" method=\"post\">
\t\t\t\t\t\t\t<span style = \"float: right; margin-top: 10px;\">Pseudo 
\t\t\t\t\t\t\t<input class=\"input-mini\" type=\"text\" id=\"login\" name=\"login\" style = \"margin-right: 10px\" />
\t\t\t\t\t\t\tMot de passe 
\t\t\t\t\t\t\t<input class=\"input-mini\" type=\"password\" id=\"mot_de_passe\" name=\"mot_de_passe\" style = \"margin-right: 10px;\" />
\t\t\t\t\t\t\t<button class=\"btn btn-primary btn-mini\" type=\"submit\">Connexion <i class=\"icon-white icon-ok-sign\"></i> </button></span>
\t\t\t\t\t\t</form>
\t\t\t\t\t";
            }
            // line 44
            echo "\t\t\t\t";
        }
        // line 45
        echo "\t\t\t\t</li>
\t\t\t</ul>
\t\t</div>
\t</div>

  <hr>

  ";
        // line 52
        $this->displayBlock('votenmasse_body', $context, $blocks);
        // line 54
        echo "  
  <hr>
  
";
    }

    // line 52
    public function block_votenmasse_body($context, array $blocks = array())
    {
        // line 53
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
        return array (  142 => 53,  139 => 52,  130 => 52,  121 => 45,  118 => 44,  106 => 36,  103 => 35,  96 => 31,  91 => 30,  88 => 29,  73 => 20,  71 => 19,  67 => 18,  59 => 16,  55 => 15,  47 => 10,  43 => 8,  40 => 7,  33 => 4,  30 => 3,  136 => 53,  132 => 54,  128 => 51,  122 => 47,  113 => 41,  109 => 40,  104 => 37,  101 => 36,  98 => 35,  89 => 29,  85 => 28,  80 => 25,  77 => 24,  75 => 23,  66 => 16,  63 => 17,  57 => 13,  54 => 12,  51 => 11,  45 => 9,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
