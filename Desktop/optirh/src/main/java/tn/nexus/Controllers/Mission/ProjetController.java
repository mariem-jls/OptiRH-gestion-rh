package tn.nexus.Controllers.Mission;

import com.sun.javafx.charts.Legend;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.User;
import tn.nexus.Services.Mission.ProjetService;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.File;
import java.io.IOException;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;

public class ProjetController implements WrapWithSideBar {
    @FXML
    private ComboBox<User> userFilterCombo;
    @FXML
    private CheckBox completedFilterCheck;
    private MissionService missionService;
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
    private TextField emailSearchField;
    @FXML
    private AnchorPane sideBar;

    @FXML
    private Button viewMissionsButton; // Bouton pour afficher les missions du projet sélectionné

    private final ProjetService projetService = new ProjetService();

    @FXML
    public void initialize() {
        try {
            initializeSideBar(sideBar);

            // Configuration des colonnes
            idColumn.setCellValueFactory(new PropertyValueFactory<>("id"));
            nomColumn.setCellValueFactory(new PropertyValueFactory<>("nom"));
            descriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
            userNomColumn.setCellValueFactory(new PropertyValueFactory<>("userNom"));
            createdAtColumn.setCellValueFactory(new PropertyValueFactory<>("createdAt"));
            viewMissionsButton.setOnAction(event -> handleViewMissions());
            // Initialisation des filtres
            initializeSearchField();
            refreshProjectTable();

            viewMissionsButton.setOnAction(event -> handleViewMissions());

        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors du chargement : " + e.getMessage());
        }
    }

    private void initializeSearchField() {
        // Listener pour la recherche en temps réel
        emailSearchField.textProperty().addListener((obs, oldVal, newVal) -> {
            try {
                refreshTable();
            } catch (SQLException e) {
                showAlert(Alert.AlertType.ERROR, "Erreur", e.getMessage());
            }
        });

        // Listener pour la case à cocher
        completedFilterCheck.selectedProperty().addListener((obs, oldVal, newVal) -> {
            try {
                refreshTable();
            } catch (SQLException e) {
                showAlert(Alert.AlertType.ERROR, "Erreur", e.getMessage());
            }
        });
    }

    private void refreshTable() throws SQLException {
        List<Projet> projets;
        String searchEmail = emailSearchField.getText().trim();

        if (completedFilterCheck.isSelected()) {
            projets = projetService.showCompletedProjects();
            if (!searchEmail.isEmpty()) {
                projets.retainAll(projetService.searchProjectsByUserEmail(searchEmail));
            }
        } else {
            projets = searchEmail.isEmpty() ?
                    projetService.showAll2() :
                    projetService.searchProjectsByUserEmail(searchEmail);
        }

        projectTable.getItems().setAll(projets);
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
            e.printStackTrace(); // Afficher l'exception complète pour mieux comprendre le problème
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
    private void refreshProjectTable() throws SQLException {
        refreshTable();
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
    private void initializeFilters() throws SQLException {
        if (userFilterCombo != null) {
            // Récupération des chefs de projet uniquement
            userFilterCombo.getItems().addAll(projetService.getAllChefProjet());

            // Ajout de l'option "Tous"
            userFilterCombo.getItems().add(0, new User(-1, "Tous les projets"));
            userFilterCombo.getSelectionModel().selectFirst();

            // Rafraîchissement automatique
            userFilterCombo.valueProperty().addListener((obs, oldVal, newVal) -> {
                try {
                    refreshTable();
                } catch (SQLException e) {
                    throw new RuntimeException(e);
                }
            });
        } else {
            showAlert(Alert.AlertType.ERROR, "Erreur", "ComboBox 'userFilterCombo' non initialisé.");
        }
    }

    @FXML
    private void handleExportPDF() {
        try {
            // Récupérer tous les projets
            List<Projet> projets = projectTable.getItems();

            // Configurer le FileChooser
            FileChooser fileChooser = new FileChooser();
            fileChooser.setTitle("Exporter les projets en PDF");
            fileChooser.getExtensionFilters().add(
                    new FileChooser.ExtensionFilter("Fichiers PDF", "*.pdf")
            );

            // Afficher la boîte de dialogue de sauvegarde
            File file = fileChooser.showSaveDialog(projectTable.getScene().getWindow());

            if (file != null) {
                // Exporter les projets en PDF
                PDFExporter.exportProjectsToPDF(projets, file.getAbsolutePath());

                // Afficher une confirmation
                Alert alert = new Alert(Alert.AlertType.INFORMATION);
                alert.setTitle("Export réussi");
                alert.setHeaderText(null);
                alert.setContentText("Les projets ont été exportés avec succès !");
                alert.showAndWait();
            }
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur",
                    "Erreur lors de la récupération des données : " + e.getMessage());
        } catch (Exception e) {
            showAlert(Alert.AlertType.ERROR, "Erreur",
                    "Erreur lors de l'export PDF : " + e.getMessage());
        }
    }
    public Mission getMission(int missionId) {
        try {

            return missionService.getMissionById(missionId);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de récupérer la mission : " + e.getMessage(), Alert.AlertType.ERROR);
            return null;
        }
    }

    private void showAlert(String title, String message, Alert.AlertType type) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }


    @FXML
    private void showStatisticsDashboard() {
        try {
            Parent root = FXMLLoader.load(getClass().getResource("/Mission/StatistiqueMission.fxml"));
            Stage stage = new Stage();
            stage.setTitle("Analytique des Missions");
            stage.setScene(new Scene(root, 1000, 800));
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
        }
    }

}