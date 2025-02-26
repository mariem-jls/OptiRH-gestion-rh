package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.stage.Stage;
import tn.nexus.Entities.reclamation.Reclamation;
import tn.nexus.Services.reclamation.ReclamationService;

import java.io.IOException;
import java.sql.SQLException;
import java.time.LocalDate;
import java.util.List;

public class ReclamationfrontController {
    @FXML
    private TableView<Reclamation> reclamationsTable;
    @FXML
    private TableColumn<Reclamation, Integer> idColumn;
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

    public void initialize() throws SQLException {

        descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
        statusColumn.setCellValueFactory(new PropertyValueFactory<>("status"));
        dateColumn.setCellValueFactory(new PropertyValueFactory<>("date"));

        List<Reclamation> reclamationList = reclamationService.showAll();
        ObservableList<Reclamation> observableReclamationList = FXCollections.observableArrayList(reclamationList);
        reclamationsTable.setItems(observableReclamationList);

        statusField.getItems().addAll("En attente", "En cours", "Résolue");

        // Ajouter une colonne d'action avec un bouton "Réponse"
        TableColumn<Reclamation, Void> actionColumn = new TableColumn<>("Action");
        actionColumn.setCellFactory(param -> new TableCell<>() {
            private final Button btn = new Button("Réponse");

            {
                btn.setOnAction(event -> {
                    Reclamation reclamation = getTableView().getItems().get(getIndex());
                    try {
                        FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/reponsefront.fxml"));
                        Parent root = loader.load();
                        ReponseViewController controller = loader.getController();
                        controller.setReclamationId(reclamation.getId());
                        Stage stage = new Stage();
                        stage.setScene(new Scene(root));
                        stage.setTitle("Les réponses");
                        stage.show();
                    } catch (IOException e) {
                        e.printStackTrace();
                    }
                });
            }



            @Override
            protected void updateItem(Void item, boolean empty) {
                super.updateItem(item, empty);
                if (empty) {
                    setGraphic(null);
                } else {
                    setGraphic(btn);
                }
            }
        });

        reclamationsTable.getColumns().add(actionColumn);
    }

    @FXML
    public void ajouterReclamation() throws SQLException {
        // Vérifier si les champs sont vides ou nuls
        if (descriptionField.getText() == null || descriptionField.getText().trim().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "La description ne peut pas être vide !");
            return;
        }
        if (statusField.getValue() == null || statusField.getValue().trim().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Le statut ne peut pas être vide !");
            return;
        }
        if (dateField.getValue() == null) {
            showAlert(Alert.AlertType.WARNING, "La date ne peut pas être vide !");
            return;
        }

        // Créer une nouvelle réclamation
        Reclamation reclamation = new Reclamation(
                descriptionField.getText(),
                statusField.getValue(),
                java.sql.Date.valueOf(dateField.getValue()),
                1 // Fixe l'utilisateur ID à 1
        );

        // Insérer la réclamation dans la base de données
        reclamationService.insert(reclamation);

        // Ajouter la réclamation à la TableView
        reclamationsTable.getItems().add(reclamation);

        // Effacer les champs après l'ajout
        clearFields();
    }

    @FXML
    public void modifierReclamation() throws SQLException {
        Reclamation selectedReclamation = reclamationsTable.getSelectionModel().getSelectedItem();
        if (selectedReclamation != null) {
            // Vérifier si les champs sont vides ou nuls
            if (descriptionField.getText() == null || descriptionField.getText().trim().isEmpty()) {
                showAlert(Alert.AlertType.WARNING, "La description ne peut pas être vide !");
                return;
            }
            if (statusField.getValue() == null || statusField.getValue().trim().isEmpty()) {
                showAlert(Alert.AlertType.WARNING, "Le statut ne peut pas être vide !");
                return;
            }
            if (dateField.getValue() == null) {
                showAlert(Alert.AlertType.WARNING, "La date ne peut pas être vide !");
                return;
            }

            // Mettre à jour la réclamation sélectionnée
            selectedReclamation.setDescription(descriptionField.getText());
            selectedReclamation.setStatus(statusField.getValue());
            selectedReclamation.setDate(java.sql.Date.valueOf(dateField.getValue()));

            // Mettre à jour la réclamation dans la base de données
            reclamationService.update(selectedReclamation);

            // Rafraîchir la TableView
            reclamationsTable.refresh();

            // Effacer les champs après la modification
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
            if (rowsAffected > 0) {
                showAlert(Alert.AlertType.INFORMATION, "Réclamation supprimée avec succès !");
            } else {
                showAlert(Alert.AlertType.WARNING, "La suppression a échoué !");
            }
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