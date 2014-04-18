<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GroupeUtilisateur
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\GroupeUtilisateurRepository")
 */
class GroupeUtilisateur
{
	/**
   * @ORM\Id
   * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Groupe")
   */
  private $groupe;

  /**
   * @ORM\Id
   * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Utilisateur", cascade={"remove"})
   * @ORM\JoinColumn(name="utilisateur_id", referencedColumnName="id", onDelete="CASCADE")
   */
  private $utilisateur;

    /**
     * @var boolean
     *
     * @ORM\Column(name="moderateur", type="boolean")
     */
    private $moderateur;

    /**
     * @var boolean
     *
     * @ORM\Column(name="accepte", type="boolean")
     */
    private $accepte;
	
	/**
	* @var string
	*
	* @ORM\Column(name="message", type = "text", nullable = true)
	*/
	private $message;
	
	public function __construct() {
		$this->message = NULL;
	}


  // Getter et setter pour l'entité Groupe
  public function setGroupe(\Votenmasse\VotenmasseBundle\Entity\Groupe $groupe)
  {
    $this->groupe = $groupe;
  }
  public function getGroupe()
  {
    return $this->groupe;
  }

  // Getter et setter pour l'entité Utilisateur
  public function setUtilisateur(\Votenmasse\VotenmasseBundle\Entity\Utilisateur $utilisateur)
  {
    $this->utilisateur = $utilisateur;
  }
  public function getUtilisateur()
  {
    return $this->utilisateur;
  }

    /**
     * Set moderateur
     *
     * @param boolean $moderateur
     * @return GroupeUtilisateur
     */
    public function setModerateur($moderateur)
    {
        $this->moderateur = $moderateur;

        return $this;
    }

    /**
     * Get moderateur
     *
     * @return boolean 
     */
    public function getModerateur()
    {
        return $this->moderateur;
    }

    /**
     * Set accepte
     *
     * @param boolean $accepte
     * @return GroupeUtilisateur
     */
    public function setAccepte($accepte)
    {
        $this->accepte = $accepte;

        return $this;
    }

    /**
     * Get accepte
     *
     * @return boolean 
     */
    public function getAccepte()
    {
        return $this->accepte;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return GroupeUtilisateur
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }
}
