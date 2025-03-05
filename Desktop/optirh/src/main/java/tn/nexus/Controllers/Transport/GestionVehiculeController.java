package tn.nexus.Controllers.Transport;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.paint.Color;
import javafx.stage.Stage;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.Transport.VehiculeService;

import java.io.IOException;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class GestionVehiculeController {

    @FXML private TextField idField;
    @FXML
    private TableColumn<Vehicule, Integer> idColumn;
    @FXML private ComboBox<String> disponibiliteCombo;
    @FXML private TextField typeField;
    @FXML private TextField nbrPlaceField;
    @FXML private Label errorMessage;
    @FXML private TableView<Vehicule> vehiculeTable;
    @FXML private TableColumn<Vehicule, String> disponibiliteColumn;
    @FXML private TableColumn<Vehicule, String> typeColumn;
    @FXML private TableColumn<Vehicule, Integer> nbrPlaceColumn;
    @FXML
    private TableColumn<Vehicule, Integer> placesColumn;
    @FXML
    private TableColumn<Vehicule, Integer> trajetIdColumn;

    private final VehiculeService vehiculeService = new VehiculeService();
    private final ObservableList<Vehicule> vehiculeList = FXCollections.observableArrayList();

    private int trajetId; // ID du trajet associé

    // Méthode pour initialiser l'ID du trajet
    public void setTrajetId(int trajetId) {
        this.trajetId = trajetId;
        System.out.println("Trajet ID reçu dans GestionVehiculeController: " + trajetId);
        loadVehicules(); // Charger les véhicules associés à ce trajet
    }

    @FXML
    public void initialize() {

        // Lier les colonnes de la TableView aux propriétés de l'entité Vehicule
        disponibiliteColumn.setCellValueFactory(new PropertyValueFactory<>("disponibilite"));
        typeColumn.setCellValueFactory(new PropertyValueFactory<>("type"));
        nbrPlaceColumn.setCellValueFactory(new PropertyValueFactory<>("nbrplace"));

        // Ajouter un écouteur de sélection à la TableView
        vehiculeTable.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> {
                    if (newValue != null) {
                        fillFieldsWithSelectedVehicule(newValue);
                    }
                }
        );
    }

    // Remplir les champs de saisie avec les valeurs du véhicule sélectionné
    private void fillFieldsWithSelectedVehicule(Vehicule vehicule) {
        idField.setText(String.valueOf(vehicule.getId())); // Remplir le champ ID (invisible)
        disponibiliteCombo.setValue(vehicule.getDisponibilite());
        typeField.setText(vehicule.getType());
        nbrPlaceField.setText(String.valueOf(vehicule.getNbrplace()));
    }

    // Charger la liste des véhicules associés au trajet
    private void loadVehicules() {
        try {
            vehiculeList.clear();
            vehiculeList.addAll(vehiculeService.getVehiculesByTrajetId(trajetId));
            vehiculeTable.setItems(vehiculeList);
        } catch (SQLException e) {
            showError("Erreur lors du chargement des véhicules : " + e.getMessage());
        }
    }

    // Valider que tous les champs sont remplis
    private List<String> validateFields() {
        List<String> errors = new ArrayList<>();

        if (disponibiliteCombo.getValue() == null) {
            errors.add("La disponibilité doit être sélectionnée.");
        }
        if (typeField.getText().isEmpty()) {
            errors.add("Le type de véhicule est obligatoire.");
        }
        if (nbrPlaceField.getText().isEmpty()) {
            errors.add("Le nombre de places est obligatoire.");
        }

        return errors;
    }

    // Valider que le type de véhicule ne contient que des lettres, des chiffres ou des tirets
    private List<String> validateTypeVehicule() {
        List<String> errors = new ArrayList<>();

        String type = typeField.getText();
        if (!type.matches("[a-zA-Z0-9\\-]+")) {
            errors.add("Le type de véhicule ne doit contenir que des lettres, des chiffres ou des tirets.");
        }

        return errors;
    }

    // Valider que le nombre de places est un entier et ne dépasse pas 20
    private List<String> validateNombrePlaces() {
        List<String> errors = new ArrayList<>();

        try {
            int nbrPlace = Integer.parseInt(nbrPlaceField.getText());
            if (nbrPlace < 1 || nbrPlace > 20) {
                errors.add("Le nombre de places doit être compris entre 1 et 20.");
            }
        } catch (NumberFormatException e) {
            errors.add("Le nombre de places doit être un nombre valide.");
        }

        return errors;
    }

    // Gérer l'ajout d'un véhicule
    @FXML
    public void handleAjouterVehicule() {
        try {
            // Charger la nouvelle interface d'ajout
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/AjouterVehicule.fxml"));
            Parent root = loader.load();

            // Passer l'ID du trajet au contrôleur d'ajout
            AjouterVehiculeController controller = loader.getController();
            controller.setTrajetId(trajetId);

            // Définir un callback pour rafraîchir la table après l'ajout
            controller.setOnAjoutSuccess(this::loadVehicules);

            // Afficher la nouvelle interface
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Ajouter un Véhicule");
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            showError("Erreur lors de l'ouverture de l'interface d'ajout.");
        }
    }

    // Gérer la modification d'un véhicule
    @FXML
    public void handleModifierVehicule() {
        // Liste pour collecter les erreurs
        List<String> errors = new ArrayList<>();

        // Valider que tous les champs sont remplis
        errors.addAll(validateFields());

        // Valider que le type de véhicule est valide
        errors.addAll(validateTypeVehicule());

        // Valider que le nombre de places est valide
        errors.addAll(validateNombrePlaces());

        // Si des erreurs sont détectées, les afficher et arrêter l'exécution
        if (!errors.isEmpty()) {
            showErrors(errors);
            return;
        }

        try {
            // Convertir l'ID en entier
            int id = Integer.parseInt(idField.getText());
            String disponibilite = disponibiliteCombo.getValue();
            String type = typeField.getText();
            int nbrPlace = Integer.parseInt(nbrPlaceField.getText());
            Vehicule vehicule = new Vehicule(id, disponibilite, type, nbrPlace, trajetId, 0);

            // Modifier le véhicule dans la base de données
            int result = vehiculeService.update(vehicule);
            if (result > 0) {
                showSuccess("Véhicule modifié avec succès !");
                loadVehicules(); // Recharger la liste des véhicules
                clearFields();
            } else {
                showError("Erreur lors de la modification du véhicule.");
            }
        } catch (NumberFormatException e) {
            showError("L'ID doit être un nombre valide !");
        } catch (SQLException e) {
            showError("Erreur de base de données : " + e.getMessage());
        }
    }

    // Gérer la suppression d'un véhicule
    @FXML
    public void handleSupprimerVehicule() {
        String idText = idField.getText();

        if (idText.isEmpty()) {
            showError("Veuillez sélectionner un véhicule à supprimer !");
            return;
        }

        try {
            int id = Integer.parseInt(idText);
            Vehicule vehicule = new Vehicule(id, "", "", 0, trajetId, 0);

            int result = vehiculeService.delete(vehicule);
            if (result > 0) {
                showSuccess("Véhicule supprimé avec succès !");
                loadVehicules(); // Recharger la liste des véhicules
                clearFields();
            } else {
                showError("Erreur lors de la suppression du véhicule.");
            }
        } catch (NumberFormatException e) {
            showError("L'ID doit être un nombre valide !");
        } catch (SQLException e) {
            showError("Erreur de base de données : " + e.getMessage());
        }
    }

    // Gérer l'annulation (vider les champs)
    @FXML
    public void handleAnnuler() {
        clearFields();
    }

    // Afficher plusieurs messages d'erreur
    private void showErrors(List<String> errors) {
        StringBuilder errorMessageText = new StringBuilder();
        for (String error : errors) {
            errorMessageText.append("- ").append(error).append("\n");
        }
        showError(errorMessageText.toString());
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

    // Vider les champs de saisie
    private void clearFields() {
        idField.clear();
        disponibiliteCombo.getSelectionModel().clearSelection();
        typeField.clear();
        nbrPlaceField.clear();
    }

    @FXML
    public void handleOpenGestionReservation() {
        Vehicule selectedVehicule = vehiculeTable.getSelectionModel().getSelectedItem();
        if (selectedVehicule != null) {
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/GestionReservation.fxml"));
                Parent root = loader.load();

                // Passer l'ID du véhicule et du trajet au contrôleur de gestion des réservations
                GestionReservationController controller = loader.getController();
                controller.setVehiculeAndTrajetId(selectedVehicule.getId(), trajetId);

                // Afficher l'interface de gestion des réservations
                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Gestion des Réservations pour le Véhicule #" + selectedVehicule.getId());
                stage.show();
            } catch (IOException e) {
                e.printStackTrace();
            }
        } else {
            showError("Veuillez sélectionner un véhicule !");
        }
    }
    @FXML
    public void handleOpenModifierVehicule() {
        Vehicule selectedVehicule = vehiculeTable.getSelectionModel().getSelectedItem();
        if (selectedVehicule != null) {
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/ModifierVehicule.fxml"));
                Parent root = loader.load();

                // Passer le véhicule sélectionné au contrôleur de modification
                ModifierVehiculeController controller = loader.getController();
                controller.setVehicule(selectedVehicule);

                // Définir un callback pour rafraîchir la TableView après modification
                controller.setOnModificationSuccess(this::loadVehicules);

                // Afficher la fenêtre de modification
                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Modifier un Véhicule");
                stage.show();
            } catch (IOException e) {
                e.printStackTrace();
            }
        } else {
            showError("Veuillez sélectionner un véhicule à modifier !");
        }
    }
}