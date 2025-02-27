package tn.nexus.Controllers.Recrutement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Services.Recrutement.OffreService;

import java.io.IOException;
import java.time.LocalDate;
import java.time.LocalDateTime;

public class AjoutOffreController {

    @FXML
    private TextField posteField;
    @FXML
    private TextArea descriptionArea;
    @FXML
    private ComboBox<String> statutComboBox;
    @FXML
    private DatePicker dateCreationPicker;
    @FXML
    private ComboBox<String> modeTravailComboBox;
    @FXML
    private ComboBox<String> typeContratComboBox;
    @FXML
    private TextField localisationField;
    @FXML
    private ComboBox<String> niveauExperienceComboBox;
    @FXML
    private TextField nbPostesField;
    @FXML
    private DatePicker dateExpirationPicker;

    private OffreService offreService = new OffreService();

    @FXML
    private void initialize() {
        // Validation du statut par défaut
        if(statutComboBox.getValue() == null) {
            statutComboBox.setValue("Brouillon");
        }
        // Initialiser la date de création à aujourd'hui et la rendre non modifiable
        dateCreationPicker.setValue(LocalDate.now());
        dateCreationPicker.setDisable(true);
        // Validation de la date d'expiration
        dateExpirationPicker.valueProperty().addListener((observable, oldValue, newValue) -> {
            if (newValue != null && newValue.isBefore(dateCreationPicker.getValue())) {
                showError("La date d'expiration ne peut pas être avant la date de création.");
                dateExpirationPicker.setValue(null);
            }
        });
    }
    // Méthode pour créer l'offre avec validation de saisie et redirection vers la liste
    @FXML
    private void handleCreateOffre(ActionEvent event) {
        String poste = posteField.getText();
        String description = descriptionArea.getText();
        String statut = statutComboBox.getValue();
        LocalDate dateCreation = dateCreationPicker.getValue();
        String modeTravail = modeTravailComboBox.getValue();
        String typeContrat = typeContratComboBox.getValue();
        String localisation = localisationField.getText();
        String niveauExperience = niveauExperienceComboBox.getValue();
        int nbPostes = Integer.parseInt(nbPostesField.getText());
        LocalDate dateExpiration = dateExpirationPicker.getValue();

        // Contrôles de saisie
        if (poste.isEmpty()) {
            showError("Le champ Poste ne peut pas être vide.");
            return;
        }
        if (poste.length() > 100) {
            showError("Le poste ne peut pas dépasser 100 caractères.");
            return;
        }
        if (description.isEmpty()) {
            showError("Le champ Description ne peut pas être vide.");
            return;
        }
        if (description.length() > 500) {
            showError("La description ne peut pas dépasser 500 caractères.");
            return;
        }
        if (statut == null) {
            showError("Veuillez sélectionner un statut.");
            return;
        }
        if (modeTravail == null) {
            showError("Veuillez sélectionner un mode de travail.");
            return;
        }
        if (typeContrat == null) {
            showError("Veuillez sélectionner un type de contrat.");
            return;
        }
        if (localisation.isEmpty()) {
            showError("Le champ Localisation ne peut pas être vide.");
            return;
        }
        if (niveauExperience == null) {
            showError("Veuillez sélectionner un niveau d'expérience.");
            return;
        }
        if (nbPostes <= 0) {
            showError("Le nombre de postes doit être supérieur à zéro.");
            return;
        }
        if (dateCreation == null) {
            showError("Veuillez sélectionner une date de création.");
            return;
        }
        if (dateExpiration == null) {
            showError("Veuillez sélectionner une date d'expiration.");
            return;
        }
        if (dateExpiration != null && dateExpiration.isBefore(dateCreation)) {
            showError("La date d'expiration ne peut pas être avant la date de création.");
            return;
        }


        // Convertir la date en LocalDateTime
        LocalDateTime localDateTime = dateCreation.atStartOfDay();
        LocalDateTime localDateTimeExpiration = dateExpiration != null ? dateExpiration.atStartOfDay() : null;


        // Créer une nouvelle offre
        Offre offre = new Offre(poste, description, statut, localDateTime, modeTravail, typeContrat, localisation, niveauExperience, nbPostes, localDateTimeExpiration);

        // Appeler le service pour insérer l'offre
        try {
            int result = offreService.insert(offre);
            if (result > 0) {
                // Offre insérée avec succès, afficher un message de succès
                showSuccess("L'offre a été créée avec succès.");

                // Redirection vers la page liste des offres
                redirectToListeOffres();
            } else {
                showError("Échec de la création de l'offre.");
            }
        } catch (Exception e) {
            showError("Une erreur est survenue : " + e.getMessage());
        }
    }

    // Méthode pour annuler l'action
    @FXML
    private void handleCancel(ActionEvent event) {
        // Rediriger vers la page Offres
        redirectToListeOffres();
    }

    // Méthode utilitaire pour afficher un message d'erreur
    private void showError(String message) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle("Erreur");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    // Méthode utilitaire pour afficher un message de succès
    private void showSuccess(String message) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Succès");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    // Méthode pour rediriger vers la page de la liste des offres
    private void redirectToListeOffres() {
        try {
            // Charger le fichier Offres.fxml (vérifier le chemin selon votre organisation des ressources)
            Parent root = FXMLLoader.load(getClass().getResource("/Recrutement/Offres.fxml"));
            Scene scene = new Scene(root);
            // Récupérer la fenêtre actuelle à partir d'un composant (ici, posteField)
            Stage stage = (Stage) posteField.getScene().getWindow();
            stage.setScene(scene);
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            showError("Erreur lors du chargement de la page des offres : " + e.getMessage());
        }
    }
}
