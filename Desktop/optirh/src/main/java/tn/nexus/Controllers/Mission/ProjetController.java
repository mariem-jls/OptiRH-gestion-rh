package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.ProjetService;
import tn.nexus.Utils.WrapWithSideBar;

import java.sql.SQLException;
import java.sql.Timestamp;

public class ProjetController implements WrapWithSideBar {

    @FXML
    private TableView<Projet> projectTable;

    @FXML
    private TableColumn<Projet, Integer> idColumn;

    @FXML
    private TableColumn<Projet, String> nomColumn;

    @FXML
    private TableColumn<Projet, String> descriptionColumn;

    @FXML
    private TableColumn<Projet, String> userNomColumn;

    @FXML
    private TableColumn<Projet, Timestamp> createdAtColumn;

    @FXML
    private AnchorPane sideBar;

    @FXML
    private Button viewMissionsButton; // Bouton pour afficher les missions du projet sélectionné

    private final ProjetService projetService = new ProjetService();

    @FXML
    public void initialize() {
        try {
            initializeSideBar(sideBar);

            // Configurer les colonnes de la TableView
            idColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
            nomColumn.setCellValueFactory(new PropertyValueFactory<>("nom"));
            descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
            userNomColumn.setCellValueFactory(new PropertyValueFactory<>("userNom"));
            createdAtColumn.setCellValueFactory(new PropertyValueFactory<>("createdAt"));

            // Charger les projets dans la TableView
            refreshProjectTable();

            // Configurer le bouton pour afficher les missions du projet sélectionné
            viewMissionsButton.setOnAction(event -> handleViewMissions());
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors du chargement des projets : " + e.getMessage());
        }
    }

    // Rafraîchir la TableView des projets
    private void refreshProjectTable() throws SQLException {
        projectTable.getItems().setAll(projetService.showAll2());
    }

    @FXML
    private void handleAddProject() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/AjouterProjet.fxml"));
            Parent root = loader.load();

            // Obtenir le contrôleur de la fenêtre d'ajout
            AjouterProjetController ajouterProjetController = loader.getController();

            // Définir le callback pour rafraîchir la TableView après l'ajout
            ajouterProjetController.setOnProjectAdded(() -> {
                try {
                    refreshProjectTable();
                } catch (SQLException e) {
                    showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors du rafraîchissement des projets : " + e.getMessage());
                }
            });

            // Créer une nouvelle fenêtre pour afficher l'interface AjouterProjet
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Ajouter un projet");
            stage.showAndWait(); // Attendre que la fenêtre soit fermée
        } catch (Exception e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de l'ouverture de la fenêtre d'ajout : " + e.getMessage());
        }
    }

    @FXML
    private void handleUpdateProject() {
        // Récupérer le projet sélectionné dans la TableView
        Projet projetSelectionne = projectTable.getSelectionModel().getSelectedItem();

        if (projetSelectionne == null) {
            showAlert(Alert.AlertType.WARNING, "Aucun projet sélectionné", "Veuillez sélectionner un projet à modifier.");
            return;
        }

        try {
            // Charger le fichier FXML de l'interface de modification
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/ModifierProjet.fxml"));
            Parent root = loader.load();

            // Passer le projet sélectionné au contrôleur de modification
            ModifierProjetController controller = loader.getController();
            controller.setProjet(projetSelectionne);

            // Créer une nouvelle fenêtre pour afficher l'interface de modification
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Modifier un projet");
            stage.showAndWait(); // Attendre que la fenêtre soit fermée

            // Rafraîchir la TableView après la modification
            refreshProjectTable();
        } catch (Exception e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la modification du projet : " + e.getMessage());
        }
    }

    @FXML
    private void handleDeleteProject() {
        // Récupérer le projet sélectionné dans la TableView
        Projet projetSelectionne = projectTable.getSelectionModel().getSelectedItem();

        if (projetSelectionne == null) {
            showAlert(Alert.AlertType.WARNING, "Aucun projet sélectionné", "Veuillez sélectionner un projet à supprimer.");
            return;
        }

        // Confirmer la suppression avec l'utilisateur
        Alert confirmation = new Alert(Alert.AlertType.CONFIRMATION);
        confirmation.setTitle("Confirmation de suppression");
        confirmation.setHeaderText(null);
        confirmation.setContentText("Êtes-vous sûr de vouloir supprimer le projet \"" + projetSelectionne.getNom() + "\" ?");

        if (confirmation.showAndWait().orElse(ButtonType.CANCEL) == ButtonType.OK) {
            try {
                // Supprimer le projet de la base de données
                projetService.delete(projetSelectionne);

                // Rafraîchir la TableView après la suppression
                refreshProjectTable();

                showAlert(Alert.AlertType.INFORMATION, "Succès", "Le projet a été supprimé avec succès.");
            } catch (SQLException e) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la suppression du projet : " + e.getMessage());
            }
        }
    }
    @FXML
    private void handleViewMissions() {
        // Récupérer le projet sélectionné
        Projet projetSelectionne = projectTable.getSelectionModel().getSelectedItem();

        if (projetSelectionne == null) {
            showAlert(Alert.AlertType.WARNING, "Aucun projet sélectionné", "Veuillez sélectionner un projet pour afficher ses missions.");
            return;
        }

        try {
            // Charger le fichier FXML de la page des missions
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Mission.fxml"));
            Parent root = loader.load();

            // Passer le projet sélectionné au contrôleur des missions
            MissionController missionController = loader.getController();
            missionController.setProjet(projetSelectionne);

            // Créer une nouvelle fenêtre pour afficher les missions
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Missions du projet : " + projetSelectionne.getNom());
            stage.show();
        } catch (Exception e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de l'ouverture de la page des missions : " + e.getMessage());
        }
    }
    // Afficher une alerte
    private void showAlert(Alert.AlertType alertType, String title, String message) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}