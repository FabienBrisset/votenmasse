<?php

/* ::layout.html.twig */
class __TwigTemplate_0d5df4d23f1d12f9b12c83bc09d0447f7b7316abfd9b36aaaf7470dc270518af extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'title' => array($this, 'block_title'),
            'stylesheets' => array($this, 'block_stylesheets'),
            'body' => array($this, 'block_body'),
            'javascripts' => array($this, 'block_javascripts'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        // line 1
        echo "<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />

    <title>";
        // line 6
        $this->displayBlock('title', $context, $blocks);
        echo "</title>

    ";
        // line 8
        $this->displayBlock('stylesheets', $context, $blocks);
        // line 11
        echo "  </head>

  <body>
    <div class=\"container\">
      <div class=\"row\">
        <div id=\"content\">
          ";
        // line 17
        $this->displayBlock('body', $context, $blocks);
        // line 19
        echo "        </div>
      </div>

      <footer class = \"row\">
\t\t<div align = \"center\" class = \"well\">
\t\t\t<p><button style = \"margin-right: 20px\" class=\"btn btn-small\"><a href=\"";
        // line 24
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">A propos</a></button>
\t\t\t<button style = \"margin-right: 20px\" class=\"btn btn-small\"><a href=\"";
        // line 25
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Contacts</a></button>
\t\t\t<button style = \"margin-right: 20px\" class=\"btn btn-small\"><a href=\"";
        // line 26
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Développeurs</a></button>
\t\t\t<button class=\"btn btn-small\"><a href=\"";
        // line 27
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Aide</a></button></p>
\t\t\t<p><h5><i style = \"margin-right: 10px\" class=\"icon-envelope\"></i>Votenmasse<i style = \"margin-left: 10px\" class=\"icon-envelope\"></i></h5></p><br>
\t\t\t<p><h6><a href=\"";
        // line 29
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_administration");
        echo "\">Espace réservé à l'administration</a></h6></p>
\t\t</div>
\t  </footer>
    </div>

  ";
        // line 34
        $this->displayBlock('javascripts', $context, $blocks);
        // line 38
        echo "
  </body>
</html>";
    }

    // line 6
    public function block_title($context, array $blocks = array())
    {
        echo "Votenmasse";
    }

    // line 8
    public function block_stylesheets($context, array $blocks = array())
    {
        // line 9
        echo "      <link rel=\"stylesheet\" href=\"";
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("css/bootstrap.css"), "html", null, true);
        echo "\" type=\"text/css\" />
    ";
    }

    // line 17
    public function block_body($context, array $blocks = array())
    {
        // line 18
        echo "          ";
    }

    // line 34
    public function block_javascripts($context, array $blocks = array())
    {
        // line 35
        echo "    <script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\"></script>
    <script src=\"";
        // line 36
        echo twig_escape_filter($this->env, $this->env->getExtension('assets')->getAssetUrl("js/bootstrap.js"), "html", null, true);
        echo "\"></script>
  ";
    }

    public function getTemplateName()
    {
        return "::layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  116 => 36,  110 => 34,  93 => 8,  87 => 6,  81 => 38,  79 => 34,  62 => 26,  58 => 25,  37 => 11,  35 => 8,  23 => 1,  142 => 53,  139 => 52,  130 => 52,  121 => 45,  118 => 44,  106 => 18,  103 => 17,  96 => 9,  91 => 30,  88 => 29,  73 => 20,  71 => 29,  67 => 18,  59 => 16,  55 => 15,  47 => 19,  43 => 8,  40 => 7,  33 => 4,  30 => 6,  136 => 53,  132 => 54,  128 => 51,  122 => 47,  113 => 35,  109 => 40,  104 => 37,  101 => 36,  98 => 35,  89 => 29,  85 => 28,  80 => 25,  77 => 24,  75 => 23,  66 => 27,  63 => 17,  57 => 13,  54 => 24,  51 => 11,  45 => 17,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
