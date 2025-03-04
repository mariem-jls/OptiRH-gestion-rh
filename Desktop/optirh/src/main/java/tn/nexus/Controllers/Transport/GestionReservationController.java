package tn.nexus.Controllers.Transport;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.paint.Color;
import tn.nexus.Entities.User;
import tn.nexus.Entities.transport.ReservationTrajet;
import tn.nexus.Services.Transport.ReservationTrajetService;
import tn.nexus.Services.UserService;

import java.sql.SQLException;
import java.util.List;

public class GestionReservationController {

    @FXML private Label errorMessage;
    @FXML private TableView<ReservationTrajet> reservationTable;

    @FXML private TableColumn<ReservationTrajet, String> disponibiliteColumn;
    @FXML private TableColumn<ReservationTrajet, String> userNameColumn;
    @FXML private TableColumn<ReservationTrajet, String> userEmailColumn;
    @FXML private TableColumn<ReservationTrajet, String> userRoleColumn;
    @FXML private TableColumn<ReservationTrajet, String> userAddressColumn;

    private final ReservationTrajetService reservationService = new ReservationTrajetService();
    private final UserService userService = new UserService();
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
        userNameColumn.setCellValueFactory(cellData -> {
            try {
                User user = userService.getUserById(cellData.getValue().getUserId());
                return new javafx.beans.property.SimpleStringProperty(user.getNom());
            } catch (SQLException e) {
                return new javafx.beans.property.SimpleStringProperty("N/A");
            }
        });
        userEmailColumn.setCellValueFactory(cellData -> {
            try {
                User user = userService.getUserById(cellData.getValue().getUserId());
                return new javafx.beans.property.SimpleStringProperty(user.getEmail());
            } catch (SQLException e) {
                return new javafx.beans.property.SimpleStringProperty("N/A");
            }
        });
        userRoleColumn.setCellValueFactory(cellData -> {
            try {
                User user = userService.getUserById(cellData.getValue().getUserId());
                return new javafx.beans.property.SimpleStringProperty(user.getRole().name());
            } catch (SQLException e) {
                return new javafx.beans.property.SimpleStringProperty("N/A");
            }
        });
        userAddressColumn.setCellValueFactory(cellData -> {
            try {
                User user = userService.getUserById(cellData.getValue().getUserId());
                return new javafx.beans.property.SimpleStringProperty(user.getAddress());
            } catch (SQLException e) {
                return new javafx.beans.property.SimpleStringProperty("N/A");
            }
        });
    }

    private void loadReservations() {
        try {
            reservationList.clear();

            // Récupérer les réservations pour ce véhicule et ce trajet
            List<ReservationTrajet> reservations = reservationService.getReservationsByVehiculeAndTrajet(vehiculeId, trajetId);

            // Ajouter les réservations à la liste observable
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