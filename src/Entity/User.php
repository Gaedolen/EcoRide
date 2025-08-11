<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Role $role = null;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Voiture::class)]
    private Collection $voitures;

    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: Covoiturage::class)]
    private Collection $covoiturages;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’email est obligatoire.')]
    #[Assert\Email(message: "L'adresse email '{{ value }}' n'est pas valide.")]    
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le pseudo est obligatoire.')]
    private ?string $pseudo = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $prenom = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'L’adresse est obligatoire.')]
    private ?string $adresse = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Le numéro de téléphone est obligatoire.')]
    private ?string $telephone = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $photo;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'La date de naissance est requise.')]
    #[Assert\LessThan([
        'value' => 'today -18 years',
    ])]
     private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isChauffeur = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isPassager = false;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $note = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $isSuspended = false;

    #[ORM\OneToMany(mappedBy: 'cible', targetEntity: Avis::class)]
    private Collection $avisRecus;

    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Avis::class)]
    private Collection $avisDonnes;

        // Setter et Getter

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = [];

        if ($this->role !== null) {
            $roles[] = 'ROLE_' . strtoupper($this->role->getLibelle());
        }

        $roles[] = 'ROLE_USER'; // exige que chaque utilisateur ait au moins le ROLE_USER

        return array_unique($roles);
    }


    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return resource|null
     */
    public function getPhoto()
    {
        return $this->photo;
    }

    /**
     * @param resource|string|null $photo
     */
    public function setPhoto($photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function isChauffeur(): bool
    {
        return $this->isChauffeur ?? false;
    }

    public function setIsChauffeur(bool $isChauffeur): self
    {
        $this->isChauffeur = $isChauffeur;
        return $this;
    }

    public function isPassager(): bool
    {
        return $this->isPassager ?? false;
    }

    public function setIsPassager(bool $isPassager): self
    {
        $this->isPassager = $isPassager;
        return $this;
    }

    public function __construct()
    {
        $this->voitures = new ArrayCollection();
        $this->covoiturages = new ArrayCollection();
        $this->avisRecus = new ArrayCollection();
        $this->avisDonnes = new ArrayCollection();
    }

    public function getVoitures(): Collection
    {
        return $this->voitures;
    }

    public function getCovoiturages(): Collection
    {
        return $this->covoiturages;
    }

    public function addCovoiturage(Covoiturage $covoiturage): static
    {
        if (!$this->covoiturages->contains($covoiturage)) {
            $this->covoiturages[] = $covoiturage;
            $covoiturage->setUtilisateur($this);
        }

        return $this;
    }

    public function removeCovoiturage(Covoiturage $covoiturage): static
    {
        if ($this->covoiturages->removeElement($covoiturage)) {
            if ($covoiturage->getUtilisateur() === $this) {
                $covoiturage->setUtilisateur(null);
            }
        }

        return $this;
    }

    public function getPhotoData(): ?string
    {
        if ($this->photo === null) {
            return null;
        }

        if (is_resource($this->photo)) {
            rewind($this->photo);
            $photoContent = stream_get_contents($this->photo);
        } else {
            $photoContent = $this->photo;
        }

        return base64_encode($photoContent);
    }

    public function getNote(): ?float
    {
        return $this->note;
    }

    public function setNote(?float $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->isSuspended ?? false;
    }

    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;
        return $this;
    }

    public function getAvisRecus(): Collection
    {
        return $this->avisRecus;
    }

    public function getAvisDonnes(): Collection
    {
        return $this->avisDonnes;
    }

    public function addAvisRecu(Avis $avis): self
    {
        if (!$this->avisRecus->contains($avis)) {
            $this->avisRecus[] = $avis;
            $avis->setCible($this);
        }

        return $this;
    }

    public function removeAvisRecu(Avis $avis): self
    {
        if ($this->avisRecus->removeElement($avis)) {
            if ($avis->getCible() === $this) {
                $avis->setCible(null);
            }
        }

        return $this;
    }

    public function addAvisDonne(Avis $avis): self
    {
        if (!$this->avisDonnes->contains($avis)) {
            $this->avisDonnes[] = $avis;
            $avis->setAuteur($this);
        }

        return $this;
    }

    public function removeAvisDonne(Avis $avis): self
    {
        if ($this->avisDonnes->removeElement($avis)) {
            // set the owning side to null (unless already changed)
            if ($avis->getAuteur() === $this) {
                $avis->setAuteur(null);
            }
        }

        return $this;
    }
}
