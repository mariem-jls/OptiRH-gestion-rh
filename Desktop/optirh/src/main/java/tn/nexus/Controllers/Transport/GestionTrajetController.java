
package tn.nexus.Controllers.Transport;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.scene.paint.Color;
import javafx.stage.Stage;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Services.Transport.TrajetService;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.IOException;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;

public class GestionTrajetController implements WrapWithSideBar {

    @FXML private TextField idField;
    @FXML private TextField typeField;
    @FXML private TextField stationField;
    @FXML private TextField departField;
    @FXML private TextField arriveField;
    @FXML private Label errorMessage;
    @FXML private TableView<Trajet> trajetTable;
    @FXML private TableColumn<Trajet, Integer> idColumn;
    @FXML private TableColumn<Trajet, String> typeColumn;
    @FXML private TableColumn<Trajet, String> stationColumn;
    @FXML private TableColumn<Trajet, String> departColumn;
    @FXML private TableColumn<Trajet, String> arriveColumn;
@FXML
        AnchorPane sideBar;
    private final TrajetService trajetService = new TrajetService();
    private final ObservableList<Trajet> trajetList = FXCollections.observableArrayList();

    @FXML
    public void initialize() {
        // Lier les colonnes de la TableView aux propriétés de l'entité Trajet

        initializeSideBar(sideBar);
        typeColumn.setCellValueFactory(new PropertyValueFactory<>("type"));
        stationColumn.setCellValueFactory(new PropertyValueFactory<>("station"));
        departColumn.setCellValueFactory(new PropertyValueFactory<>("depart"));
        arriveColumn.setCellValueFactory(new PropertyValueFactory<>("arrive"));
        // Charger la liste des trajets dans le tableau
        loadTrajets();

        // Ajouter un écouteur de sélection à la TableView
        trajetTable.getSelectionModel().selectedItemProperty().addListener(
                (observable, oldValue, newValue) -> {
                    if (newValue != null) {
                        // Remplir les champs de saisie avec les valeurs de la ligne sélectionnée
                        fillFieldsWithSelectedTrajet(newValue);
                    }
                }
        );
    }

    // Remplir les champs de saisie avec les valeurs du trajet sélectionné
    private void fillFieldsWithSelectedTrajet(Trajet trajet) {
        typeField.setText(trajet.getType());
        stationField.setText(trajet.getStation());
        departField.setText(trajet.getDepart());
        arriveField.setText(trajet.getArrive());
    }

    // Charger la liste des trajets depuis la base de données
    private void loadTrajets() {
        try {
            trajetList.clear();
            trajetList.addAll(trajetService.showAll());
            trajetTable.setItems(trajetList);
        } catch (SQLException e) {
            showError("Erreur lors du chargement des trajets : " + e.getMessage());
        }
    }

    // Valider le champ "Type"
    private List<String> validateType(String type) {
        List<String> errors = new ArrayList<>();

        // Vérifier la longueur (2 à 20 caractères)
        if (type.length() < 2 || type.length() > 20) {
            errors.add("Le type doit contenir entre 2 et 20 caractères.");
        }

        // Vérifier que le type ne contient que des lettres et des espaces
        if (!type.matches("[a-zA-Z\\s]+")) {
            errors.add("Le type ne doit contenir que des lettres et des espaces.");
        }

        return errors;
    }

    // Valider le champ "Station"
    private List<String> validateStation(String station) {
        List<String> errors = new ArrayList<>();

        // Vérifier la longueur (2 à 20 caractères)
        if (station.length() < 2 || station.length() > 20) {
            errors.add("La station doit contenir entre 2 et 20 caractères.");
        }

        // Vérifier que la station ne contient pas de caractères spéciaux interdits
        if (!station.matches("[a-zA-Z0-9\\s\\-]+")) {
            errors.add("La station ne doit contenir que des lettres, des chiffres, des espaces et des tirets.");
        }

        return errors;
    }

    // Gérer l'ajout d'un trajet
    @FXML
    public void handleAjouterTrajet() {
        try {
            // Charger la nouvelle interface d'ajout
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/AjouterTrajet.fxml"));
            Parent root = loader.load();

            // Passer le contrôleur de l'interface d'ajout
            AjouterTrajetController controller = loader.getController();

            // Définir un callback pour rafraîchir la table après l'ajout
            controller.setOnAjoutSuccess(() -> {
                loadTrajets(); // Recharger les trajets dans la table
            });

            // Afficher la nouvelle interface
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Ajouter un Trajet");
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            showError("Erreur lors de l'ouverture de l'interface d'ajout.");
        }
    }

    // Gérer la modification d'un trajet
    @FXML
    public void handleModifierTrajet() {
        Trajet selectedTrajet = trajetTable.getSelectionModel().getSelectedItem();
        if (selectedTrajet != null) {
            try {
                // Charger la nouvelle interface de modification
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/ModifierTrajet.fxml"));
                Parent root = loader.load();

                // Passer le trajet sélectionné au contrôleur de modification
                ModifierTrajetController controller = loader.getController();
                controller.setTrajet(selectedTrajet);

                // Définir un callback pour rafraîchir la table après la modification
                controller.setOnModificationSuccess(() -> {
                    loadTrajets(); // Recharger les trajets dans la table
                });

                // Afficher la nouvelle interface
                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Modifier le Trajet #" + selectedTrajet.getId());
                stage.show();
            } catch (IOException e) {
                e.printStackTrace();
                showError("Erreur lors de l'ouverture de l'interface de modification.");
            }
        } else {
            showError("Veuillez sélectionner un trajet à modifier !");
        }
    }

    // Gérer la suppression d'un trajet
    @FXML
    public void handleSupprimerTrajet() {
        Trajet selectedTrajet = trajetTable.getSelectionModel().getSelectedItem(); // Récupération du trajet sélectionné

        if (selectedTrajet == null) {
            showError("Veuillez sélectionner un trajet à supprimer !");
            return;
        }

        // Demande de confirmation
        Alert alert = new Alert(Alert.AlertType.CONFIRMATION);
        alert.setTitle("Confirmation de suppression");
        alert.setHeaderText(null);
        alert.setContentText("Êtes-vous sûr de vouloir supprimer ce trajet ?");

        if (alert.showAndWait().orElse(ButtonType.CANCEL) != ButtonType.OK) {
            return; // Annulation si l'utilisateur choisit "Annuler"
        }

        try {
            int result = trajetService.delete(selectedTrajet);
            if (result > 0) {
                showSuccess("Trajet supprimé avec succès !");
                loadTrajets(); // Recharger la liste des trajets
                clearFields();
            } else {
                showError("Erreur lors de la suppression du trajet.");
            }
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
        typeField.clear();
        stationField.clear();
        departField.clear();
        arriveField.clear();
    }

    @FXML
    public void handleOpenGestionVehicule() {
        Trajet selectedTrajet = trajetTable.getSelectionModel().getSelectedItem();
        if (selectedTrajet != null) {
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/transport/GestionVehicule.fxml"));
                Parent root = loader.load();

                // Passer l'ID du trajet sélectionné au contrôleur de gestion des véhicules
                GestionVehiculeController controller = loader.getController();
                controller.setTrajetId(selectedTrajet.getId());

                // Afficher l'interface de gestion des véhicules
                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Gestion des Véhicules pour le Trajet #" + selectedTrajet.getId());
                stage.show();
            } catch (IOException e) {
                e.printStackTrace();
            }
        } else {
            showError("Veuillez sélectionner un trajet !");
        }
    }

    // Méthode pour ouvrir la fenêtre des statistiques
    public void handleOpenStatistiques(ActionEvent event) {
        try {
            // Charger le fichier FXML des statistiques
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Transport/Statistique.fxml"));
            Parent root = loader.load();

            // Créer une nouvelle scène
            Scene scene = new Scene(root);

            // Créer une nouvelle fenêtre
            Stage stage = new Stage();
            stage.setTitle("Statistiques des Réservations");
            stage.setScene(scene);

            // Afficher la fenêtre
            stage.show();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}