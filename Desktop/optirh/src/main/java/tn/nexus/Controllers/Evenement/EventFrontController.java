package tn.nexus.Controllers.Evenement;

import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
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
import java.sql.SQLException;
import java.util.List;

public class EventFrontController {

    @FXML
    private FlowPane eventContainer; // Conteneur des cartes événements

    @FXML
    private Button myReservationButton; // Bouton "Mes Réservations"

    private final EvenementServices evenementService = new EvenementServices(); // Service de gestion des événements

    @FXML
    public void initialize() {
        try {
            loadEvents(); // Charger les événements
        } catch (SQLException e) {
            e.printStackTrace();
        }

        // Action du bouton "Mes Réservations"
        myReservationButton.setOnAction(this::handleMyReservation);
    }

    /**
     * Charge les événements et les affiche sous forme de cartes.
     */
    private void loadEvents() throws SQLException {
        eventContainer.getChildren().clear(); // Nettoyer avant d'ajouter

        List<Evenement> events = evenementService.showAll(); // Récupérer tous les événements

        for (Evenement event : events) {
            VBox card = createEventCard(event); // Créer une carte événement
            eventContainer.getChildren().add(card);
        }
    }

    /**
     * Crée une carte événement avec image, titre, prix et date.
     */
    private VBox createEventCard(Evenement event) {
        VBox card = new VBox(10);
        card.getStyleClass().add("event-card"); // Appliquer le style CSS

        // Image de l'événement
        ImageView imageView = new ImageView();
        // ImageView plus petite
        imageView.setFitWidth(180);
        imageView.setFitHeight(120);

        imageView.setPreserveRatio(false);
        imageView.getStyleClass().add("event-image");

        // Charger l'image depuis un fichier ou afficher une image par défaut
        File file = new File(event.getImage());
        if (file.exists()) {
            imageView.setImage(new Image(file.toURI().toString()));
        }

        // Titre de l'événement
        Label titleLabel = new Label(event.getTitre());
        titleLabel.getStyleClass().add("event-title");

        // Prix de l'événement
        Label priceLabel = new Label("Prix: " + event.getPrix() + " TND");
        priceLabel.getStyleClass().add("event-price");

        // Date de l'événement
        Label dateLabel = new Label("📅 " + event.getDateDebut().toString());
        dateLabel.getStyleClass().add("event-date");

        // Conteneur pour prix et date
        HBox priceDateBox = new HBox(15, priceLabel, dateLabel);
        priceDateBox.getStyleClass().add("event-info");

        // Bouton Voir Détails
        Button detailsButton = new Button("Voir Détails");
        detailsButton.getStyleClass().add("event-button");
        detailsButton.setOnAction(e -> openEventDetails(event));

        // Effet hover sur le bouton
        detailsButton.setOnMouseEntered(e -> detailsButton.setStyle("-fx-background-color: #005f6b; -fx-text-fill: white;"));
        detailsButton.setOnMouseExited(e -> detailsButton.setStyle("-fx-background-color: #007b8f; -fx-text-fill: white;"));

        // Ajouter les éléments à la carte
        card.getChildren().addAll(imageView, titleLabel, priceDateBox, detailsButton);

        return card;
    }

    /**
     * Ouvre une fenêtre avec les détails de l'événement.
     */
    private void openEventDetails(Evenement event) {
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
    }

    /**
     * Ouvre la fenêtre des réservations.
     */
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
}
