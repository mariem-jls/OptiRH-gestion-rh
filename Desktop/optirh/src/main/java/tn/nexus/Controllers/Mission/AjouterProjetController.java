package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Entities.User;
import tn.nexus.Services.Mission.ProjetService;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.Enums.Role;

public class AjouterProjetController {
    @FXML
    private TextField nomField;

    @FXML
    private TextArea descriptionField;

    @FXML
    private TextField emailField;

    private ProjetService projectService = new ProjetService();
    private UserService userService = new UserService();

    private Runnable onProjectAdded; // Callback pour notifier l'ajout d'un projet

    // Méthode pour définir le callback
    public void setOnProjectAdded(Runnable onProjectAdded) {
        this.onProjectAdded = onProjectAdded;
    }

    @FXML
    public void handleAddProject() {
        // Récupérer les données du formulaire
        String nom = nomField.getText().trim();
        String description = descriptionField.getText().trim();
        String email = emailField.getText().trim();

        // Contrôles de saisie
        if (nom.isEmpty() || description.isEmpty() || email.isEmpty()) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Veuillez remplir tous les champs.");
            return;
        }

        // Validation du format de l'email
        if (!isValidEmail(email)) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "L'email n'est pas valide.");
            return;
        }

        try {
            // Vérifier que l'utilisateur existe et a le rôle de Chef Projet
            User chefProjet = userService.getUserByEmailAndRole(email, Role.Chef_Projet);
            if (chefProjet == null) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Aucun chef de projet trouvé avec cet email.");
                return;
            }

            // Créer un nouveau projet
            Projet project = new Projet(nom, description, chefProjet.getId());

            // Insérer le projet dans la base de données
            projectService.insert(project);
            showAlert(Alert.AlertType.INFORMATION, "Succès", "Le projet a été ajouté avec succès.");
            clearForm();

            // Notifier le contrôleur principal que le projet a été ajouté
            if (onProjectAdded != null) {
                onProjectAdded.run();
            }

            // Fermer la fenêtre d'ajout
            Stage stage = (Stage) nomField.getScene().getWindow();
            stage.close();
        } catch (Exception e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Une erreur s'est produite : " + e.getMessage());
        }
    }

    // Méthode pour valider le format de l'email
    private boolean isValidEmail(String email) {
        String regex = "^[A-Za-z0-9+_.-]+@(.+)$";
        return email.matches(regex);
    }

    // Méthode pour afficher une alerte
    private void showAlert(Alert.AlertType alertType, String title, String message) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    // Méthode pour vider le formulaire
    private void clearForm() {
        nomField.clear();
        descriptionField.clear();
        emailField.clear();
    }
}