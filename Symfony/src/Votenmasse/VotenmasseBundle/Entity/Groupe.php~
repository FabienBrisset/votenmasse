<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Groupe
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\GroupeRepository")
 */
class Groupe
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
     * @ORM\Column(name="nom", type="string", length=255)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="administrateur", type="string", length=255)
     */
    private $administrateur;

    /**
     * @var string
     *
     * @ORM\Column(name="etat", type="string", length=255)
     */
    private $etat;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255)
     */
    private $description;

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
     * Set nom
     *
     * @param string $nom
     * @return Groupe
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set administrateur
     *
     * @param string $administrateur
     * @return Groupe
     */
    public function setAdministrateur($administrateur)
    {
        $this->administrateur = $administrateur;

        return $this;
    }

    /**
     * Get administrateur
     *
     * @return string 
     */
    public function getAdministrateur()
    {
        return $this->administrateur;
    }

    /**
     * Set etat
     *
     * @param string $etat
     * @return Groupe
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat
     *
     * @return string 
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Groupe
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set utilisateurs
     *
     * @param integer $utilisateurs
     * @return Groupe
     */
    public function setUtilisateurs($utilisateurs)
    {
        $this->utilisateurs = $utilisateurs;

        return $this;
    }

    /**
     * Get utilisateurs
     *
     * @return integer 
     */
    public function getUtilisateurs()
    {
        return $this->utilisateurs;
    }

    /**
     * Set moderateurs
     *
     * @param boolean $moderateurs
     * @return Groupe
     */
    public function setModerateurs($moderateurs)
    {
        $this->moderateurs = $moderateurs;

        return $this;
    }

    /**
     * Get moderateurs
     *
     * @return boolean 
     */
    public function getModerateurs()
    {
        return $this->moderateurs;
    }
	
	/**
     * Set actif
     *
     * @param string $actif
     * @return Groupe
     */
    public function setActif($actif)
    {
        $this->actif = $actif;

        return $this;
    }

    /**
     * Get actif
     *
     * @return string 
     */
    public function getActif()
    {
        return $this->actif;
    }
}
