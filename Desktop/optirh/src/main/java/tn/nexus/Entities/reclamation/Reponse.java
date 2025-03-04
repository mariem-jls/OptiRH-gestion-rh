package tn.nexus.Entities.reclamation;

import java.sql.Date;

public class Reponse {
    private int id;
    private String description;
    private Date date;
    private int reclamationId;
    private int rating;  // Si vous souhaitez gérer des ratings

    // Constructeur par défaut
    public Reponse() {
    }

    // Ajout du constructeur avec 5 paramètres
    public Reponse(int id, String description, Date date, int reclamationId, int rating) {
        this.id = id;
        this.description = description;
        this.date = date;
        this.reclamationId = reclamationId;
        this.rating = rating;
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

    public Date getDate() {
        return date;
    }

    public void setDate(Date date) {
        this.date = date;
    }

    public int getReclamationId() {
        return reclamationId;
    }

    public void setReclamationId(int reclamationId) {
        this.reclamationId = reclamationId;
    }

    public int getRating() {
        return rating;
    }

    public void setRating(int rating) {
        this.rating = rating;
    }

    @Override
    public String toString() {
        return "Reponse{" +
                "id=" + id +
                ", description='" + description + '\'' +
                ", date=" + date +
                ", reclamationId=" + reclamationId +
                ", rating=" + rating +
                '}';
    }
}
