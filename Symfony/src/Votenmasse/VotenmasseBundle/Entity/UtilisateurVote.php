<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UtilisateurVote
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\UtilisateurVoteRepository")
 */
class UtilisateurVote
{
	// UtilisateurVote = Modérateurs 

    /**
	  * @ORM\Id
	  * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Vote")
	  */
	private $vote;

	/**
	  * @ORM\Id
	  * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Utilisateur", cascade={"remove"})
	  * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="id", onDelete="CASCADE")
	  */
	private $utilisateur;



    /**
     * Set vote
     *
     * @param \Votenmasse\VotenmasseBundle\Entity\Vote $vote
     * @return UtilisateurVote
     */
    public function setVote(\Votenmasse\VotenmasseBundle\Entity\Vote $vote)
    {
        $this->vote = $vote;

        return $this;
    }

    /**
     * Get vote
     *
     * @return \Votenmasse\VotenmasseBundle\Entity\Vote 
     */
    public function getVote()
    {
        return $this->vote;
    }

    /**
     * Set utilisateur
     *
     * @param \Votenmasse\VotenmasseBundle\Entity\Utilisateur $utilisateur
     * @return UtilisateurVote
     */
    public function setUtilisateur(\Votenmasse\VotenmasseBundle\Entity\Utilisateur $utilisateur)
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Get utilisateur
     *
     * @return \Votenmasse\VotenmasseBundle\Entity\Utilisateur 
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }
}
