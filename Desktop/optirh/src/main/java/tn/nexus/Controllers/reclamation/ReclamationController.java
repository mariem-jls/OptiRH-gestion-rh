package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
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
    private TextField descriptionField;
    @FXML
    private DatePicker dateField;
    @FXML
    private ComboBox<String> statusField;

    private final ReclamationService reclamationService = new ReclamationService();

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
            ObservableList<Reclamation> observableReclamationList = FXCollections.observableArrayList(reclamationList);
            reclamationsTable.setItems(observableReclamationList);

            // Remplissage de la ComboBox
            statusField.setItems(FXCollections.observableArrayList("En attente", "En cours", "Résolue"));
            if(statusField.getValue() == null) {
                statusField.setValue("en attente");
            }

            // Ajout d'une colonne "Action" avec un bouton "Réponse"
            TableColumn<Reclamation, Void> actionColumn = new TableColumn<>("Action");
            actionColumn.setCellFactory(param -> new TableCell<>() {
                private final Button btn = new Button("Réponse");
                {
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
            reclamationsTable.getColumns().add(actionColumn);

            // Listener de sélection de table
            reclamationsTable.getSelectionModel().selectedItemProperty().addListener((obs, oldSelection, newSelection) -> {
                if (newSelection != null) {
                    descriptionField.setText(newSelection.getDescription());
                    statusField.setValue(newSelection.getStatus());
                    dateField.setValue(newSelection.getDate().toLocalDate());
                }
            });
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    @FXML
    public void ajouterReclamation() throws SQLException {
        String description = descriptionField.getText();
        if (description == null || description.trim().length() < 2) {
            showAlert(Alert.AlertType.WARNING, "La description doit contenir au moins 2 caractères !");
            return;
        }
        if (dateField.getValue() == null || dateField.getValue().isBefore(LocalDate.now())) {
            showAlert(Alert.AlertType.WARNING, "Veuillez sélectionner une date valide (aujourd'hui ou plus tard).");
            return;
        }

        String status = statusField.getValue() != null ? statusField.getValue() : "En attente";
        if (dateField.getValue() == null) {
            showAlert(Alert.AlertType.WARNING, "La date ne peut pas être vide !");
            return;
        }

        Reclamation reclamation = new Reclamation(description, status, java.sql.Date.valueOf(dateField.getValue()), 1);
        reclamationService.insert(reclamation);
        reclamationsTable.getItems().add(reclamation);
        clearFields();
    }

    @FXML
    public void modifierReclamation() throws SQLException {
        Reclamation selectedReclamation = reclamationsTable.getSelectionModel().getSelectedItem();
        if (selectedReclamation != null) {
            selectedReclamation.setDescription(descriptionField.getText());
            selectedReclamation.setStatus(statusField.getValue());
            selectedReclamation.setDate(java.sql.Date.valueOf(dateField.getValue()));
            reclamationService.update(selectedReclamation);
            reclamationsTable.refresh();
            clearFields();
        } else {
            showAlert(Alert.AlertType.WARNING, "Aucune réclamation sélectionnée !");
        }
    }

    @FXML
    public void supprimerReclamation() throws SQLException {
        Reclamation selectedReclamation = reclamationsTable.getSelectionModel().getSelectedItem();
        if (selectedReclamation != null) {
            int rowsAffected = reclamationService.delete(selectedReclamation);
            reclamationsTable.getItems().remove(selectedReclamation);
            showAlert(rowsAffected > 0 ? Alert.AlertType.INFORMATION : Alert.AlertType.WARNING, "Suppression effectuée");
        } else {
            showAlert(Alert.AlertType.WARNING, "Aucune réclamation sélectionnée !");
        }
    }

    @FXML
    public void clearFields() {
        descriptionField.clear();
        dateField.setValue(null);
        statusField.setValue(null);
    }

    private void showAlert(Alert.AlertType type, String message) {
        Alert alert = new Alert(type);
        alert.setTitle("Information");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}
