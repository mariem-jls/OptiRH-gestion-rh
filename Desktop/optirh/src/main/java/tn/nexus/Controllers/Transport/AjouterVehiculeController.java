package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.paint.Color;
import javafx.stage.Stage;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.Transport.VehiculeService;

import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class AjouterVehiculeController {

    @FXML private ComboBox<String> disponibiliteCombo;
    @FXML private ComboBox<String> typeCombo;
    @FXML private TextField nbrPlaceField;
    @FXML private Label errorMessage;

    private int trajetId; // ID du trajet associé
    private Runnable onAjoutSuccess; // Callback pour rafraîchir la table après l'ajout
    private final VehiculeService vehiculeService = new VehiculeService();

    // Méthode pour initialiser l'ID du trajet
    public void setTrajetId(int trajetId) {
        this.trajetId = trajetId;
    }

    // Méthode pour définir le callback après l'ajout
    public void setOnAjoutSuccess(Runnable onAjoutSuccess) {
        this.onAjoutSuccess = onAjoutSuccess;
    }

    // Gérer l'ajout d'un véhicule
    @FXML
    public void handleAjouterVehicule() {
        List<String> errors = validateFields();

        if (!errors.isEmpty()) {
            showErrors(errors);
            return;
        }

        String disponibilite = disponibiliteCombo.getValue();
        String type = typeCombo.getValue();
        int nbrPlace = Integer.parseInt(nbrPlaceField.getText());
        Vehicule vehicule = new Vehicule(0, disponibilite, type, nbrPlace, trajetId, 0);

        try {
            int result = vehiculeService.insert(vehicule);
            if (result > 0) {
                showSuccess("Véhicule ajouté avec succès !");
                if (onAjoutSuccess != null) {
                    onAjoutSuccess.run(); // Rafraîchir la table dans l'interface principale
                }
                closeWindow();
            } else {
                showError("Erreur lors de l'ajout du véhicule.");
            }
        } catch (SQLException e) {
            showError("Erreur de base de données : " + e.getMessage());
        }
    }

    // Gérer l'annulation
    @FXML
    public void handleAnnuler() {
        closeWindow();
    }

    // Valider les champs
    private List<String> validateFields() {
        List<String> errors = new ArrayList<>();

        if (disponibiliteCombo.getValue() == null) {
            errors.add("La disponibilité doit être sélectionnée.");
        }
        if (typeCombo.getValue().isEmpty()) {
            errors.add("Le type de véhicule est obligatoire.");
        }
        if (nbrPlaceField.getText().isEmpty()) {
            errors.add("Le nombre de places est obligatoire.");
        }

        return errors;
    }

    // Afficher les erreurs
    private void showErrors(List<String> errors) {
        StringBuilder errorMessageText = new StringBuilder();
        for (String error : errors) {
            errorMessageText.append("- ").append(error).append("\n");
        }
        showError(errorMessageText.toString());
    }

    // Afficher un message d'erreur
    private void showError(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(Color.RED);
        errorMessage.setVisible(true);
    }

    // Afficher un message de succès
    private void showSuccess(String message) {
        errorMessage.setText(message);
        errorMessage.setTextFill(Color.GREEN);
        errorMessage.setVisible(true);
    }

    // Fermer la fenêtre
    private void closeWindow() {
        Stage stage = (Stage) typeCombo.getScene().getWindow();
        stage.close();
    }
}