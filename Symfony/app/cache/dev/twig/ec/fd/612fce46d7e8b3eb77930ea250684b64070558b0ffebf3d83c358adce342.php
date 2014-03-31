<?php

/* VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig */
class __TwigTemplate_ecfd612fce46d7e8b3eb77930ea250684b64070558b0ffebf3d83c358adce342 extends Twig_Template
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
        echo " - Forum 
";
    }

    // line 7
    public function block_votenmasse_body($context, array $blocks = array())
    {
        // line 8
        echo "
<table class=\"table\">
\t\t<tr>
\t\t\t<td>
\t\t\t\t<div class=\"well\">
\t\t\t\t\t<h2 id = \"titre_formulaire\" align = \"center\"><b>Bienvenue sur le Forum</b></h2>
\t\t\t\t\t<center>
\t\t\t\t\t\t<h4><b>Veuillez choisir le vote</b></h4><br>
\t\t\t\t\t\t
\t\t\t\t\t\t\t\t";
        // line 17
        $context['_parent'] = (array) $context;
        $context['_seq'] = twig_ensure_traversable((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")));
        foreach ($context['_seq'] as $context["_key"] => $context["item"]) {
            // line 18
            echo " \t\t\t\t\t\t\t   <a href=\"";
            echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_commentaire", array("nomVote" => (isset($context["item"]) ? $context["item"] : $this->getContext($context, "item")))), "html", null, true);
            echo "\">Lien vers le vote: ";
            echo twig_escape_filter($this->env, (isset($context["item"]) ? $context["item"] : $this->getContext($context, "item")), "html", null, true);
            echo " </a></br>
\t\t\t\t\t\t\t\t";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['item'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 20
        echo "\t\t\t\t\t\t
\t\t\t\t</div>
\t\t\t</td>
\t\t</tr>
\t\t
\t</table>

";
    }

    public function getTemplateName()
    {
        return "VotenmasseVotenmasseBundle:Votenmasse:forum.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  68 => 20,  57 => 18,  53 => 17,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
