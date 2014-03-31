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
        echo "\t";
        if (array_key_exists("erreur", $context)) {
            // line 9
            echo "\t\t<h5 style=\"color:red\">";
            echo twig_escape_filter($this->env, (isset($context["erreur"]) ? $context["erreur"] : $this->getContext($context, "erreur")), "html", null, true);
            echo "</h5>
\t";
        }
        // line 11
        echo "\t";
        if (array_key_exists("inscription_valide", $context)) {
            // line 12
            echo "\t\t";
            if ((!(null === (isset($context["inscription_valide"]) ? $context["inscription_valide"] : $this->getContext($context, "inscription_valide"))))) {
                // line 13
                echo "\t\t\t<h5 style=\"color:red\">";
                echo twig_escape_filter($this->env, (isset($context["inscription_valide"]) ? $context["inscription_valide"] : $this->getContext($context, "inscription_valide")), "html", null, true);
                echo "</h5>
\t\t";
            }
            // line 15
            echo "\t";
        }
        // line 16
        echo "\t<table class=\"table\">
\t\t<tr>
\t\t\t<td>
\t\t\t\t<div class=\"well\">
\t\t\t\t\t
\t\t\t\t</div>
\t\t\t</td>
\t\t\t";
        // line 23
        if (array_key_exists("utilisateur", $context)) {
            // line 24
            echo "\t\t\t\t";
            if ((null === (isset($context["utilisateur"]) ? $context["utilisateur"] : $this->getContext($context, "utilisateur")))) {
                // line 25
                echo "\t\t\t\t<td>
\t\t\t\t\t<div class=\"well\">
\t\t\t\t\t\t<h3 id = \"titre_formulaire\"><b>Formulaire d'inscription</b></h3>
\t\t\t\t\t\t<form method=\"post\" class=\"Inscription\" ";
                // line 28
                echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'enctype');
                echo ">
\t\t\t\t\t\t";
                // line 29
                echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'widget');
                echo "
\t\t\t\t\t\t<input type=\"submit\" class=\"btn btn-primary\" id=\"form_validatation\" />
\t\t\t\t\t\t</form>
\t\t\t\t\t</div>
\t\t\t\t</td>
\t\t\t\t";
            }
            // line 35
            echo "\t\t\t";
        }
        // line 36
        echo "\t\t\t";
        if ((!array_key_exists("utilisateur", $context))) {
            // line 37
            echo "\t\t\t\t<td>
\t\t\t\t\t<div class=\"well\">
\t\t\t\t\t\t<h3 id = \"titre_formulaire\"><b>Formulaire d'inscription</b></h3>
\t\t\t\t\t\t<form method=\"post\" class=\"Inscription\" ";
            // line 40
            echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'enctype');
            echo ">
\t\t\t\t\t\t";
            // line 41
            echo $this->env->getExtension('form')->renderer->searchAndRenderBlock((isset($context["form"]) ? $context["form"] : $this->getContext($context, "form")), 'widget');
            echo "
\t\t\t\t\t\t<input type=\"submit\" class=\"btn btn-primary\" id=\"form_validatation\" />
\t\t\t\t\t\t</form>
\t\t\t\t\t</div>
\t\t\t\t</td>
\t\t\t";
        }
        // line 47
        echo "\t\t</tr>
\t\t<tr>
\t\t\t<td colspan = \"2\">
\t\t\t\t<div class=\"well\">
\t\t\t\t\t\t<img src = \"";
        // line 51
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/pub.png"), "html", null, true);
        echo "\" alt = \"Public\" /> Ce logo signifie que le vote ou le groupe est public (accessible à tous) <br>
\t\t\t\t\t\t<img src = \"";
        // line 52
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/rai.png"), "html", null, true);
        echo "\" alt = \"Réservé aux inscrits\" /> Ce logo signifie que le vote ou le groupe est réservé aux inscrits <br>
\t\t\t\t\t\t<img src = \"";
        // line 53
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/pri.png"), "html", null, true);
        echo "\" alt = \"Privé\" /> Ce logo signifie que le vote ou le groupe est privé (vous devez être membre du groupe)
\t\t\t\t</div>
\t\t\t</td>
\t\t</tr>
\t</table>

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
        return array (  136 => 53,  132 => 52,  128 => 51,  122 => 47,  113 => 41,  109 => 40,  104 => 37,  101 => 36,  98 => 35,  89 => 29,  85 => 28,  80 => 25,  77 => 24,  75 => 23,  66 => 16,  63 => 15,  57 => 13,  54 => 12,  51 => 11,  45 => 9,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
