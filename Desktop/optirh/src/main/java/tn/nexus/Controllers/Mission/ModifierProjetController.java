package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.ProjetService;

public class ModifierProjetController {
        @FXML
        private TextField nomField;

        @FXML
        private TextArea descriptionField;

        private Projet projet;
        private ProjetService projetService = new ProjetService();

        // Méthode pour définir le projet à modifier
        public void setProjet(Projet projet) {
            this.projet = projet;
            nomField.setText(projet.getNom());
            descriptionField.setText(projet.getDescription());
        }

        @FXML
        public void handleSave() {
            // Mettre à jour les détails du projet
            projet.setNom(nomField.getText().trim());
            projet.setDescription(descriptionField.getText().trim());

            try {
                // Mettre à jour le projet dans la base de données
                projetService.update(projet);

                // Fermer la fenêtre de modification
                nomField.getScene().getWindow().hide();
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

