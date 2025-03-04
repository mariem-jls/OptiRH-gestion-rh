package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.collections.transformation.FilteredList;
import javafx.collections.transformation.SortedList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.fxml.Initializable;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.stage.Stage;
import tn.nexus.Entities.reclamation.Reclamation;
import tn.nexus.Services.reclamation.ReclamationService;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.IOException;
import java.net.URL;
import java.sql.SQLException;
import java.time.LocalDate;
import java.util.List;
import java.util.Objects;
import java.util.ResourceBundle;

public class ReclamationController implements Initializable, WrapWithSideBar {
    @FXML
    private AnchorPane sideBar;
    @FXML
    private TableView<Reclamation> reclamationsTable;
    @FXML
    private TableColumn<Reclamation, String> descriptionColumn;
    @FXML
    private TableColumn<Reclamation, String> statusColumn;
    @FXML
    private TableColumn<Reclamation, LocalDate> dateColumn;
    @FXML
    private TextField searchField;
    @FXML
    private ComboBox<String> filterStatusField;

    private final ReclamationService reclamationService = new ReclamationService();
    private ObservableList<Reclamation> observableReclamationList;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        initializeSideBar(sideBar);
        try {
            // Configuration des colonnes
            descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
            statusColumn.setCellValueFactory(new PropertyValueFactory<>("status"));
            dateColumn.setCellValueFactory(new PropertyValueFactory<>("date"));

            // Chargement des données
            List<Reclamation> reclamationList = reclamationService.showAll();
            observableReclamationList = FXCollections.observableArrayList(reclamationList);

            // Configuration des filtres
            filterStatusField.setItems(FXCollections.observableArrayList("Tous", "En attente", "En cours", "Résolue"));
            filterStatusField.setValue("Tous");

            // Création d'une FilteredList pour la recherche et le filtrage
            FilteredList<Reclamation> filteredData = new FilteredList<>(observableReclamationList, p -> true);

            // Ajout des écouteurs pour la recherche et le filtrage
            searchField.textProperty().addListener((observable, oldValue, newValue) -> applyFilters(filteredData));
            filterStatusField.valueProperty().addListener((observable, oldValue, newValue) -> applyFilters(filteredData));

            // Création d'une SortedList pour trier les données
            SortedList<Reclamation> sortedData = new SortedList<>(filteredData);
            sortedData.comparatorProperty().bind(reclamationsTable.comparatorProperty());

            // Liaison des données filtrées et triées à la table
            reclamationsTable.setItems(sortedData);

            // Ajout d'une colonne "Action" avec un bouton "Réponse"
            TableColumn<Reclamation, Void> actionColumn = new TableColumn<>("Action");
            actionColumn.setCellFactory(param -> new TableCell<>() {
                private final Button btn = new Button("Réponse");
                {
                    btn.setStyle("-fx-background-color: #007BFF; -fx-text-fill: white; -fx-font-weight: bold; -fx-background-radius: 5;");
                    btn.setOnAction(event -> {
                        Reclamation reclamation = getTableView().getItems().get(getIndex());
                        try {
                            FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/ReponseForm.fxml"));
                            Parent root = loader.load();
                            ReponseController controller = loader.getController();
                            controller.setReclamationId(reclamation.getId());
                            Stage stage = new Stage();
                            stage.setScene(new Scene(root));
                            stage.setTitle("Gérer les réponses");
                            stage.show();
                        } catch (IOException e) {
                            e.printStackTrace();
                        }
                    });
                }

                @Override
                protected void updateItem(Void item, boolean empty) {
                    super.updateItem(item, empty);
                    setGraphic(empty ? null : btn);
                }
            });

            // Ajouter la colonne "Action" à la table
            reclamationsTable.getColumns().add(actionColumn);

        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void applyFilters(FilteredList<Reclamation> filteredData) {
        String searchKeyword = searchField.getText().toLowerCase();
        String selectedStatus = filterStatusField.getValue();

        filteredData.setPredicate(reclamation -> {
            if (reclamation == null) return false;
            boolean statusMatches = Objects.equals(selectedStatus, "Tous") || Objects.equals(reclamation.getStatus(), selectedStatus);
            boolean searchMatches = searchKeyword.isEmpty() || reclamation.getDescription().toLowerCase().contains(searchKeyword);
            return statusMatches && searchMatches;
        });
    }

    @FXML
    public void supprimerReclamation() throws SQLException {
        Reclamation selectedReclamation = reclamationsTable.getSelectionModel().getSelectedItem();
        if (selectedReclamation == null) {
            showAlert(Alert.AlertType.WARNING, "Aucune réclamation sélectionnée !");
            return;
        }

        if (reclamationService.delete(selectedReclamation) > 0) {
            observableReclamationList.remove(selectedReclamation);
            showAlert(Alert.AlertType.INFORMATION, "Réclamation supprimée !");
        } else {
            showAlert(Alert.AlertType.WARNING, "Échec de la suppression !");
        }
    }

    private void showAlert(Alert.AlertType type, String message) {
        Alert alert = new Alert(type);
        alert.setTitle("Information");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}