package tn.nexus.Controllers.Mission;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.AnchorPane;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.User;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.WrapWithSideBar;

import java.sql.SQLException;
import java.time.LocalDate;
import java.util.List;
import java.util.stream.Collectors;

public class MissionEmployeController  implements WrapWithSideBar  {
    @FXML
    private AnchorPane sideBar;
    @FXML
    private TextField emailField;

    @FXML
    private Button loadMissionsButton;

    @FXML
    private TableView<Mission> todoTable;

    @FXML
    private TableColumn<Mission, String> todoMissionNameColumn;

    @FXML
    private TableColumn<Mission, String> todoDescriptionColumn;

    @FXML
    private TableView<Mission> inProgressTable;

    @FXML
    private TableColumn<Mission, String> inProgressMissionNameColumn;

    @FXML
    private TableColumn<Mission, String> inProgressDescriptionColumn;

    @FXML
    private TableView<Mission> doneTable;

    @FXML
    private TableColumn<Mission, String> doneMissionNameColumn;

    @FXML
    private TableColumn<Mission, String> doneDescriptionColumn;

    @FXML
    private Button filterTodayButton;

    @FXML
    private Button filterThisMonthButton;

    @FXML
    private Button clearFilterButton;

    @FXML
    private Button changeStatusButton;

    private final MissionService missionService = new MissionService();
    private final UserService userService = new UserService();

    private List<Mission> allMissions; // Liste pour stocker toutes les missions
    private int employeId; // ID de l'employé connecté

    // ObservableList pour gérer les données des tables
    private final ObservableList<Mission> todoList = FXCollections.observableArrayList();
    private final ObservableList<Mission> inProgressList = FXCollections.observableArrayList();
    private final ObservableList<Mission> doneList = FXCollections.observableArrayList();

    @FXML
    public void initialize() {
        // Configurer les colonnes des TableView
        configureTableColumns();
        initializeSideBar(sideBar);

        // Lier les ObservableList aux TableView
        todoTable.setItems(todoList);
        inProgressTable.setItems(inProgressList);
        doneTable.setItems(doneList);

        // Configurer les boutons
        loadMissionsButton.setOnAction(event -> handleLoadMissions());
        filterTodayButton.setOnAction(event -> handleFilterToday());
        filterThisMonthButton.setOnAction(event -> handleFilterThisMonth());
        clearFilterButton.setOnAction(event -> handleClearFilter());
        changeStatusButton.setOnAction(event -> handleChangeStatus());
    }

    private void configureTableColumns() {
        // Configurer les colonnes pour chaque TableView
        todoMissionNameColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        todoDescriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));

        inProgressMissionNameColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        inProgressDescriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));

        doneMissionNameColumn.setCellValueFactory(new PropertyValueFactory<>("titre"));
        doneDescriptionColumn.setCellValueFactory(new PropertyValueFactory<>("description"));
    }

    private void loadMissions() {
        try {
            // Récupérer toutes les missions assignées à l'employé
            allMissions = missionService.getTasksByUserAndStatus(employeId, null);
            System.out.println("Nombre de missions chargées : " + allMissions.size()); // Log

            // Afficher toutes les missions initialement
            refreshTables(allMissions);
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors du chargement des missions : " + e.getMessage());
        }
    }

    @FXML
    private void handleLoadMissions() {
        // Récupérer l'email saisi
        String email = emailField.getText().trim();

        if (email.isEmpty()) {
            showAlert(Alert.AlertType.WARNING, "Champ vide", "Veuillez saisir votre email.");
            return;
        }

        try {
            // Récupérer l'utilisateur par email (sans vérifier le rôle)
            User employe = userService.getUserByEmail(email);

            if (employe != null) {
                employeId = employe.getId();
                System.out.println("ID de l'employé récupéré : " + employeId); // Log
                loadMissions(); // Charger les missions de l'employé
            } else {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Aucun utilisateur trouvé avec cet email.");
            }
        } catch (SQLException e) {
            showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la récupération de l'utilisateur : " + e.getMessage());
        }
    }

    @FXML
    private void handleFilterToday() {
        // Filtrer les missions pour aujourd'hui
        LocalDate today = LocalDate.now();
        List<Mission> filteredMissions = allMissions.stream()
                .filter(mission -> mission.getDateTerminer().toLocalDateTime().toLocalDate().isEqual(today))
                .collect(Collectors.toList());

        // Rafraîchir les TableView avec les missions filtrées
        refreshTables(filteredMissions);
    }

    @FXML
    private void handleFilterThisMonth() {
        // Filtrer les missions pour ce mois-ci
        LocalDate now = LocalDate.now();
        List<Mission> filteredMissions = allMissions.stream()
                .filter(mission -> mission.getDateTerminer().toLocalDateTime().toLocalDate().getMonth() == now.getMonth() &&
                        mission.getDateTerminer().toLocalDateTime().toLocalDate().getYear() == now.getYear())
                .collect(Collectors.toList());

        // Rafraîchir les TableView avec les missions filtrées
        refreshTables(filteredMissions);
    }

    @FXML
    private void handleClearFilter() {
        // Afficher toutes les missions sans filtre
        refreshTables(allMissions);
    }

    @FXML
    private void handleChangeStatus() {
        // Récupérer la mission sélectionnée dans l'une des TableView
        Mission selectedMission = getSelectedMission();

        if (selectedMission == null) {
            showAlert(Alert.AlertType.WARNING, "Aucune mission sélectionnée", "Veuillez sélectionner une mission pour changer son statut.");
            return;
        }

        // Afficher une boîte de dialogue pour choisir le nouveau statut
        ChoiceDialog<String> dialog = new ChoiceDialog<>("To Do", List.of("To Do", "In Progress", "Done"));
        dialog.setTitle("Changer le statut");
        dialog.setHeaderText(null);
        dialog.setContentText("Choisissez le nouveau statut de la mission :");

        dialog.showAndWait().ifPresent(newStatut -> {
            try {
                System.out.println("Mise à jour du statut de la mission ID " + selectedMission.getId() + " vers : " + newStatut); // Log
                // Mettre à jour le statut de la mission dans la base de données
                missionService.updateMissionStatus(selectedMission.getId(), newStatut);

                // Mettre à jour le statut localement
                selectedMission.setStatus(newStatut);

                // Rafraîchir les tables sans recharger toutes les missions
                refreshTablesAfterStatusChange(selectedMission);

            } catch (SQLException e) {
                showAlert(Alert.AlertType.ERROR, "Erreur", "Erreur lors de la mise à jour du statut : " + e.getMessage());
            }
        });
    }

    private Mission getSelectedMission() {
        // Vérifier dans quelle TableView une mission est sélectionnée
        if (!todoTable.getSelectionModel().isEmpty()) {
            return todoTable.getSelectionModel().getSelectedItem();
        } else if (!inProgressTable.getSelectionModel().isEmpty()) {
            return inProgressTable.getSelectionModel().getSelectedItem();
        } else if (!doneTable.getSelectionModel().isEmpty()) {
            return doneTable.getSelectionModel().getSelectedItem();
        }
        return null;
    }

    private void refreshTables(List<Mission> missions) {
        // Vider les listes existantes
        todoList.clear();
        inProgressList.clear();
        doneList.clear();

        // Ajouter les missions aux listes correspondantes
        for (Mission mission : missions) {
            System.out.println("Mission à afficher : " + mission.getTitre() + " - Statut : " + mission.getStatus()); // Log
            switch (mission.getStatus().toLowerCase()) {
                case "to do":
                    todoList.add(mission);
                    System.out.println("Ajoutée à todoTable"); // Log
                    break;
                case "in progress":
                    inProgressList.add(mission);
                    System.out.println("Ajoutée à inProgressTable"); // Log
                    break;
                case "done":
                    doneList.add(mission);
                    System.out.println("Ajoutée à doneTable"); // Log
                    break;
                default:
                    System.out.println("Statut inconnu : " + mission.getStatus()); // Log
                    break;
            }
        }
    }

    private void refreshTablesAfterStatusChange(Mission updatedMission) {
        // Supprimer la mission de toutes les tables
        todoList.remove(updatedMission);
        inProgressList.remove(updatedMission);
        doneList.remove(updatedMission);

        // Ajouter la mission à la table correspondante à son nouveau statut
        switch (updatedMission.getStatus().toLowerCase()) {
            case "to do":
                todoList.add(updatedMission);
                break;
            case "in progress":
                inProgressList.add(updatedMission);
                break;
            case "done":
                doneList.add(updatedMission);
                break;
            default:
                System.out.println("Statut inconnu : " + updatedMission.getStatus()); // Log
                break;
        }
    }

    private void showAlert(Alert.AlertType alertType, String title, String message) {
        Alert alert = new Alert(alertType);
        alert.setTitle(title);
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}