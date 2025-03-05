package tn.nexus.Entities.Recrutement;

import javafx.beans.property.SimpleStringProperty;

public class ResultatAnalyse {
    private final SimpleStringProperty nom;
    private final SimpleStringProperty experience;
    private final SimpleStringProperty technologies;
    private final SimpleStringProperty linkedin;
    private final SimpleStringProperty matchPercentage;
    public ResultatAnalyse(String nom, String experience, String technologies, String linkedin, String matchPercentage) {
        this.nom = new SimpleStringProperty(nom);
        this.experience = new SimpleStringProperty(experience);
        this.technologies = new SimpleStringProperty(technologies);
        this.linkedin = new SimpleStringProperty(linkedin);
        this.matchPercentage = new SimpleStringProperty(matchPercentage);

    }

    // Getters pour les propriétés
    public String getNom() { return nom.get(); }
    public String getExperience() { return experience.get(); }
    public String getTechnologies() { return technologies.get(); }
    public String getLinkedin() { return linkedin.get(); }
    public String getMatchPercentage() { return matchPercentage.get(); }

    // Property getters
    public SimpleStringProperty nomProperty() { return nom; }
    public SimpleStringProperty experienceProperty() { return experience; }
    public SimpleStringProperty technologiesProperty() { return technologies; }
    public SimpleStringProperty linkedinProperty() { return linkedin; }
}
