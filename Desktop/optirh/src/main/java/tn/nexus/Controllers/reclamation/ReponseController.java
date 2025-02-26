package tn.nexus.Controllers.reclamation;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import tn.nexus.Entities.reclamation.Reponse;
import tn.nexus.Services.reclamation.ReponseService;

import java.sql.Date;
import java.time.LocalDate;
import java.util.List;

public class ReponseController {
    @FXML
    private TextArea descriptionField;
    @FXML
    private DatePicker dateField;
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
        reponsesTable.getSelectionModel().selectedItemProperty().addListener((obs, oldSelection, newSelection) -> {
            if (newSelection != null) {
                // Mettre à jour le champ description avec la valeur de la réponse sélectionnée
                descriptionField.setText(newSelection.getDescription());
            }
        });
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

    @FXML
    public void ajouterReponse() {
        // Vérifier si les champs sont vides ou nuls
        if (descriptionField.getText() == null || descriptionField.getText().trim().isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "La description ne peut pas être vide !");
            return;
        }
        if (dateField.getValue() == null) {
            showAlert(Alert.AlertType.WARNING, "La date ne peut pas être vide !");
            return;
        }
        if (dateField.getValue() == null || dateField.getValue().isBefore(LocalDate.now())) {
            showAlert(Alert.AlertType.WARNING, "Veuillez sélectionner une date valide (aujourd'hui ou plus tard).");
            return;
        }
        try {
            Reponse reponse = new Reponse(
                    0,
                    descriptionField.getText(),
                    Date.valueOf(dateField.getValue()),
                    reclamationId
            );
            reponseService.insert(reponse);
            loadReponses();
            clearFields();
        } catch (Exception e) {
            e.printStackTrace();
            showAlert(Alert.AlertType.ERROR, "Une erreur s'est produite lors de l'ajout de la réponse.");
        }
    }

    @FXML
    public void modifierReponse() {
        Reponse selectedReponse = reponsesTable.getSelectionModel().getSelectedItem();
        if (selectedReponse != null) {
            // Vérifier si les champs sont vides ou nuls
            if (descriptionField.getText() == null || descriptionField.getText().trim().isEmpty()) {
                showAlert(Alert.AlertType.WARNING, "La description ne peut pas être vide !");
                return;
            }
            if (dateField.getValue() == null) {
                showAlert(Alert.AlertType.WARNING, "La date ne peut pas être vide !");
                return;
            }

            try {
                selectedReponse.setDescription(descriptionField.getText());
                selectedReponse.setDate(Date.valueOf(dateField.getValue()));
                reponseService.update(selectedReponse);
                loadReponses();
            } catch (Exception e) {
                e.printStackTrace(); // L'erreur est simplement loguée, aucune alerte n'est affichée
            }
        } else {
            showAlert(Alert.AlertType.WARNING, "Aucune réponse sélectionnée !");
        }
    }

    @FXML
    public void supprimerReponse() {
        Reponse selectedReponse = reponsesTable.getSelectionModel().getSelectedItem();
        if (selectedReponse != null) {
            try {
                reponseService.delete(selectedReponse);
                loadReponses();
            } catch (Exception e) {
                e.printStackTrace();
                showAlert(Alert.AlertType.ERROR, "Une erreur s'est produite lors de la suppression de la réponse.");
            }
        } else {
            showAlert(Alert.AlertType.WARNING, "Aucune réponse sélectionnée !");
        }
    }

    @FXML
    public void clearFields() {
        descriptionField.clear();
        dateField.setValue(null);
    }

    private void showAlert(Alert.AlertType type, String message) {
        Alert alert = new Alert(type);
        alert.setTitle("Information");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}