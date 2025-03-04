package tn.nexus.Controllers.Evenement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.image.Image;
import javafx.scene.image.ImageView;
import javafx.scene.layout.FlowPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Services.Evenement.EvenementServices;

import java.io.File;
import java.io.IOException;
import java.security.SecureRandom;
import java.sql.SQLException;
import java.util.List;
import java.util.Optional;

public class EventFrontController {

    @FXML
    private FlowPane eventContainer;
    @FXML
    private Button prevButton, nextButton, myReservationButton;

    private int currentPage = 0;
    private static final int EVENTS_PER_PAGE = 4;
    private final EvenementServices evenementService = new EvenementServices();
    private List<Evenement> events; // Liste des événements

    @FXML
    public void initialize() {
        try {
            events = evenementService.showAll();
            updatePagination();
        } catch (SQLException e) {
            e.printStackTrace();
        }
        myReservationButton.setOnAction(this::handleMyReservation);
    }

    private void updatePagination() {
        eventContainer.getChildren().clear();
        int start = currentPage * EVENTS_PER_PAGE;
        int end = Math.min(start + EVENTS_PER_PAGE, events.size());

        for (int i = start; i < end; i += 2) {
            HBox row = new HBox(20);
            row.getStyleClass().add("event-row");

            for (int j = 0; j < 2 && i + j < end; j++) {
                row.getChildren().add(createEventCard(events.get(i + j)));
            }

            eventContainer.getChildren().add(row);
        }

        prevButton.setDisable(currentPage == 0);
        nextButton.setDisable(end >= events.size());
    }

    @FXML
    private void nextPage(ActionEvent event) {
        if ((currentPage + 1) * EVENTS_PER_PAGE < events.size()) {
            currentPage++;
            updatePagination();
        }
    }

    @FXML
    private void prevPage(ActionEvent event) {
        if (currentPage > 0) {
            currentPage--;
            updatePagination();
        }
    }

    private VBox createEventCard(Evenement event) {
        VBox card = new VBox(10);
        card.getStyleClass().add("event-card");

        ImageView imageView = new ImageView();
        imageView.setFitWidth(180);
        imageView.setFitHeight(120);
        imageView.setPreserveRatio(false);
        imageView.getStyleClass().add("event-image");

        File file = new File(event.getImage());
        if (file.exists()) {
            imageView.setImage(new Image(file.toURI().toString()));
        }

        Label titleLabel = new Label(event.getTitre());
        titleLabel.getStyleClass().add("event-title");

        Label priceLabel = new Label("Prix: " + event.getPrix() + " TND");
        priceLabel.getStyleClass().add("event-price");

        Label dateLabel = new Label("📅 " + event.getDateDebut().toString());
        dateLabel.getStyleClass().add("event-date");

        HBox priceDateBox = new HBox(15, priceLabel, dateLabel);
        priceDateBox.getStyleClass().add("event-info");

        Button detailsButton = new Button("Voir Détails");
        detailsButton.getStyleClass().add("event-button");
        detailsButton.setOnAction(e -> openEventDetails(event));

        card.getChildren().addAll(imageView, titleLabel, priceDateBox, detailsButton);

        return card;
    }

    private void openEventDetails(Evenement event) {
        if (showCaptchaDialog()) { // Vérifie si la réponse est correcte
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/event_details.fxml"));
                Parent root = loader.load();

                EventDetailsController controller = loader.getController();
                controller.setEventData(event);

                Stage stage = new Stage();
                stage.setTitle("Détails de l'événement");
                stage.setScene(new Scene(root));
                stage.show();
            } catch (IOException e) {
                e.printStackTrace();
            }
        } else {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("CAPTCHA incorrect");
            alert.setHeaderText(null);
            alert.setContentText("Le CAPTCHA est incorrect. Veuillez réessayer.");
            alert.showAndWait();
        }
    }

    @FXML
    private void handleMyReservation(ActionEvent event) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/reservation_list.fxml"));
            Parent reservationView = loader.load();

            Stage newStage = new Stage();
            newStage.setScene(new Scene(reservationView));
            newStage.setTitle("Mes Réservations");
            newStage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

    private boolean showCaptchaDialog() {
        String captcha = generateCaptcha(6); // Génère un CAPTCHA de 6 caractères
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle("Vérification CAPTCHA");
        alert.setHeaderText("Veuillez entrer le code affiché pour accéder aux détails :");

        // Affichage du CAPTCHA dans un Label
        Label captchaLabel = new Label("🔒 " + captcha);
        captchaLabel.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: blue;");

        // Champ de texte pour la saisie
        TextField captchaInput = new TextField();
        captchaInput.setPromptText("Entrez le code ici...");

        VBox vbox = new VBox(10, captchaLabel, captchaInput);
        alert.getDialogPane().setContent(vbox);

        // Boutons OK/Annuler
        Optional<ButtonType> result = alert.showAndWait();

        return result.isPresent() && result.get() == ButtonType.OK && captchaInput.getText().trim().equalsIgnoreCase(captcha);
    }

    // Méthode pour générer un CAPTCHA aléatoire (lettres + chiffres)
    private String generateCaptcha(int length) {
        String chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        SecureRandom random = new SecureRandom();
        StringBuilder captcha = new StringBuilder();

        for (int i = 0; i < length; i++) {
            int index = random.nextInt(chars.length());
            captcha.append(chars.charAt(index));
        }

        return captcha.toString();
    }
}
