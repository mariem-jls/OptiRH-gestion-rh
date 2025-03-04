package tn.nexus.Controllers.Evenement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.Node;
import javafx.scene.control.Alert;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Entities.Evenement.Reservation_evenement;
import tn.nexus.Entities.User;
import tn.nexus.Services.Evenement.Reservation_evenementServices;
import tn.nexus.Utils.Enums.Role;

import java.io.File;
import java.sql.SQLException;
import java.time.LocalDate;

public class EventDetailsController {

    @FXML
    private Label DateDebutData;

    @FXML
    private Label DateFinData;

    @FXML
    private Label DescriptionData;

    @FXML
    private Label HeureData;

    @FXML
    private Label LieuxData;

    @FXML
    private Label PrixData;

    @FXML
    private TextField emailField;

    @FXML
    private ImageView eventImage;

    @FXML
    private TextField firstNameField;

    @FXML
    private TextField lastNameField;

    @FXML
    private TextField phoneField;

    @FXML
    private Label titleLabel;


    private Evenement currentEvent;
    private Reservation_evenementServices reservationService = new Reservation_evenementServices();


    User u1 = new User(3, "ikbel", "ikbel@esprit.tn", "$2a$10$X6j9uxlqquyTIaY9UBWnAO/82JZpQYIPWyRC5hsdWu5ew32oy.NK2", Role.Chef_Projet, "BorjLouzir");

    /*******************get data******************/
    public void setEventData(Evenement event) {
        this.currentEvent = event;
        titleLabel.setText(event.getTitre());
        DateDebutData.setText(event.getDateDebut().toString());
        DateFinData.setText(event.getDateFin().toString());
        DescriptionData.setText(event.getDescription());
        PrixData.setText(String.valueOf(event.getPrix()));
        HeureData.setText(String.valueOf(event.getHeure()));
        LieuxData.setText(String.valueOf(event.getLieu()));
        firstNameField.setText(u1.getNom());
        emailField.setText(u1.getEmail());
        File file = new File(event.getImage());
        if (file.exists()) {
            eventImage.setImage(new Image(file.toURI().toString()));
        }
    }



    @FXML
    void handleReservation(ActionEvent event) {
        String firstName = lastNameField.getText();
        String phone = phoneField.getText();

        if (firstName.isEmpty() || phone.isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Champs vides", null, "Veuillez remplir tous les champs avant de réserver !");
            return;
        }

        if (!firstName.matches("^[A-Za-zÀ-ÖØ-öø-ÿ\\s-]{2,30}$")) {
            showAlert(Alert.AlertType.WARNING, "Prénom invalide", null, "Le prénom doit contenir uniquement des lettres et avoir entre 2 et 30 caractères.");
            return;
        }

        // Vérification du format du téléphone
        if (!phone.matches("^\\+\\d{11,14}$")) {
            showAlert(Alert.AlertType.WARNING, "Numéro de téléphone invalide", null, "Veuillez entrer un numéro de téléphone valide (ex: +21612345678).");
            return;
        }

        // Création de l'objet Reservation_evenement
        Reservation_evenement reservation = new Reservation_evenement();
        reservation.setIdUser(1); // ID utilisateur fixe
        reservation.setIdEvenement(currentEvent.getIdEvenement()); // ID de l'événement
        reservation.setFirstName(firstName);
        reservation.setLastName(u1.getNom());
        reservation.setEmail(u1.getEmail());
        reservation.setTelephone(phone);
        reservation.setDateReservation(LocalDate.now()); // Date actuelle

        // Service de réservation
        Reservation_evenementServices reservationService = new Reservation_evenementServices();

        try {
            // Vérifier si l'utilisateur a déjà réservé cet événement
            if (reservationService.isReservationExists(1, currentEvent.getIdEvenement())) {
                showAlert(Alert.AlertType.ERROR, "Réservation existante", null, "Vous avez déjà réservé cet événement !");
                return; // Stopper le processus
            }

            // Tenter l'insertion
            int result = reservationService.insert(reservation);
            if (result > 0) {
                // Envoi du SMS après réservation réussie
                String smsMessage = "Bonjour " + firstName + ", votre réservation pour l'evenement '"
                        + currentEvent.getTitre() + "' a été confirmée !";
                SmsService.sendSms(phone, smsMessage);

                showAlert(Alert.AlertType.INFORMATION, "Réservation confirmée", null, "Votre réservation a été enregistrée avec succès ! Un SMS de confirmation a été envoyé.");
                Stage stage = (Stage) ((Node) event.getSource()).getScene().getWindow();
                stage.close();
            } else {
                showAlert(Alert.AlertType.ERROR, "Erreur", null, "Une erreur est survenue lors de la réservation. Veuillez réessayer.");
            }
        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Erreur SQL", null, "Une erreur s'est produite lors de l'enregistrement dans la base de données.");
        }
    }




    /****************Alerte*************************/
    // Fonction pour afficher une alerte
    private void showAlert(Alert.AlertType alertType, String title, String headerText, String contentText) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(headerText);
        alert.setContentText(contentText);
        alert.showAndWait();
    }

}