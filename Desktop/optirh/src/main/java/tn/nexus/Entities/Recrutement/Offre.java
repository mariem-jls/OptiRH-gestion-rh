package tn.nexus.Entities.Recrutement;

import java.time.LocalDateTime;

public class Offre {

    private int id;
    private String poste;
    private String description;
    private String statut;
    private LocalDateTime dateCreation;
    private String modeTravail; // Présentiel, Hybride, Télétravail
    private String typeContrat; // CDI, CDD, Stage, Freelance...
    private String localisation; // Ville, pays ou télétravail
    private String niveauExperience; // Débutant, Junior, Senior...
    private int nbPostes; // Nombre de postes ouverts
    private LocalDateTime dateExpiration; // Date limite de candidature

    // Constructeurs
    public Offre() {}

    public Offre(int id, String poste, String description, String statut, LocalDateTime dateCreation,
                 String modeTravail, String typeContrat, String localisation, String niveauExperience,
                 int nbPostes, LocalDateTime dateExpiration) {
        this.id = id;
        this.poste = poste;
        this.description = description;
        this.statut = statut;
        this.dateCreation = dateCreation;
        this.modeTravail = modeTravail;
        this.typeContrat = typeContrat;
        this.localisation = localisation;
        this.niveauExperience = niveauExperience;
        this.nbPostes = nbPostes;
        this.dateExpiration = dateExpiration;
    }

    public Offre(String poste, String description, String statut, LocalDateTime dateCreation,
                 String modeTravail, String typeContrat, String localisation, String niveauExperience,
                 int nbPostes, LocalDateTime dateExpiration) {
        this.poste = poste;
        this.description = description;
        this.statut = statut;
        this.dateCreation = dateCreation;
        this.modeTravail = modeTravail;
        this.typeContrat = typeContrat;
        this.localisation = localisation;
        this.niveauExperience = niveauExperience;
        this.nbPostes = nbPostes;
        this.dateExpiration = dateExpiration;
    }

    // Getters et Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getPoste() {
        return poste;
    }

    public void setPoste(String poste) {
        this.poste = poste;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getStatut() {
        return statut;
    }

    public void setStatut(String statut) {
        this.statut = statut;
    }

    public LocalDateTime getDateCreation() {
        return dateCreation;
    }

    public void setDateCreation(LocalDateTime dateCreation) {
        this.dateCreation = dateCreation;
    }

    public String getModeTravail() {
        return modeTravail;
    }

    public void setModeTravail(String modeTravail) {
        this.modeTravail = modeTravail;
    }

    public String getTypeContrat() {
        return typeContrat;
    }

    public void setTypeContrat(String typeContrat) {
        this.typeContrat = typeContrat;
    }

    public String getLocalisation() {
        return localisation;
    }

    public void setLocalisation(String localisation) {
        this.localisation = localisation;
    }

    public String getNiveauExperience() {
        return niveauExperience;
    }

    public void setNiveauExperience(String niveauExperience) {
        this.niveauExperience = niveauExperience;
    }

    public int getNbPostes() {
        return nbPostes;
    }

    public void setNbPostes(int nbPostes) {
        this.nbPostes = nbPostes;
    }

    public LocalDateTime getDateExpiration() {
        return dateExpiration;
    }

    public void setDateExpiration(LocalDateTime dateExpiration) {
        this.dateExpiration = dateExpiration;
    }
    public boolean isActive() {
        return "Publiée".equalsIgnoreCase(this.statut);
    }

    @Override
    public String toString() {
        return "Offre{" +
                "id=" + id +
                ", poste='" + poste + '\'' +
                ", description='" + description + '\'' +
                ", statut='" + statut + '\'' +
                ", dateCreation=" + (dateCreation != null ? dateCreation : "null") +
                ", modeTravail='" + modeTravail + '\'' +
                ", typeContrat='" + typeContrat + '\'' +
                ", localisation='" + localisation + '\'' +
                ", niveauExperience='" + niveauExperience + '\'' +
                ", nbPostes=" + nbPostes +
                ", dateExpiration=" + (dateExpiration != null ? dateExpiration : "null") +
                '}';
    }
}
