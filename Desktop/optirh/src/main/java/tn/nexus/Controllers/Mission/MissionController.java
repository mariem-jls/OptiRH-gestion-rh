package tn.nexus.Controllers.Mission;

import javafx.animation.KeyFrame;
import javafx.animation.Timeline;
import javafx.application.Platform;
import javafx.beans.property.Property;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.*;
import javafx.scene.layout.AnchorPane;
import javafx.stage.FileChooser;
import javafx.stage.Stage;
import javafx.util.Duration;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.Mission.Projet;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Utils.Mission.EmailSender;
import tn.nexus.Utils.WrapWithSideBar;

import java.io.File;
import java.sql.SQLException;
import java.sql.Timestamp;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.stream.Collectors;

public class MissionController implements WrapWithSideBar {

    private final EmailSender emailSender = new EmailSender();
    private ScheduledExecutorService scheduler;
    @FXML
    private AnchorPane sideBar;
    @FXML
    private ListView<Mission> toDoList;
    @FXML
    private ListView<Mission> inProgressList;
    @FXML
    private ListView<Mission> doneList;
    @FXML
    private ListView<Mission> filteredList;

    @FXML
    private Button editButton;
    @FXML
    private Button changeStatusButton;
    @FXML
    private Button deleteButton;
    @FXML
    private Button returnToProjectButton;
    @FXML
    private TextField emailFilterField;

    @FXML
    private DatePicker startDatePicker;

    @FXML
    private DatePicker endDatePicker;

    @FXML
    private ComboBox<String> filterTypeComboBox;

    private final MissionService missionService = new MissionService();
    private Projet projet;
    private Mission selectedMission;

    public void setProjet(Projet projet) {
        this.projet = projet;
        refreshLists();
    }

    @FXML
    public void initialize() {
        initializeSideBar(sideBar);
        configureListViews();
        setupFilterComponents();
        loadInitialData();
        startAutoNotifications();

    }
    private void startAutoNotifications() {
        scheduler = Executors.newSingleThreadScheduledExecutor();
        scheduler.scheduleAtFixedRate(() -> {
            try {
                checkDeadlinesAndNotify();
            } catch (SQLException e) {
                e.printStackTrace();
            }
        }, 0, 6, TimeUnit.HOURS); // Vérifie toutes les 6 heures
    }

    private void checkDeadlinesAndNotify() throws SQLException {
        List<Mission> missions = missionService.getTasksApproachingDeadline();
        for (Mission mission : missions) {
            String userEmail = missionService.getUserEmailByMission(mission.getId());
            if (userEmail != null) {
                emailSender.sendDeadlineAlert(mission, userEmail);
            }
        }
    }

    // Ajoute cette méthode pour nettoyer
    public void shutdown() {
        if (scheduler != null && !scheduler.isShutdown()) {
            scheduler.shutdown();
        }
    }

    private void configureListViews() {
        toDoList.setCellFactory(listView -> new MissionListCell());
        inProgressList.setCellFactory(listView -> new MissionListCell());
        doneList.setCellFactory(listView -> new MissionListCell());
        filteredList.setCellFactory(listView -> new MissionListCell());

        configureListViewSelection(toDoList);
        configureListViewSelection(inProgressList);
        configureListViewSelection(doneList);
        configureListViewSelection(filteredList);
    }

    private void setupFilterComponents() {
        filterTypeComboBox.setItems(FXCollections.observableArrayList(
                "Filtrer par email",
                "Filtrer par date",
                "Filtrer par email et date"
        ));

        filterTypeComboBox.valueProperty().addListener((obs, oldVal, newVal) -> {
            updateFilterUI(newVal);
            applyFilter();
        });

        setupDebouncedFilter(emailFilterField.textProperty());
        setupDebouncedFilter(startDatePicker.valueProperty());
        setupDebouncedFilter(endDatePicker.valueProperty());
    }

    private void setupDebouncedFilter(Property<?> property) {
        property.addListener((obs, oldVal, newVal) -> {
            Timeline timeline = new Timeline(new KeyFrame(
                    Duration.millis(500),
                    ae -> applyFilter()
            ));
            timeline.play();
        });
    }

    private void updateFilterUI(String filterType) {
        emailFilterField.setVisible(false);
        startDatePicker.setVisible(false);
        endDatePicker.setVisible(false);

        if (filterType != null) {
            switch (filterType) {
                case "Filtrer par email":
                    emailFilterField.setVisible(true);
                    break;
                case "Filtrer par date":
                    startDatePicker.setVisible(true);
                    endDatePicker.setVisible(true);
                    break;
                case "Filtrer par email et date":
                    emailFilterField.setVisible(true);
                    startDatePicker.setVisible(true);
                    endDatePicker.setVisible(true);
                    break;
            }
        }
    }

    private void loadInitialData() {
        try {
            filteredList.getItems().setAll(missionService.getTasksWithDateTerminedAndStatusNotDone());
        } catch (SQLException e) {
            showAlert("Erreur", e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    private void applyFilter() {
        String filterType = filterTypeComboBox.getValue();
        if (filterType == null) return;

        try {
            List<Mission> filteredMissions = switch (filterType) {
                case "Filtrer par email" -> handleEmailFilter();
                case "Filtrer par date" -> handleDateFilter();
                case "Filtrer par email et date" -> handleCombinedFilter();
                default -> null;
            };

            if (filteredMissions != null) {
                updateMissionLists(filteredMissions);
            }
        } catch (SQLException e) {
            showAlert("Erreur", e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    private List<Mission> handleEmailFilter() throws SQLException {
        String email = emailFilterField.getText().trim();
        if (email.isEmpty()) return null;
        return missionService.getTasksByUserEmail(email);
    }

    private List<Mission> handleDateFilter() throws SQLException {
        if (startDatePicker.getValue() == null || endDatePicker.getValue() == null) return null;

        Timestamp start = Timestamp.valueOf(startDatePicker.getValue().atStartOfDay());
        Timestamp end = Timestamp.valueOf(endDatePicker.getValue().atTime(23, 59, 59));
        return missionService.getTasksByDateRange(start, end);
    }

    private List<Mission> handleCombinedFilter() throws SQLException {
        String email = emailFilterField.getText().trim();
        if (email.isEmpty() || startDatePicker.getValue() == null || endDatePicker.getValue() == null) return null;

        Timestamp start = Timestamp.valueOf(startDatePicker.getValue().atStartOfDay());
        Timestamp end = Timestamp.valueOf(endDatePicker.getValue().atTime(23, 59, 59));
        return missionService.getTasksByUserEmailAndDateRange(email, start, end);
    }

    private void updateMissionLists(List<Mission> missions) {
        if (missions == null) return;

        List<Mission> toDo = missions.stream()
                .filter(m -> "To Do".equals(m.getStatus()))
                .collect(Collectors.toList());

        List<Mission> inProgress = missions.stream()
                .filter(m -> "In Progress".equals(m.getStatus()))
                .collect(Collectors.toList());

        List<Mission> done = missions.stream()
                .filter(m -> "Done".equals(m.getStatus()))
                .collect(Collectors.toList());

        Platform.runLater(() -> {
            toDoList.getItems().setAll(toDo);
            inProgressList.getItems().setAll(inProgress);
            doneList.getItems().setAll(done);
        });
    }

    // Méthodes existantes restantes (non modifiées)
    private void configureListViewSelection(ListView<Mission> listView) {
        listView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            selectedMission = newVal;
            boolean disable = newVal == null;
            editButton.setDisable(disable);
            changeStatusButton.setDisable(disable);
            deleteButton.setDisable(disable);
        });
    }

    @FXML
    private void handleExportPDF() {
        // Récupérer toutes les missions
        List<Mission> allMissions = toDoList.getItems();
        allMissions.addAll(inProgressList.getItems());
        allMissions.addAll(doneList.getItems());

        // Ouvrir un dialogue pour choisir l'emplacement du fichier
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Enregistrer le PDF");
        fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("Fichiers PDF", "*.pdf"));
        File file = fileChooser.showSaveDialog(null);

        if (file != null) {
            // Exporter les missions en PDF
            PDFExporter.exportMissionsToPDF(allMissions, file.getAbsolutePath());

            // Afficher une confirmation
            Alert alert = new Alert(Alert.AlertType.INFORMATION);
            alert.setTitle("Exportation réussie");
            alert.setHeaderText(null);
            alert.setContentText("Le fichier PDF a été enregistré avec succès.");
            alert.showAndWait();
        }
    }
    private void refreshLists() {
        try {
            if (projet != null) {
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

            AjouterMissionController ajouterMissionController = loader.getController();
            ajouterMissionController.setProjet(projet);

            Stage stage = new Stage();
            stage.setScene(new Scene(root));
            stage.setTitle("Ajouter une mission");
            stage.showAndWait();

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

                ModifierMissionController controller = loader.getController();
                controller.setMission(selectedMission);

                Stage stage = new Stage();
                stage.setScene(new Scene(root));
                stage.setTitle("Modifier une mission");
                stage.showAndWait();

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
                String newStatus;
                switch (selectedMission.getStatus()) {
                    case "To Do":
                        newStatus = "In Progress";
                        break;
                    case "In Progress":
                        newStatus = "Done";
                        break;
                    case "Done":
                        newStatus = "To Do";
                        break;
                    default:
                        newStatus = "To Do";
                        break;
                }

                selectedMission.setStatus(newStatus);
                missionService.update2(selectedMission);

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
            Alert confirmation = new Alert(Alert.AlertType.CONFIRMATION);
            confirmation.setTitle("Confirmation de suppression");
            confirmation.setHeaderText(null);
            confirmation.setContentText("Êtes-vous sûr de vouloir supprimer la mission \"" + selectedMission.getTitre() + "\" ?");

            if (confirmation.showAndWait().orElse(ButtonType.CANCEL) == ButtonType.OK) {
                try {
                    missionService.delete(selectedMission);
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
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Mission/Projet.fxml"));
            Parent root = loader.load();
            Scene currentScene = returnToProjectButton.getScene();
            currentScene.setRoot(root);
        } catch (Exception e) {
            showAlert("Erreur", "Une erreur s'est produite lors du chargement de la page des projets.", Alert.AlertType.ERROR);
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
    private void handleFilterByUserEmail() {
        String email = emailFilterField.getText();
        if (email == null || email.isEmpty()) {
            showAlert("Erreur", "Veuillez saisir un email valide.", Alert.AlertType.ERROR);
            return;
        }
        try {
            List<Mission> filteredMissions = missionService.getTasksByUserEmail(email);
            toDoList.getItems().setAll(filteredMissions);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de filtrer les missions par email : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    @FXML
    private void handleFilterByDateRange() {
        if (startDatePicker.getValue() == null || endDatePicker.getValue() == null) {
            showAlert("Erreur", "Veuillez sélectionner une plage de dates valide.", Alert.AlertType.ERROR);
            return;
        }
        Timestamp startDate = Timestamp.valueOf(startDatePicker.getValue().atStartOfDay());
        Timestamp endDate = Timestamp.valueOf(endDatePicker.getValue().atTime(23, 59, 59));
        try {
            List<Mission> filteredMissions = missionService.getTasksByDateRange(startDate, endDate);
            toDoList.getItems().setAll(filteredMissions);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de filtrer les missions par date : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }

    @FXML
    private void handleFilterByUserEmailAndDateRange() {
        String email = emailFilterField.getText();
        if (email == null || email.isEmpty() || startDatePicker.getValue() == null || endDatePicker.getValue() == null) {
            showAlert("Erreur", "Veuillez saisir un email et une plage de dates valides.", Alert.AlertType.ERROR);
            return;
        }
        Timestamp startDate = Timestamp.valueOf(startDatePicker.getValue().atStartOfDay());
        Timestamp endDate = Timestamp.valueOf(endDatePicker.getValue().atTime(23, 59, 59));
        try {
            List<Mission> filteredMissions = missionService.getTasksByUserEmailAndDateRange(email, startDate, endDate);
            toDoList.getItems().setAll(filteredMissions);
        } catch (SQLException e) {
            showAlert("Erreur", "Impossible de filtrer les missions par email et date : " + e.getMessage(), Alert.AlertType.ERROR);
        }
    }
}