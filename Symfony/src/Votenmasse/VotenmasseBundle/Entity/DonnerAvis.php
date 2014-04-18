<?php

namespace Votenmasse\VotenmasseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DonnerAvis
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Votenmasse\VotenmasseBundle\Entity\DonnerAvisRepository")
 */
class DonnerAvis
{
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
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

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
     * @ORM\Column(name="choix3", type="string", length=255, nullable = true)
     */
    private $choix3;

    /**
     * @var string
     *
     * @ORM\Column(name="choix4", type="string", length=255, nullable = true)
     */
    private $choix4;

    /**
     * @var string
     *
     * @ORM\Column(name="choix5", type="string", length=255, nullable = true)
     */
    private $choix5;

    /**
     * @var string
     *
     * @ORM\Column(name="choix6", type="string", length=255, nullable = true)
     */
    private $choix6;

    /**
     * @var string
     *
     * @ORM\Column(name="choix7", type="string", length=255, nullable = true)
     */
    private $choix7;

    /**
     * @var string
     *
     * @ORM\Column(name="choix8", type="string", length=255, nullable = true)
     */
    private $choix8;

    /**
     * @var string
     *
     * @ORM\Column(name="choix9", type="string", length=255, nullable = true)
     */
    private $choix9;

    /**
     * @var string
     *
     * @ORM\Column(name="choix10", type="string", length=255, nullable = true)
     */
    private $choix10;
	
	public function __construct() {
		$this->date = date_create(date('Y-m-d'));
	}


    // Getter et setter pour l'entité Vote
	  public function setVote(\Votenmasse\VotenmasseBundle\Entity\Vote $vote) {
		$this->vote = $vote;
	  }
	  public function getVote() {
		return $this->vote;
	  }

    // Getter et setter pour l'entité Utilisateur
	  public function setUtilisateur(\Votenmasse\VotenmasseBundle\Entity\Utilisateur $utilisateur) {
		$this->utilisateur = $utilisateur;
	  }
	  public function getUtilisateur() {
		return $this->utilisateur;
	  }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return DonnerAvis
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set choix1
     *
     * @param string $choix1
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
     * @return DonnerAvis
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
}
