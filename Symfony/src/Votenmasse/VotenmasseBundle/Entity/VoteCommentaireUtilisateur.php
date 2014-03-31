<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * VoteCommentaireUtilisateur
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\VoteCommentaireUtilisateurRepository")
 */
class VoteCommentaireUtilisateur
{
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Vote")
     */
    private $vote;
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Commentaire")
     */
    private $commentaire;
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Utilisateur")
     */
    private $utilisateur;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateCreation", type="datetime")
     */
    private $dateCreation;
    
    public function setVote(\Votenmasse\VotenmasseBundle\Entity\Vote $vote)
    {
        $this->vote = $vote;
    
        //return $this;
    }

    public function getVote()
    {
        return $this->vote;
    }

    
    public function setUtilisateur(\Votenmasse\VotenmasseBundle\Entity\Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;
    
        return $this;
    }

    
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }
    
    public function setCommentaire(\Votenmasse\VotenmasseBundle\Entity\Commentaire $commentaire)
    {
        $this->commentaire = $commentaire;
    
        return $this;
    }

    
    public function getCommentaire()
    {
        return $this->commentaire;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     * @return VoteCommentaireUtilisateur
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;
    
        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime 
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }
    public function __construct() {
        $this->dateCreation = date_create('1990-01-01');
    }
}
