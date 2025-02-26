package tn.nexus.Entities.Evenement;

import java.time.LocalDate;

public class Reservation_evenement {

    private int idParticipation;
    private int idUser;
    private int idEvenement;
    private String firstName;
    private String lastName;
    private String email;
    private String telephone;
    private LocalDate dateReservation;
    private String titreEvenement;
    private LocalDate dateDebut;

    // Constructeur par défaut
    public Reservation_evenement() {}

    // Constructeur utilisé pour créer une réservation avec les informations de base d'un utilisateur
    public Reservation_evenement(String firstName, String lastName, String email, String telephone, LocalDate dateReservation) {
        this.firstName = firstName;
        this.lastName = lastName;
        this.email = email;
        this.telephone = telephone;
        this.dateReservation = dateReservation;
    }

    // Constructeur utilisé pour afficher une réservation avec le titre de l'événement
    public Reservation_evenement(String titreEvenement, String firstName, String lastName, String email, String telephone, LocalDate dateReservation) {
        this.titreEvenement = titreEvenement;
        this.firstName = firstName;
        this.lastName = lastName;
        this.email = email;
        this.telephone = telephone;
        this.dateReservation = dateReservation;
    }

    // Constructeur utilisé pour gérer une réservation complète avec tous les détails
    public Reservation_evenement(int idParticipation, int idUser, int idEvenement, String firstName, String lastName, String email, String telephone, LocalDate dateReservation, String titreEvenement, LocalDate dateDebut) {
        this.idParticipation = idParticipation;
        this.idUser = idUser;
        this.idEvenement = idEvenement;
        this.firstName = firstName;
        this.lastName = lastName;
        this.email = email;
        this.telephone = telephone;
        this.dateReservation = dateReservation;
        this.titreEvenement = titreEvenement;
        this.dateDebut = dateDebut;
    }

    // Constructeur utilisé pour des afficher la liste des reservation pour chaque evenement
   /* public Reservation_evenement(String lastName, String firstName, String telephone, String email) {
        this.firstName = firstName;
        this.lastName = lastName;
        this.telephone = telephone;
        this.email = email;
    }*/

    // Getters et Setters
    public int getIdParticipation() {
        return idParticipation;
    }

    public void setIdParticipation(int idParticipation) {
        this.idParticipation = idParticipation;
    }

    public int getIdUser() {
        return idUser;
    }

    public void setIdUser(int idUser) {
        this.idUser = idUser;
    }

    public int getIdEvenement() {
        return idEvenement;
    }

    public void setIdEvenement(int idEvenement) {
        this.idEvenement = idEvenement;
    }

    public String getFirstName() {
        return firstName;
    }

    public void setFirstName(String firstName) {
        this.firstName = firstName;
    }

    public String getLastName() {
        return lastName;
    }

    public void setLastName(String lastName) {
        this.lastName = lastName;
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

    public LocalDate getDateReservation() {
        return dateReservation;
    }

    public void setDateReservation(LocalDate dateReservation) {
        this.dateReservation = dateReservation;
    }

    public String getTitreEvenement() {
        return titreEvenement;
    }

    public void setTitreEvenement(String titreEvenement) {
        this.titreEvenement = titreEvenement;
    }

    public LocalDate getDateDebut() {
        return dateDebut;
    }

    public void setDateDebut(LocalDate dateDebut) {
        this.dateDebut = dateDebut;
    }

    @Override
    public String toString() {
        return "ReservationEvenement{" +
                "titreEvenement='" + titreEvenement + '\'' +
                ", firstName='" + firstName + '\'' +
                ", lastName='" + lastName + '\'' +
                ", email='" + email + '\'' +
                ", telephone='" + telephone + '\'' +
                ", dateReservation=" + dateReservation +
                '}';
    }
}
