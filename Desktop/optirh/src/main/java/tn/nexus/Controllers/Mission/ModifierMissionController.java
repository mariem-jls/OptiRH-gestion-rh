package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Services.Mission.MissionService;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;
import java.time.LocalDateTime;

public class ModifierMissionController {

    @FXML
    private TextField titreField;
    @FXML
    private TextArea descriptionField;
    @FXML
    private ComboBox<String> statusComboBox;
    @FXML
    private TextField assignedToField;
    @FXML
    private DatePicker dateTerminerPicker;
    @FXML
    private Button saveButton;
    @FXML
    private Button cancelButton;

    private Mission mission;
    private final MissionService missionService = new MissionService();

    // Méthode pour définir la mission à modifier
    public void setMission(Mission mission) {
        this.mission = mission;
        if (mission != null) {
            titreField.setText(mission.getTitre());
            descriptionField.setText(mission.getDescription());
            statusComboBox.setValue(mission.getStatus());
            assignedToField.setText(String.valueOf(mission.getAssignedTo()));
            if (mission.getDateTerminer() != null) {
                dateTerminerPicker.setValue(mission.getDateTerminer().toLocalDateTime().toLocalDate());
            }
        }
    }

    @FXML
    public void initialize() {
        statusComboBox.getItems().addAll("To Do", "In Progress", "Done");
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

            // Mettre à jour la mission
            mission.setTitre(titreField.getText());
            mission.setDescription(descriptionField.getText());
            mission.setStatus(statusComboBox.getValue());
            mission.setAssignedTo(Integer.parseInt(assignedToField.getText()));
            mission.setUpdatedAt(Timestamp.valueOf(LocalDateTime.now()));
            mission.setDateTerminer(Timestamp.valueOf(dateTerminerPicker.getValue().atStartOfDay()));

            missionService.update(mission);
            showAlert("Succès", "Mission mise à jour avec succès", Alert.AlertType.INFORMATION);
            closeWindow();
        } catch (NumberFormatException e) {
            showAlert("Erreur", "Le champ 'Assigné à' doit être un nombre valide.", Alert.AlertType.ERROR);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de mettre à jour la mission : " + e.getMessage(), Alert.AlertType.ERROR);
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