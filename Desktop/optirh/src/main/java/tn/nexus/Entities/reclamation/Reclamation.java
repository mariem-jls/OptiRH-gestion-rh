package tn.nexus.Entities.reclamation;


import java.sql.Date;

public class Reclamation {
    private int id;
    private String description;
    private String status;
    private Date date;
    private int utilisateurId;

    public Reclamation(String description, String status, Date date, int utilisateurId) {
        this.description = description;
        this.status = status;
        this.date = date;
        this.utilisateurId = utilisateurId;
    }

    public Reclamation(int id, String description, String status, Date date, int utilisateurId) {
        this.id = id;
        this.description = description;
        this.status = status;
        this.date = date;
        this.utilisateurId = utilisateurId;
    }

    // Getters et Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public String getStatus() {
        return status;
    }

    public void setStatus(String status) {
        this.status = status;
    }

    public Date getDate() {
        return date;
    }

    public void setDate(Date date) {
        this.date = date;
    }

    public int getUtilisateurId() {
        return utilisateurId;
    }

    public void setUtilisateurId(int utilisateurId) {
        this.utilisateurId = utilisateurId;
    }
}