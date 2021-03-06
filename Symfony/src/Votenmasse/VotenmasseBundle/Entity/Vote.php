<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vote
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\VoteRepository")
 */
class Vote
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
     * @ORM\Column(name="texte", type="string", length=255)
     */
    private $texte;

    /**
     * @var string
     *
     * @ORM\Column(name="choix1", type="string", length=255)
     */
    private $choix1;

    /**
     * @var string
     *
     * @ORM\Column(name="choix2", type="string", length=255)
     */
    private $choix2;

    /**
     * @var string
     *
     * @ORM\Column(name="choix3", type="string", length=255, nullable=true)
     */
    private $choix3;

    /**
     * @var string
     *
     * @ORM\Column(name="choix4", type="string", length=255, nullable=true)
     */
    private $choix4;

    /**
     * @var string
     *
     * @ORM\Column(name="choix5", type="string", length=255, nullable=true)
     */
    private $choix5;

    /**
     * @var string
     *
     * @ORM\Column(name="choix6", type="string", length=255, nullable=true)
     */
    private $choix6;

    /**
     * @var string
     *
     * @ORM\Column(name="choix7", type="string", length=255, nullable=true)
     */
    private $choix7;

    /**
     * @var string
     *
     * @ORM\Column(name="choix8", type="string", length=255, nullable=true)
     */
    private $choix8;

    /**
     * @var string
     *
     * @ORM\Column(name="choix9", type="string", length=255, nullable=true)
     */
    private $choix9;

    /**
     * @var string
     *
     * @ORM\Column(name="choix10", type="string", length=255, nullable=true)
     */
    private $choix10;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_de_creation", type="date")
     */
    private $dateDeCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_de_fin", type="date")
     */
    private $dateDeFin;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=255)
     */
    private $type;

    /**
	   * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Groupe")
	   */
    private $groupeAssocie;

    /**
     * @var boolean
     *
     * @ORM\Column(name="etat", type="boolean")
     */
    private $etat;

    /**
	  * @ORM\ManyToOne(targetEntity="Votenmasse\VotenmasseBundle\Entity\Utilisateur")
	  */
    private $createur;
	
	public function __construct() {
		$this->dateDeCreation = date_create(date('Y-m-d'));
		$this->dateDeFin = date_create(date('Y-m-d'));
		$this->etat = true;
	}


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
     * @return Vote
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
     * Set texte
     *
     * @param string $texte
     * @return Vote
     */
    public function setTexte($texte)
    {
        $this->texte = $texte;

        return $this;
    }

    /**
     * Get texte
     *
     * @return string 
     */
    public function getTexte()
    {
        return $this->texte;
    }

    /**
     * Set choix1
     *
     * @param string $choix1
     * @return Vote
     */
    public function setChoix1($choix1)
    {
        $this->choix1 = $choix1;

        return $this;
    }

    /**
     * Get choix1
     *
     * @return string 
     */
    public function getChoix1()
    {
        return $this->choix1;
    }

    /**
     * Set choix2
     *
     * @param string $choix2
     * @return Vote
     */
    public function setChoix2($choix2)
    {
        $this->choix2 = $choix2;

        return $this;
    }

    /**
     * Get choix2
     *
     * @return string 
     */
    public function getChoix2()
    {
        return $this->choix2;
    }

    /**
     * Set choix3
     *
     * @param string $choix3
     * @return Vote
     */
    public function setChoix3($choix3)
    {
        $this->choix3 = $choix3;

        return $this;
    }

    /**
     * Get choix3
     *
     * @return string 
     */
    public function getChoix3()
    {
        return $this->choix3;
    }

    /**
     * Set choix4
     *
     * @param string $choix4
     * @return Vote
     */
    public function setChoix4($choix4)
    {
        $this->choix4 = $choix4;

        return $this;
    }

    /**
     * Get choix4
     *
     * @return string 
     */
    public function getChoix4()
    {
        return $this->choix4;
    }

    /**
     * Set choix5
     *
     * @param string $choix5
     * @return Vote
     */
    public function setChoix5($choix5)
    {
        $this->choix5 = $choix5;

        return $this;
    }

    /**
     * Get choix5
     *
     * @return string 
     */
    public function getChoix5()
    {
        return $this->choix5;
    }

    /**
     * Set choix6
     *
     * @param string $choix6
     * @return Vote
     */
    public function setChoix6($choix6)
    {
        $this->choix6 = $choix6;

        return $this;
    }

    /**
     * Get choix6
     *
     * @return string 
     */
    public function getChoix6()
    {
        return $this->choix6;
    }

    /**
     * Set choix7
     *
     * @param string $choix7
     * @return Vote
     */
    public function setChoix7($choix7)
    {
        $this->choix7 = $choix7;

        return $this;
    }

    /**
     * Get choix7
     *
     * @return string 
     */
    public function getChoix7()
    {
        return $this->choix7;
    }

    /**
     * Set choix8
     *
     * @param string $choix8
     * @return Vote
     */
    public function setChoix8($choix8)
    {
        $this->choix8 = $choix8;

        return $this;
    }

    /**
     * Get choix8
     *
     * @return string 
     */
    public function getChoix8()
    {
        return $this->choix8;
    }

    /**
     * Set choix9
     *
     * @param string $choix9
     * @return Vote
     */
    public function setChoix9($choix9)
    {
        $this->choix9 = $choix9;

        return $this;
    }

    /**
     * Get choix9
     *
     * @return string 
     */
    public function getChoix9()
    {
        return $this->choix9;
    }

    /**
     * Set choix10
     *
     * @param string $choix10
     * @return Vote
     */
    public function setChoix10($choix10)
    {
        $this->choix10 = $choix10;

        return $this;
    }

    /**
     * Get choix10
     *
     * @return string 
     */
    public function getChoix10()
    {
        return $this->choix10;
    }

    /**
     * Set dateFin
     *
     * @param \DateTime $dateFin
     * @return Vote
     */
    public function setDateDeFin($dateDeFin)
    {
        $this->dateDeFin = $dateDeFin;

        return $this;
    }

    /**
     * Get dateFin
     *
     * @return \DateTime 
     */
    public function getDateDeFin()
    {
        return $this->dateDeFin;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Vote
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set etat
     *
     * @param boolean $etat
     * @return Vote
     */
    public function setEtat($etat)
    {
        $this->etat = $etat;

        return $this;
    }

    /**
     * Get etat
     *
     * @return boolean 
     */
    public function getEtat()
    {
        return $this->etat;
    }

    /**
     * Set dateDeCreation
     *
     * @param \DateTime $dateDeCreation
     * @return Vote
     */
    public function setDateDeCreation($dateDeCreation)
    {
        $this->dateDeCreation = $dateDeCreation;

        return $this;
    }

    /**
     * Get dateDeCreation
     *
     * @return \DateTime 
     */
    public function getDateDeCreation()
    {
        return $this->dateDeCreation;
    }

    /**
     * Set groupeAssocie
     *
     * @param \Votenmasse\VotenmasseBundle\Entity\Groupe $groupeAssocie
     * @return Vote
     */
    public function setGroupeAssocie(\Votenmasse\VotenmasseBundle\Entity\Groupe $groupeAssocie = null)
    {
        $this->groupeAssocie = $groupeAssocie;

        return $this;
    }

    /**
     * Get groupeAssocie
     *
     * @return \Votenmasse\VotenmasseBundle\Entity\Groupe 
     */
    public function getGroupeAssocie()
    {
        return $this->groupeAssocie;
    }

    /**
     * Set createur
     *
     * @param \Votenmasse\VotenmasseBundle\Entity\Utilisateur $createur
     * @return Vote
     */
    public function setCreateur(\Votenmasse\VotenmasseBundle\Entity\Utilisateur $createur = null)
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * Get createur
     *
     * @return \Votenmasse\VotenmasseBundle\Entity\Utilisateur 
     */
    public function getCreateur()
    {
        return $this->createur;
    }
}
