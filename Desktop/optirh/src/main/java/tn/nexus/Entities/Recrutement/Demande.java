package tn.nexus.Entities.Recrutement;

import java.sql.Date;
import java.sql.Timestamp;

public class Demande {

    private int id;
    private int utilisateurId;
    private int offreId;

    public enum Statut {
        ACCEPTEE,
        REFUSEE,
        EN_ATTENTE;
    }
    private Statut statut; // "En attente", "Acceptée", "Refusée"
    private Timestamp date;
    private String description;
    private String fichierPieceJointe;
    private String nomComplet;
    private String email;
    private String telephone;
    private String adresse;
    private Date dateDebutDisponible;
    private String situationActuelle;

    public Demande() {}

    public Demande(int id, int utilisateurId, int offreId, Statut statut, Timestamp date, String description, String fichierPieceJointe, String nomComplet, String email, String telephone, String adresse, Date dateDebutDisponible, String situationActuelle) {
        this.id = id;
        this.utilisateurId = utilisateurId;
        this.offreId = offreId;
        this.statut = statut;
        this.date = date;
        this.description = description;
        this.fichierPieceJointe = fichierPieceJointe;
        this.nomComplet = nomComplet;
        this.email = email;
        this.telephone = telephone;
        this.adresse = adresse;
        this.dateDebutDisponible = dateDebutDisponible;
        this.situationActuelle = situationActuelle;
    }

    public Demande(int utilisateurId, int offreId, Statut statut, Timestamp date, String description, String fichierPieceJointe, String nomComplet, String email, String telephone, String adresse, Date dateDebutDisponible, String situationActuelle) {
        this.utilisateurId = utilisateurId;
        this.offreId = offreId;
        this.statut = statut;
        this.date = date;
        this.description = description;
        this.fichierPieceJointe = fichierPieceJointe;
        this.nomComplet = nomComplet;
        this.email = email;
        this.telephone = telephone;
        this.adresse = adresse;
        this.dateDebutDisponible = dateDebutDisponible;
        this.situationActuelle = situationActuelle;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public int getUtilisateurId() {
        return utilisateurId;
    }

    public void setUtilisateurId(int utilisateurId) {
        this.utilisateurId = utilisateurId;
    }

    public int getOffreId() {
        return offreId;
    }

    public void setOffreId(int offreId) {
        this.offreId = offreId;
    }

    public Statut getStatut() {
        return statut;
    }

    public void setStatut(Statut statut) {
        this.statut = statut;
    }

    public Timestamp getDate() {
        return date;
    }

    public void setDate(Timestamp date) {
        this.date = date;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getFichierPieceJointe() {
        return fichierPieceJointe;
    }

    public void setFichierPieceJointe(String fichierPieceJointe) {
        this.fichierPieceJointe = fichierPieceJointe;
    }

    public String getNomComplet() {
        return nomComplet;
    }

    public void setNomComplet(String nomComplet) {
        this.nomComplet = nomComplet;
    }

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getTelephone() {
        return telephone;
    }

    public void setTelephone(String telephone) {
        this.telephone = telephone;
    }

    public String getAdresse() {
        return adresse;
    }

    public void setAdresse(String adresse) {
        this.adresse = adresse;
    }

    public Date getDateDebutDisponible() {
        return dateDebutDisponible;
    }

    public void setDateDebutDisponible(Date dateDebutDisponible) {
        this.dateDebutDisponible = dateDebutDisponible;
    }

    public String getSituationActuelle() {
        return situationActuelle;
    }

    public void setSituationActuelle(String situationActuelle) {
        this.situationActuelle = situationActuelle;
    }

    @Override
    public String toString() {
        return "Demande{" +
                "id=" + id +
                ", utilisateurId=" + utilisateurId +
                ", offreId=" + offreId +
                ", statut=" + statut +
                ", date=" + date +
                ", description='" + description + '\'' +
                ", fichierPieceJointe='" + fichierPieceJointe + '\'' +
                ", nomComplet='" + nomComplet + '\'' +
                ", email='" + email + '\'' +
                ", telephone='" + telephone + '\'' +
                ", adresse='" + adresse + '\'' +
                ", dateDebutDisponible=" + dateDebutDisponible +
                ", situationActuelle='" + situationActuelle + '\'' +
                '}';
    }
}

