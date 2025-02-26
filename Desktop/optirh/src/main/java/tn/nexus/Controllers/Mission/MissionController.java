package tn.nexus.Controllers.Mission;

import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.stage.Stage;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Utils.WrapWithSideBar;

import java.sql.SQLException;
import java.util.List;

public class MissionController implements WrapWithSideBar {

    @FXML
    private AnchorPane sideBar;
    @FXML
    private ListView<Mission> toDoList;
    @FXML
    private ListView<Mission> inProgressList;
    @FXML
    private ListView<Mission> doneList;

    @FXML
    private Button editButton; // Bouton Modifier
    @FXML
    private Button changeStatusButton; // Bouton Changer Statut
    @FXML
    private Button deleteButton; // Bouton Supprimer

    @FXML
    private Button returnToProjectButton;

    private final MissionService missionService = new MissionService();
    private Projet projet; // Projet sélectionné
    private Mission selectedMission; // Mission sélectionnée

    // Méthode pour définir le projet sélectionné
    public void setProjet(Projet projet) {
        this.projet = projet;
        refreshLists(); // Rafraîchir les missions après avoir défini le projet
    }

    @FXML
    private ListView<Mission> filteredList; // Nouvelle ListView pour les missions filtrées

    @FXML
    public void initialize() {
        initializeSideBar(sideBar);

        // Configurer les ListView avec des écouteurs de sélection
        configureListViewSelection(toDoList);
        configureListViewSelection(inProgressList);
        configureListViewSelection(doneList);
        configureListViewSelection(filteredList); // Configurer la nouvelle ListView

        // Désactiver les boutons par défaut
        editButton.setDisable(true);
        changeStatusButton.setDisable(true);
        deleteButton.setDisable(true);

        // Configurer le bouton pour retourner à la page des projets
        returnToProjectButton.setOnAction(event -> goToProjectPage());

        // Charger les missions filtrées
        loadFilteredMissions();
    }

    private void loadFilteredMissions() {
        try {
            List<Mission> filteredMissions = missionService.getTasksWithDateTerminedAndStatusNotDone();
            filteredList.getItems().setAll(filteredMissions);

        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de charger les missions filtrées : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    // Méthode pour charger les missions par utilisateur
    public void loadMissionsByUser(int userId) {
        try {
            List<Mission> userMissions = missionService.getTasksByUserAndStatus(userId, "To Do");
            toDoList.getItems().setAll(userMissions);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de charger les missions de l'utilisateur : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }
    // Configurer les écouteurs de sélection pour les ListView
    private void configureListViewSelection(ListView<Mission> listView) {
        listView.getSelectionModel().selectedItemProperty().addListener((obs, oldSelection, newSelection) -> {
            if (newSelection != null) {
                selectedMission = newSelection; // Mettre à jour la mission sélectionnée
                editButton.setDisable(false); // Activer le bouton Modifier
                changeStatusButton.setDisable(false); // Activer le bouton Changer Statut
                deleteButton.setDisable(false); // Activer le bouton Supprimer
            } else {
                selectedMission = null; // Aucune mission sélectionnée
                editButton.setDisable(true); // Désactiver le bouton Modifier
                changeStatusButton.setDisable(true); // Désactiver le bouton Changer Statut
                deleteButton.setDisable(true); // Désactiver le bouton Supprimer
            }
        });
    }

    // Rafraîchir les ListView avec les missions du projet sélectionné
    private void refreshLists() {
        try {
            if (projet != null) {
                // Récupérer les missions du projet sélectionné par statut
                toDoList.getItems().setAll(missionService.getTasksByProjectAndStatus(projet.getId(), "To Do"));
                inProgressList.getItems().setAll(missionService.getTasksByProjectAndStatus(projet.getId(), "In Progress"));
                doneList.getItems().setAll(missionService.getTasksByProjectAndStatus(projet.getId(), "Done"));
                filteredList.getItems().setAll(missionService.getTasksWithDateTerminedAndStatusNotDone());
            }
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de charger les missions : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    @FXML
    private void handleAddMission() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/AjouterMission.fxml"));
            Parent root = loader.load();

            // Passer le projet sélectionné au contrôleur d'ajout de mission
            AjouterMissionController ajouterMissionController = loader.getController();
            ajouterMissionController.setProjet(projet);

            // Créer une nouvelle fenêtre pour afficher l'interface AjouterMission
            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Ajouter une mission");
            stage.showAndWait(); // Attendre que la fenêtre soit fermée

            // Rafraîchir les ListView après l'ajout
            refreshLists();
        } catch (Exception e) {
            showAlert("Erreur", "Impossible d'ouvrir le formulaire d'ajout : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }


    @FXML
    private void handleEditMission() {
        if (selectedMission != null) {
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/ModifierUneMission.fxml"));
                Parent root = loader.load();

                // Passer la mission sélectionnée au contrôleur de modification
                ModifierMissionController controller = loader.getController();
                controller.setMission(selectedMission);

                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Modifier une mission");
                stage.showAndWait();

                // Rafraîchir les ListView après la modification
                refreshLists();
            } catch (Exception e) {
                showAlert("Erreur", "Impossible d'ouvrir le formulaire de modification : " + e.getMessage(), Alert.AlertType.ERROR);
            }
        }
    }

    @FXML
    private void handleChangeStatus() {
        if (selectedMission != null) {
            try {
                // Déterminer le nouveau statut en fonction du statut actuel
                String newStatus;
                switch (selectedMission.getStatus()) {
                    case "To Do":
                        newStatus = "In Progress";
                        break;
                    case "In Progress":
                        newStatus = "Done";
                        break;
                    case "Done":
                        newStatus = "To Do"; // Optionnel : revenir à "To Do" si nécessaire
                        break;
                    default:
                        newStatus = "To Do";
                        break;
                }

                // Mettre à jour le statut de la mission dans la base de données
                selectedMission.setStatus(newStatus);
                missionService.update2(selectedMission);

                // Rafraîchir les ListView après la mise à jour
                refreshLists();

                showAlert("Succès", "Statut de la mission mis à jour avec succès.", Alert.AlertType.INFORMATION);
            } catch (SQLException e) {
                showAlert("Erreur", "Impossible de mettre à jour le statut de la mission : " + e.getMessage(), Alert.AlertType.ERROR);
            }
        }
    }

    @FXML
    private void handleDeleteMission() {
        if (selectedMission != null) {
            // Confirmer la suppression avec l'utilisateur
            Alert confirmation = new Alert(Alert.AlertType.CONFIRMATION);
            confirmation.setTitle("Confirmation de suppression");
            confirmation.setHeaderText(null);
            confirmation.setContentText("Êtes-vous sûr de vouloir supprimer la mission \"" + selectedMission.getTitre() + "\" ?");

            if (confirmation.showAndWait().orElse(ButtonType.CANCEL) == ButtonType.OK) {
                try {
                    // Supprimer la mission de la base de données
                    missionService.delete(selectedMission);

                    // Rafraîchir les ListView après la suppression
                    refreshLists();

                    showAlert("Succès", "Mission supprimée avec succès", Alert.AlertType.INFORMATION);
                } catch (SQLException e) {
                    showAlert("Erreur", "Impossible de supprimer la mission : " + e.getMessage(), Alert.AlertType.ERROR);
                }
            }
        }
    }

    @FXML
    private void goToProjectPage() {
        try {
            // Charger le fichier FXML de la page des projets
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
            Parent root = loader.load();

            // Obtenir la scène actuelle
            Scene currentScene = returnToProjectButton.getScene();

            // Remplacer le contenu de la scène actuelle par la nouvelle page
            currentScene.setRoot(root);
        } catch (Exception e) {
            showAlert("Erreur", "Une erreur s'est produite lors du chargement de la page des projets.", Alert.AlertType.ERROR);
        }
    }

    // Afficher une alerte
    private void showAlert(String title, String message, Alert.AlertType type) {
        Alert alert = new Alert(type);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}