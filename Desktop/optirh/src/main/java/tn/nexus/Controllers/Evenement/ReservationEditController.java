package tn.nexus.Controllers.Evenement;

import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Reservation_evenement;
import tn.nexus.Entities.User;
import tn.nexus.Services.Evenement.Reservation_evenementServices;

import java.sql.SQLException;

public class ReservationEditController {

    @FXML
    private Button annuler;
    @FXML
    private TextField nomField, prenomField, telephoneField, emailField;

    private Reservation_evenement reservation;
    private User userConnecte;
    private final Reservation_evenementServices reservationService = new Reservation_evenementServices();

    private static final String PHONE_REGEX = "^\\+\\d{11,14}$";
    private static final String NAME_REGEX = "^[A-Za-zÀ-ÖØ-öø-ÿ\\s-]{2,30}$";

    public void initData(Reservation_evenement reservation, User user) {
        this.reservation = reservation;
        this.userConnecte = user;

        prenomField.setText(reservation.getFirstName());
        telephoneField.setText(reservation.getTelephone());
        emailField.setText(user.getEmail());
        nomField.setText(user.getNom());
    }

    @FXML
    private void handleSaveAction() {
        if (!isInputValid()) return;

        reservation.setFirstName(prenomField.getText());
        reservation.setTelephone(telephoneField.getText());
        reservation.setEmail(userConnecte.getEmail());
        reservation.setLastName(userConnecte.getNom());

        try {
            reservationService.update(reservation);
            fermerModale();
        } catch (SQLException e) {
            showAlert("Erreur", "Échec de la mise à jour : " + e.getMessage());
        }
    }

    private boolean isInputValid() {
        if (prenomField.getText().isEmpty() || telephoneField.getText().isEmpty()) {
            showAlert("Erreur", "Tous les champs doivent être remplis !");
            return false;
        }
        if (!telephoneField.getText().matches(PHONE_REGEX)) {
            showAlert("Erreur", "Le numéro de téléphone doit commencer par '+' et contenir entre 11 et 14 chiffres.");
            return false;
        }
        if (!prenomField.getText().matches(NAME_REGEX)) {
            showAlert("Prénom invalide", "Le prénom doit contenir uniquement des lettres et avoir entre 2 et 30 caractères.");
            return false;
        }
        return true;
    }

    private void showAlert(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    @FXML
    private void handleClear() {
        prenomField.clear();
        telephoneField.clear();
    }

    @FXML
    private void fermerModale() {
        ((Stage) nomField.getScene().getWindow()).close();
    }

    @FXML
    private void annuler() {
        fermerModale();
    }
}
