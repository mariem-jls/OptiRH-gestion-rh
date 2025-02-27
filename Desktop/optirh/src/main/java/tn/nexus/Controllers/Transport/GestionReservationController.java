package tn.nexus.Controllers.Transport;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.paint.Color;
import tn.nexus.Entities.transport.ReservationTrajet;
import tn.nexus.Services.Transport.ReservationTrajetService;
import tn.nexus.Services.UserService;

import java.sql.SQLException;
import java.util.List;

public class GestionReservationController {


    @FXML private Label errorMessage;
    @FXML private TableView<ReservationTrajet> reservationTable;
    @FXML private TableColumn<ReservationTrajet, Integer> idColumn;
    @FXML private TableColumn<ReservationTrajet, String> disponibiliteColumn;
    @FXML private TableColumn<ReservationTrajet, Integer> userIdColumn;
    @FXML private TableColumn<ReservationTrajet, String> userNameColumn;

    private final ReservationTrajetService reservationService = new ReservationTrajetService();
    private final ObservableList<ReservationTrajet> reservationList = FXCollections.observableArrayList();

    private int vehiculeId; // ID du véhicule associé
    private int trajetId; // ID du trajet associé

    // Méthode pour initialiser l'ID du véhicule et du trajet
    public void setVehiculeAndTrajetId(int vehiculeId, int trajetId) {
        this.vehiculeId = vehiculeId;
        this.trajetId = trajetId;
        loadReservations(); // Charger les réservations associées à ce véhicule et trajet
    }

    @FXML
    public void initialize() {
        // Lier les colonnes de la TableView aux propriétés de l'entité ReservationTrajet
        disponibiliteColumn.setCellValueFactory(new PropertyValueFactory<>("disponibilite"));
        userNameColumn.setCellValueFactory(new PropertyValueFactory<>("userName"));


    }


    // Charger la liste des réservations associées au véhicule et au trajet
    private void loadReservations() {
        try {
            reservationList.clear();

            List<ReservationTrajet> reservations = reservationService.getReservationsByVehiculeAndTrajet(vehiculeId, trajetId);

            UserService userService = new UserService();

            // Récupérer et ajouter le nom de l'utilisateur pour chaque réservation
            for (ReservationTrajet reservation : reservations) {
                UserService UserService = new UserService();
                String userName = UserService.getUserNameById(reservation.getUserId());
                reservation.setUserName(userName);
            }

            reservationList.addAll(reservations);
            reservationTable.setItems(reservationList);

        } catch (SQLException e) {
            showError("Erreur lors du chargement des réservations : " + e.getMessage());
        }
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
