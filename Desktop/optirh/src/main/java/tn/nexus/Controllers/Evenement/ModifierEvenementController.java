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
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;

public class ModifierEvenementController {

    @FXML private DatePicker dateDebutField, dateFinField;
    @FXML private TextArea descriptionField;
    @FXML private TextField heureField, imageField, lieuField, titreField, prixField, latitudeField, longitudeField;

    private final EvenementServices serviceEvenement = new EvenementServices();
    private Evenement evenementActuel;
    private ListeEvenementController listeEvenementController;

    public void setListeEvenementController(ListeEvenementController controller) {
        this.listeEvenementController = controller;
    }

    /****************Bouton clear*****************/
    @FXML
    private void clearFields() {
        titreField.clear(); lieuField.clear(); descriptionField.clear(); prixField.clear();
        dateDebutField.setValue(null);
        dateFinField.setValue(null);
        heureField.clear(); imageField.clear();
        latitudeField.clear(); longitudeField.clear();
    }

    /*********Alerte*************/
    private void showAlert(Alert.AlertType alertType, String title, String headerText, String contentText) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(headerText);
        alert.setContentText(contentText);
        alert.showAndWait();
    }

    /*********Choix image***********/
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

    /**********Initialisation du Data dans les champs*********/
    public void initData(Evenement evenement) {
        this.evenementActuel = evenement;

        if (evenement != null) {
            titreField.setText(evenement.getTitre());
            lieuField.setText(evenement.getLieu());
            descriptionField.setText(evenement.getDescription());
            prixField.setText(String.valueOf(evenement.getPrix()));
            dateDebutField.setValue(evenement.getDateDebut());
            dateFinField.setValue(evenement.getDateFin());
            imageField.setText(evenement.getImage());
            latitudeField.setText(String.valueOf(evenement.getLatitude()));
            longitudeField.setText(String.valueOf(evenement.getLongitude()));

            if (evenement.getHeure() != null) {
                heureField.setText(evenement.getHeure().toString());
            }
        }
    }

    /*************Validation*************/
    private boolean isFieldValid(String text, int maxLength) {
        return !text.isEmpty() && text.length() <= maxLength && text.matches("[a-zA-Z0-9 ]+");
    }

    /*************Bouton Modifier******************/
    @FXML
    void modifierEvenement(ActionEvent event) throws SQLException {
        if (evenementActuel == null) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "Aucun événement sélectionné !");
            return;
        }

        // Suppression des espaces superflus
        String titre = titreField.getText().trim();
        String lieu = lieuField.getText().trim();
        String description = descriptionField.getText().trim();
        String image = imageField.getText().trim();
        String prixText = prixField.getText().trim();
        String heureText = heureField.getText().trim();
        String latitudeText = latitudeField.getText().trim();
        String longitudeText = longitudeField.getText().trim();

        if (titre.isEmpty() || lieu.isEmpty() || description.isEmpty() || prixText.isEmpty() || heureText.isEmpty() || latitudeText.isEmpty() || longitudeText.isEmpty()
                || dateDebutField.getValue() == null || dateFinField.getValue() == null || image.isEmpty()) {
            showAlert(Alert.AlertType.ERROR, "Avertissement", null, "Tous les champs doivent être remplis !");
            return;
        }

        if (!isFieldValid(titre, 10)) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "Le titre doit contenir seulement des lettres et chiffres (max 10 caractères).");
            return;
        }

        if (!isFieldValid(lieu, 10)) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "Le lieu doit contenir seulement des lettres et chiffres (max 10 caractères).");
            return;
        }

        double prix;
        try {
            prix = Double.parseDouble(prixText);
            if (prix < 0) {
                showAlert(Alert.AlertType.ERROR, "Erreur", null, "Le prix doit être un nombre positif !");
                return;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "Le prix doit être un nombre valide !");
            return;
        }

        LocalTime heure;
        try {
            DateTimeFormatter formatter = DateTimeFormatter.ofPattern("HH:mm");
            heure = LocalTime.parse(heureText, formatter);
        } catch (DateTimeParseException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "L'heure doit être au format HH:mm !");
            return;
        }

        LocalDate dateDebut = dateDebutField.getValue();
        if (dateDebut.isBefore(LocalDate.now())) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "La date de début ne peut pas être antérieure à aujourd'hui !");
            return;
        }

        LocalDate dateFin = dateFinField.getValue();
        if (dateFin.isBefore(dateDebut)) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "La date de fin doit être après la date de début !");
            return;
        }

        double latitude;
        try {
            latitude = Double.parseDouble(latitudeText);
            if (latitude < -90 || latitude > 90) {
                showAlert(Alert.AlertType.ERROR, "Erreur", null, "La latitude doit être comprise entre -90 et 90 !");
                return;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "La latitude doit être un nombre valide !");
            return;
        }

        double longitude;
        try {
            longitude = Double.parseDouble(longitudeText);
            if (longitude < -180 || longitude > 180) {
                showAlert(Alert.AlertType.ERROR, "Erreur", null, "La longitude doit être comprise entre -180 et 180 !");
                return;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", null, "La longitude doit être un nombre valide !");
            return;
        }

        // Mise à jour de l'événement
        evenementActuel.setTitre(titre);
        evenementActuel.setLieu(lieu);
        evenementActuel.setDescription(description);
        evenementActuel.setPrix(prix);
        evenementActuel.setDateDebut(dateDebut);
        evenementActuel.setDateFin(dateFin);
        evenementActuel.setHeure(heure);
        evenementActuel.setImage(image);
        evenementActuel.setLatitude(latitude);
        evenementActuel.setLongitude(longitude);

        serviceEvenement.update(evenementActuel);

        showAlert(Alert.AlertType.INFORMATION, "Succès", null, "Événement mis à jour avec succès !");

        if (listeEvenementController != null) {
            listeEvenementController.refreshTable();
        }

        ((Stage) titreField.getScene().getWindow()).close();
    }
}