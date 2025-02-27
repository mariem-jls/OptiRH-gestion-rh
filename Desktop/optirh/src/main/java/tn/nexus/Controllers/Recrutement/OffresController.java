package tn.nexus.Controllers.Recrutement;

import javafx.beans.property.SimpleStringProperty;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.fxml.Initializable;
import javafx.geometry.Pos;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Services.Recrutement.OffreService;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.IOException;
import java.net.URL;
import java.sql.SQLException;
import java.util.List;
import java.util.ResourceBundle;

public class OffresController implements Initializable , WrapWithSideBar {

    @FXML
    private TextField searchField;

    @FXML
    private TableView<Offre> tableOffres;

    @FXML
    private TableColumn<Offre, String> colPoste, colDescription, colStatut, colDate, colModeTravail, colTypeContrat, colLocalisation, colNiveauExperience, colNbPostes, colDateExpiration, colActions;
    @FXML
    private AnchorPane sideBar;

    private OffreService offreService = new OffreService();
    private ObservableList<Offre> offresList = FXCollections.observableArrayList();

    @Override
    public void initialize(URL location, ResourceBundle resources) {

        initializeSideBar(sideBar);

        // Lier les colonnes aux propriétés de l'objet Offre
        colPoste.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getPoste()));
        colDescription.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getDescription()));
        colStatut.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getStatut()));
        colDate.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getDateCreation().toString()));
        colModeTravail.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getModeTravail()));
        colTypeContrat.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getTypeContrat()));
        colLocalisation.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getLocalisation()));
        colNiveauExperience.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getNiveauExperience()));
        colNbPostes.setCellValueFactory(cellData -> new SimpleStringProperty(String.valueOf(cellData.getValue().getNbPostes())));
        colDateExpiration.setCellValueFactory(cellData -> new SimpleStringProperty(cellData.getValue().getDateExpiration().toString()));

        tableOffres.setColumnResizePolicy(TableView.UNCONSTRAINED_RESIZE_POLICY);

        // Ajout des boutons Modifier / Supprimer dans la colonne "Actions"
        colActions.setCellFactory(col -> new TableCell<Offre, String>() {
            private final Button editButton = new Button("Voir");
            private final Button deleteButton = new Button("Supprimer");
            private final HBox pane = new HBox(editButton, deleteButton);

            {
                // Configuration des boutons
                editButton.setPrefWidth(80);
                editButton.setMinWidth(80);
                editButton.setMaxWidth(80);

                deleteButton.setPrefWidth(100);
                deleteButton.setMinWidth(100);
                deleteButton.setMaxWidth(100);

                // Style optionnel
                editButton.getStyleClass().add("action-btn");
                deleteButton.getStyleClass().addAll("action-btn", "delete-btn");

                // Configuration du conteneur
                pane.setSpacing(10);
                pane.setAlignment(Pos.CENTER_LEFT);

                // Gestion des événements
                editButton.setOnAction(e -> handleEditOffer(getTableView().getItems().get(getIndex())));
                deleteButton.setOnAction(e -> handleDeleteOffer(getTableView().getItems().get(getIndex())));
            }

            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                setGraphic(empty ? null : pane);
            }
        });

        // Charger les offres
        loadOffres();
    }

    private void loadOffres() {
        try {
            List<Offre> offres = offreService.showAll();
            offresList.setAll(offres);
            tableOffres.setItems(offresList);
        } catch (SQLException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors du chargement des offres.", e.getMessage());
        }
    }

    @FXML
    private void handleSearch() {
        String query = searchField.getText().trim();
        if (query.isEmpty()) {
            tableOffres.setItems(offresList);
            return;
        }

        ObservableList<Offre> filteredList = offresList.filtered(offre ->
                offre.getPoste().toLowerCase().contains(query.toLowerCase())
        );

        tableOffres.setItems(filteredList);
    }

    @FXML
    private void handleAddOffer() {
        try {
            Parent root = FXMLLoader.load(getClass().getResource("/Recrutement/AjoutOffre.fxml"));
            Stage stage = (Stage) tableOffres.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException ex) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur de chargement de la page d'ajout", ex.getMessage());
        }
    }

    private void handleDeleteOffer(Offre offre) {
        if (offre == null) {
            showAlert(Alert.AlertType.WARNING, "Sélection invalide", "Aucune offre sélectionnée", "Veuillez sélectionner une offre à supprimer.");
            return;
        }

        try {
            int result = offreService.delete(offre);
            if (result > 0) {
                offresList.remove(offre);
                showAlert(Alert.AlertType.INFORMATION, "Succès", "Suppression réussie", "L'offre a été supprimée.");
            } else {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Suppression échouée", "Une erreur est survenue.");
            }
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la suppression", e.getMessage());
        }
    }

    private void handleEditOffer(Offre offre) {
        if (offre == null) {
            showAlert(Alert.AlertType.WARNING, "Sélection invalide", "Aucune offre sélectionnée", "Veuillez sélectionner une offre à modifier.");
            return;
        }

        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Recrutement/EditOffre.fxml"));
            Parent root = loader.load();
            EditOffreController controller = loader.getController();
            controller.initData(offre);

            Stage stage = (Stage) tableOffres.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur de chargement de la page de modification", e.getMessage());
        }
    }

    private void showAlert(Alert.AlertType alertType, String title, String header, String content) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(header);
        alert.setContentText(content);
        alert.showAndWait();
    }
}
