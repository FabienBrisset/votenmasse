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
        echo " - Votes
";
    }

    // line 7
    public function block_votenmasse_body($context, array $blocks = array())
    {
        // line 8
        echo "
\t<table class=\"table\">
\t\t<legend><b>Liste des votes</b></legend>
\t\t<tr>
\t\t\t<td>
\t\t\t\t<div class=\"well\">
\t\t\t\t\t";
        // line 14
        if (array_key_exists("votes", $context)) {
            // line 15
            echo "\t\t\t\t\t\t";
            if ((!(null === (isset($context["votes"]) ? $context["votes"] : $this->getContext($context, "votes"))))) {
                // line 16
                echo "\t\t\t\t\t\t\t<table>
\t\t\t\t\t\t\t\t<tr>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Type</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Nom</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Description</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Date de création</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Date de fin</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Groupe associé</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t<center><b>Etat</b></center>
\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t
\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t";
                // line 41
                $context["cpt"] = 0;
                // line 42
                echo "\t\t\t\t\t\t\t\t";
                $context['_parent'] = (array) $context;
                $context['_seq'] = twig_ensure_traversable((isset($context["votes"]) ? $context["votes"] : $this->getContext($context, "votes")));
                foreach ($context['_seq'] as $context["_key"] => $context["vote"]) {
                    // line 43
                    echo "\t\t\t\t\t\t\t\t\t<tr class = \"liste_votes\" onclick=\"document.location.href='";
                    echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_commentaire", array("nomV" => $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "nom"))), "html", null, true);
                    echo "'\">
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    // line 45
                    if (($this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "type") === "Vote public")) {
                        // line 46
                        echo "\t\t\t\t\t\t\t\t\t\t\t\t\t<center><img src = \"";
                        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/pub.png"), "html", null, true);
                        echo "\" alt = \"Public\" /></center>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    }
                    // line 47
                    echo " 
\t\t\t\t\t\t\t\t\t\t\t\t";
                    // line 48
                    if (($this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "type") === "Vote réservé aux inscrits")) {
                        // line 49
                        echo "\t\t\t\t\t\t\t\t\t\t\t\t\t<center><img src = \"";
                        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/rai.png"), "html", null, true);
                        echo "\" alt = \"Réservé aux inscrits\" /></center>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    }
                    // line 51
                    echo "\t\t\t\t\t\t\t\t\t\t\t\t";
                    if (($this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "type") === "Vote privé")) {
                        // line 52
                        echo "\t\t\t\t\t\t\t\t\t\t\t\t\t<center><img src = \"";
                        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("img/pri.png"), "html", null, true);
                        echo "\" alt = \"Privé\" /></center>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    }
                    // line 54
                    echo "\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                    // line 56
                    echo twig_escape_filter($this->env, $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "nom"), "html", null, true);
                    echo "</a></center>
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                    // line 59
                    echo twig_escape_filter($this->env, $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "texte"), "html", null, true);
                    echo " </center>
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                    // line 62
                    echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "dateDeCreation"), "d/m/y"), "html", null, true);
                    echo "</center>
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                    // line 65
                    echo twig_escape_filter($this->env, twig_date_format_filter($this->env, $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "dateDeFin"), "d/m/y"), "html", null, true);
                    echo "</center>
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    // line 68
                    if ((!(null === $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "groupeAssocie")))) {
                        // line 69
                        echo "\t\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                        echo twig_escape_filter($this->env, $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "groupeAssocie"), "html", null, true);
                        echo "</center>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    }
                    // line 71
                    echo "\t\t\t\t\t\t\t\t\t\t\t\t";
                    if ((null === $this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "groupeAssocie"))) {
                        // line 72
                        echo "\t\t\t\t\t\t\t\t\t\t\t\t\t<center>Aucun</center>
\t\t\t\t\t\t\t\t\t\t\t\t";
                    }
                    // line 74
                    echo "\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t\t\t\t\t\t\t<td>
\t\t\t\t\t\t\t\t\t\t\t\t<center>";
                    // line 76
                    if (($this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "etat") === true)) {
                        echo " En cours ";
                    }
                    echo " ";
                    if (($this->getAttribute((isset($context["vote"]) ? $context["vote"] : $this->getContext($context, "vote")), "etat") === false)) {
                        echo " Terminé ";
                    }
                    echo "</center>
\t\t\t\t\t\t\t\t\t\t\t</td>
\t\t\t\t\t
\t\t\t\t\t\t\t\t\t\t\t";
                    // line 79
                    $context["cpt"] = ((isset($context["cpt"]) ? $context["cpt"] : $this->getContext($context, "cpt")) + 1);
                    // line 80
                    echo "\t\t\t\t\t\t\t\t\t\t
\t\t\t\t\t\t\t\t\t</tr>
\t\t\t\t\t\t\t\t";
                }
                $_parent = $context['_parent'];
                unset($context['_seq'], $context['_iterated'], $context['_key'], $context['vote'], $context['_parent'], $context['loop']);
                $context = array_intersect_key($context, $_parent) + $_parent;
                // line 83
                echo "\t\t\t\t\t\t\t</table>
\t\t\t\t\t\t";
            }
            // line 85
            echo "\t\t\t\t\t";
        }
        // line 86
        echo "\t\t\t\t</div>
\t\t\t</td>
\t\t</tr>
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
        return array (  199 => 86,  196 => 85,  192 => 83,  184 => 80,  182 => 79,  170 => 76,  166 => 74,  162 => 72,  159 => 71,  153 => 69,  151 => 68,  145 => 65,  139 => 62,  133 => 59,  127 => 56,  123 => 54,  117 => 52,  114 => 51,  108 => 49,  106 => 48,  103 => 47,  97 => 46,  95 => 45,  89 => 43,  84 => 42,  82 => 41,  55 => 16,  52 => 15,  50 => 14,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
