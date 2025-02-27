package tn.nexus.Controllers.Recrutement;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Services.Recrutement.OffreService;

import java.io.IOException;
import java.sql.SQLException;
import java.time.LocalDate;

public class EditOffreController {

    @FXML
    private TextField posteField, localisationField, nbPostesField;
    @FXML
    private TextArea descriptionArea;
    @FXML
    private ComboBox<String> statutComboBox, modeTravailComboBox, typeContratComboBox, niveauExperienceComboBox;
    @FXML
    private DatePicker dateCreationPicker, dateExpirationPicker;

    private final OffreService offreService = new OffreService();
    private Offre currentOffre;

    // Initialisation des données de l'offre sélectionnée
    public void initData(Offre offre) {
        this.currentOffre = offre;
        posteField.setText(offre.getPoste());
        descriptionArea.setText(offre.getDescription());
        modeTravailComboBox.setValue(offre.getModeTravail());
        typeContratComboBox.setValue(offre.getTypeContrat());
        localisationField.setText(offre.getLocalisation());
        niveauExperienceComboBox.setValue(offre.getNiveauExperience());
        nbPostesField.setText(String.valueOf(offre.getNbPostes()));

        // Définir le statut par défaut si non défini
        statutComboBox.setValue(offre.getStatut() != null ? offre.getStatut() : "En attente");

        // Définir la date de création par défaut (aujourd'hui) et la rendre non modifiable
        if (offre.getDateCreation() == null) {
            dateCreationPicker.setValue(LocalDate.now());
        } else {
            dateCreationPicker.setValue(offre.getDateCreation().toLocalDate());
        }
        dateCreationPicker.setDisable(true);

        // Définir la date d'expiration si existante
        dateExpirationPicker.setValue(offre.getDateExpiration() != null ? offre.getDateExpiration().toLocalDate() : null);

        // Ajout d'un écouteur pour la validation des dates
        dateExpirationPicker.valueProperty().addListener((observable, oldValue, newValue) -> {
            if (newValue != null && newValue.isBefore(dateCreationPicker.getValue())) {
                showAlert(Alert.AlertType.WARNING, "Date invalide", "La date d'expiration ne peut pas être avant la date de création.", "");
                dateExpirationPicker.setValue(null);
            }
        });
    }

    // Sauvegarde les modifications
    @FXML
    private void handleSave() {
        if (!validateInputs()) return;

        // Mise à jour des valeurs
        currentOffre.setPoste(posteField.getText());
        currentOffre.setDescription(descriptionArea.getText());
        currentOffre.setModeTravail(modeTravailComboBox.getValue());
        currentOffre.setTypeContrat(typeContratComboBox.getValue());
        currentOffre.setLocalisation(localisationField.getText());
        currentOffre.setNiveauExperience(niveauExperienceComboBox.getValue());
        currentOffre.setNbPostes(Integer.parseInt(nbPostesField.getText()));
        currentOffre.setStatut(statutComboBox.getValue());

        // Conversion LocalDate → LocalDateTime
        currentOffre.setDateCreation(dateCreationPicker.getValue().atStartOfDay());

        if (dateExpirationPicker.getValue() != null) {
            currentOffre.setDateExpiration(dateExpirationPicker.getValue().atStartOfDay());
        } else {
            currentOffre.setDateExpiration(null);
        }

        try {
            offreService.update(currentOffre);
            showAlert(Alert.AlertType.INFORMATION, "Succès", "L'offre a été mise à jour avec succès", "");
            redirectToOffresPage();
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la mise à jour", e.getMessage());
        }
    }

    // Vérification des entrées utilisateur
    private boolean validateInputs() {
        if (posteField.getText().trim().isEmpty() ||
                descriptionArea.getText().trim().isEmpty() ||
                modeTravailComboBox.getValue() == null ||
                typeContratComboBox.getValue() == null ||
                localisationField.getText().trim().isEmpty() ||
                niveauExperienceComboBox.getValue() == null ||
                nbPostesField.getText().trim().isEmpty() ||
                statutComboBox.getValue() == null) {

            showAlert(Alert.AlertType.WARNING, "Champs vides", "Veuillez remplir tous les champs obligatoires.", "");
            return false;
        }

        try {
            int nbPostes = Integer.parseInt(nbPostesField.getText().trim());
            if (nbPostes <= 0) {
                showAlert(Alert.AlertType.WARNING, "Valeur invalide", "Le nombre de postes doit être positif.", "");
                return false;
            }
        } catch (NumberFormatException e) {
            showAlert(Alert.AlertType.WARNING, "Format incorrect", "Le nombre de postes doit être un nombre valide.", "");
            return false;
        }

        LocalDate dateCreation = dateCreationPicker.getValue();
        LocalDate dateExpiration = dateExpirationPicker.getValue();

        if (dateExpiration != null && dateExpiration.isBefore(dateCreation)) {
            showAlert(Alert.AlertType.WARNING, "Date invalide", "La date d'expiration ne peut pas être avant la date de création.", "");
            return false;
        }

        return true;
    }

    // Annulation et retour à la liste des offres
    @FXML
    private void handleCancel() {
        redirectToOffresPage();
    }

    // Redirection vers Offres.fxml
    private void redirectToOffresPage() {
        try {
            Parent root = FXMLLoader.load(getClass().getResource("/Recrutement/Offres.fxml"));
            Stage stage = (Stage) posteField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Impossible de charger la page Offres", e.getMessage());
        }
    }

    // Affichage des alertes
    private void showAlert(Alert.AlertType alertType, String title, String header, String content) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(header);
        alert.setContentText(content);
        alert.showAndWait();
    }
}
