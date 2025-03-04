package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Utils.Mission.EmailSender;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;
import java.time.LocalDateTime;

public class AjouterMissionController {

    @FXML
    private TextField titreField;
    private final EmailSender emailSender = new EmailSender();

    @FXML
    private TextArea descriptionField;
    @FXML
    private ComboBox<String> statusComboBox;
    @FXML
    private TextField assignedToField;
    @FXML
    private DatePicker dateTerminerPicker; // Nouveau champ pour la date de terminaison
    @FXML
    private Button saveButton;
    @FXML
    private Button cancelButton;

    private Projet projet; // Projet sélectionné
    private final MissionService missionService = new MissionService();

    @FXML
    public void initialize() {
        statusComboBox.getItems().addAll("To Do", "In Progress", "Done");
    }

    // Méthode pour définir le projet sélectionné
    public void setProjet(Projet projet) {
        this.projet = projet;
    }

    @FXML
    private void handleSave() {
        try {
            // Validation des champs
            if (titreField.getText().isEmpty() || descriptionField.getText().isEmpty() || statusComboBox.getValue() == null
                    || assignedToField.getText().isEmpty() || dateTerminerPicker.getValue() == null) {
                showAlert("Erreur", "Veuillez remplir tous les champs", Alert.AlertType.ERROR);
                return;
            }

            int assignedTo = Integer.parseInt(assignedToField.getText());
            LocalDate dateTerminer = dateTerminerPicker.getValue();

            // Création de la mission avec l'ID du projet sélectionné
            Mission newMission = new Mission(
                    0, // ID sera généré automatiquement par la base de données
                    titreField.getText(),
                    descriptionField.getText(),
                    statusComboBox.getValue(),
                    projet.getId(), // Utilisation directe de l'ID du projet
                    assignedTo,
                    Timestamp.valueOf(LocalDateTime.now()), // created_at
                    Timestamp.valueOf(LocalDateTime.now()), // updated_at
                    Timestamp.valueOf(dateTerminer.atStartOfDay()) // date_terminer
            );

            // Ajout de la mission
            missionService.addMission(newMission);
            String userEmail = missionService.getUserEmailByUserId(assignedTo);

            // Envoyer la notification
            if(userEmail != null) {
                emailSender.sendNewMissionNotification(newMission, userEmail);}
            showAlert("Succès", "Mission ajoutée avec succès", Alert.AlertType.INFORMATION);
            closeWindow();
        } catch (NumberFormatException e) {
            showAlert("Erreur", "Le champ 'Assigné à' doit être un nombre valide.", Alert.AlertType.ERROR);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible d'ajouter la mission : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    @FXML
    private void handleCancel() {
        closeWindow();
    }

    private void closeWindow() {
        Stage stage = (Stage) cancelButton.getScene().getWindow();
        stage.close();
    }

    private void showAlert(String title, String message, Alert.AlertType type) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}