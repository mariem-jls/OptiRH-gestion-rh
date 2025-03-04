package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.paint.Color;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Services.Transport.TrajetService;

import java.sql.SQLException;

public class ModifierTrajetController {

    @FXML private ComboBox<String> typeCombo;
    @FXML private TextField stationField;
    @FXML private TextField departField;
    @FXML private TextField arriveField;
    @FXML private Label errorMessage;
    @FXML private TextField longitudeDepartField;
    @FXML private TextField latitudeDepartField;
    @FXML private TextField longitudeArriveeField;
    @FXML private TextField latitudeArriveeField;

    private Trajet trajet; // Le trajet à modifier
    private final TrajetService trajetService = new TrajetService();
    private Runnable onModificationSuccess;

    // Méthode pour initialiser les données du trajet
    public void setTrajet(Trajet trajet) {
        this.trajet = trajet;
        typeCombo.setValue(trajet.getType());
        stationField.setText(trajet.getStation());
        departField.setText(trajet.getDepart());
        arriveField.setText(trajet.getArrive());
        longitudeDepartField.setText(String.valueOf(trajet.getLongitudeDepart()));
        latitudeDepartField.setText(String.valueOf(trajet.getLatitudeDepart()));
        longitudeArriveeField.setText(String.valueOf(trajet.getLongitudeArrivee()));
        latitudeArriveeField.setText(String.valueOf(trajet.getLatitudeArrivee()));

    }

    // Méthode pour définir le callback
    public void setOnModificationSuccess(Runnable onModificationSuccess) {
        this.onModificationSuccess = onModificationSuccess;
    }

    // Gérer l'enregistrement des modifications
    @FXML
    public void handleEnregistrer() {
        String type = typeCombo.getValue();
        String station = stationField.getText();
        String depart = departField.getText();
        String arrive = arriveField.getText();
// Récupérer les nouvelles coordonnées
        String longitudeDepartText = longitudeDepartField.getText();
        String latitudeDepartText = latitudeDepartField.getText();
        String longitudeArriveeText = longitudeArriveeField.getText();
        String latitudeArriveeText = latitudeArriveeField.getText();
        // Valider les champs
        if (type.isEmpty() || station.isEmpty() || depart.isEmpty() || arrive.isEmpty() ||
                longitudeDepartText.isEmpty() || latitudeDepartText.isEmpty() ||
                longitudeArriveeText.isEmpty() || latitudeArriveeText.isEmpty())  {
            showError("Tous les champs doivent être remplis !");
            return;
        }

        try {
        // Convertir les valeurs des coordonnées en double
        double longitudeDepart = Double.parseDouble(longitudeDepartText);
        double latitudeDepart = Double.parseDouble(latitudeDepartText);
        double longitudeArrivee = Double.parseDouble(longitudeArriveeText);
        double latitudeArrivee = Double.parseDouble(latitudeArriveeText);

        // Mettre à jour le trajet
        trajet.setType(type);
        trajet.setStation(station);
        trajet.setDepart(depart);
        trajet.setArrive(arrive);
        trajet.setLongitudeDepart(longitudeDepart);
        trajet.setLatitudeDepart(latitudeDepart);
        trajet.setLongitudeArrivee(longitudeArrivee);
        trajet.setLatitudeArrivee(latitudeArrivee);


            int result = trajetService.update(trajet);
            if (result > 0) {
                showSuccess("Trajet modifié avec succès !");
                // Fermer la fenêtre de modification
                typeCombo.getScene().getWindow().hide();
// Appeler le callback pour rafraîchir la TableView dans le contrôleur principal
                if (onModificationSuccess != null) {
                    onModificationSuccess.run();
                }

            } else {
                showError("Erreur lors de la modification du trajet.");
                // Appeler le callback pour notifier la réussite de la modification
                if (onModificationSuccess != null) {
                    onModificationSuccess.run();
                }

                // Fermer la fenêtre
                typeCombo.getScene().getWindow().hide();
            }
        } catch (SQLException e) {
            showError("Erreur de base de données : " + e.getMessage());
        }
    }

    // Gérer l'annulation
    @FXML
    public void handleAnnuler() {
        // Fermer la fenêtre
        typeCombo.getScene().getWindow().hide();
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

}