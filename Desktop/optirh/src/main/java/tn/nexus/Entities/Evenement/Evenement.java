package tn.nexus.Entities.Evenement;

import java.time.LocalDate;
import java.time.LocalTime;

public class Evenement {

    private int idEvenement;
    private String titre;
    private String lieu;
    private String description;
    private double prix;
    private LocalDate dateDebut;
    private LocalDate dateFin;
    private String image;
    private LocalTime heure;
    private double longitude;
    private double latitude;
    private StatusEvenement status;

    // Constructeurs
    public Evenement() {}

    public Evenement(String titre, String lieu, String description, double prix, LocalDate dateDebut, LocalDate dateFin, String image, LocalTime heure, double Longitude, double Latitude) {

        this.titre = titre;
        this.lieu = lieu;
        this.description = description;
        this.prix = prix;
        this.dateDebut = dateDebut;
        this.dateFin = dateFin;
        this.image = image;
        this.heure = heure;
        this.longitude = Longitude;
        this.latitude = Latitude;

    }

    public Evenement(int idEvenement, String titre, String description, String lieu, double prix, LocalDate dateDebut, LocalDate dateFin, LocalTime heure, String image) {
        this.idEvenement = idEvenement;
        this.titre = titre;
        this.description = description;
        this.lieu = lieu;
        this.prix = prix;
        this.dateDebut = dateDebut;
        this.dateFin = dateFin;
        this.heure = heure;
        this.image = image;
    }

    public Evenement(int idEvenement, String titre, LocalDate dateDebut) {
        this.idEvenement = idEvenement;
        this.titre = titre;
        this.dateDebut = dateDebut;
    }

    public Evenement(String titre, String lieu, String description, double prix, String image, LocalDate dateDebut, LocalDate dateFin, LocalTime heure, double latitude, double longitude) {
        this.titre = titre;
        this.lieu = lieu;
        this.description = description;
        this.prix = prix;
        this.image = image;
        this.dateDebut = dateDebut;
        this.dateFin = dateFin;
        this.heure = heure;
        this.longitude = longitude;
        this.latitude = latitude;
    }


    // Getters et Setters
    public StatusEvenement getStatus() {
        return status;
    }

    public void setStatus(StatusEvenement status) {
        this.status = status;
    }

    public double getLatitude() {
        return latitude;
    }
    public void setLatitude(double latitude) {
        this.latitude = latitude;
    }
    public double getLongitude() {
        return longitude;
    }
    public void setLongitude(double longitude) {
        this.longitude = longitude;
    }

    public int getIdEvenement() {
        return idEvenement;
    }

    public void setIdEvenement(int idEvenement) {
        this.idEvenement = idEvenement;
    }

    public String getTitre() {
        return titre;
    }

    public void setTitre(String titre) {
        this.titre = titre;
    }

    public String getLieu() {
        return lieu;
    }

    public void setLieu(String lieu) {
        this.lieu = lieu;
    }

    public double getPrix() {
        return prix;
    }

    public void setPrix(double prix) {
        this.prix = prix;
    }

    public LocalDate getDateDebut() {
        return dateDebut;
    }

    public void setDateDebut(LocalDate dateDebut) {
        this.dateDebut = dateDebut;
    }

    public LocalDate getDateFin() {
        return dateFin;
    }

    public void setDateFin(LocalDate dateFin) {
        this.dateFin = dateFin;
    }

    public String getImage() {
        return image;
    }

    public void setImage(String image) {
        this.image = image;
    }

    public LocalTime getHeure() {
        return heure;
    }

    public void setHeure(LocalTime heure) {
        this.heure = heure;
    }
    public String getDescription() {
        return description;
    }
    public void setDescription(String description) {
        this.description = description;
    }

    @Override
    public String toString() {
        return "Evenement{" +
                "idEvenement=" + idEvenement +
                ", titre='" + titre + '\'' +
                ", lieu='" + lieu + '\'' +
                ", description='" + description + '\'' +
                ", prix=" + prix +
                ", dateDebut=" + dateDebut +
                ", dateFin=" + dateFin +
                ", image='" + image + '\'' +
                ", heure=" + heure +
                '}';
    }

    public void calculerStatus() {
        LocalDate today = LocalDate.now(); // Convertir now en LocalDate

        if (dateDebut.isBefore(today) && dateFin.isAfter(today)) {
            this.status = StatusEvenement.EN_COURS;
        } else if (dateFin.isBefore(today)) {
            this.status = StatusEvenement.TERMINE;
        } else {
            this.status = StatusEvenement.A_VENIR;
        }
    }


}
