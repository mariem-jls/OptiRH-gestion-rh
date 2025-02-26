package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.TableColumn;
import javafx.scene.control.TableView;
import javafx.scene.control.cell.PropertyValueFactory;
import tn.nexus.Entities.reclamation.Reponse;
import tn.nexus.Services.reclamation.ReponseService;

import java.sql.Date;
import java.util.List;

public class ReponseViewController {
    @FXML
    private TableView<Reponse> reponsesTable;

    @FXML
    private TableColumn<Reponse, String> descriptionColumn;
    @FXML
    private TableColumn<Reponse, Date> dateColumn;

    private int reclamationId;
    private final ReponseService reponseService = new ReponseService();

    public void setReclamationId(int reclamationId) {
        this.reclamationId = reclamationId;
        loadReponses();
    }

    @FXML
    public void initialize() {

        descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
        dateColumn.setCellValueFactory(new PropertyValueFactory<>("date"));
    }

    private void loadReponses() {
        try {
            List<Reponse> reponses = reponseService.getReponsesByReclamationId(reclamationId);
            ObservableList<Reponse> observableReponses = FXCollections.observableArrayList(reponses);
            reponsesTable.setItems(observableReponses);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}