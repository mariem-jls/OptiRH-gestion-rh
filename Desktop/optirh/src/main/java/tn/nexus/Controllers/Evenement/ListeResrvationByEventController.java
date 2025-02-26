package tn.nexus.Controllers.Evenement;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.scene.control.Button;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.stage.Stage;
import tn.nexus.Entities.Evenement.Reservation_evenement;
import tn.nexus.Services.Evenement.Reservation_evenementServices;

import java.sql.SQLException;
import java.util.List;

public class ListeResrvationByEventController {

    @FXML
    private TableColumn<Reservation_evenement, String> dateReservation;

    @FXML
    private TableColumn<Reservation_evenement, String> emailC;

    @FXML
    private Button fermerFenetre;

    @FXML
    private TableColumn<Reservation_evenement, String> firstNameC;

    @FXML
    private TableColumn<Reservation_evenement, String> lastNameC;

    @FXML
    private TableColumn<Reservation_evenement, String> telephoneC;

    @FXML
    private TableView<Reservation_evenement> tableViewReservationsByEvent;


    private  Reservation_evenementServices serviceReservation = new Reservation_evenementServices();

    @FXML
    public void initialize() {
        firstNameC.setCellValueFactory(new PropertyValueFactory<>("firstName"));
        lastNameC.setCellValueFactory(new PropertyValueFactory<>("lastName"));
        emailC.setCellValueFactory(new PropertyValueFactory<>("email"));
        telephoneC.setCellValueFactory(new PropertyValueFactory<>("telephone"));
        dateReservation.setCellValueFactory(new PropertyValueFactory<>("dateReservation"));
    }

    public void loadReservations(int eventId) {
        try {
            List<Reservation_evenement> reservations = serviceReservation.getReservationsByEvent(eventId);
            ObservableList<Reservation_evenement> observableList = FXCollections.observableArrayList(reservations);
            tableViewReservationsByEvent.setItems(observableList);
        } catch (SQLException e) {
            System.out.println("Erreur lors du chargement des réservations : " + e.getMessage());
        }
    }


    @FXML
    void fermerFenetre(ActionEvent event) {
        Stage stage = (Stage) tableViewReservationsByEvent.getScene().getWindow();
        stage.close();
    }


}
