package tn.nexus.Entities.reclamation;

import java.util.Date;

public class Reponse {
    private int id;
    private String description;
    private Date date;
    private int reclamationId;

    public Reponse() {
    }

    public Reponse(int id, String description, Date date, int reclamationId) {
        this.id = id;
        this.description = description;
        this.date = date;
        this.reclamationId = reclamationId;
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

    @Override
    public String toString() {
        return "Reponse{" +
                "id=" + id +
                ", description='" + description + '\'' +
                ", date=" + date +
                ", reclamationId=" + reclamationId +
                '}';
    }
}