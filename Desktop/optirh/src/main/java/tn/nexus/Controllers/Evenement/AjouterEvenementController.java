package tn.nexus.Controllers.Evenement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.DatePicker;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Services.Evenement.EvenementServices;

import java.io.File;
import java.sql.SQLException;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeParseException;

public class AjouterEvenementController {

    @FXML private DatePicker dateDebutField, dateFinField;
    @FXML private TextArea descriptionField;
    @FXML private TextField heureField, imageField, lieuField, titreField, prixField,latitudeField,longitudeField;

    /*****************Instance du ServiceEvenement*************/

    private final EvenementServices serviceEvenement = new EvenementServices();

    private ListeEvenementController listeEvenementController;
    public void setListeEvenementController(ListeEvenementController controller) {
        this.listeEvenementController = controller;
    }
    /************Boutton clear *********************/
    @FXML
    private void clearFields() {
        titreField.clear(); lieuField.clear(); descriptionField.clear(); prixField.clear();
        dateDebutField.setValue(null); dateFinField.setValue(null);
        heureField.clear(); imageField.clear();
    }

    /**************Alerte*************/

    private void showAlert(Alert.AlertType type, String title, String message) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
    /*************Choix d image************/

    @FXML
    void choisirImage(ActionEvent event) {
        FileChooser fileChooser = new FileChooser();
        fileChooser.getExtensionFilters().add(
                new FileChooser.ExtensionFilter("Images (*.png, *.jpg, *.jpeg)", "*.png", "*.jpg", "*.jpeg")
        );
        File selectedFile = fileChooser.showOpenDialog(null);
        if (selectedFile != null) {
            imageField.setText(selectedFile.getAbsolutePath());
        }
    }

    /*************Controle Saisie******************/

    private boolean isFieldValid(String text, int maxLength, String fieldName) {
        if (text.isEmpty()) {
            return false;
        }
        if (text.length() > maxLength || !text.matches("[a-zA-Z0-9 ]+")) {
            return false;
        }
        return true;
    }

    private boolean validateFields() {
        if (titreField.getText().isEmpty() || lieuField.getText().isEmpty() ||
                descriptionField.getText().isEmpty() || imageField.getText().isEmpty() ||
                prixField.getText().isEmpty() || heureField.getText().isEmpty() || longitudeField.getText().isEmpty()|| latitudeField.getText().isEmpty()||
                dateDebutField.getValue() == null || dateFinField.getValue() == null) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Tous les champs sont requis !");
            return false;
        }
        if (!isFieldValid(titreField.getText(), 10, "Titre") ) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Le titre  doit contenir seulement des lettres et chiffres (max 10 caractères).");
            return false;
        }
        if (!isFieldValid(lieuField.getText(), 10, "Lieu")) {
            showAlert(Alert.AlertType.ERROR, "Erreur", " le lieu doit contenir seulement des lettres et chiffres (max 10 caractères).");
            return false;
        }
        try {
            double prix = Double.parseDouble(prixField.getText());
            if (prix < 0) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Le prix doit être positif !");
                return false;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Le prix doit être un nombre valide !");
            return false;
        }
        try {
            LocalTime.parse(heureField.getText());
        } catch (DateTimeParseException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "L'heure doit être au format HH:mm !");
            return false;
        }
        LocalDate dateDebut = dateDebutField.getValue();
        LocalDate dateFin = dateFinField.getValue();
        /*if (dateDebut.isBefore(LocalDate.now())) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "La date de début ne peut pas être antérieure à aujourd'hui !");
            return false;
        }
        if (dateFin.isBefore(dateDebut)) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "La date de fin doit être après la date de début !");
            return false;
        }*/
        try {
            double latitude = Double.parseDouble(latitudeField.getText());
            if (latitude < -90 || latitude > 90) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "La latitude doit être comprise entre -90 et 90 !");
                return false;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "La latitude doit être un nombre valide !");
            return false;
        }

        try {
            double longitude = Double.parseDouble(longitudeField.getText());
            if (longitude < -180 || longitude > 180) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "La longitude doit être comprise entre -180 et 180 !");
                return false;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "La longitude doit être un nombre valide !");
            return false;
        }
        return true;
    }

    /************Boutton Ajouter***************/

    @FXML
    void ajouterEvenement(ActionEvent event) throws SQLException {
        if (!validateFields()) return;

        Evenement evenement = new Evenement(
                titreField.getText(), lieuField.getText(), descriptionField.getText(),
                Double.parseDouble(prixField.getText()), dateDebutField.getValue(),
                dateFinField.getValue(), imageField.getText(), LocalTime.parse(heureField.getText()),
                Double.parseDouble(latitudeField.getText()), Double.parseDouble(longitudeField.getText()) // Ajout de latitude et longitude

        );

        serviceEvenement.insert(evenement);
        if (listeEvenementController != null) {
            listeEvenementController.refreshTable();
        }
        clearFields();
        ((Stage) titreField.getScene().getWindow()).close();

    }
}
