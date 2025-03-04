package tn.nexus.Entities.Mission;

import java.sql.Timestamp;
import java.time.LocalDate;
import java.util.ArrayList;
import java.util.List;

public class Mission {
    private int id;
    private String titre;
    private String description;
    private String status; // To Do, In Progress, Done
    private int projectId; // Référence au projet
    private int assignedTo; // Référence à l'utilisateur assigné
    private Timestamp createdAt;
    private Timestamp updatedAt;
    private Timestamp dateTerminer; // Nouvel attribut

    public Mission(int id, String titre, String description, String status, int projectId, int assignedTo, Timestamp createdAt, Timestamp updatedAt, Timestamp dateTerminer) {
        this.id = id;
        this.titre = titre;
        this.description = description;
        this.status = status;
        this.projectId = projectId;
        this.assignedTo = assignedTo;
        this.createdAt = createdAt;
        this.updatedAt = updatedAt;
        this.dateTerminer = dateTerminer;
    }
    // Constructeurs
    public Mission() {}

    public Mission(int id, String titre, String description, String status, int projectId, int assignedTo, Timestamp createdAt, Timestamp updatedAt) {
        this.id = id;
        this.titre = titre;
        this.description = description;
        this.status = status;
        this.projectId = projectId;
        this.assignedTo = assignedTo;
        this.createdAt = createdAt;
        this.updatedAt = updatedAt;
    }

    public Mission(LocalDate createdAt, LocalDate dateTerminer, String s) {

    }

    // Getters et Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getTitre() {
        return titre;
    }

    public void setTitre(String titre) {
        this.titre = titre;
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

    public int getProjectId() {
        return projectId;
    }

    public void setProjectId(int projectId) {
        this.projectId = projectId;
    }

    public int getAssignedTo() {
        return assignedTo;
    }

    public void setAssignedTo(int assignedTo) {
        this.assignedTo = assignedTo;
    }

    public Timestamp getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(Timestamp createdAt) {
        this.createdAt = createdAt;
    }

    public Timestamp getUpdatedAt() {
        return updatedAt;
    }

    public void setUpdatedAt(Timestamp updatedAt) {
        this.updatedAt = updatedAt;
    }

    @Override
    public String toString() {
        return "Mission{" +
                "id=" + id +
                ", titre='" + titre + '\'' +
                ", status='" + status + '\'' +
                '}';
    }

    public Timestamp getDateTerminer() { return dateTerminer; }
    public void setDateTerminer(Timestamp dateTerminer) { this.dateTerminer = dateTerminer; }
}

