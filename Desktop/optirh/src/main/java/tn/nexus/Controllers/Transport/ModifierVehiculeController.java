package tn.nexus.Controllers.Transport;

import javafx.fxml.FXML;
import javafx.scene.control.ComboBox;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.paint.Color;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.Transport.VehiculeService;

import java.sql.SQLException;

public class ModifierVehiculeController {

    @FXML private ComboBox<String> disponibiliteCombo;
    @FXML private TextField typeField;
    @FXML private TextField nbrPlaceField;
    @FXML private Label errorMessage;

    private Vehicule vehicule; // Le véhicule à modifier
    private final VehiculeService vehiculeService = new VehiculeService();
    private Runnable onModificationSuccess;

    // Méthode pour initialiser les données du véhicule
    public void setVehicule(Vehicule vehicule) {
        this.vehicule = vehicule;
        disponibiliteCombo.setValue(vehicule.getDisponibilite());
        typeField.setText(vehicule.getType());
        nbrPlaceField.setText(String.valueOf(vehicule.getNbrplace()));
    }

    // Méthode pour définir le callback
    public void setOnModificationSuccess(Runnable onModificationSuccess) {
        this.onModificationSuccess = onModificationSuccess;
    }

    // Gérer l'enregistrement des modifications
    @FXML
    public void handleEnregistrer() {
        String disponibilite = disponibiliteCombo.getValue();
        String type = typeField.getText();
        String nbrPlaceText = nbrPlaceField.getText();

        // Valider les champs
        if (disponibilite == null || type.isEmpty() || nbrPlaceText.isEmpty()) {
            showError("Tous les champs doivent être remplis !");
            return;
        }

        try {
            int nbrPlace = Integer.parseInt(nbrPlaceText);
            if (nbrPlace < 1 || nbrPlace > 20) {
                showError("Le nombre de places doit être compris entre 1 et 20.");
                return;
            }

            // Mettre à jour le véhicule
            vehicule.setDisponibilite(disponibilite);
            vehicule.setType(type);
            vehicule.setNbrplace(nbrPlace);

            int result = vehiculeService.update(vehicule);
            if (result > 0) {
                showSuccess("Véhicule modifié avec succès !");
                // Fermer la fenêtre de modification
                typeField.getScene().getWindow().hide();
                // Appeler le callback pour rafraîchir la TableView dans le contrôleur principal
                if (onModificationSuccess != null) {
                    onModificationSuccess.run();
                }
            } else {
                showError("Erreur lors de la modification du véhicule.");
            }
        } catch (NumberFormatException e) {
            showError("Le nombre de places doit être un nombre valide !");
        } catch (SQLException e) {
            showError("Erreur de base de données : " + e.getMessage());
        }
    }

    // Gérer l'annulation
    @FXML
    public void handleAnnuler() {
        // Fermer la fenêtre
        typeField.getScene().getWindow().hide();
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