package tn.nexus.Controllers.Evenement;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.stage.Modality;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Entities.Evenement.Reservation_evenement;
import tn.nexus.Entities.User;
import tn.nexus.Services.Auth.UserSession;
import tn.nexus.Services.Evenement.EvenementServices;
import tn.nexus.Services.Evenement.Reservation_evenementServices;
import tn.nexus.Utils.Enums.Role;

import java.io.IOException;
import java.sql.SQLException;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;

public class ReservationListController {

    @FXML private TableColumn<Reservation_evenement, LocalDate> BookingTimeColumn;
    @FXML private TableColumn<Reservation_evenement, String> TitreColumn;
    @FXML private TableColumn<Reservation_evenement, LocalDate> DateEventColumn;
    @FXML private TableColumn<Reservation_evenement, String> ActionColumn;
    @FXML private TableView<Reservation_evenement> reservationTable;

    private final Reservation_evenementServices reservationService = new Reservation_evenementServices();
    private final EvenementServices evenementService = new EvenementServices();
    //private final User currentUser = new User(3, "ikbel", "ikbel@esprit.tn", "$2a$10$X6j9uxlqquyTIaY9UBWnAO/82JZpQYIPWyRC5hsdWu5ew32oy.NK2", Role.Chef_Projet, "BorjLouzir");
    private UserSession userSession = UserSession.getInstance();

    private ObservableList<Reservation_evenement> observableReservations;

    @FXML
    public void initialize() {
        try {
            updateReservationList();
            configureTableColumns();
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Impossible de récupérer les réservations.", e.getMessage());
        }
    }

    private void updateReservationList() throws SQLException {
        observableReservations = FXCollections.observableArrayList(reservationService.getReservationsByUserID(userSession.getUser()));
        reservationTable.setItems(observableReservations);
    }

    private void configureTableColumns() {
        BookingTimeColumn.setCellValueFactory(new PropertyValueFactory<>("dateReservation"));
        TitreColumn.setCellValueFactory(new PropertyValueFactory<>("titreEvenement"));


        DateEventColumn.setCellValueFactory(new PropertyValueFactory<>("dateDebut"));
        ActionColumn.setCellFactory(param -> new TableCell<>() {
            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) {
                    setGraphic(null);
                } else {
                    Reservation_evenement reservation = getTableRow().getItem();
                    if (reservation != null) {
                        setGraphic(new HBox(10, createButton("Modifier", "#007b8f", e -> handleEditAction(reservation)),
                                createButton("Supprimer", "#ff4d4d", e -> handleDeleteAction(reservation))));
                    }
                }
            }
        });
    }

    private Button createButton(String text, String color, javafx.event.EventHandler<javafx.event.ActionEvent> event) {
        Button button = new Button(text);
        button.setStyle("-fx-background-color: " + color + "; -fx-text-fill: white; -fx-background-radius: 8px;");
        button.setOnAction(event);
        return button;
    }

    private void handleDeleteAction(Reservation_evenement reservation) {
        try {
            Evenement evenement = evenementService.getEvenementById(reservation.getIdEvenement());
            if (evenement == null) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Événement non trouvé.", null);
                return;
            }
            if (Duration.between(LocalDateTime.now(), evenement.getDateDebut().atStartOfDay()).toHours() < 24) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Impossible de supprimer : début dans moins de 24h.", null);
                return;
            }
            reservationService.delete(reservation);
            showAlert(Alert.AlertType.INFORMATION, "Succès", "Réservation supprimée avec succès!", null);
            updateReservationList();
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la suppression.", e.getMessage());
        }
    }

    private void handleEditAction(Reservation_evenement reservation) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Evenement/ReservationEditForm.fxml"));
            AnchorPane page = loader.load();
            Stage dialogStage = new Stage();
            dialogStage.setTitle("Modifier Réservation");
            dialogStage.initModality(Modality.WINDOW_MODAL);
            dialogStage.initOwner(reservationTable.getScene().getWindow());
            dialogStage.setScene(new Scene(page));

            ReservationEditController controller = loader.getController();
            controller.initData(reservation, userSession.getUser());
            dialogStage.showAndWait();
        } catch (IOException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Impossible d'ouvrir la modification.", e.getMessage());
        }
    }

    private void showAlert(Alert.AlertType type, String title, String header, String content) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(header);
        alert.setContentText(content);
        alert.showAndWait();
    }
}
