package tn.nexus.Entities.Mission;
import java.sql.Timestamp;
import java.util.ArrayList;
import java.util.List;

public class Projet {
    private int id;
    private String nom;
    private String description;
    private Timestamp createdAt;
    private int createdBy; // Référence à l'utilisateur qui a créé le projet
    private String userNom;
    private List<Mission> missions = new ArrayList<>();

    // Constructeurs
    public Projet() {}

    public Projet(int id, String nom, String description, Timestamp createdAt, int createdBy) {
        this.id = id;
        this.nom = nom;
        this.description = description;
        this.createdAt = createdAt;
        this.createdBy = createdBy;
    }
    public Projet(int id, String nom, String description, Timestamp createdAt, String userNom) {
        this.id = id;
        this.nom = nom;
        this.description = description;
        this.createdAt = createdAt;
        this.userNom = userNom;
    }
    public Projet(String nom, String description, int createdBy) {
        this.nom = nom;
        this.description = description;
        this.createdBy = createdBy;
    }
    // Méthode pour ajouter des missions
    public void addMission(Mission mission) {
        this.missions.add(mission);
        mission.setProjectId(this.id); // Maintient la cohérence bidirectionnelle
    }

    // Getter/Setter pour la liste complète
    public List<Mission> getMissions() {
        return new ArrayList<>(missions); // Retourne une copie non modifiable
    }

    public void setMissions(List<Mission> missions) {
        this.missions.clear();
        this.missions.addAll(missions);
    }



    // Getters et Setters
    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }

    public Timestamp getCreatedAt() {
        return createdAt;
    }

    public void setCreatedAt(Timestamp createdAt) {
        this.createdAt = createdAt;
    }

    public int getCreatedBy() {
        return createdBy;
    }

    public void setCreatedBy(int createdBy) {
        this.createdBy = createdBy;
    }

    @Override
    public String toString() {
        return "Project{" +
                "id=" + id +
                ", nom='" + nom + '\'' +
                '}';
    }
    public String getUserNom() {
        return userNom;
    }

    public void setUserNom(String userNom) {
        this.userNom = userNom;
    }
}