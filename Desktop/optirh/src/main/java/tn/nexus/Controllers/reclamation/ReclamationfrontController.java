package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.collections.transformation.FilteredList;
import javafx.collections.transformation.SortedList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.stage.Stage;
import tn.nexus.Entities.reclamation.Reclamation;
import tn.nexus.Services.reclamation.EmailService;
import tn.nexus.Services.reclamation.ReclamationService;

import java.io.IOException;
import java.sql.SQLException;
import java.time.LocalDate;
import java.util.Objects;

public class ReclamationfrontController {
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
    @FXML
    private TextField searchField;
    @FXML
    private ComboBox<String> filterStatusField;

    private final ReclamationService reclamationService = new ReclamationService();
    private ObservableList<Reclamation> observableReclamationList;

    public void initialize() throws SQLException {
        if (reclamationsTable == null || descriptionColumn == null || statusColumn == null || dateColumn == null) {
            System.err.println("FXML components are not properly injected.");
            return;
        }
        if(statusField.getValue() == null) {
            statusField.setValue("En attente");
        }

        // Configuration des colonnes de la table
        descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
        statusColumn.setCellValueFactory(new PropertyValueFactory<>("status"));
        dateColumn.setCellValueFactory(new PropertyValueFactory<>("date"));

        // Chargement des données
        observableReclamationList = FXCollections.observableArrayList(reclamationService.showAll());

        // Configuration des filtres
        statusField.setItems(FXCollections.observableArrayList("En attente", "En cours", "Résolue"));
        filterStatusField.setItems(FXCollections.observableArrayList("Tous", "En attente", "En cours", "Résolue"));
        filterStatusField.setValue("Tous");

        FilteredList<Reclamation> filteredData = new FilteredList<>(observableReclamationList, p -> true);
        searchField.textProperty().addListener((observable, oldValue, newValue) -> applyFilters(filteredData));
        filterStatusField.valueProperty().addListener((observable, oldValue, newValue) -> applyFilters(filteredData));

        SortedList<Reclamation> sortedData = new SortedList<>(filteredData);
        sortedData.comparatorProperty().bind(reclamationsTable.comparatorProperty());
        reclamationsTable.setItems(sortedData);

        // Ajouter la colonne "Réponse"
        addResponseButtonToTable();
    }

    private void addResponseButtonToTable() {
        // Ajouter une colonne "Action" avec un bouton "Réponse"
        TableColumn<Reclamation, Void> actionColumn = new TableColumn<>("Action");
        actionColumn.setCellFactory(param -> new TableCell<>() {
            private final Button btn = new Button("Réponse");

            {
                btn.setStyle("-fx-background-color: #007BFF; -fx-text-fill: white; -fx-font-weight: bold; -fx-background-radius: 5;");
                btn.setOnAction(event -> {
                    Reclamation reclamation = getTableView().getItems().get(getIndex());
                    openReponseView(reclamation);
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
    }

    private void openReponseView(Reclamation reclamation) {
        try {
            // Charger la vue de réponse (fichier FXML)
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/reclamation/reponsefront.fxml"));
            Parent root = loader.load();

            // Passer l'ID de la réclamation à la vue de réponse
            ReponseViewController controller = loader.getController();
            controller.setReclamationId(reclamation.getId());

            // Afficher la fenêtre de réponse dans une nouvelle scène
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Répondre à la réclamation");
            stage.show();
        } catch (IOException e) {
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
    public void ajouterReclamation() throws SQLException {
        if (descriptionField.getText().isEmpty() || statusField.getValue() == null || dateField.getValue() == null) {
            showAlert(Alert.AlertType.WARNING, "Veuillez remplir tous les champs !");
            return;
        }

        if (dateField.getValue().isBefore(LocalDate.now())) {
            showAlert(Alert.AlertType.WARNING, "Veuillez sélectionner une date valide !");
            return;
        }

        Reclamation reclamation = new Reclamation(
                descriptionField.getText(),
                statusField.getValue(),
                java.sql.Date.valueOf(dateField.getValue()),
                1
        );
        reclamationService.insert(reclamation);
        observableReclamationList.add(reclamation);
        clearFields();
        String subject = "Nouvelle Réclamation Ajoutée";
        String content = "<html>" +
                "<body style='font-family: Arial, sans-serif; background: linear-gradient(to bottom, #000428, #004e92); padding: 20px; color: white;'>" +
                "<div style='max-width: 600px; margin: auto; background: rgba(255, 255, 255, 0.1); border-radius: 30px; " +
                "box-shadow: 0px 0px 40px rgba(0, 191, 255, 0.5); overflow: hidden; backdrop-filter: blur(10px);'>" +

                // En-tête avec nouvelle couleur de barre et logo géant
                "<div style='background: linear-gradient(to right, #ff6f61, #ff9a9e); padding: 50px; text-align: center; position: relative;'>" +
                "<div style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle, rgba(255, 111, 97, 0.3), rgba(255, 154, 158, 0));'></div>" +
                "<img src='https://i.imgur.com/uA7E20n.png' alt='Logo' style='width: 250px; display: block; margin: auto; filter: drop-shadow(0px 0px 20px rgba(0, 191, 255, 0.8));'>" +
                "<h1 style='color: white; margin-top: 20px; font-size: 40px; font-weight: bold; text-shadow: 0px 0px 15px rgba(0, 191, 255, 0.8);'>Nouvelle Réclamation Ajoutée</h1>" +
                "</div>" +

                // Contenu avec effets néon et couleurs vibrantes
                "<div style='padding: 30px; text-align: left;'>" +
                "<div style='background: rgba(255, 255, 255, 0.1); padding: 25px; border-radius: 20px; box-shadow: 0px 0px 20px rgba(0, 191, 255, 0.3); backdrop-filter: blur(10px);'>" +

                "<p style='font-size: 20px; color: white; margin-bottom: 20px;'>" +
                "<strong style='background: linear-gradient(to right, #ff416c, #ff4b2b); color: white; padding: 10px 15px; border-radius: 10px; box-shadow: 0px 0px 15px rgba(255, 65, 108, 0.8); display: inline-block;'>Description :</strong> " +
                "<span style='margin-left: 10px; text-shadow: 0px 0px 10px rgba(0, 191, 255, 0.8);'>yuppy yuppopoo</span></p>" +

                "<p style='font-size: 20px; color: white; margin-bottom: 20px;'>" +
                "<strong style='background: linear-gradient(to right, #00cdac, #02aab0); color: white; padding: 10px 15px; border-radius: 10px; box-shadow: 0px 0px 15px rgba(0, 205, 172, 0.8); display: inline-block;'>Statut :</strong> " +
                "<span style='color: #00cdac; font-weight: bold; margin-left: 10px; text-shadow: 0px 0px 10px rgba(0, 205, 172, 0.8);'>En attente</span></p>" +

                "<p style='font-size: 20px; color: white;'>" +
                "<strong style='background: linear-gradient(to right, #ff9a9e, #fad0c4); color: white; padding: 10px 15px; border-radius: 10px; box-shadow: 0px 0px 15px rgba(255, 154, 158, 0.8); display: inline-block;'>Date :</strong> " +
                "<span style='margin-left: 10px; text-shadow: 0px 0px 10px rgba(0, 191, 255, 0.8);'>2025-03-02</span></p>" +

                "</div>" +
                "</div>" +

                "</div>" + // Fin du conteneur principal
                "</body>" +
                "</html>";



        EmailService.sendEmail(subject, content);
    }

    @FXML
    public void modifierReclamation() throws SQLException {
        Reclamation selectedReclamation = reclamationsTable.getSelectionModel().getSelectedItem();
        if (selectedReclamation == null) {
            showAlert(Alert.AlertType.WARNING, "Aucune réclamation sélectionnée !");
            return;
        }

        if (descriptionField.getText().isEmpty() || statusField.getValue() == null || dateField.getValue() == null) {
            showAlert(Alert.AlertType.WARNING, "Veuillez remplir tous les champs !");
            return;
        }

        selectedReclamation.setDescription(descriptionField.getText());
        selectedReclamation.setStatus(statusField.getValue());
        selectedReclamation.setDate(java.sql.Date.valueOf(dateField.getValue()));
        reclamationService.update(selectedReclamation);
        reclamationsTable.refresh();
        clearFields();
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