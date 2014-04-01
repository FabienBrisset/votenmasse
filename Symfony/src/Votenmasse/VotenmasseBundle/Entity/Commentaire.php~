<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Commentaire
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\CommentaireRepository")
 */
class Commentaire
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
     /**
     * @var string
     *
     * @ORM\Column(name="texteCommentaire", type="text")
     */
    private $texteCommentaire;
     /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Set texteCommentaires
     *
     * @param string $texte
     * @return Commentaire
     */
    public function setTexteCommentaire($texte)
    {
        $this->texteCommentaire = $texte;
    
        return $this;
    }
     /**
     * Get texteCommentaires
     *
     * @return string 
     */
    public function getTexteCommentaire()
    {
        return $this->texteCommentaire;
    }
}
