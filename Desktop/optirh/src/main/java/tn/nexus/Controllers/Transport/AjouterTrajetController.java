package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Services.Transport.TrajetService;

import java.sql.SQLException;

public class AjouterTrajetController {

    @FXML private ComboBox<String> typeCombo;
    @FXML private TextField stationField;
    @FXML private TextField departField;
    @FXML private TextField arriveField;
    @FXML private Label errorMessage;
    @FXML private TextField longitudeDepartField;
    @FXML private TextField latitudeDepartField;
    @FXML private TextField longitudeArriveeField;
    @FXML private TextField latitudeArriveeField;

    private Runnable onAjoutSuccess;
    private final TrajetService trajetService = new TrajetService();

    public void setOnAjoutSuccess(Runnable onAjoutSuccess) {
        this.onAjoutSuccess = onAjoutSuccess;
    }

    @FXML
    public void handleAjouter() {
        String type = typeCombo.getValue();
        String station = stationField.getText();
        String depart = departField.getText();
        String arrive = arriveField.getText();
        // Récupérer les valeurs des coordonnées depuis les TextField
        double longitudeDepart = Double.parseDouble(longitudeDepartField.getText());
        double latitudeDepart = Double.parseDouble(latitudeDepartField.getText());
        double longitudeArrivee = Double.parseDouble(longitudeArriveeField.getText());
        double latitudeArrivee = Double.parseDouble(latitudeArriveeField.getText());

        // Validation des champs (vous pouvez réutiliser les méthodes de validation existantes)
        if (type.isEmpty() || station.isEmpty() || depart.isEmpty() || arrive.isEmpty()) {
            errorMessage.setText("Tous les champs sont obligatoires.");
            errorMessage.setVisible(true);
            return;
        }

        // Créer un nouveau trajet
        Trajet trajet = new Trajet(0, type, station, depart, arrive, longitudeDepart, latitudeDepart, longitudeArrivee, latitudeArrivee);

        try {
            int result = trajetService.insert(trajet);
            if (result > 0) {
                if (onAjoutSuccess != null) {
                    onAjoutSuccess.run(); // Notifier le succès de l'ajout
                }
                closeWindow();
            } else {
                errorMessage.setText("Erreur lors de l'ajout du trajet.");
                errorMessage.setVisible(true);
            }
        } catch (SQLException e) {
            errorMessage.setText("Erreur de base de données : " + e.getMessage());
            errorMessage.setVisible(true);
        }
    }

    @FXML
    public void handleAnnuler() {
        closeWindow();
    }

    private void closeWindow() {
        Stage stage = (Stage) typeCombo.getScene().getWindow();
        stage.close();
    }
}