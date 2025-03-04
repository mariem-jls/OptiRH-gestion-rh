package tn.nexus.Controllers.Mission;

import javafx.collections.FXCollections;
import javafx.concurrent.Task;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.*;
import javafx.scene.paint.Color;
import javafx.scene.text.Text;
import tn.nexus.Entities.Mission.Mission;
import tn.nexus.Entities.User;
import tn.nexus.Services.Mission.MissionService;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.WrapWithSideBar;

import java.sql.SQLException;
import java.time.LocalDate;
import java.time.YearMonth;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

public class MissionEmployeController implements WrapWithSideBar {

    @FXML
    private AnchorPane sideBar;
    @FXML
    private TextField emailField;
    @FXML
    private Button loadMissionsButton;
    @FXML
    private GridPane calendarGrid;
    @FXML
    private Label monthYearLabel;
    @FXML
    private Button previousMonthButton;
    @FXML
    private Button nextMonthButton;
    @FXML
    private Button filterTodayButton;
    @FXML
    private Button filterThisMonthButton;
    @FXML
    private Button clearFilterButton;
    @FXML
    private Button changeStatusButton;
    @FXML
    private ProgressIndicator loadingSpinner;
    @FXML
    private ComboBox<String> statusFilterCombo;

    private final MissionService missionService = new MissionService();
    private final UserService userService = new UserService();
    private YearMonth currentYearMonth;
    private Map<LocalDate, List<Mission>> missionsByDate = new HashMap<>();
    private int employeId;
    private DateTimeFormatter monthFormatter = DateTimeFormatter.ofPattern("MMMM yyyy", Locale.FRENCH);

    @FXML
    public void initialize() {
        initializeSideBar(sideBar);
        currentYearMonth = YearMonth.now();
        setupCalendar();
        setupEventHandlers();
        setupStatusFilter();
    }

    private void setupStatusFilter() {
        statusFilterCombo.setItems(FXCollections.observableArrayList(
                "Tous", "To Do", "In Progress", "Done"));
        statusFilterCombo.getSelectionModel().selectFirst();
        statusFilterCombo.setOnAction(e -> applyFilters());
    }

    private void applyFilters() {
        loadMissions();
    }

    // Supprimer la méthode en double et unifier le comportement

    @FXML
    private void loadMissions() {
        String email = emailField.getText().trim();

        if (!isValidEmail(email)) {
            showAlert("Erreur", "Email invalide");
            return;
        }

        loadingSpinner.setVisible(true);

        Task<User> fetchUserTask = new Task<>() {
            @Override
            protected User call() throws Exception {
                return userService.getUserByEmail(email);
            }
        };

        fetchUserTask.setOnSucceeded(event -> {
            User user = fetchUserTask.getValue();
            if (user != null) {
                employeId = user.getId(); // Set employeId here
                loadUserMissions(employeId);
            } else {
                showAlert("Erreur", "Aucun utilisateur trouvé");
            }
            loadingSpinner.setVisible(false);
        });

        fetchUserTask.setOnFailed(event -> {
            showAlert("Erreur", "Échec de chargement");
            loadingSpinner.setVisible(false);
        });

        new Thread(fetchUserTask).start();
    }



    private boolean isValidEmail(String email) {
        if (email == null || email.trim().isEmpty()) {
            return false;
        }

        // Regex pour validation basique d'email
        String emailRegex = "^[a-zA-Z0-9_+&*-]+(?:\\.[a-zA-Z0-9_+&*-]+)*@(?:[a-zA-Z0-9-]+\\.)+[a-zA-Z]{2,7}$";
        Pattern pattern = Pattern.compile(emailRegex);
        Matcher matcher = pattern.matcher(email.trim());
        return matcher.matches();
    }

    private void loadUserMissions(int userId) {
        String statusFilter = statusFilterCombo.getValue();
        if("Tous".equals(statusFilter)) statusFilter = null;

        String finalStatusFilter = statusFilter;
        Task<List<Mission>> missionTask = new Task<>() {
            protected List<Mission> call() throws Exception {
                return missionService.getTasksByUserAndStatus(userId, finalStatusFilter);
            }
        };

        missionTask.setOnSucceeded(e -> {
            List<Mission> missions = missionTask.getValue();

            // Filter missions to current month
            List<Mission> filteredMissions = missions.stream()
                    .filter(mission -> {
                        LocalDate missionDate = mission.getDateTerminer().toLocalDateTime().toLocalDate();
                        return YearMonth.from(missionDate).equals(currentYearMonth);
                    })
                    .collect(Collectors.toList());

            missionsByDate = groupMissionsByDate(filteredMissions);
            updateCalendarDays();
            loadingSpinner.setVisible(false);
        });

        new Thread(missionTask).start();
    }

    private VBox createDayCell(LocalDate date) {
        return createDayCell(date, date.getDayOfWeek().getValue());
    }

    private VBox createDayCell(LocalDate date, int dayOfWeek) {
        VBox cell = new VBox(3);
        cell.setPadding(new Insets(10));
        cell.setStyle("-fx-border-color: #ddd; -fx-border-radius: 3px;");
        cell.setPrefSize(120, 80);

        // Style weekend
        if(dayOfWeek > 5) {
            cell.setStyle("-fx-background-color: #f5f5f5; -fx-border-color: #ddd; -fx-border-radius: 3px;");
        }

        Text dayNumber = new Text(String.valueOf(date.getDayOfMonth()));
        dayNumber.setStyle(date.equals(LocalDate.now()) ?
                "-fx-fill: #2196F3; -fx-font-weight: bold;" : "-fx-fill: #666;");

        cell.getChildren().add(dayNumber);
        return cell;
    }

    private void showDateDetails(LocalDate date) {
        List<Mission> missions = missionsByDate.getOrDefault(date, List.of());
        // Afficher dialogue avec liste détaillée
    }



    private Label createMissionLabel(Mission mission) {
        Label label = new Label(mission.getTitre());
        label.setStyle(getMissionStyle(mission.getStatus()));

        // Tooltip avec détails
        String tooltipText = String.format(
                "Description: %s\nStatut: %s\nDe: %s\nÀ: %s",
                mission.getDescription(),
                mission.getStatus(),
                mission.getCreatedAt().toLocalDateTime().toLocalDate(),
                mission.getDateTerminer().toLocalDateTime().toLocalDate());
        label.setTooltip(new Tooltip(tooltipText));

        // Gestion clic pour changement statut
        label.setOnMouseClicked(e -> showStatusChangeDialog(mission));

        return label;
    }

    private void showStatusChangeDialog(Mission mission) {
        ChoiceDialog<String> dialog = new ChoiceDialog<>(
                mission.getStatus(),
                List.of("To Do", "In Progress", "Done"));

        dialog.setTitle("Changement statut");
        dialog.setHeaderText("Modifier le statut de: " + mission.getTitre());
        Optional<String> result = dialog.showAndWait();

        result.ifPresent(newStatus -> {
            try {
                missionService.updateMissionStatus(mission.getId(), newStatus);
                loadUserMissions(employeId); // Rafraîchir seulement les missions
            } catch (SQLException ex) {
                showAlert("Erreur", ex.getMessage());
            }
        });
    }


    private void setupCalendar() {
        calendarGrid.getChildren().clear();
        calendarGrid.setHgap(5);
        calendarGrid.setVgap(5);
        updateCalendarHeader();
        updateCalendarDays();
    }

    private void updateCalendarHeader() {
        // Days of week
        String[] days = {"Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"};
        for (int i = 0; i < 7; i++) {
            Label dayLabel = new Label(days[i]);
            dayLabel.setStyle("-fx-font-weight: bold; -fx-padding: 5px;");
            calendarGrid.add(dayLabel, i, 0);
        }
    }

    private void updateCalendarDays() {
        // Vider toutes les lignes sauf l'en-tête
        calendarGrid.getChildren().removeIf(node ->
                GridPane.getRowIndex(node) != null && GridPane.getRowIndex(node) > 0
        );

        int row = 1;
        int col = currentYearMonth.atDay(1).getDayOfWeek().getValue() - 1;

        for (int day = 1; day <= currentYearMonth.lengthOfMonth(); day++) {
            LocalDate date = currentYearMonth.atDay(day);
            VBox dayCell = createDayCell(date, date.getDayOfWeek().getValue()); // Utiliser la version avec jour de la semaine
            calendarGrid.add(dayCell, col, row);

            if (++col > 6) {
                col = 0;
                row++;
            }
        }

        monthYearLabel.setText(currentYearMonth.format(monthFormatter)); // Utiliser le formateur français
        highlightToday();
        populateMissions();
    }

    private void populateMissions() {
        // Nettoyer les anciennes missions
        calendarGrid.getChildren().forEach(node -> {
            if(node instanceof VBox vbox && GridPane.getRowIndex(node) != null && GridPane.getRowIndex(node) > 0) {
                vbox.getChildren().removeIf(child -> child instanceof Label);
            }
        });

        missionsByDate.forEach((date, missions) -> {
            int row = getRowForDate(date);
            int col = date.getDayOfWeek().getValue() - 1;

            VBox cell = (VBox) calendarGrid.getChildren()
                    .filtered(n -> GridPane.getRowIndex(n) == row && GridPane.getColumnIndex(n) == col)
                    .stream().findFirst().orElse(null);

            if(cell != null) {
                missions.forEach(mission -> {
                    Label missionLabel = createMissionLabel(mission);
                    cell.getChildren().add(missionLabel);
                });
            }
        });
    }

    private String getMissionStyle(String status) {
        return switch (status.toLowerCase()) {
            case "to do" -> "-fx-background-color: #FFCDD2; -fx-text-fill: #B71C1C;";
            case "in progress" -> "-fx-background-color: #FFF9C4; -fx-text-fill: #F57F17;";
            case "done" -> "-fx-background-color: #C8E6C9; -fx-text-fill: #1B5E20;";
            default -> "";
        };
    }

    private Map<LocalDate, List<Mission>> groupMissionsByDate(List<Mission> missions) {
        return missions.stream()
                .collect(Collectors.groupingBy(m ->
                        m.getDateTerminer().toLocalDateTime().toLocalDate()));
    }

    private int getRowForDate(LocalDate date) {
        int firstDayOfMonth = currentYearMonth.atDay(1).getDayOfWeek().getValue() - 1;
        int dayOfMonth = date.getDayOfMonth();
        return (firstDayOfMonth + dayOfMonth - 1) / 7 + 1;
    }

    private void setupEventHandlers() {
        previousMonthButton.setOnAction(e -> {
            currentYearMonth = currentYearMonth.minusMonths(1);
            setupCalendar();
            if (employeId > 0) {
                loadUserMissions(employeId);
            }
        });

        nextMonthButton.setOnAction(e -> {
            currentYearMonth = currentYearMonth.plusMonths(1);
            setupCalendar();
            if (employeId > 0) {
                loadUserMissions(employeId);
            }
        });

        // Add event handlers for today and this month filters
        filterTodayButton.setOnAction(e -> filterToday());
        filterThisMonthButton.setOnAction(e -> filterThisMonth());

        clearFilterButton.setOnAction(e -> clearFilters());

        statusFilterCombo.setOnAction(e -> {
            if(employeId > 0) {
                loadUserMissions(employeId);
            }
        });
    }

    private void filterToday() {
        currentYearMonth = YearMonth.now();
        setupCalendar(); // Updates the calendar to current month
        if (employeId > 0) {
            loadUserMissions(employeId); // Reload missions for current month
        }
        highlightToday(); // Highlight today's date
    }

    private void filterThisMonth() {
        currentYearMonth = YearMonth.now();
        setupCalendar();
        if (employeId > 0) {
            loadUserMissions(employeId);
        }
    }

    private void clearFilters() {
        currentYearMonth = YearMonth.now();
        statusFilterCombo.getSelectionModel().selectFirst(); // Reset status filter
        setupCalendar(); // Reset calendar display first
        if (employeId > 0) {
            loadUserMissions(employeId); // Load missions with current month and all statuses
        }
    }
    private void highlightToday() {
        calendarGrid.getChildren().forEach(node -> {
            if (node instanceof VBox vbox &&
                    !vbox.getChildren().isEmpty() &&
                    vbox.getChildren().get(0) instanceof Text text &&
                    text.getText().equals(String.valueOf(LocalDate.now().getDayOfMonth()))) {
                {
                    vbox.setStyle("-fx-border-color: #2196F3; -fx-border-width: 2px;");
                }
            }});
    }

    private void showAlert(String title, String message) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle(title);
        alert.setContentText(message);
        alert.showAndWait();
    }
}