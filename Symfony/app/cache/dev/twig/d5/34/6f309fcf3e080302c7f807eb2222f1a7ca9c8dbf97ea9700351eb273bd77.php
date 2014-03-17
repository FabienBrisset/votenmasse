<?php

/* ::layout.html.twig */
class __TwigTemplate_d5346f309fcf3e080302c7f807eb2222f1a7ca9c8dbf97ea9700351eb273bd77 extends Twig_Template
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
\t\t\t<p><button class=\"btn btn-small\"><a href=\"";
        // line 24
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">A propos</a></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
\t\t\t<button class=\"btn btn-small\"><a href=\"";
        // line 25
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Contacts</a></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
\t\t\t<button class=\"btn btn-small\"><a href=\"";
        // line 26
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Développeurs</a></button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
\t\t\t<button class=\"btn btn-small\"><a href=\"";
        // line 27
        echo $this->env->getExtension('routing')->getPath("votenmasse_votenmasse_accueil");
        echo "\">Aide</a></button></p>
\t\t\t<p><h5><i class=\"icon-envelope\"></i>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Votenmasse&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class=\"icon-envelope\"></i></h5></p>
\t\t</div>
\t  </footer>
    </div>

  ";
        // line 33
        $this->displayBlock('javascripts', $context, $blocks);
        // line 37
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

    // line 33
    public function block_javascripts($context, array $blocks = array())
    {
        // line 34
        echo "    <script src=\"//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js\"></script>
    <script src=\"";
        // line 35
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
        return array (  112 => 35,  109 => 34,  106 => 33,  102 => 18,  99 => 17,  92 => 9,  89 => 8,  83 => 6,  77 => 37,  75 => 33,  66 => 27,  62 => 26,  58 => 25,  54 => 24,  45 => 17,  37 => 11,  35 => 8,  23 => 1,  100 => 34,  97 => 33,  90 => 35,  88 => 33,  71 => 19,  67 => 18,  63 => 17,  59 => 16,  55 => 15,  47 => 19,  43 => 8,  40 => 7,  33 => 4,  30 => 6,  42 => 8,  39 => 7,  32 => 4,  29 => 3,);
    }
}
